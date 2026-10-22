<?php
/**
 * Automatische Deutsch -> Englisch-Übersetzung über die DeepL-API, für
 * Admin-Inhalte (Berichte, Mitgliederprofile), die beim Speichern einmal
 * automatisch auf Englisch mitgespeichert werden (siehe admin/report-edit.php,
 * admin/member-edit.php). Deutsch bleibt überall die normale Anzeige; Englisch
 * wird nur gezeigt, wenn der Besucher über den DE/EN-Umschalter auf Englisch
 * wechselt (siehe config/i18n.php).
 *
 * API-Key wird verschlüsselt in config/env.local.php hinterlegt (siehe
 * admin/settings.php, gleiches Prinzip wie DB_PASSWORD/SMTP_PASSWORD).
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/logging.php';

function getDeepLApiKey(): string {
    return decryptSecret(env('DEEPL_API_KEY', ''));
}

function isDeepLConfigured(): bool {
    return getDeepLApiKey() !== '';
}

/**
 * Übersetzt mehrere Texte in einem einzigen API-Aufruf (DE -> EN) - schont
 * das Monatskontingent und ist schneller als ein Aufruf pro Feld. Leere
 * Texte werden nicht an DeepL gesendet, sondern bleiben einfach leer.
 *
 * Gibt bei fehlendem API-Key, fehlender cURL-Erweiterung oder einem
 * Netzwerk-/API-Fehler null zurück - der Aufrufer soll den
 * Speichervorgang dann NICHT blockieren, sondern die englische Fassung
 * leer lassen (die Anzeige fällt in diesem Fall automatisch auf die
 * deutsche Fassung zurück, siehe z.B. pages/berichte.php).
 *
 * @param string[] $texts Beliebige Schlüssel erlaubt (int oder string) -
 *     die Rückgabe verwendet dieselben Schlüssel.
 * @return string[]|null
 */
function translateBatchToEnglish(array $texts): ?array {
    $key = getDeepLApiKey();
    if ($key === '' || !function_exists('curl_init')) {
        return null;
    }

    $nonEmptyIndexes = [];
    $payloadTexts = [];
    foreach ($texts as $i => $text) {
        if (trim((string) $text) !== '') {
            $nonEmptyIndexes[] = $i;
            $payloadTexts[] = (string) $text;
        }
    }
    if (empty($payloadTexts)) {
        return array_fill_keys(array_keys($texts), '');
    }

    // DeepL-Free-Keys enden immer auf ":fx" und nutzen einen eigenen
    // Endpunkt - so funktioniert sowohl ein kostenloser als auch ein
    // bezahlter Account ohne weitere Einstellung.
    $isFreeAccount = str_ends_with($key, ':fx');
    $url = $isFreeAccount ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';

    $fields = [];
    foreach ($payloadTexts as $text) {
        $fields[] = 'text=' . urlencode($text);
    }
    $fields[] = 'source_lang=DE';
    $fields[] = 'target_lang=EN-US';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => implode('&', $fields),
        CURLOPT_HTTPHEADER => [
            'Authorization: DeepL-Auth-Key ' . $key,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }
    $data = json_decode($response, true);
    if (!isset($data['translations']) || !is_array($data['translations'])) {
        return null;
    }

    $result = array_fill_keys(array_keys($texts), '');
    foreach ($nonEmptyIndexes as $pos => $originalIndex) {
        $result[$originalIndex] = $data['translations'][$pos]['text'] ?? '';
    }
    return $result;
}

/**
 * Übersetzt einen einzelnen Text DE -> EN. Siehe translateBatchToEnglish()
 * für das Verhalten bei leerem Text bzw. Fehlern (null = Fehler, '' = Text
 * war leer).
 */
function translateToEnglish(string $text): ?string {
    if (trim($text) === '') {
        return '';
    }
    $result = translateBatchToEnglish([$text]);
    return $result === null ? null : ($result[0] ?? null);
}

