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

/**
 * Holt für bereits bestehende Berichte (angelegt, bevor es die automatische
 * Übersetzung gab, oder deren Übersetzung zuletzt fehlgeschlagen ist) die
 * englische Fassung nach - genau dieselbe Logik wie beim normalen Speichern
 * in admin/report-edit.php, nur in einem Rutsch für mehrere Berichte.
 *
 * Läuft in Portionen ($limit Berichte pro Aufruf), damit ein einzelner
 * Seitenaufruf nicht zu lange dauert und das DeepL-Kontingent nicht auf
 * einen Schlag verbraucht wird - admin/settings.php zeigt dafür einen
 * Button, auf den man bei Bedarf mehrfach klickt.
 *
 * @return array{done:int,remaining:int,failed:int}
 */
function backfillReportTranslations(PDO $db, int $limit = 20): array {
    $done = 0;
    $failed = 0;

    $stmt = $db->prepare("SELECT * FROM reports WHERE (title_en IS NULL OR title_en = '') AND title != '' LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll();

    foreach ($reports as $report) {
        $linksArr = json_decode($report['links'] ?? '', true) ?: [];
        $imgStmt = $db->prepare("SELECT id, caption FROM report_images WHERE report_id = ? AND (caption_en IS NULL OR caption_en = '') AND caption != ''");
        $imgStmt->execute([$report['id']]);
        $images = $imgStmt->fetchAll();

        $toTranslate = ['title' => $report['title'], 'content' => $report['content'] ?? ''];
        foreach ($linksArr as $i => $link) {
            $toTranslate['link_' . $i] = is_array($link) ? ($link['label'] ?? '') : '';
        }
        foreach ($images as $i => $img) {
            $toTranslate['caption_' . $i] = $img['caption'];
        }

        $translated = translateBatchToEnglish($toTranslate);
        if ($translated === null) {
            $failed++;
            continue;
        }

        $linksEnArr = [];
        foreach ($linksArr as $i => $link) {
            $linksEnArr[] = ['url' => is_array($link) ? ($link['url'] ?? '') : '', 'label' => $translated['link_' . $i] ?? ''];
        }
        $linksEnJson = !empty($linksEnArr) ? json_encode($linksEnArr, JSON_UNESCAPED_UNICODE) : null;

        $db->prepare("UPDATE reports SET title_en = ?, content_en = ?, links_en = ? WHERE id = ?")
            ->execute([$translated['title'] ?? '', $translated['content'] ?? '', $linksEnJson, $report['id']]);

        foreach ($images as $i => $img) {
            $db->prepare("UPDATE report_images SET caption_en = ? WHERE id = ?")
                ->execute([$translated['caption_' . $i] ?? '', $img['id']]);
        }

        $done++;
    }

    if ($done > 0) {
        checkDeepLUsageAndWarn($db);
    }

    $remainingStmt = $db->query("SELECT COUNT(*) FROM reports WHERE (title_en IS NULL OR title_en = '') AND title != ''");
    return ['done' => $done, 'remaining' => (int) $remainingStmt->fetchColumn(), 'failed' => $failed];
}

/**
 * Dasselbe Prinzip wie backfillReportTranslations(), nur für Mitgliederprofile
 * (Bio + Funktionsbezeichnungen).
 *
 * @return array{done:int,remaining:int,failed:int}
 */
function backfillMemberTranslations(PDO $db, int $limit = 20): array {
    $done = 0;
    $failed = 0;

    $stmt = $db->prepare("SELECT * FROM members WHERE (bio_en IS NULL OR bio_en = '') AND bio IS NOT NULL AND bio != '' LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $members = $stmt->fetchAll();

    foreach ($members as $member) {
        $funcs = json_decode($member['functions'] ?? '[]', true) ?: [];
        $toTranslate = ['bio' => $member['bio'] ?? ''];
        foreach ($funcs as $i => $f) {
            $toTranslate['role_' . $i] = $f['role'] ?? '';
        }

        $translated = translateBatchToEnglish($toTranslate);
        if ($translated === null) {
            $failed++;
            continue;
        }

        $funcsEn = [];
        foreach ($funcs as $i => $f) {
            $funcsEn[] = ['section' => $f['section'] ?? '', 'role' => $translated['role_' . $i] ?? ''];
        }

        $db->prepare("UPDATE members SET bio_en = ?, functions_en = ? WHERE id = ?")
            ->execute([$translated['bio'] ?? '', json_encode($funcsEn, JSON_UNESCAPED_UNICODE), $member['id']]);

        $done++;
    }

    if ($done > 0) {
        checkDeepLUsageAndWarn($db);
    }

    $remainingStmt = $db->query("SELECT COUNT(*) FROM members WHERE (bio_en IS NULL OR bio_en = '') AND bio IS NOT NULL AND bio != ''");
    return ['done' => $done, 'remaining' => (int) $remainingStmt->fetchColumn(), 'failed' => $failed];
}
