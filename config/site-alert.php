<?php
/**
 * Allgemeiner Warnhinweis, der (falls aktiv) als Vollbild-Overlay über die
 * gesamte Website/den gesamten Bildschirm erscheint (siehe
 * includes/header.php), bestätigt werden muss und danach als kleine
 * Markierung links liegen bleibt. Der Typ (Wetter, Verkehr, Hochwasser,
 * Sicherheit, Sonstiges) bestimmt nur Icon/Vorauswahl der Farbe - admin-
 * gesteuert (Testmodus/manuell) oder automatisch durch eine echte
 * GeoSphere-Warnung ausgelöst.
 *
 * Drei Quellen, in dieser Reihenfolge (die erste zutreffende gewinnt):
 * 1. Testmodus: fester Beispielinhalt, zum schnellen Prüfen der Optik
 * 2. Manuell: frei eingegebener Typ/Text/Ort/Bereich/Farbe
 * 3. GeoSphere Austria: eine echte aktive Wetterwarnung für
 *    Innsbruck-Stadt/Innsbruck-Land löst automatisch denselben
 *    Vollbild-Hinweis aus wie eine manuelle Warnung (siehe
 *    config/geosphere-warnings.php)
 */

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/geosphere-warnings.php';

function getSiteAlertTypes(): array {
    return [
        'wetter' => ['label' => 'Wetter', 'icon' => 'fa-cloud-bolt'],
        'verkehr' => ['label' => 'Verkehr', 'icon' => 'fa-car-burst'],
        'hochwasser' => ['label' => 'Hochwasser / Wasser', 'icon' => 'fa-water'],
        'sicherheit' => ['label' => 'Sicherheit', 'icon' => 'fa-shield-halved'],
        'sonstiges' => ['label' => 'Sonstiges', 'icon' => 'fa-triangle-exclamation'],
    ];
}

/**
 * Liefert den aktuell anzuzeigenden Seiten-Warnhinweis, oder null, wenn
 * keiner aktiv ist.
 */
function getActiveSiteAlert(): ?array {
    if (getSiteSetting('site_alert_test_mode', '0') === '1') {
        return [
            'type' => 'sonstiges',
            'color' => '#e67e22',
            'location' => 'Testort',
            'area' => 'Testbereich',
            'text' => 'Dies ist ein Test des Seiten-Warnhinweises (Testmodus in Admin -> Einstellungen aktiv) - kein echter Hinweis.',
        ];
    }

    if (getSiteSetting('site_alert_enabled', '0') === '1') {
        return [
            'type' => getSiteSetting('site_alert_type', 'sonstiges'),
            'color' => getSiteSetting('site_alert_color', '#e74c3c'),
            'location' => getSiteSetting('site_alert_location', ''),
            'area' => getSiteSetting('site_alert_area', ''),
            'text' => getSiteSetting('site_alert_text', ''),
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
 * Innsbruck-Stadt oder Innsbruck-Land aktiv ist - denselben Hinweis, wie
 * ihn auch eine manuelle Warnung erzeugen würde (gleiche Anzeige/Overlay).
 */
function getGeoSphereSiteAlert(): ?array {
    $worst = getWorstGeoSphereInnsbruckWarning();
    if ($worst === null) {
        return null;
    }

    $colorsByLevel = [1 => '#f1c40f', 2 => '#e67e22', 3 => '#e74c3c'];

    return [
        'type' => 'wetter',
        'color' => $colorsByLevel[$worst['level']] ?? '#e74c3c',
        'location' => $worst['region'],
        'area' => '',
        'text' => 'GeoSphere Austria warnt aktuell vor: ' . $worst['type'] . '.',
    ];
}
