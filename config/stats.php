<?php
/**
 * Einsatz-Statistik für den aktuellen Berichtszeitraum.
 *
 * Der Zeitraum beginnt am 1. Freitag im März und läuft, bis er manuell
 * über Admin -> Einstellungen zurückgesetzt wird (kein automatischer
 * Jahreswechsel). Das Startdatum wird in site_settings gespeichert.
 * Der Zeitraum wird bewusst nirgends öffentlich angezeigt.
 */

/**
 * Liefert das Datum (Y-m-d) des 1. Freitags im März eines Jahres.
 */
function getFirstFridayOfMarch(int $year): string {
    $date = new DateTime("$year-03-01");
    $dayOfWeek = (int)$date->format('N'); // 1 (Montag) bis 7 (Sonntag)
    $offset = (5 - $dayOfWeek + 7) % 7; // 5 = Freitag
    $date->modify("+$offset days");
    return $date->format('Y-m-d');
}

/**
 * Liest das aktuelle Startdatum des Berichtszeitraums aus site_settings.
 * Ist noch keines gesetzt, wird der zuletzt vergangene 1. Freitag im März
 * als Startwert berechnet und gespeichert.
 */
function getStatsPeriodStart(PDO $db): string {
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'stats_period_start'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    if ($value) {
        return $value;
    }

    $today = date('Y-m-d');
    $year = (int)date('Y', strtotime($today));
    $firstFriday = getFirstFridayOfMarch($year);
    $start = $today >= $firstFriday ? $firstFriday : getFirstFridayOfMarch($year - 1);

    saveStatsPeriodStart($db, $start);
    return $start;
}

function saveStatsPeriodStart(PDO $db, string $date): void {
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'stats_period_start'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = 'stats_period_start'")->execute([$date]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES ('stats_period_start', ?)")->execute([$date]);
    }
}

/**
 * Setzt den Berichtszeitraum manuell zurück: neuer Start ist der zuletzt
 * vergangene (oder heutige) 1. Freitag im März.
 */
function resetStatsPeriod(PDO $db): string {
    $today = date('Y-m-d');
    $year = (int)date('Y', strtotime($today));
    $firstFriday = getFirstFridayOfMarch($year);
    $start = $today >= $firstFriday ? $firstFriday : getFirstFridayOfMarch($year - 1);

    saveStatsPeriodStart($db, $start);
    return $start;
}

/**
 * Zählt Einsätze/Übungen seit dem aktuellen Berichtszeitraum-Start,
 * aufgeschlüsselt nach Subkategorie. Archivierte Berichte werden
 * mitgezählt - die Archivierung ist nur eine Alters-Kennzeichnung,
 * kein Ausschlusskriterium.
 */
function getEinsatzStats(PDO $db): array {
    $start = getStatsPeriodStart($db);

    $count = function (string $extraWhere, array $params) use ($db, $start): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND date >= ? $extraWhere");
        $stmt->execute(array_merge([$start], $params));
        return (int)$stmt->fetchColumn();
    };

    return [
        'start' => $start,
        'einsatz_gesamt' => $count("AND category = 'einsatz'", []),
        'uebung' => $count("AND category = 'uebung'", []),
        'brand' => $count("AND category = 'einsatz' AND subcategory = 'brand'", []),
        'technisch' => $count("AND category = 'einsatz' AND subcategory = 'technisch'", []),
        'unterstuetzung' => $count("AND category = 'einsatz' AND subcategory = 'unterstuetzung'", []),
        'abc' => $count("AND category = 'einsatz' AND subcategory = 'abc'", []),
    ];
}
