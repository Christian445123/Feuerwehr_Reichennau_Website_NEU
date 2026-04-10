<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$rows = $db->query("SELECT id, firstname, lastname, function, group_name, extra_groups FROM members WHERE active = 1 ORDER BY sort_order")->fetchAll();
foreach ($rows as $r) {
    echo "#{$r['id']} {$r['firstname']} {$r['lastname']} | func={$r['function']} | group={$r['group_name']} | extra={$r['extra_groups']}\n";
}
