<?php
/**
 * Einsatz-Statistik für einen Berichtszeitraum, der immer vom 06. März
 * bis zum 06. März des Folgejahres läuft (z.B. 06.03.2026 - 06.03.2027),
 * statt einem Kalenderjahr.
 */

/**
 * Liefert [$start, $end] (Y-m-d) des aktuell laufenden Berichtszeitraums.
 * $end ist exklusiv (Berichte am 06. März gehören bereits zum neuen Zeitraum).
 */
function getBerichtsjahrRange(?string $today = null): array {
    $today = $today ?? date('Y-m-d');
    $year = (int)date('Y', strtotime($today));
    $marchSixThisYear = sprintf('%04d-03-06', $year);

    if ($today >= $marchSixThisYear) {
        $start = $marchSixThisYear;
        $end = sprintf('%04d-03-06', $year + 1);
    } else {
        $start = sprintf('%04d-03-06', $year - 1);
        $end = $marchSixThisYear;
    }

    return [$start, $end];
}

/**
 * Zählt Einsätze/Übungen im aktuellen Berichtszeitraum, aufgeschlüsselt
 * nach Subkategorie. Archivierte Berichte werden mitgezählt - die
 * Archivierung ist nur eine Alters-Kennzeichnung, kein Ausschlusskriterium.
 */
function getEinsatzStats(PDO $db): array {
    [$start, $end] = getBerichtsjahrRange();

    $count = function (string $extraWhere, array $params) use ($db, $start, $end): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND date >= ? AND date < ? $extraWhere");
        $stmt->execute(array_merge([$start, $end], $params));
        return (int)$stmt->fetchColumn();
    };

    return [
        'start' => $start,
        'end' => $end,
        'einsatz_gesamt' => $count("AND category = 'einsatz'", []),
        'uebung' => $count("AND category = 'uebung'", []),
        'brand' => $count("AND category = 'einsatz' AND subcategory = 'brand'", []),
        'technisch' => $count("AND category = 'einsatz' AND subcategory = 'technisch'", []),
        'unterstuetzung' => $count("AND category = 'einsatz' AND subcategory = 'unterstuetzung'", []),
        'abc' => $count("AND category = 'einsatz' AND subcategory = 'abc'", []),
    ];
}
