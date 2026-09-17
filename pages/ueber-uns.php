<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ranks.php';
require_once __DIR__ . '/../config/badges.php';
$db = getDB();

// Organigramm-Namen (Struktur ist fest unten im Template, nur die Namen sind
// über Admin -> Organigramm pflegbar)
$orgRows = $db->query("SELECT position_key, name FROM org_chart_positions")->fetchAll();
$org = [];
foreach ($orgRows as $r) { $org[$r['position_key']] = $r['name']; }
function orgName(array $org, string $key): string {
    $name = trim($org[$key] ?? '');
    return $name !== '' ? $name : 'derzeit nicht besetzt';
}

// Rang-Icons fürs Organigramm: Namen im Organigramm werden gegen die
// Mitgliederliste gematcht, um automatisch das passende Dienstgrad-Abzeichen
// anzuzeigen (wie im Vorbild-Organigramm), ohne Ränge doppelt pflegen zu müssen.
$memberRankByName = [];
$rankLookupStmt = $db->query("SELECT firstname, lastname, rank FROM members WHERE rank IS NOT NULL AND rank != ''");
foreach ($rankLookupStmt->fetchAll() as $mr) {
    $key = mb_strtolower(trim($mr['firstname'] . ' ' . $mr['lastname']));
    $memberRankByName[$key] = $mr['rank'];
}
function orgRankBadge(array $memberRankByName, string $name): ?string {
    $key = mb_strtolower(trim($name));
    $rank = $memberRankByName[$key] ?? null;
    if (!$rank || !isset(getAllRanks()[$rank])) return null;
    return getRankBadgePath($rank);
}

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

