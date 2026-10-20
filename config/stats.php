<?php
/**
 * Einsatz-Statistik für das laufende Kalenderjahr.
 *
 * Gezählt wird immer vom 1.1. bis zum 31.12. des "Aktuellen Kalenderjahres",
 * wie es unter Admin -> Einstellungen -> Berichtsjahre eingetragen ist
 * (siehe config/berichte.php, getBerichteAktuellesJahr()) - nicht einfach
 * das tatsächliche Jahr der Serveruhr, damit Statistik und Berichte-Seite
 * garantiert zum selben Jahr zählen und nicht auseinanderlaufen können.
 * Der Zähler springt daher erst um, wenn dieses Einstellungsfeld zum
 * Jahreswechsel manuell (oder automatisch, falls dort nichts einträgen
 * ist) weitergestellt wird. Es gibt drei Kategorien: Brandeinsätze,
 * Technische Einsätze und ABC-Einsätze.
 */

require_once __DIR__ . '/berichte.php';

/**
 * Zählt veröffentlichte Einsätze des "Aktuellen Kalenderjahres", aufgeschlüsselt
 * nach Einsatzart. Archivierte Berichte werden mitgezählt - die Archivierung
 * ist nur eine Alters-Kennzeichnung, kein Ausschlusskriterium.
 */
function getEinsatzStats(PDO $db): array {
    $year = getBerichteAktuellesJahr();
    $start = "$year-01-01";
    $end = "$year-12-31";

    // Gezählt wird jeder Bericht, der irgendwo (als erste ODER als zweite
    // Kategorie) mit "Einsatz" markiert ist - auch eine Übung, bei der
    // zusätzlich ein Einsatz stattfand. Da ein Einsatz bis zu 2 Einsatzarten
    // gleichzeitig haben kann (z.B. Brand, der in ABC überging), steckt die
    // subcategory-Spalte eine kommagetrennte Liste - FIND_IN_SET prüft, ob
    // die gesuchte Einsatzart darin vorkommt, ein Bericht mit mehreren
    // Einsatzarten zählt also bei jeder davon mit.
    //
    // Ein unveröffentlichter Bericht zählt zusätzlich mit, wenn im Admin
    // "Trotzdem in der Statistik zählen" angehakt wurde (count_unpublished) -
    // er bleibt dabei auf der Website unsichtbar, nur der Zähler reagiert.
    // Die Abfrage läuft bei jedem Seitenaufruf neu gegen die Datenbank, ein
    // neuer Bericht wirkt sich also sofort aus, ganz ohne Cache.
    $count = function (string $subcategory) use ($db, $start, $end): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE (published = 1 OR count_unpublished = 1) AND date BETWEEN ? AND ? AND ((category = 'einsatz' AND FIND_IN_SET(?, subcategory)) OR (category2 = 'einsatz' AND FIND_IN_SET(?, subcategory2)))");
        $stmt->execute([$start, $end, $subcategory, $subcategory]);
        return (int)$stmt->fetchColumn();
    };

    $brand = $count('brand');
    $technisch = $count('technisch');
    $abc = $count('abc');

    return [
        'year' => $year,
        'brand' => $brand,
        'technisch' => $technisch,
        'abc' => $abc,
        // "Einsätze gesamt" wird bewusst aus den drei Kategorie-Zahlen
        // errechnet (Summe), nicht separat gezählt - ein Bericht mit zwei
        // Einsatzarten (z.B. Brand + ABC) zählt dadurch doppelt zur
        // Gesamtzahl, weil er auch doppelt in den Kategorie-Zahlen steckt.
        'einsatz_gesamt' => $brand + $technisch + $abc,
    ];
}
