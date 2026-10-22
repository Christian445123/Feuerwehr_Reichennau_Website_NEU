<?php
/**
 * Sprachumschaltung Deutsch/Englisch für die öffentliche Website.
 *
 * Deutsch ist immer die Standardsprache. Über den Link "?lang=en" (siehe
 * includes/nav.php) wird für ein Jahr ein Cookie gesetzt, danach bleibt
 * Englisch bis zum manuellen Zurückschalten oder Löschen des Cookies
 * bestehen. Admin-Bereich und Zugangssperre (Zugangspasswort/Wartungsmodus)
 * sind von dieser Umschaltung nicht betroffen - nur die öffentliche Website.
 */

if (!function_exists('e')) {
    function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

const SUPPORTED_LANGUAGES = ['de', 'en'];
const LANGUAGE_COOKIE_NAME = 'site_lang';

/**
 * Ermittelt die aktuelle Sprache (einmal pro Request) und setzt bei einem
 * expliziten "?lang=xx"-Parameter das Cookie neu. Muss aufgerufen werden,
 * bevor irgendetwas an den Browser gesendet wurde (Cookies lassen sich sonst
 * nicht mehr setzen) - siehe Aufruf in config/gate.php.
 */
function initLanguage(): string {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && in_array($requested, SUPPORTED_LANGUAGES, true)) {
        $resolved = $requested;
        if (!headers_sent()) {
            setcookie(LANGUAGE_COOKIE_NAME, $resolved, [
                'expires' => time() + 60 * 60 * 24 * 365,
                'path' => '/',
                'samesite' => 'Lax',
            ]);
        }
        return $resolved;
    }

    $cookieValue = $_COOKIE[LANGUAGE_COOKIE_NAME] ?? '';
    $resolved = in_array($cookieValue, SUPPORTED_LANGUAGES, true) ? $cookieValue : 'de';
    return $resolved;
}

/**
 * Aktuelle Sprache ('de' oder 'en'). Ruft initLanguage() nur auf, falls das
 * noch nicht an anderer Stelle (config/gate.php) passiert ist.
 */
function getLanguage(): string {
    return initLanguage();
}

function isEnglish(): bool {
    return getLanguage() === 'en';
}

/**
 * Baut die URL für den DE/EN-Umschalter: dieselbe Seite (inkl. aller
 * übrigen Query-Parameter), nur mit geänderter "lang"-Angabe.
 */
function languageSwitchUrl(string $targetLang): string {
    $params = $_GET;
    $params['lang'] = $targetLang;
    return 'index.php?' . http_build_query($params);
}

/**
 * Übersetzt einen Schlüssel aus lang/de.php bzw. lang/en.php. Ohne
 * Übersetzung (z.B. Tippfehler im Schlüssel) wird der Schlüssel selbst
 * zurückgegeben, damit ein fehlender Eintrag im Frontend auffällt, statt die
 * Seite leer/kaputt aussehen zu lassen.
 *
 * Platzhalter im Text im Format "{{name}}" werden durch $vars['name'] ersetzt.
 */
function t(string $key, array $vars = []): string {
    static $translations = [];
    $lang = getLanguage();

    if (!isset($translations[$lang])) {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        $translations[$lang] = file_exists($file) ? (include $file) : [];
    }

    $text = $translations[$lang][$key] ?? null;
    if ($text === null && $lang !== 'de') {
        // Fehlt ein Schlüssel in der englischen Übersetzung, lieber den
        // deutschen Text zeigen als nur den rohen Schlüssel.
        if (!isset($translations['de'])) {
            $deFile = __DIR__ . '/../lang/de.php';
            $translations['de'] = file_exists($deFile) ? (include $deFile) : [];
        }
        $text = $translations['de'][$key] ?? null;
    }
    if ($text === null) {
        return $key;
    }

    foreach ($vars as $name => $value) {
        $text = str_replace('{{' . $name . '}}', (string) $value, $text);
    }
    return $text;
}
