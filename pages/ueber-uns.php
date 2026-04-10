<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ranks.php';
$db = getDB();

$groups = ['Kommando', 'Ausschuss', 'Mannschaft', 'Ehrenmitglieder'];
$groupIcons = [
    'Kommando' => 'fa-star', 'Ausschuss' => 'fa-user-tie',
    'Mannschaft' => 'fa-hard-hat', 'Ehrenmitglieder' => 'fa-medal'
];
$groupDescriptions = [
    'Kommando' => 'Das Kommando der Freiwilligen Feuerwehr Reichenau bildet die Führungsebene unserer Wehr.',
    'Ausschuss' => 'Der Ausschuss unterstützt das Kommando in verwaltungstechnischen und organisatorischen Belangen.',
    'Mannschaft' => 'Unsere aktive Einsatzmannschaft besteht aus engagierten Frauen und Männern, die sich freiwillig für die Sicherheit der Bevölkerung einsetzen.',
    'Ehrenmitglieder' => 'Unsere Ehrenmitglieder haben sich über viele Jahre hinweg besonders um die Freiwillige Feuerwehr Reichenau verdient gemacht.'
];
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Über Uns</h1>
            <p class="page-subtitle">Die Freiwillige Feuerwehr Reichenau stellt sich vor</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <?php foreach ($groups as $group): ?>
                <?php
                $stmt = $db->prepare("SELECT * FROM members WHERE group_name = ? AND active = 1 ORDER BY sort_order, lastname");
                $stmt->execute([$group]);
                $groupMembers = $stmt->fetchAll();
                ?>
                <div class="content-card" id="<?php echo strtolower(str_replace(' ', '', $group)); ?>">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas <?php echo $groupIcons[$group] ?? 'fa-users'; ?>"></i></div>
                        <h2><?php echo htmlspecialchars($group); ?> <span class="member-count"><?php echo count($groupMembers); ?></span></h2>
                    </div>
                    <div class="content-card-body">
                        <p><?php echo htmlspecialchars($groupDescriptions[$group] ?? ''); ?></p>

                        <?php if (!empty($groupMembers)): ?>
                            <?php $gridClass = ($group === 'Kommando') ? 'grid-kommando' : ''; ?>
                            <div class="members-public-grid <?php echo $gridClass; ?>">
                                <?php foreach ($groupMembers as $m): ?>
                                    <div class="member-public-card">
                                        <?php if ($m['photo']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($m['photo']); ?>"
                                                 alt="<?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?>"
                                                 class="member-public-photo">
                                        <?php else: ?>
                                            <div class="member-public-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="member-public-info">
                                            <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                            <?php if ($m['function']): ?>
                                                <span class="member-public-function"><?php echo htmlspecialchars($m['function']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($m['rank']): ?>
                                                <span class="member-public-rank">
                                                    <img src="<?php echo htmlspecialchars(getRankBadgePath($m['rank'])); ?>"
                                                         alt="<?php echo htmlspecialchars(getRankName($m['rank'])); ?>"
                                                         class="rank-badge-inline"
                                                         title="<?php echo htmlspecialchars($m['rank'] . ' – ' . getRankName($m['rank'])); ?>">
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted-public"><em>Mitglieder werden in Kürze ergänzt.</em></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Jugend -->
                <div class="content-card" id="jugend">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-child"></i></div>
                        <h2>Jugend</h2>
                    </div>
                    <div class="content-card-body">
                        <p>Die Jugendfeuerwehr der FF Reichenau bildet unseren Nachwuchs aus.</p>
                        <?php
                        $jugendStmt = $db->prepare("SELECT * FROM members WHERE group_name = 'Jugend' AND active = 1 ORDER BY sort_order, lastname");
                        $jugendStmt->execute();
                        $jugendMembers = $jugendStmt->fetchAll();
                        ?>
                        <?php if (!empty($jugendMembers)): ?>
                            <div class="members-public-grid">
                                <?php foreach ($jugendMembers as $m): ?>
                                    <div class="member-public-card">
                                        <?php if ($m['photo']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($m['photo']); ?>"
                                                 alt="<?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?>"
                                                 class="member-public-photo">
                                        <?php else: ?>
                                            <div class="member-public-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="member-public-info">
                                            <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted-public"><em>Mitglieder werden in Kürze ergänzt.</em></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Geschichte -->
                <div class="content-card" id="geschichte">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-landmark"></i></div>
                        <h2>Geschichte</h2>
                    </div>
                    <div class="content-card-body">
                        <p>In den 70er Jahren wurden so genannte Hilfestationsgruppen (HISTA) für den Katastrophenschutz in den Stadtteilen aufgebaut. Auch in der Reichenau wurde eine HISTA Gruppe mit einer Handvoll Männer installiert. Es handelte sich dabei um die Station „HISTA 5".</p>
                        <p>Im Laufe der Zeit kam die Idee daraus eine Freiwillige Feuerwehr zu gründen und so wurde im März 1982 die Löschgruppe Reichenau unter der Führung der FF Mühlau, Kdt. Anton Unteregger gegründet. Am 21.09.1984 war es dann so weit und die Löschgruppe Reichenau wurde in die eigene Freiwillige Feuerwehr Reichenau umgewandelt. Die 10. Freiwillige Feuerwehr in Innsbruck und somit die jüngste Einheit war geboren.</p>
                        <p>Das Schutzgebiet erstreckt sich auf die Reichenau, Pradl, Teile des Pradler Saggen und des Gewerbegebietes Rossau. Der erste Kommandant war Harald Fröhlich und bei der Wache handelte es sich dabei um ein Flugdach im städtischen Zentralbauhof in der Rossau. Der erste Fuhrpark bestand, aus einem Tanklöschfahrzeug auf Mercedes-Klöckner aus dem Jahre 1936, einem alten VW-Bus und einem Land Rover.</p>
                        <p>Im Jahre 1986 wurde die erste Etappe für den Bau einer Feuerwache in Angriff genommen. Der Umkleide- und Kameradschaftsraum befand sich zu dieser Zeit im Keller des Hauptgebäudes im Zentralbauhof und somit ca. 100 Meter von der Fahrzeughalle entfernt.</p>
                        <p>Im Jahre 1988 übernahm Armin Praxmarer das Kommando der Einheit, welches er bis auf eine Unterbrechung von 4 Jahren (Kommandant Werner Federspiel) inne hatte. Im selben Jahr wurde von der FF Inzing ein Kleinlöschfahrzeug durch die Stadt Innsbruck und zur Gänze aus Eigenmitteln ein gebrauchter Ford Transit angekauft und in Eigenregie, in vielen Arbeitsstunden zu einem Feuerwehrfahrzeug (LLF) umgebaut. 1990 wurde das alte TLF durch ein gebrauchtes TLF von der FF Wilten ausgetauscht. Die Wache musste daraufhin aufwendig adaptiert werden, wobei die gesamten Arbeiten von den Kameraden der Einheit durchgeführt wurden.</p>
                        <p>Im Jahre 1993 wurde unter dem damaligen Branddirektor Ing. Thomas Angermair die Spezialisierung zur Gefahrguteinheit in Angriff genommen. Dazu erhielten wir, ein von der BF ausgeschiedenes Feuerwehrfahrzeug Mercedes 508D, welches Gefahrstoffpumpen und eine Dekontaminationsstraße enthielt.</p>
                        <p>Im Jahre 2003 erhielten wir ein nagelneues Tanklöschfahrzeug auf Scania und einen neuen Schulungsraum in einem Nebengebäude des Zentralbauhofes, wo wiederum einiges durch Eigenleistung finanziert und gearbeitet wurde.</p>
                        <p>Die größten Einsätze in der noch jungen Geschichte der Einheit waren mit Sicherheit die Hochwasser in den 80er Jahren entlang der Sill, die Großbrände Planküchen, Wagnerische Universitätsbuchdruckerei 1989 und Tiroler Loden AG im Jahre 2001, aber auch die Hochwasserunterstützung im Jahr 2002 in Niederösterreich und der Einsatz beim Hochwasser 2005 in Innsbruck und in Wörgl.</p>
                        <p>2008 übergab Armin Praxmarer aus Altersgründen das Kommando an den neu gewählten Kommandanten Helmut Plank. Armin wurde noch im selben Jahr die Ehrenmitgliedschaft verliehen.</p>

                        <div class="geschichte-gallery">
                            <img src="assets/images/geschichte1.jpg" alt="Geschichte der FF Reichenau" class="content-image">
                            <img src="assets/images/geschichte2.jpg" alt="Geschichte der FF Reichenau" class="content-image">
                            <img src="assets/images/geschichte3.jpg" alt="Geschichte der FF Reichenau" class="content-image">
                        </div>
                    </div>
                </div>

                <!-- Schutzbereich -->
                <div class="content-card" id="schutzbereich">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-shield-alt"></i></div>
                        <h2>Schutzbereich</h2>
                    </div>
                    <div class="content-card-body">
                        <h4>Einwohnerzahl im Schutzgebiet der FF Reichenau:</h4>
                        <p>Derzeit sind im Schutzgebiet der FF Reichenau <strong>27.575 Einwohner</strong> mit Hauptwohnsitz und <strong>2.760 Einwohner</strong> mit Nebenwohnsitz gemeldet. Das Schutzgebiet der FF Reichenau umfasst somit ca. <strong>14.000 Haushalte</strong> (Umrechnungsschlüssel: es wird mit 2,2 Personen pro Haushalt gerechnet).</p>
                        <div class="schutzbereich-images">
                            <img src="assets/images/schutzgebiet_karte.jpg" alt="Karte Schutzgebiet" class="content-image">
                            <img src="assets/images/schutzgebiet.jpg" alt="Schutzgebiet der FF Reichenau" class="content-image">
                        </div>
                    </div>
                </div>

                <!-- Organigramm -->
                <div class="content-card" id="organigramm">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-sitemap"></i></div>
                        <h2>Organigramm</h2>
                    </div>
                    <div class="content-card-body">
                        <img src="assets/images/organigramm.jpg" alt="Organigramm der FF Reichenau" class="content-image">
                    </div>
                </div>

            </div>
        </div>
    </section>
