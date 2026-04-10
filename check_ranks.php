<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
// Check columns first
$cols = $db->query("PRAGMA table_info(members)")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns: ";
foreach ($cols as $c) echo $c['name'] . ', ';
echo "\n\n";

$stmt = $db->query("SELECT DISTINCT rank FROM members WHERE rank IS NOT NULL AND rank != '' ORDER BY rank");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['rank'] . PHP_EOL;
}
