<?php
/**
 * Tiroler Feuerwehr Dienstgrade
 * Werden aus der Datenbank geladen und im Admin-Bereich verwaltet.
 */

/**
 * Alle Ränge aus der DB laden (gecacht pro Request)
 */
function getAllRanks(): array {
    static $ranks = null;
    if ($ranks !== null) return $ranks;

    $db = getDB();
    $rows = $db->query("SELECT * FROM ranks WHERE active = 1 ORDER BY sort_order, abbr")->fetchAll();
    $ranks = [];
    foreach ($rows as $r) {
        $ranks[$r['abbr']] = [
            'name' => $r['name'],
            'category' => $r['category'],
            'sort_order' => (int)$r['sort_order'],
            'badge' => $r['badge'],
        ];
    }
    return $ranks;
}

/**
 * Badge-Bildpfad für einen Dienstgrad
 */
function getRankBadgePath(string $rank): string {
    $all = getAllRanks();
    if (isset($all[$rank]) && $all[$rank]['badge']) {
        return $all[$rank]['badge'];
    }
    return 'assets/images/ranks/' . strtolower($rank) . '.png';
}

/**
 * Vollständiger Name eines Dienstgrads
 */
function getRankName(string $rank): string {
    $all = getAllRanks();
    return $all[$rank]['name'] ?? $rank;
}

/**
 * Kategorie eines Dienstgrads
 */
function getRankCategory(string $rank): string {
    $all = getAllRanks();
    return $all[$rank]['category'] ?? 'Sonstige';
}

/**
 * Dienstgrade gruppiert nach Kategorie für Dropdown
 */
function getRanksGrouped(): array {
    $grouped = [];
    foreach (getAllRanks() as $abbr => $info) {
        $category = $info['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][$abbr] = $info['name'];
    }
    return $grouped;
}
