<?php
/**
 * Generischer Key-Value-Speicher für Website-Einstellungen (site_settings).
 *
 * Wird u.a. von maintenance_mode, site_password, ga_measurement_id,
 * einsatzbereitschaft_* und schutzbereich_polygon verwendet.
 */

require_once __DIR__ . '/database.php';

function getSiteSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

function setSiteSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = ?")->execute([$value, $key]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES (?, ?)")->execute([$key, $value]);
    }
}
