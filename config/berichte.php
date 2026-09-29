<?php
/**
 * Kalenderjahr-Einteilung für die Einsatzberichte (Aktuelles Jahr / Vorjahr /
 * Archiv). Wird über Admin -> Einstellungen verwaltet und in site_settings
 * gespeichert. Ohne gespeicherten Wert wird automatisch das echte
 * Kalenderjahr verwendet, damit die Einteilung auch ohne Adminaktion immer
 * aktuell bleibt.
 */

require_once __DIR__ . '/database.php';

function getBerichteAktuellesJahr(): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'berichte_aktuelles_jahr'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return ($value !== false && $value !== '') ? (int) $value : (int) date('Y');
}

function getBerichteVorjahr(): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'berichte_vorjahr'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return ($value !== false && $value !== '') ? (int) $value : getBerichteAktuellesJahr() - 1;
}

function setBerichteJahre(int $aktuellesJahr, int $vorjahr): void {
    $db = getDB();
    $values = [
        'berichte_aktuelles_jahr' => (string) $aktuellesJahr,
        'berichte_vorjahr' => (string) $vorjahr,
    ];
    foreach ($values as $key => $value) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() > 0) {
            $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = ?")->execute([$value, $key]);
        } else {
            $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES (?, ?)")->execute([$key, $value]);
        }
    }
}

/**
 * Beliebig viele externe Links pro Bericht (Instagram, Facebook, externe
 * Website, ...). Werden als JSON-Array von URLs in reports.links
 * gespeichert (siehe Migration 2026_09_29_add_report_links) und über
 * Admin -> Berichte mit einem "+"-Button gepflegt.
 */
function getReportLinks(array $report): array {
    $links = json_decode($report['links'] ?? '', true);
    return is_array($links) ? array_values(array_filter($links, fn($l) => is_string($l) && trim($l) !== '')) : [];
}

/**
 * Icon + Beschriftung werden automatisch aus der URL erkannt, damit beim
 * Bearbeiten kein zusätzliches Icon-Auswahlfeld gepflegt werden muss.
 */
function detectReportLinkIcon(string $url): array {
    if (stripos($url, 'instagram.com') !== false) {
        return ['icon' => 'fab fa-instagram', 'label' => 'Auf Instagram ansehen'];
    }
    if (stripos($url, 'facebook.com') !== false) {
        return ['icon' => 'fab fa-facebook', 'label' => 'Auf Facebook ansehen'];
    }
    if (stripos($url, 'youtube.com') !== false || stripos($url, 'youtu.be') !== false) {
        return ['icon' => 'fab fa-youtube', 'label' => 'Auf YouTube ansehen'];
    }
    return ['icon' => 'fas fa-external-link-alt', 'label' => 'Weitere Informationen'];
}
