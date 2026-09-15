<?php
/**
 * Datenbank-Migrationen
 *
 * Jede Migration hat eine eindeutige, unveränderliche ID und eine run()-Funktion.
 * Migrationen werden bei jedem Request automatisch geprüft (siehe runMigrations()
 * in database.php) und genau einmal ausgeführt. Neue Änderungen einfach als neue
 * Migration mit neuer eindeutiger ID unten anhängen; niemals bestehende Einträge
 * nachträglich ändern.
 */

function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function addColumnIfMissing(PDO $db, string $table, string $column, string $mysqlType, string $sqliteType = ''): void {
    if (columnExists($db, $table, $column)) return;
    $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $mysqlType");
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

                $existsBySlug = $db->prepare("SELECT COUNT(*) FROM reports WHERE source_slug = ?");
                // Falls diese Berichte schon zuvor per manuellem SQL-Import (ohne source_slug)
                // eingespielt wurden: passende Zeile anhand Titel+Datum+Inhalt finden und nur
                // den source_slug nachtragen, statt einen Duplikat-Bericht anzulegen. Ein
                // SELECT ... LIMIT 1 vor dem UPDATE verhindert, dass bei mehreren gleich
                // lautenden Alt-Zeilen (z.B. zwei leere "Umgestürzter Bauzaun"-Berichte) aus
                // Versehen dieselbe Zeile zweimal oder ein Massen-UPDATE mehrere Zeilen trifft.
                $findUnlinked = $db->prepare("SELECT id FROM reports WHERE title = ? AND date = ? AND content = ? AND source_slug IS NULL ORDER BY id LIMIT 1");
                $linkSlug = $db->prepare("UPDATE reports SET source_slug = ? WHERE id = ?");
                $insertReport = $db->prepare("INSERT INTO reports (title, category, subcategory, content, date, author, published, source_slug) VALUES (?,?,?,?,?,?,?,?)");
                $insertImg = $db->prepare("INSERT INTO report_images (report_id, filename, caption, sort_order) VALUES (?,?,?,?)");
                $countImages = $db->prepare("SELECT COUNT(*) FROM report_images WHERE report_id = ?");

                foreach ($entries as $e) {
                    $existsBySlug->execute([$e['source_slug']]);
                    if ($existsBySlug->fetchColumn() > 0) continue;

                    $findUnlinked->execute([$e['title'], $e['date'], $e['content']]);
                    $unlinkedId = $findUnlinked->fetchColumn();

                    if ($unlinkedId) {
                        $linkSlug->execute([$e['source_slug'], $unlinkedId]);
                        // Bilder nur nachtragen, falls noch keine für diesen Bericht vorhanden sind
                        $countImages->execute([$unlinkedId]);
                        if ($countImages->fetchColumn() > 0) continue;
                        $reportId = $unlinkedId;
                    } else {
                        $insertReport->execute([
                            $e['title'], $e['category'], $e['subcategory'] ?? null,
                            $e['content'], $e['date'], $e['author'] ?? 'FF Reichenau', $e['published'] ?? 1,
                            $e['source_slug'],
                        ]);
                        $reportId = $db->lastInsertId();
                    }

                    foreach ($e['images'] as $img) {
                        $insertImg->execute([$reportId, $img['filename'], $img['caption'] ?? '', $img['sort_order'] ?? 0]);
                    }
                }
            },
        ],

        [
            'id' => '2026_09_14_remove_landes_stab_ranks',
            'run' => function (PDO $db) {
                // Landes-/Stabsränge werden für eine einzelne Ortsfeuerwehr nicht benötigt.
                $toRemove = ['LFARZT', 'LFKUR', 'LBD-STV', 'LBD', 'FARZT', 'FKUR'];
                $clearMembers = $db->prepare("UPDATE members SET rank = '' WHERE rank = ?");
                $deleteRank = $db->prepare("DELETE FROM ranks WHERE abbr = ?");
                foreach ($toRemove as $abbr) {
                    $clearMembers->execute([$abbr]);
                    $deleteRank->execute([$abbr]);
                }
            },
        ],

        [
            'id' => '2026_09_15_update_ranks_from_orgchart',
            'run' => function (PDO $db) {
                // Dienstgrade laut Organigramm angeglichen (Plank & Danner bewusst ausgenommen).
                $updates = [
                    ['Martin', 'Tiefnig', 'OLM'],
                    ['Marcel', 'Achs', 'OLM'],
                    ['Angela', 'Pelzl', 'LM'],
                    ['Johannes', 'Bauernfeind', 'OLM'],
                    ['Dominik', 'Gasser', 'LM'],
                    ['Fabian', 'Langer', 'LM'],
                    ['Michel', 'Hilweg', 'OLM'],
                    ['Michael', 'Pelzl', 'OLM'],
                ];
                $stmt = $db->prepare("UPDATE members SET rank = ? WHERE firstname = ? AND lastname = ?");
                foreach ($updates as [$first, $last, $rank]) {
                    $stmt->execute([$rank, $first, $last]);
                }
            },
        ],

        [
            'id' => '2026_09_16_add_user_permissions',
            'run' => function (PDO $db) {
                addColumnIfMissing($db, 'users', 'permissions', "TEXT DEFAULT NULL");
                // Bestehende Benutzer (vor Einführung des Rechtesystems) erhalten
                // automatisch Vollzugriff, damit sich niemand selbst aussperrt.
                $db->exec("UPDATE users SET permissions = '[\"*\"]' WHERE permissions IS NULL OR permissions = ''");
            },
        ],

        [
            'id' => '2026_09_16_create_org_chart',
            'run' => function (PDO $db) {
                $db->exec("CREATE TABLE IF NOT EXISTS org_chart_positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    position_key VARCHAR(50) UNIQUE NOT NULL,
                    label VARCHAR(100) NOT NULL,
                    name VARCHAR(150) DEFAULT '',
                    sort_order INT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Struktur des Organigramms (Positionen + Layout) ist fest im Code
                // verankert (siehe pages/ueber-uns.php) - hier werden nur einmalig
                // die aktuellen Namen je Position vorbefüllt. Danach ausschließlich
                // über Admin -> Organigramm pflegen, diese Migration überschreibt
                // spätere Änderungen nicht mehr.
                $positions = [
                    ['kommandant', 'Kommandant', 'David Danner', 1],
                    ['kassier', 'Kassier', 'Martin Rainalter', 2],
                    ['kommandant_stv', 'Kommandant Stv.', 'Helmut Plank', 3],
                    ['schriftfuehrer', 'Schriftführerin', 'Nina Rippl', 4],
                    ['feuerwehrkurat', 'Feuerwehrkurat', 'Paul Kneussl', 5],
                    ['obermaschinist', 'Obermaschinist', 'Martin Tiefnig', 6],
                    ['geraetewart', 'Gerätewart', 'Michael Pelzl', 7],
                    ['zugskommandant', 'Zugskommandant', '', 8],
                    ['jugendbetreuer', 'Jugendbetreuerin', 'Angela Pelzl', 9],
                    ['funkbeauftragter', 'Funkbeauftragter', 'Martin Rainalter', 10],
                    ['atemschutzbeauftragter', 'Atemschutzbeauftragter', 'Marcel Achs', 11],
                    ['gruppenkdt_1', 'Gruppenkommandant', 'Martin Tiefnig', 12],
                    ['gruppenkdt_2', 'Gruppenkommandant', 'Harald Glenda', 13],
                    ['gruppenkdt_3', 'Gruppenkommandant', 'J. Bauernfeind', 14],
                    ['gruppenkdt_4', 'Gruppenkommandant', 'Matthias Stauder', 15],
                    ['gruppenkdt_5', 'Gruppenkommandant', 'Dominik Gasser', 16],
                    ['gruppenkdt_stv_1', 'Gruppenkommandant-Stv.', 'Marcel Achs', 17],
                    ['gruppenkdt_stv_2', 'Gruppenkommandant-Stv.', 'Fabian Langer', 18],
                    ['gruppenkdt_stv_3', 'Gruppenkommandant-Stv.', 'Angela Pelzl', 19],
                    ['gruppenkdt_stv_4', 'Gruppenkommandant-Stv.', 'Michel Hilweg', 20],
                    ['gruppenkdt_stv_5', 'Gruppenkommandant-Stv.', 'Martin Rainalter', 21],
                ];

                $check = $db->prepare("SELECT COUNT(*) FROM org_chart_positions WHERE position_key = ?");
                $insert = $db->prepare("INSERT INTO org_chart_positions (position_key, label, name, sort_order) VALUES (?,?,?,?)");
                foreach ($positions as [$key, $label, $name, $order]) {
                    $check->execute([$key]);
                    if ($check->fetchColumn() > 0) continue;
                    $insert->execute([$key, $label, $name, $order]);
                }
            },
        ],

    ];
}

/**
 * Führt alle noch nicht angewendeten Migrationen aus.
 */
function runMigrations(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS migrations (
        id VARCHAR(191) PRIMARY KEY,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
