<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ranks.php';
require_once __DIR__ . '/../config/members.php';
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

// Namen im Organigramm werden gegen die Mitgliederliste gematcht, um beim
// Klick dieselben eingegebenen Mitglieder-Details wie bei den Kacheln zu
// öffnen, ohne Daten doppelt pflegen zu müssen.
$memberIdByName = [];
$rankLookupStmt = $db->query("SELECT id, firstname, lastname FROM members WHERE active = 1");
foreach ($rankLookupStmt->fetchAll() as $mr) {
    $key = mb_strtolower(trim($mr['firstname'] . ' ' . $mr['lastname']));
    $memberIdByName[$key] = (int) $mr['id'];
}

$groupIcons = ['Ausschuss' => 'fa-user-tie'];
$groupDescriptions = [
    'Ausschuss' => 'Der Ausschuss unterstützt das Kommando in verwaltungstechnischen und organisatorischen Belangen.',
];

$membersJson = buildMembersJson($db);
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Ausschuss &amp; Organigramm</h1>
            <p class="page-subtitle">Verwaltung und Struktur der Freiwilligen Feuerwehr Reichenau</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <?php renderUeberUnsGroupCard($db, 'Ausschuss', $groupIcons, $groupDescriptions); ?>

                <?php
                function orgBox2(array $org, array $memberIdByName, string $key, string $label): void {
                    $name = orgName($org, $key);
                    $isVacant = ($name === 'derzeit nicht besetzt');
                    // Namen im Organigramm werden gegen die Mitgliederliste gematcht, damit
                    // ein Klick dieselben (eingegebenen) Details wie bei den Mitglieder-Kacheln
                    // öffnet - keine separate Datenpflege nötig.
                    $memberId = !$isVacant ? ($memberIdByName[mb_strtolower(trim($name))] ?? null) : null;
                    ?>
                    <div class="orgchart-item">
                        <div class="orgchart-item-label"><?php echo htmlspecialchars($label); ?></div>
                        <div class="orgchart-item-name<?php echo $isVacant ? ' orgchart-item-vacant' : ''; ?><?php echo $memberId ? ' orgchart-item-clickable' : ''; ?>"
                             <?php if ($memberId): ?>onclick="showMemberDetail(<?php echo (int) $memberId; ?>)"<?php endif; ?>>
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
                                        <?php orgBox2($org, $memberIdByName, 'kommandant', 'Kommandant'); ?>
                                    </div>
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberIdByName, 'schriftfuehrer', 'Schriftführerin'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'kommandant_stv', 'Kommandant-Stv.'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'kassier', 'Kassier'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span>Gruppen</span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberIdByName, "gruppenkdt_$i", 'Gruppenkommandant'); ?>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberIdByName, "gruppenkdt_stv_$i", 'Gruppenkdt.-Stv'); ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span>Beauftragte</span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'geraetewart', 'Gerätewart'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'obermaschinist', 'Obermaschinist'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'jugendbetreuer', 'Jugendbetreuerin'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'ausbildung', 'Ausbildung'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'atemschutzbeauftragter', 'Atemschutz'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'geraetewart_gehilfe', 'Gerätewart-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'obermaschinist_gehilfe', 'Obermaschinist-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'jugendbetreuer_gehilfe', 'JB-Gehilfe'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'ausbildung_hoehensicherung', 'Ausb. Höhensicherung'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'atemschutz_gehilfe', 'Atemschutz-Gehilfe'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'funkbeauftragter', 'Funk'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'oeffentlichkeitsarbeit', 'Öffentlichkeitsarbeit und EDV'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'nachschub_kantine_1', 'Nachschub / Kantine'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_1', 'Fahne'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'bekleidung', 'Bekleidung'); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberIdByName, 'nachschub_kantine_2', 'Nachschub / Kantine'); ?>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_2', 'Fahne'); ?>
                                        <div class="orgchart-empty"></div>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_3', 'Fahne'); ?>
                                        <div class="orgchart-empty"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/../includes/member-detail-modal.php'; ?>
