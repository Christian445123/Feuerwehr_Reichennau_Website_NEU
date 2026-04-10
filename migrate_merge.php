<?php
/**
 * Migration: Merge duplicate members and create functions-based system
 * 
 * Old: separate entries per group with function field + extra_groups
 * New: one entry per person with comma-separated functions field
 */
require_once __DIR__ . '/config/database.php';
$db = getDB();

// 1. Add 'functions' column if not exists
try {
    $db->exec("ALTER TABLE members ADD COLUMN functions TEXT DEFAULT ''");
    echo "Added 'functions' column.\n";
} catch (Exception $e) {
    echo "Column 'functions' already exists.\n";
}

// 2. Gather all members and group by unique person (firstname+lastname)
$all = $db->query("SELECT * FROM members ORDER BY id")->fetchAll();

$people = [];
foreach ($all as $row) {
    $key = strtolower(trim($row['firstname']) . '|' . trim($row['lastname']));
    if (!isset($people[$key])) {
        $people[$key] = [
            'keep_id' => $row['id'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'rank' => $row['rank'],
            'photo' => $row['photo'],
            'sort_order' => $row['sort_order'],
            'active' => $row['active'],
            'group_name' => $row['group_name'],
            'entry_date' => $row['entry_date'] ?? '',
            'phone' => $row['phone'] ?? '',
            'email' => $row['email'] ?? '',
            'bio' => $row['bio'] ?? '',
            'functions' => [],
            'duplicate_ids' => [],
        ];
    } else {
        $people[$key]['duplicate_ids'][] = $row['id'];
        // Prefer the entry that has a photo
        if (!$people[$key]['photo'] && $row['photo']) {
            $people[$key]['photo'] = $row['photo'];
        }
        // Prefer the entry with a rank set
        if (!$people[$key]['rank'] && $row['rank']) {
            $people[$key]['rank'] = $row['rank'];
        }
        // Prefer entry with lowest sort_order
        if ($row['sort_order'] > 0 && ($people[$key]['sort_order'] == 0 || $row['sort_order'] < $people[$key]['sort_order'])) {
            $people[$key]['sort_order'] = $row['sort_order'];
        }
        // Keep details from any entry
        if (!$people[$key]['entry_date'] && ($row['entry_date'] ?? '')) $people[$key]['entry_date'] = $row['entry_date'];
        if (!$people[$key]['phone'] && ($row['phone'] ?? '')) $people[$key]['phone'] = $row['phone'];
        if (!$people[$key]['email'] && ($row['email'] ?? '')) $people[$key]['email'] = $row['email'];
        if (!$people[$key]['bio'] && ($row['bio'] ?? '')) $people[$key]['bio'] = $row['bio'];
        // Group: prefer non-Mannschaft if one is Jugend/Ehrenmitglieder
        if (in_array($row['group_name'], ['Jugend', 'Ehrenmitglieder'])) {
            $people[$key]['group_name'] = $row['group_name'];
        }
    }
    
    // Collect functions from this row
    $func = trim($row['function'] ?? '');
    if ($func && $func !== '' && !in_array($func, ['Ehrenmitglied'])) {
        $people[$key]['functions'][] = $func;
    }
    // Also check extra_groups as potential function indicators
    $extra = trim($row['extra_groups'] ?? '');
    if ($extra) {
        foreach (explode(',', $extra) as $eg) {
            $eg = trim($eg);
            // Don't add generic group names as functions, they're derived
        }
    }
}

// 3. Apply migrations
$deletedCount = 0;
$mergedCount = 0;

foreach ($people as $key => $p) {
    $funcList = array_unique($p['functions']);
    $funcString = implode(',', $funcList);
    
    // Update the kept entry
    $stmt = $db->prepare("UPDATE members SET 
        rank = ?, photo = ?, sort_order = ?, group_name = ?, 
        functions = ?, extra_groups = '',
        entry_date = ?, phone = ?, email = ?, bio = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    $stmt->execute([
        $p['rank'], $p['photo'], $p['sort_order'], $p['group_name'],
        $funcString,
        $p['entry_date'], $p['phone'], $p['email'], $p['bio'],
        $p['keep_id']
    ]);
    
    if (!empty($p['duplicate_ids'])) {
        $mergedCount++;
        echo "MERGED: {$p['firstname']} {$p['lastname']} (keep #{$p['keep_id']}, delete #" . implode(',#', $p['duplicate_ids']) . ") -> functions: $funcString\n";
        
        // Delete duplicates
        foreach ($p['duplicate_ids'] as $delId) {
            $db->prepare("DELETE FROM members WHERE id = ?")->execute([$delId]);
            $deletedCount++;
        }
    } else if ($funcString) {
        echo "UPDATE: {$p['firstname']} {$p['lastname']} (#{$p['keep_id']}) -> functions: $funcString\n";
    }
}

echo "\nMerged $mergedCount people, deleted $deletedCount duplicate entries.\n";

// 4. Verify
$count = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
echo "Total members now: $count\n";
