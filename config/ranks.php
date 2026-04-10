<?php
/**
 * Tiroler Feuerwehr Dienstgrade
 * Nur für das Bundesland Tirol gültig.
 *
 * Jeder Eintrag: [Abkürzung => [Name, Kategorie, Sortierung]]
 * Badge-Bild: assets/images/ranks/{strtolower(abkuerzung)}.svg
 */

define('TIROL_RANKS', [
    // ── Mannschaftsdienstgrade ──
    'PFM'  => ['Probefeuerwehrmann', 'Mannschaft', 10],
    'FM'   => ['Feuerwehrmann', 'Mannschaft', 20],
    'OFM'  => ['Oberfeuerwehrmann', 'Mannschaft', 30],
    'HFM'  => ['Hauptfeuerwehrmann', 'Mannschaft', 40],

    // ── Chargendienstgrade (Löschmeister) ──
    'LM'   => ['Löschmeister', 'Chargen', 50],
    'OLM'  => ['Oberlöschmeister', 'Chargen', 60],
    'HLM'  => ['Hauptlöschmeister', 'Chargen', 70],

    // ── Chargendienstgrade (Brandmeister) ──
    'BM'   => ['Brandmeister', 'Chargen', 80],
    'OBM'  => ['Oberbrandmeister', 'Chargen', 90],
    'HBM'  => ['Hauptbrandmeister', 'Chargen', 100],

    // ── Verwaltungsdienstgrade ──
    'V'    => ['Verwalter', 'Verwaltung', 110],
    'OV'   => ['Oberverwalter', 'Verwaltung', 120],
    'HV'   => ['Hauptverwalter', 'Verwaltung', 130],

    // ── Offiziersdienstgrade ──
    'BI'   => ['Brandinspektor', 'Offiziere', 140],
    'OBI'  => ['Oberbrandinspektor', 'Offiziere', 150],
    'HBI'  => ['Hauptbrandinspektor', 'Offiziere', 160],

    // ── Höhere Offiziersdienstgrade ──
    'ABI'  => ['Abschnittsbrandinspektor', 'Höhere Offiziere', 170],
    'BR'   => ['Brandrat', 'Höhere Offiziere', 180],
    'OBR'  => ['Oberbrandrat', 'Höhere Offiziere', 190],

    // ── Stabsdienstgrade ──
    'FARZT' => ['Feuerwehrarzt', 'Stab', 200],
    'FKUR'  => ['Feuerwehrkurat', 'Stab', 210],

    // ── Ehrendienstgrade ──
    'EBI'   => ['Ehrenbrandinspektor', 'Ehren', 300],
    'EOBI'  => ['Ehrenoberbrandinspektor', 'Ehren', 310],
    'EHBI'  => ['Ehrenhauptbrandinspektor', 'Ehren', 320],
    'ELM'   => ['Ehrenlöschmeister', 'Ehren', 330],
    'EOLM'  => ['Ehrenoberlöschmeister', 'Ehren', 340],
    'EHV'   => ['Ehrenhauptverwalter', 'Ehren', 350],
]);

/**
 * Badge-Bildpfad für einen Dienstgrad
 */
function getRankBadgePath(string $rank): string {
    $file = 'assets/images/ranks/' . strtolower($rank) . '.svg';
    return $file;
}

/**
 * Vollständiger Name eines Dienstgrads
 */
function getRankName(string $rank): string {
    return TIROL_RANKS[$rank][0] ?? $rank;
}

/**
 * Kategorie eines Dienstgrads
 */
function getRankCategory(string $rank): string {
    return TIROL_RANKS[$rank][1] ?? 'Sonstige';
}

/**
 * Dienstgrade gruppiert nach Kategorie für Dropdown
 */
function getRanksGrouped(): array {
    $grouped = [];
    foreach (TIROL_RANKS as $abbr => $info) {
        $category = $info[1];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][$abbr] = $info[0];
    }
    return $grouped;
}
