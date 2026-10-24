<?php
/**
 * Allgemeiner Warnhinweis, der (falls aktiv) als Vollbild-Overlay über die
 * gesamte Website/den gesamten Bildschirm erscheint (siehe
 * includes/header.php), bestätigt werden muss und danach als kleine
 * Markierung links liegen bleibt - zusätzlich prominent im Hero der
 * Startseite (siehe pages/home.php). Die Kategorie (Erdbeben, Hochwasser,
 * Unwetter, Verkehr, Chemieunfall, ...) bestimmt Icon und die angezeigten
 * Verhaltenstipps; Kategorien sind frei erweiter- und bearbeitbar über
 * Admin -> Einstellungen (Tabelle site_alert_categories, siehe
 * config/migrations.php für die Vorbefüllung).
 *
 * Drei Quellen, in dieser Reihenfolge (die erste zutreffende gewinnt):
 * 1. Testmodus: fester Beispielinhalt, zum schnellen Prüfen der Optik
 * 2. Manuell: frei gewählte Kategorie + Text/Ort/Bereich/Farbe
 * 3. GeoSphere Austria: eine echte aktive Wetterwarnung für
 *    Innsbruck-Stadt/Innsbruck-Land/Tirol löst automatisch denselben
 *    Vollbild-Hinweis aus wie eine manuelle Warnung (siehe
 *    config/geosphere-warnings.php), Kategorie "unwetter"
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/geosphere-warnings.php';

/**
 * Alle Warnhinweis-Kategorien, sortiert, als [category_key => [...]].
 */
function getSiteAlertTypes(): array {
    $db = getDB();
    $rows = $db->query("SELECT * FROM site_alert_categories ORDER BY sort_order ASC, label ASC")->fetchAll();
    $types = [];
    foreach ($rows as $row) {
        $types[$row['category_key']] = [
            'label' => $row['label'],
            'icon' => $row['icon'],
            'tips' => $row['tips'] ?? '',
        ];
    }
    return $types;
}

function getSiteAlertCategory(string $key): ?array {
    $types = getSiteAlertTypes();
    return $types[$key] ?? null;
}

/**
 * Legt eine neue Kategorie an oder aktualisiert eine bestehende (anhand
 * $key). $key wird dabei nie verändert - nur Label/Icon/Tipps.
 */
function saveSiteAlertCategory(string $key, string $label, string $icon, string $tips, int $sortOrder = 0): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_alert_categories WHERE category_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_alert_categories SET label = ?, icon = ?, tips = ?, sort_order = ? WHERE category_key = ?")
            ->execute([$label, $icon, $tips, $sortOrder, $key]);
    } else {
        $db->prepare("INSERT INTO site_alert_categories (category_key, label, icon, tips, sort_order) VALUES (?,?,?,?,?)")
            ->execute([$key, $label, $icon, $tips, $sortOrder]);
    }
}

/**
 * Löscht eine Kategorie. Ist sie gerade als manueller Warnhinweis aktiv
 * ausgewählt, wird der Hinweis automatisch deaktiviert, damit keine
 * verwaiste Auswahl stehen bleibt.
 */
function deleteSiteAlertCategory(string $key): void {
    $db = getDB();
    $db->prepare("DELETE FROM site_alert_categories WHERE category_key = ?")->execute([$key]);
    if (getSiteSetting('site_alert_type', '') === $key) {
        setSiteSetting('site_alert_enabled', '0');
    }
}

/**
 * Liefert den aktuell anzuzeigenden Seiten-Warnhinweis, oder null, wenn
 * keiner aktiv ist. Enthält immer 'tips' (ggf. leerer String) zusätzlich
 * zum freien Hinweistext.
 */
function getActiveSiteAlert(): ?array {
    $types = getSiteAlertTypes();

    if (getSiteSetting('site_alert_test_mode', '0') === '1') {
        $fallback = $types['sonstiges'] ?? ['icon' => 'fa-triangle-exclamation', 'tips' => ''];
        return [
            'type' => 'sonstiges',
            'color' => '#e67e22',
            'location' => 'Testort',
            'area' => 'Testbereich',
            'text' => 'Dies ist ein Test des Seiten-Warnhinweises (Testmodus in Admin -> Einstellungen aktiv) - kein echter Hinweis.',
            'tips' => $fallback['tips'] ?? '',
        ];
    }

    if (getSiteSetting('site_alert_enabled', '0') === '1') {
        $type = getSiteSetting('site_alert_type', 'sonstiges');
        $category = $types[$type] ?? null;
        return [
            'type' => $category !== null ? $type : 'sonstiges',
            'color' => getSiteSetting('site_alert_color', '#e74c3c'),
            'location' => getSiteSetting('site_alert_location', ''),
            'area' => getSiteSetting('site_alert_area', ''),
            'text' => getSiteSetting('site_alert_text', ''),
            'tips' => $category['tips'] ?? '',
        ];
    }

    $geosphereAlert = getGeoSphereSiteAlert();
    if ($geosphereAlert !== null) {
        return $geosphereAlert;
    }

    return null;
}

/**
 * Baut - falls aktuell eine echte GeoSphere-Austria-Warnung für
 * Innsbruck-Stadt, Innsbruck-Land oder Tirol insgesamt aktiv ist - denselben
 * Hinweis, wie ihn auch eine manuelle Warnung erzeugen würde (gleiche
 * Anzeige/Overlay, Kategorie "unwetter" inkl. deren Tipps).
 */
function getGeoSphereSiteAlert(): ?array {
    $worst = getWorstGeoSphereInnsbruckWarning();
    if ($worst === null) {
        return null;
    }

    $colorsByLevel = [1 => '#f1c40f', 2 => '#e67e22', 3 => '#e74c3c'];
    $unwetterCategory = getSiteAlertCategory('unwetter');

    return [
        'type' => 'unwetter',
        'color' => $colorsByLevel[$worst['level']] ?? '#e74c3c',
        'location' => $worst['region'],
        'area' => '',
        'text' => 'GeoSphere Austria warnt aktuell vor: ' . $worst['type'] . '.',
        'tips' => $unwetterCategory['tips'] ?? '',
    ];
}
