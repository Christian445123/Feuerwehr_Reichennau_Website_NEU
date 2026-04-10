<?php
/**
 * Migration 2: Convert functions to JSON with section assignments.
 * Maps known members to their correct section+role combinations.
 */
require_once __DIR__ . '/config/database.php';
$db = getDB();

// Known mappings: member_id => JSON functions array
$knownFunctions = [
    // Kommando + Ausschuss (both sections)
    47 => [['section' => 'Kommando', 'role' => 'Kommandant'], ['section' => 'Ausschuss', 'role' => 'Stv-Kdt. & Bezirkskommandant']],  // Helmut Plank
    48 => [['section' => 'Kommando', 'role' => 'Kdt.-Stv.'], ['section' => 'Ausschuss', 'role' => 'Kommandant']],  // David Danner
    49 => [['section' => 'Kommando', 'role' => 'Kassier'], ['section' => 'Ausschuss', 'role' => 'Kassier']],  // Martin Rainalter
    50 => [['section' => 'Kommando', 'role' => 'Schriftführerin'], ['section' => 'Ausschuss', 'role' => 'Schriftführerin']],  // Nina Rippl
    
    // Ausschuss only
    55 => [['section' => 'Ausschuss', 'role' => 'Zugskommandant']],  // Matthias Stauder
    56 => [['section' => 'Ausschuss', 'role' => 'Gruppenkommandant']],  // Harald Glenda
    57 => [['section' => 'Ausschuss', 'role' => 'Gerätewart']],  // Martin Tiefnig
    58 => [['section' => 'Ausschuss', 'role' => 'Atemschutzwart']],  // Michael Pelzl
    59 => [['section' => 'Ausschuss', 'role' => 'Funkwart']],  // Johannes Bauernfeind
    60 => [['section' => 'Ausschuss', 'role' => 'Jugendbetreuerin']],  // Angela Pelzl
    61 => [['section' => 'Ausschuss', 'role' => 'Zeugwart']],  // Dominik Gasser
    
    // Ehrenmitglieder with special function
    92 => [['section' => 'Ehrenmitglieder', 'role' => 'Ehrenkommandant']],  // Harald Fröhlich
    94 => [['section' => 'Ehrenmitglieder', 'role' => 'Ehrenkommandant']],  // Armin Praxmarer
];

$stmt = $db->prepare("UPDATE members SET functions = ? WHERE id = ?");

foreach ($knownFunctions as $id => $funcs) {
    $json = json_encode($funcs, JSON_UNESCAPED_UNICODE);
    $stmt->execute([$json, $id]);
    
    // Get name for output
    $nameStmt = $db->prepare("SELECT firstname, lastname FROM members WHERE id = ?");
    $nameStmt->execute([$id]);
    $name = $nameStmt->fetch();
    if ($name) {
        $roles = array_map(fn($f) => $f['section'] . ': ' . $f['role'], $funcs);
        echo "#{$id} {$name['firstname']} {$name['lastname']} -> " . implode(' | ', $roles) . "\n";
    }
}

// Set empty JSON array for members without functions
$db->exec("UPDATE members SET functions = '[]' WHERE functions = '' OR functions IS NULL");

// Clear old fields
$db->exec("UPDATE members SET extra_groups = ''");

echo "\nDone. All members now have JSON functions.\n";

// Verify
$all = $db->query("SELECT id, firstname, lastname, rank, group_name, functions FROM members WHERE active = 1 ORDER BY id")->fetchAll();
echo "\n=== VERIFICATION ===\n";
foreach ($all as $m) {
    $funcs = json_decode($m['functions'], true) ?: [];
    $funcStr = empty($funcs) ? '(keine)' : implode(', ', array_map(fn($f) => $f['section'] . ':' . $f['role'], $funcs));
    echo "#{$m['id']} {$m['firstname']} {$m['lastname']} [{$m['group_name']}] -> {$funcStr}\n";
}
