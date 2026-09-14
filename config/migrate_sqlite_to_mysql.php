<?php
/**
 * Einmalige Migration: überträgt alle Daten aus der bisherigen SQLite-Datenbank
 * (data/ffr.db) in die neue MySQL/MariaDB-Datenbank (Zugangsdaten aus .env).
 *
 * Aufruf: php config/migrate_sqlite_to_mysql.php
 */

require_once __DIR__ . '/database.php';

if (DB_DRIVER !== 'mysql') {
    fwrite(STDERR, "DB_DRIVER in .env ist nicht auf 'mysql' gesetzt. Abbruch.\n");
    exit(1);
}

$sqlitePath = __DIR__ . '/../data/ffr.db';
if (!file_exists($sqlitePath)) {
    fwrite(STDERR, "SQLite-Datenbank nicht gefunden: $sqlitePath\n");
    exit(1);
}

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Erstellt/aktualisiert das MySQL-Schema (Tabellen, Standard-Ränge etc.)
$mysql = getDB();
initDatabase();

$tables = ['users', 'ranks', 'members', 'reports', 'report_images', 'site_settings'];

$mysql->exec('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
    $count = (int)$sqlite->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "→ $table: $count Zeile(n) in SQLite gefunden.\n";

    if ($count === 0) {
        continue;
    }

    $mysql->exec("DELETE FROM $table");

    $rows = $sqlite->query("SELECT * FROM $table")->fetchAll();
    $columns = array_keys($rows[0]);
    $columnList = implode(', ', $columns);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $stmt = $mysql->prepare("INSERT INTO $table ($columnList) VALUES ($placeholders)");
    foreach ($rows as $row) {
        $stmt->execute(array_values($row));
    }

    if (in_array('id', $columns, true)) {
        $maxId = (int)$mysql->query("SELECT MAX(id) FROM $table")->fetchColumn();
        $mysql->exec("ALTER TABLE $table AUTO_INCREMENT = " . ($maxId + 1));
    }

    echo "  ✓ $table: " . count($rows) . " Zeile(n) übertragen.\n";
}

$mysql->exec('SET FOREIGN_KEY_CHECKS=1');

echo "\nMigration abgeschlossen.\n";
