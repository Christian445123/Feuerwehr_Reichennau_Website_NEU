<?php
/**
 * Allgemeiner, admin-gesteuerter Warnhinweis, der (falls aktiv) als
 * Banner über die gesamte Seitenbreite ganz oben auf jeder Seite erscheint
 * (siehe includes/header.php). Hat nichts mit Wetter/GeoSphere zu tun -
 * der Typ (Wetter, Verkehr, Hochwasser, Sicherheit, Sonstiges) wird frei
 * im Admin-Bereich gewählt und bestimmt nur Icon/Vorauswahl der Farbe.
 *
 * Zwei unabhängige Quellen, Testmodus hat Vorrang:
 * - Testmodus: fester Beispielinhalt, zum schnellen Prüfen der Optik
 * - Manuell: frei eingegebener Typ/Text/Bereich/Farbe
 */

require_once __DIR__ . '/settings.php';

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
            'area' => 'Testbereich',
            'text' => 'Dies ist ein Test des Seiten-Warnhinweises (Testmodus in Admin -> Einstellungen aktiv) - kein echter Hinweis.',
        ];
    }

    if (getSiteSetting('site_alert_enabled', '0') === '1') {
        return [
            'type' => getSiteSetting('site_alert_type', 'sonstiges'),
            'color' => getSiteSetting('site_alert_color', '#e74c3c'),
            'area' => getSiteSetting('site_alert_area', ''),
            'text' => getSiteSetting('site_alert_text', ''),
        ];
    }

    return null;
}
