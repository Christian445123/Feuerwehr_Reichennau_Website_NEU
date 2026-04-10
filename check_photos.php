<?php
require 'config/database.php';
$db = getDB();
$stmt = $db->query('SELECT id, firstname, lastname, group_name, photo FROM members WHERE active = 1 ORDER BY group_name, lastname');
$members = $stmt->fetchAll();
$withPhoto = 0; $noPhoto = 0;
foreach ($members as $m) {
    if ($m['photo']) {
        $path = 'uploads/' . $m['photo'];
        $exists = file_exists($path);
        $size = $exists ? filesize($path) : 0;
        if (!$exists || $size < 1000) {
            echo "PROBLEM: {$m['firstname']} {$m['lastname']} ({$m['group_name']}) - photo={$m['photo']} exists=" . ($exists?'yes':'NO') . " size=$size\n";
        }
        $withPhoto++;
    } else {
        $noPhoto++;
        echo "NO PHOTO: {$m['firstname']} {$m['lastname']} ({$m['group_name']})\n";
    }
}
echo "\nTotal: " . count($members) . " members, $withPhoto with photo, $noPhoto without photo\n";
