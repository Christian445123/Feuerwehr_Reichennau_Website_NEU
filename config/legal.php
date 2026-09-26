<?php
/**
 * Bearbeitbare Rechtstexte: Datenschutzerklärung, Impressum, Allgemeine Hinweise.
 *
 * Jeder Text besteht aus Abschnitten (Symbol, Überschrift, HTML-Inhalt). Die
 * Standardtexte stehen in legal-defaults.php; sobald im Admin gespeichert
 * wird, liegt die Fassung als JSON in site_settings (legal_<schlüssel>) und
 * ersetzt den Standard.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/hero.php'; // getHeroSetting / setHeroSetting

function getLegalDocuments(): array {
    return [
        'datenschutz' => 'Datenschutzerklärung',
        'impressum' => 'Impressum',
        'hinweise' => 'Allgemeine Hinweise',
    ];
}

function getLegalDefaults(string $key): array {
    $all = require __DIR__ . '/legal-defaults.php';
    return $all[$key] ?? [];
}

function isLegalCustomized(PDO $db, string $key): bool {
    return getHeroSetting($db, 'legal_' . $key, '') !== '';
}

function getLegalSections(PDO $db, string $key): array {
    $stored = json_decode(getHeroSetting($db, 'legal_' . $key, ''), true);
    return is_array($stored) && $stored ? $stored : getLegalDefaults($key);
}

function saveLegalSections(PDO $db, string $key, array $sections): void {
    setHeroSetting($db, 'legal_' . $key, json_encode(array_values($sections), JSON_UNESCAPED_UNICODE));
}

function resetLegalSections(PDO $db, string $key): void {
    setHeroSetting($db, 'legal_' . $key, '');
}

/**
 * Erlaubt nur harmlose Formatierungs-Tags und entfernt Skripte,
 * Event-Handler (onclick ...) und javascript:-Links.
 */
function sanitizeLegalHtml(string $html): string {
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><h3><h4><h5><ul><ol><li><a><div><span><hr><button><table><tr><td><th><thead><tbody>');
    $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $html);
    return $html;
}

/**
 * Gibt die Abschnitte als Inhaltskarten aus (Symbol + Überschrift + Text).
 */
function renderLegalSections(array $sections, string $wrapperClass = ''): void {
    foreach ($sections as $s) {
        $icon = htmlspecialchars(preg_replace('/[^a-z0-9 \-]/i', '', $s['icon'] ?? 'fas fa-file-alt'));
        $title = htmlspecialchars(html_entity_decode($s['title'] ?? ''));
        $body = sanitizeLegalHtml($s['body'] ?? '');
        if ($wrapperClass !== '') echo '<div class="' . $wrapperClass . '">';
        echo '<div class="content-card"><div class="content-card-header"><div class="content-card-icon"><i class="' . $icon . '"></i></div>';
        echo '<h2>' . $title . '</h2></div><div class="content-card-body">' . $body . '</div></div>';
        if ($wrapperClass !== '') echo '</div>';
    }
}
