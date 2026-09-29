<?php
/**
 * Grenzlinie der Schutzbereich-Karte auf der Seite "Über uns".
 *
 * Wird als JSON-Array von [lat, lng]-Paaren in site_settings gespeichert
 * (gleiches Muster wie einsatzbereitschaft_*) und über den Admin-Bereich
 * (admin/schutzbereich.php) mit einer interaktiven Leaflet-Karte bearbeitet.
 * Ohne gespeicherten Wert gilt die zuletzt von Hand nachgezogene Form als
 * Standard.
 */

require_once __DIR__ . '/settings.php';

const SCHUTZBEREICH_DEFAULT_POLYGON = [
    [47.2780, 11.4010],
    [47.2810, 11.4100],
    [47.2830, 11.4210],
    [47.2825, 11.4330],
    [47.2795, 11.4425],
    [47.2765, 11.4475],
    [47.2735, 11.4455],
    [47.2705, 11.4415],
    [47.2685, 11.4390],
    [47.2665, 11.4360],
    [47.2648, 11.4350],
    [47.2662, 11.4315],
    [47.2640, 11.4260],
    [47.2620, 11.4200],
    [47.2605, 11.4140],
    [47.2600, 11.4090],
    [47.2612, 11.4043],
    [47.2633, 11.4010],
    [47.2598, 11.3998],
    [47.2650, 11.3985],
    [47.2700, 11.3990],
    [47.2745, 11.4000],
];

function getSchutzbereichPolygon(): array {
    $json = getSiteSetting('schutzbereich_polygon', '');
    if ($json === '') {
        return SCHUTZBEREICH_DEFAULT_POLYGON;
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded) || count($decoded) < 3) {
        return SCHUTZBEREICH_DEFAULT_POLYGON;
    }
    $coords = [];
    foreach ($decoded as $point) {
        if (!is_array($point) || count($point) !== 2) {
            continue;
        }
        $lat = (float) $point[0];
        $lng = (float) $point[1];
        if ($lat < 40 || $lat > 55 || $lng < 5 || $lng > 20) {
            continue;
        }
        $coords[] = [$lat, $lng];
    }
    return count($coords) >= 3 ? $coords : SCHUTZBEREICH_DEFAULT_POLYGON;
}

function setSchutzbereichPolygon(array $coords): void {
    setSiteSetting('schutzbereich_polygon', json_encode(array_values($coords)));
}

function resetSchutzbereichPolygon(): void {
    setSiteSetting('schutzbereich_polygon', json_encode(SCHUTZBEREICH_DEFAULT_POLYGON));
}
