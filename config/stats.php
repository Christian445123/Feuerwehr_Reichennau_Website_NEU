<?php
/**
 * Einsatz-Statistik für das laufende Kalenderjahr.
 *
 * Gezählt wird immer vom 1.1. bis zum 31.12. des aktuellen Jahres - der
 * Zähler springt zum Jahreswechsel automatisch auf 0. Es gibt drei
 * Kategorien: Brandeinsätze, Technische Einsätze und ABC-Einsätze.
 */

/**
 * Zählt veröffentlichte Einsätze des laufenden Kalenderjahres, aufgeschlüsselt
 * nach Einsatzart. Archivierte Berichte werden mitgezählt - die Archivierung
 * ist nur eine Alters-Kennzeichnung, kein Ausschlusskriterium.
 */
function getEinsatzStats(PDO $db): array {
    $year = (int)date('Y');
    $start = "$year-01-01";
    $end = "$year-12-31";

    $count = function (string $subcategory) use ($db, $start, $end): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND category = 'einsatz' AND subcategory = ? AND date BETWEEN ? AND ?");
        $stmt->execute([$subcategory, $start, $end]);
        return (int)$stmt->fetchColumn();
    };

    $stats = [
        'year' => $year,
        'brand' => $count('brand'),
        'technisch' => $count('technisch'),
        'abc' => $count('abc'),
    ];
    $stats['einsatz_gesamt'] = $stats['brand'] + $stats['technisch'] + $stats['abc'];
    return $stats;
}
