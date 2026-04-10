<?php
require_once __DIR__ . '/database.php';
$db = getDB();
$db->exec('DELETE FROM members');
echo "Members gelöscht.\n";
require __DIR__ . '/seed.php';