// Collect all members for modal JSON
$allMembersStmt = $db->query("SELECT id, firstname, lastname, rank, functions, badge1, badge2, group_name, photo, entry_date, phone, email, bio FROM members WHERE active = 1");
$allMembers = $allMembersStmt->fetchAll();
$membersJson = [];
foreach ($allMembers as $am) {
    $funcs = json_decode($am['functions'] ?? '[]', true) ?: [];
    $funcLabels = array_map(fn($f) => $f['role'] . ' (' . $f['section'] . ')', $funcs);
    $badgeList = [];
    foreach ([$am['badge1'] ?? null, $am['badge2'] ?? null] as $bc) {
        if ($bc) $badgeList[] = ['code' => $bc, 'name' => getBadgeName($bc), 'color' => getBadgeColor($bc), 'image' => getBadgeImage($bc)];
    }
    $membersJson[$am['id']] = [
        'name' => $am['firstname'] . ' ' . $am['lastname'],
        'photo' => $am['photo'] ? 'uploads/' . $am['photo'] : '',
        'rank' => $am['rank'],
        'rankName' => $am['rank'] ? getRankName($am['rank']) : '',
        'rankBadge' => $am['rank'] ? getRankBadgePath($am['rank']) : '',
        'badges' => $badgeList,
        'functions' => $funcLabels,
        'group' => $am['group_name'],
        'entry_date' => $am['entry_date'] ?? '',
        'phone' => $am['phone'] ?? '',
        'email' => $am['email'] ?? '',
        'bio' => $am['bio'] ?? '',
    ];
}
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

                <?php
                function renderUeberUnsGroupCard(PDO $db, string $group, array $groupIcons, array $groupDescriptions): void {
                    // Kommando/Ausschuss: members who have this section in their JSON functions
                    // Mannschaft/Ehrenmitglieder: members with this as group_name
                    if (in_array($group, ['Kommando', 'Ausschuss'])) {
                        $pattern = '%"section":"' . $group . '"%';
                        $stmt = $db->prepare("SELECT * FROM members WHERE active = 1 AND functions LIKE ? ORDER BY sort_order, lastname");
                        $stmt->execute([$pattern]);
                    } else {
                        $stmt = $db->prepare("SELECT * FROM members WHERE group_name = ? AND active = 1 ORDER BY sort_order, lastname");
                        $stmt->execute([$group]);
                    }
                    $groupMembers = $stmt->fetchAll();
                    ?>
                    <div class="content-card" id="<?php echo strtolower(str_replace(' ', '', $group)); ?>">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas <?php echo $groupIcons[$group] ?? 'fa-users'; ?>"></i></div>
                            <h2><?php echo htmlspecialchars($group); ?> <span class="member-count"><?php echo count($groupMembers); ?></span></h2>
                        </div>
                        <div class="content-card-body">
                            <p><?php echo htmlspecialchars($groupDescriptions[$group] ?? ''); ?></p>

                            <?php if ($group === 'Ausschuss'): ?>
                                <img src="assets/images/ausschuss_gruppenbild.jpg" alt="Der Ausschuss der FF Reichenau" class="content-image ausschuss-gruppenbild" data-lightbox-group="ausschuss-gruppenbild">
                            <?php endif; ?>

                            <?php if (!empty($groupMembers)): ?>
                                <?php $gridClass = ($group === 'Kommando') ? 'grid-kommando' : ''; ?>
                                <div class="members-public-grid <?php echo $gridClass; ?>">
                                    <?php foreach ($groupMembers as $m): ?>
                                        <?php
                                        // Get the role for this specific section
                                        $funcs = json_decode($m['functions'] ?? '[]', true) ?: [];
                                        $roleInSection = '';
                                        foreach ($funcs as $f) {
                                            if (($f['section'] ?? '') === $group) {
                                                $roleInSection = $f['role'] ?? '';
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="member-public-card" data-member-id="<?php echo (int)$m['id']; ?>" onclick="showMemberDetail(<?php echo (int)$m['id']; ?>)">
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
                                                <?php if ($roleInSection): ?>
                                                    <span class="member-public-function"><?php echo htmlspecialchars($roleInSection); ?></span>
                                                <?php endif; ?>
                                                <?php if ($m['rank']): ?>
                                                    <span class="member-public-rank">
                                                        <img src="<?php echo htmlspecialchars(getRankBadgePath($m['rank'])); ?>"
                                                             alt="<?php echo htmlspecialchars(getRankName($m['rank'])); ?>"
                                                             class="rank-badge-inline"
                                                             title="<?php echo htmlspecialchars($m['rank'] . ' – ' . getRankName($m['rank'])); ?>">
                                                    </span>
                                                <?php endif; ?>
                                                <?php foreach ([$m['badge1'] ?? null, $m['badge2'] ?? null] as $bc): ?>
                                                    <?php if ($bc): ?>
                                                        <img src="<?php echo htmlspecialchars(getBadgeImage($bc)); ?>" alt="<?php echo htmlspecialchars($bc); ?>" class="badge-inline-img" title="<?php echo htmlspecialchars(getBadgeName($bc)); ?>">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted-public"><em>Mitglieder werden in Kürze ergänzt.</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }

                foreach (['Kommando', 'Ausschuss'] as $group) {
                    renderUeberUnsGroupCard($db, $group, $groupIcons, $groupDescriptions);
                }
                ?>

                <!-- Organigramm (direkt unter dem Ausschuss platziert) -->
                <?php
                function orgBox2(array $org, array $memberRankByName, string $key, string $label): void {
                    $name = orgName($org, $key);
                    $isVacant = ($name === 'derzeit nicht besetzt');
                    $badge = !$isVacant ? orgRankBadge($memberRankByName, $name) : null;
                    ?>
                    <div class="orgchart-item">
                        <div class="orgchart-item-label"><?php echo htmlspecialchars($label); ?></div>
                        <div class="orgchart-item-name<?php echo $isVacant ? ' orgchart-item-vacant' : ''; ?>">
                            <?php if ($badge): ?>
                                <img src="<?php echo htmlspecialchars($badge); ?>" alt="" class="orgchart-rank-badge">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($name); ?></span>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="content-card" id="organigramm">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-sitemap"></i></div>
                        <h2>Organigramm</h2>
                    </div>
                    <div class="content-card-body">
                        <div class="orgchart">

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span>Kommando</span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberRankByName, 'kommandant', 'Kommandant'); ?>
                                    </div>
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberRankByName, 'schriftfuehrer', 'Schriftführerin'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'kommandant_stv', 'Kommandant-Stv.'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'kassier', 'Kassier'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span>Gruppen</span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberRankByName, "gruppenkdt_$i", 'Gruppenkommandant'); ?>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberRankByName, "gruppenkdt_stv_$i", 'Gruppenkdt.-Stv'); ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span>Beauftragte</span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberRankByName, 'geraetewart', 'Gerätewart'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'obermaschinist', 'Obermaschinist'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'jugendbetreuer', 'Jugendbetreuerin'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'ausbildung', 'Ausbildung'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'atemschutzbeauftragter', 'Atemschutz'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberRankByName, 'geraetewart_gehilfe', 'Gerätewart-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'obermaschinist_gehilfe', 'Obermaschinist-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'jugendbetreuer_gehilfe', 'JB-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'ausbildung_hoehensicherung', 'Ausb. Höhensicherung'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'atemschutz_gehilfe', 'Atemschutz-Gehilfe'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberRankByName, 'funkbeauftragter', 'Funk'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'oeffentlichkeitsarbeit', 'Öffentlichkeitsarbeit und EDV'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'nachschub_kantine_1', 'Nachschub / Kantine'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'fahne_1', 'Fahne'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'bekleidung', 'Bekleidung'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberRankByName, 'nachschub_kantine_2', 'Nachschub / Kantine'); ?>
                                        <?php orgBox2($org, $memberRankByName, 'fahne_2', 'Fahne'); ?>
                                        <div class="orgchart-empty"></div>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberRankByName, 'fahne_3', 'Fahne'); ?>
                                        <div class="orgchart-empty"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <?php
                foreach (['Mannschaft', 'Ehrenmitglieder'] as $group) {
                    renderUeberUnsGroupCard($db, $group, $groupIcons, $groupDescriptions);
                }
                ?>

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
                                    <div class="member-public-card" data-member-id="<?php echo (int)$m['id']; ?>" onclick="showMemberDetail(<?php echo (int)$m['id']; ?>)">
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
                            <img src="assets/images/geschichte1.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
                            <img src="assets/images/geschichte2.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
                            <img src="assets/images/geschichte3.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
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
                            <img src="assets/images/schutzgebiet_karte.jpg" alt="Karte Schutzgebiet" class="content-image" data-lightbox-group="schutzbereich">
                            <img src="assets/images/schutzgebiet.jpg" alt="Schutzgebiet der FF Reichenau" class="content-image" data-lightbox-group="schutzbereich">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

