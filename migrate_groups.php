<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

// Add extra_groups column
try {
    $db->exec("ALTER TABLE members ADD COLUMN extra_groups TEXT DEFAULT ''");
    echo "Added column: extra_groups\n";
} catch (Exception $e) {
    echo "Column exists or error: " . $e->getMessage() . "\n";
}

// Migrate: members currently in Kommando/Ausschuss get that as extra_group, primary becomes Mannschaft
$stmt = $db->query("SELECT id, group_name FROM members WHERE group_name IN ('Kommando', 'Ausschuss')");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    $db->prepare("UPDATE members SET extra_groups = ?, group_name = 'Mannschaft' WHERE id = ?")
       ->execute([$row['group_name'], $row['id']]);
    echo "Migrated member #{$row['id']}: group_name '{$row['group_name']}' -> extra_groups, primary=Mannschaft\n";
}

echo "\nDone!\n";
