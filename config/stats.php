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

    // Zählt auch Berichte, bei denen "Einsatz" nur die zweite Kategorie ist
    // (z.B. eine Übung, bei der tatsächlich ein Einsatz stattfand) - ein
    // Bericht wird dabei nicht doppelt gezählt, selbst wenn beide Kategorien
    // zufällig gleich wären.
    $count = function (string $subcategory) use ($db, $start, $end): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND date BETWEEN ? AND ? AND ((category = 'einsatz' AND subcategory = ?) OR (category2 = 'einsatz' AND subcategory2 = ?))");
        $stmt->execute([$start, $end, $subcategory, $subcategory]);
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