/**
 * Fragt das aktuelle Zeichen-Kontingent bei DeepL ab (eigener Endpunkt,
 * zählt selbst nicht gegen das Kontingent). Gibt null zurück, wenn der Key
 * fehlt oder die Abfrage fehlschlägt.
 *
 * @return array{character_count:int,character_limit:int}|null
 */
function getDeepLUsage(): ?array {
    $key = getDeepLApiKey();
    if ($key === '' || !function_exists('curl_init')) {
        return null;
    }
    $isFreeAccount = str_ends_with($key, ':fx');
    $url = $isFreeAccount ? 'https://api-free.deepl.com/v2/usage' : 'https://api.deepl.com/v2/usage';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: DeepL-Auth-Key ' . $key],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }
    $data = json_decode($response, true);
    if (!isset($data['character_count'], $data['character_limit'])) {
        return null;
    }
    return ['character_count' => (int) $data['character_count'], 'character_limit' => (int) $data['character_limit']];
}

/**
 * Warnt rechtzeitig, bevor das monatliche DeepL-Kontingent (laut Account
 * 1.000.000 Zeichen) erreicht ist - einmal bei 90%, einmal bei 100%. Geht
 * sofort per Discord raus (über den ohnehin konfigurierten
 * Aktivitäts-Webhook, siehe logActivity()) und als Admin-Aktivitätseintrag;
 * zusätzlich als Flash-Meldung für die aktuelle Admin-Sitzung. Löst pro
 * Abrechnungszeitraum (Kalendermonat) nur einmal je Schwelle aus, gespeichert
 * in site_settings, damit nicht bei jedem weiteren Bericht erneut gewarnt
 * wird. Wird am besten direkt nach jedem erfolgreichen
 * translateBatchToEnglish()-Aufruf aufgerufen.
 */
function checkDeepLUsageAndWarn(PDO $db): void {
    $usage = getDeepLUsage();
    if ($usage === null || $usage['character_limit'] <= 0) {
        return;
    }
    $percent = ($usage['character_count'] / $usage['character_limit']) * 100;

    $period = date('Y-m');
    $storedPeriod = getSiteSetting('deepl_usage_warn_period', '');
    $level = $storedPeriod === $period ? (int) getSiteSetting('deepl_usage_warn_level', '0') : 0;

    $newLevel = $level;
    $message = null;
    if ($percent >= 100 && $level < 100) {
        $newLevel = 100;
        $message = sprintf(
            'DeepL-Übersetzungskontingent ist aufgebraucht (%s von %s Zeichen, %.1f%%). Automatische Übersetzungen (Berichte, Mitgliederprofile) funktionieren erst wieder nach der nächsten Kontingent-Zurücksetzung durch DeepL - bis dahin bleiben neue/geänderte Inhalte auf Englisch unübersetzt (Anzeige fällt automatisch auf Deutsch zurück).',
            number_format($usage['character_count'], 0, ',', '.'), number_format($usage['character_limit'], 0, ',', '.'), $percent
        );
    } elseif ($percent >= 90 && $level < 90) {
        $newLevel = 90;
        $message = sprintf(
            'DeepL-Übersetzungskontingent ist zu %.1f%% ausgeschöpft (%s von %s Zeichen). Bald funktionieren automatische Übersetzungen nicht mehr, bis DeepL das Kontingent zurücksetzt.',
            $percent, number_format($usage['character_count'], 0, ',', '.'), number_format($usage['character_limit'], 0, ',', '.')
        );
    }

    if ($storedPeriod !== $period || $newLevel !== $level) {
        setSiteSetting('deepl_usage_warn_period', $period);
        setSiteSetting('deepl_usage_warn_level', (string) $newLevel);
    }

    if ($message !== null) {
        logActivity($db, 'translate.quota_warning', $message);
        if (function_exists('flash')) {
            flash('warning', '⚠️ ' . $message);
        }
    }
}
