<?php
/**
 * Hintergrund der Startseite (Admin -> Startseiten-Hintergrund).
 *
 * Entweder ein stehendes Bild oder eine Diashow aus mehreren Bildern. Die
 * Einstellungen liegen in site_settings: hero_mode ('single' | 'slideshow'),
 * hero_interval (Sekunden pro Bild) und hero_images (JSON-Liste der Pfade).
 */

require_once __DIR__ . '/database.php';

function getHeroSetting(PDO $db, string $key, string $default = ''): string {
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function setHeroSetting(PDO $db, string $key, string $value): void {
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = ?")->execute([$value, $key]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES (?, ?)")->execute([$key, $value]);
    }
}

/** Liste der Hintergrundbilder (Pfade relativ zum Webroot, z.B. uploads/hero/x.jpg). */
function getHeroImages(PDO $db): array {
    $list = json_decode(getHeroSetting($db, 'hero_images', '[]'), true);
    return is_array($list) ? array_values($list) : [];
}

function saveHeroImages(PDO $db, array $images): void {
    setHeroSetting($db, 'hero_images', json_encode(array_values($images)));
}

function getHeroMode(PDO $db): string {
    return getHeroSetting($db, 'hero_mode', 'slideshow') === 'single' ? 'single' : 'slideshow';
}

function getHeroInterval(PDO $db): int {
    return max(2, min(60, (int) getHeroSetting($db, 'hero_interval', '6')));
}

/**
 * Bilder, die auf der Startseite tatsächlich angezeigt werden: im Modus
 * "Einzelbild" nur das erste, sonst alle.
 */
function getActiveHeroImages(PDO $db): array {
    $images = getHeroImages($db);
    return getHeroMode($db) === 'single' ? array_slice($images, 0, 1) : $images;
}
