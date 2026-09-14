<?php
/**
 * Datenbank-Migrationen
 *
 * Jede Migration hat eine eindeutige, unveränderliche ID und eine run()-Funktion.
 * Migrationen werden bei jedem Request automatisch geprüft (siehe runMigrations()
 * in database.php) und genau einmal ausgeführt - egal ob lokal (SQLite) oder auf
 * dem Live-Server (MySQL). Neue Änderungen einfach als neue Migration mit neuer
 * eindeutiger ID unten anhängen; niemals bestehende Einträge nachträglich ändern.
 */

function columnExists(PDO $db, string $table, string $column): bool {
    if (DB_DRIVER === 'mysql') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }
    $cols = $db->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if ($c['name'] === $column) return true;
    }
    return false;
}

function addColumnIfMissing(PDO $db, string $table, string $column, string $mysqlType, string $sqliteType): void {
    if (columnExists($db, $table, $column)) return;
    $type = DB_DRIVER === 'mysql' ? $mysqlType : $sqliteType;
    $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $type");
}

function getMigrations(): array {
    return [

        [
            'id' => '2026_09_14_add_functions_column_members',
            'run' => function (PDO $db) {
                addColumnIfMissing($db, 'members', 'functions', 'TEXT DEFAULT NULL', 'TEXT DEFAULT NULL');
            },
        ],

        [
            'id' => '2026_09_14_add_badge_columns_members',
            'run' => function (PDO $db) {
                addColumnIfMissing($db, 'members', 'badge1', 'VARCHAR(20) DEFAULT NULL', 'TEXT DEFAULT NULL');
                addColumnIfMissing($db, 'members', 'badge2', 'VARCHAR(20) DEFAULT NULL', 'TEXT DEFAULT NULL');
            },
        ],

        [
            'id' => '2026_09_14_add_subcategory_column_reports',
            'run' => function (PDO $db) {
                addColumnIfMissing($db, 'reports', 'subcategory', 'VARCHAR(50) DEFAULT NULL', 'TEXT DEFAULT NULL');
            },
        ],

        [
            'id' => '2026_09_14_add_new_ranks',
            'run' => function (PDO $db) {
                $new = [
                    ['FTECH',  'Feuerwehrtechniker',          'Offiziere',         135],
                    ['BVW',    'Bezirksverwalter',            'Höhere Offiziere',  165],
                    ['BFI',    'Bezirksfeuerwehrinspektor',   'Inspektoren',       191],
                    ['LFI',    'Landesfeuerwehrinspektor',    'Inspektoren',       192],
                    ['LFARZT', 'Landesfeuerwehrarzt',         'Landes',            193],
                    ['LFKUR',  'Landesfeuerwehrkurat',        'Landes',            194],
                ];
                $check = $db->prepare("SELECT COUNT(*) FROM ranks WHERE abbr = ?");
                $insert = $db->prepare("INSERT INTO ranks (abbr, name, category, sort_order, badge, active) VALUES (?,?,?,?,?,1)");
                foreach ($new as $r) {
                    $check->execute([$r[0]]);
                    if ($check->fetchColumn() > 0) continue;
                    $badge = 'assets/images/ranks/' . strtolower($r[0]) . '.png';
                    $insert->execute([$r[0], $r[1], $r[2], $r[3], $badge]);
                }
            },
        ],

        [
            'id' => '2026_09_14_fix_danner_plank_roles',
            'run' => function (PDO $db) {
                $updates = [
                    ['David', 'Danner', [
                        ['section' => 'Kommando', 'role' => 'Kommandant'],
                        ['section' => 'Ausschuss', 'role' => 'Kommandant'],
                    ]],
                    ['Helmut', 'Plank', [
                        ['section' => 'Kommando', 'role' => 'Kdt.-Stv. & Bezirkskommandant'],
                        ['section' => 'Ausschuss', 'role' => 'Stv-Kdt. & Bezirkskommandant'],
                    ]],
                ];
                $stmt = $db->prepare("UPDATE members SET functions = ? WHERE firstname = ? AND lastname = ?");
                foreach ($updates as [$first, $last, $functions]) {
                    $stmt->execute([json_encode($functions, JSON_UNESCAPED_UNICODE), $first, $last]);
                }
            },
        ],

        [
            'id' => '2026_09_14_assign_kommando_badges',
            'run' => function (PDO $db) {
                $map = [
                    ['Helmut', 'Plank', 'KDT'], ['David', 'Danner', 'KDT'],
                    ['Martin', 'Rainalter', 'KAS'], ['Nina', 'Rippl', 'SCH'],
                    ['Matthias', 'Stauder', 'ZUG'], ['Harald', 'Glenda', 'GRP'],
                    ['Martin', 'Tiefnig', 'GW'], ['Michael', 'Pelzl', 'ATS'],
                    ['Johannes', 'Bauernfeind', 'FUNK'], ['Angela', 'Pelzl', 'JB'],
                ];
                $stmt = $db->prepare("UPDATE members SET badge1 = ? WHERE firstname = ? AND lastname = ? AND (badge1 IS NULL OR badge1 = '')");
                foreach ($map as [$first, $last, $badge]) {
                    $stmt->execute([$badge, $first, $last]);
                }
            },
        ],

        [
            'id' => '2026_09_14_import_archive_reports_2017',
            'run' => function (PDO $db) {
                $jsonPath = __DIR__ . '/../data/migrations/archive_reports_2017.json';
                if (!file_exists($jsonPath)) return;
                $entries = json_decode(file_get_contents($jsonPath), true);
                if (!$entries) return;

                // source_slug (aus der ursprünglichen ffr.at-URL) dient als eindeutiger,
                // stabiler Schlüssel - Titel+Datum reichen nicht, da mehrere Archiv-
                // Einträge (z.B. "Umgestürzter Bauzaun") identischen Titel UND Datum haben.
                addColumnIfMissing($db, 'reports', 'source_slug', 'VARCHAR(191) DEFAULT NULL', 'TEXT DEFAULT NULL');

                $exists = $db->prepare("SELECT COUNT(*) FROM reports WHERE source_slug = ?");
                $insertReport = $db->prepare("INSERT INTO reports (title, category, subcategory, content, date, author, published, source_slug) VALUES (?,?,?,?,?,?,?,?)");
                $insertImg = $db->prepare("INSERT INTO report_images (report_id, filename, caption, sort_order) VALUES (?,?,?,?)");

                foreach ($entries as $e) {
                    $exists->execute([$e['source_slug']]);
                    if ($exists->fetchColumn() > 0) continue;

                    $insertReport->execute([
                        $e['title'], $e['category'], $e['subcategory'] ?? null,
                        $e['content'], $e['date'], $e['author'] ?? 'FF Reichenau', $e['published'] ?? 1,
                        $e['source_slug'],
                    ]);
                    $reportId = $db->lastInsertId();

                    foreach ($e['images'] as $img) {
                        $insertImg->execute([$reportId, $img['filename'], $img['caption'] ?? '', $img['sort_order'] ?? 0]);
                    }
                }
            },
        ],

    ];
}

/**
 * Führt alle noch nicht angewendeten Migrationen aus.
 */
function runMigrations(PDO $db): void {
    if (DB_DRIVER === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS migrations (
            id VARCHAR(191) PRIMARY KEY,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS migrations (
            id TEXT PRIMARY KEY,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    $applied = $db->query("SELECT id FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    $appliedSet = array_flip($applied);

    $markApplied = $db->prepare("INSERT INTO migrations (id) VALUES (?)");

    foreach (getMigrations() as $migration) {
        if (isset($appliedSet[$migration['id']])) continue;

        try {
            $migration['run']($db);
            $markApplied->execute([$migration['id']]);
        } catch (Throwable $e) {
            error_log('Migration "' . $migration['id'] . '" fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
