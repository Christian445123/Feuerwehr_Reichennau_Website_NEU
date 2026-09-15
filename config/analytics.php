<?php
/**
 * Google Analytics (GA4) - Measurement-ID wird über Admin -> Einstellungen
 * verwaltet und in site_settings gespeichert. Das Skript wird öffentlich
 * erst nach Einwilligung des Besuchers geladen (siehe includes/footer.php).
 */

require_once __DIR__ . '/database.php';

function getGaMeasurementId(): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'ga_measurement_id'");
    $stmt->execute();
    return trim((string) $stmt->fetchColumn());
}

function setGaMeasurementId(string $id): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'ga_measurement_id'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = 'ga_measurement_id'")->execute([$id]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES ('ga_measurement_id', ?)")->execute([$id]);
    }
}
