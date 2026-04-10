<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT DISTINCT rank, rank_name, rank_badge FROM members WHERE rank IS NOT NULL AND rank != '' ORDER BY rank");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['rank'] . ' | ' . $row['rank_name'] . ' | ' . $row['rank_badge'] . PHP_EOL;
}