<!-- Member Detail Modal -->
<div class="member-modal-overlay" id="memberModal">
    <div class="member-modal">
        <button class="member-modal-close" onclick="closeMemberModal()" title="Schließen">&times;</button>
        <div class="member-modal-header">
            <div id="modalPhotoWrap"></div>
            <div class="member-modal-name" id="modalName"></div>
            <div class="member-modal-function" id="modalFunction"></div>
            <div class="member-modal-rank" id="modalRank" style="display:none;">
                <img id="modalRankBadge" src="" alt="">
                <span id="modalRankText"></span>
            </div>
            <div class="member-modal-badges" id="modalBadges"></div>
        </div>
        <div class="member-modal-body" id="modalBody"></div>
    </div>
</div>

<script>
var memberData = <?php echo json_encode($membersJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

function showMemberDetail(id) {
    var m = memberData[id];
    if (!m) return;

    // Photo
    var photoWrap = document.getElementById('modalPhotoWrap');
    if (m.photo) {
        photoWrap.innerHTML = '<img src="' + m.photo + '" alt="' + m.name + '" class="member-modal-photo">';
    } else {
        photoWrap.innerHTML = '<div class="member-modal-placeholder"><i class="fas fa-user"></i></div>';
    }

    // Name & Functions
    document.getElementById('modalName').textContent = m.name;
    var funcEl = document.getElementById('modalFunction');
    if (m.functions && m.functions.length > 0) {
        funcEl.textContent = m.functions.join(' · ');
        funcEl.style.display = '';
    } else {
        funcEl.textContent = '';
        funcEl.style.display = 'none';
    }

    // Rank
    var rankEl = document.getElementById('modalRank');
    if (m.rank) {
        document.getElementById('modalRankBadge').src = m.rankBadge;
        document.getElementById('modalRankText').textContent = m.rank + ' – ' + m.rankName;
        rankEl.style.display = 'inline-flex';
    } else {
        rankEl.style.display = 'none';
    }

    // Verwendungs-/Funktionsabzeichen
    var badgesEl = document.getElementById('modalBadges');
    if (m.badges && m.badges.length > 0) {
        badgesEl.innerHTML = m.badges.map(function(b) {
            return '<img class="badge-inline-img badge-modal-img" src="' + b.image + '" alt="' + escHtml(b.code) + '" title="' + escHtml(b.name) + '">';
        }).join('');
        badgesEl.style.display = 'flex';
    } else {
        badgesEl.innerHTML = '';
        badgesEl.style.display = 'none';
    }

    // Body details
    var body = document.getElementById('modalBody');
    var html = '';
    var hasDetails = m.group || m.entry_date || m.phone || m.email || m.bio || (m.functions && m.functions.length > 0);

    if (hasDetails) {
        html += '<ul class="member-modal-details">';
        if (m.group) {
            html += '<li><i class="fas fa-users"></i> ' + escHtml(m.group) + '</li>';
        }
        if (m.functions && m.functions.length > 0) {
            html += '<li><i class="fas fa-briefcase"></i> ' + m.functions.map(escHtml).join(', ') + '</li>';
        }
        if (m.entry_date) {
            html += '<li><i class="fas fa-calendar-alt"></i> Eintritt: ' + escHtml(m.entry_date) + '</li>';
        }
        if (m.phone) {
            html += '<li><i class="fas fa-phone"></i> <a href="tel:' + escHtml(m.phone) + '">' + escHtml(m.phone) + '</a></li>';
        }
        if (m.email) {
            html += '<li><i class="fas fa-envelope"></i> <a href="mailto:' + escHtml(m.email) + '">' + escHtml(m.email) + '</a></li>';
        }
        html += '</ul>';
        if (m.bio) {
            html += '<div class="member-modal-bio">' + escHtml(m.bio) + '</div>';
        }
    } else {
        html = '<div class="member-modal-empty"><i class="fas fa-info-circle"></i> Keine weiteren Details hinterlegt.</div>';
    }

    body.innerHTML = html;

    document.getElementById('memberModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMemberModal() {
    document.getElementById('memberModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close on overlay click
document.getElementById('memberModal').addEventListener('click', function(e) {
    if (e.target === this) closeMemberModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMemberModal();
});

function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
