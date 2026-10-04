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
    // zufällig gleich wären. Da je Einsatz bis zu 2 Einsatzarten gleichzeitig
    // gewählt werden können (z.B. Brand, der in ABC überging), steckt die
    // subcategory-Spalte eine kommagetrennte Liste - FIND_IN_SET prüft, ob
    // die gesuchte Einsatzart darin vorkommt, ein Bericht mit mehreren
    // Einsatzarten zählt also bei jeder davon mit.
    $count = function (string $subcategory) use ($db, $start, $end): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND date BETWEEN ? AND ? AND ((category = 'einsatz' AND FIND_IN_SET(?, subcategory)) OR (category2 = 'einsatz' AND FIND_IN_SET(?, subcategory2)))");
        $stmt->execute([$start, $end, $subcategory, $subcategory]);
        return (int)$stmt->fetchColumn();
    };

    // "Einsätze gesamt" zählt Berichte, nicht Einsatzarten - sonst würde ein
    // Einsatz mit zwei Einsatzarten (Brand + ABC) doppelt mitgezählt.
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND date BETWEEN ? AND ? AND (category = 'einsatz' OR category2 = 'einsatz')");
    $totalStmt->execute([$start, $end]);

    return [
        'year' => $year,
        'brand' => $count('brand'),
        'technisch' => $count('technisch'),
        'abc' => $count('abc'),
        'einsatz_gesamt' => (int) $totalStmt->fetchColumn(),
    ];
}
