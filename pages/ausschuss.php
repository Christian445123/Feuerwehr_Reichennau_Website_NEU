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
    return $name !== '' ? $name : t('orgchart.vacant');
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
    'Ausschuss' => t('mannschaft.desc.ausschuss'),
];

$membersJson = buildMembersJson($db);
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('ausschuss.title')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('ausschuss.subtitle')); ?></p>
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
                        <h2><?php echo e(t('ausschuss.organigramm')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <div class="orgchart">

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span><?php echo e(t('orgchart.section.kommando')); ?></span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberIdByName, 'kommandant', t('orgchart.position.kommandant')); ?>
                                    </div>
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberIdByName, 'schriftfuehrer', t('orgchart.position.schriftfuehrer')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'kommandant_stv', t('orgchart.position.kommandant_stv')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'kassier', t('orgchart.position.kassier')); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span><?php echo e(t('orgchart.section.gruppen')); ?></span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row">
                                        <?php orgBox2($org, $memberIdByName, 'zugskommandant', t('orgchart.position.zugskommandant')); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberIdByName, "gruppenkdt_$i", t('orgchart.position.gruppenkommandant')); ?>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php orgBox2($org, $memberIdByName, "gruppenkdt_stv_$i", t('orgchart.position.gruppenkdt_stv')); ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="orgchart-section">
                                <div class="orgchart-section-title"><span><?php echo e(t('orgchart.section.beauftragte')); ?></span></div>
                                <div class="orgchart-section-body">
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'geraetewart', t('orgchart.position.geraetewart')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'obermaschinist', t('orgchart.position.obermaschinist')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'jugendbetreuer', t('orgchart.position.jugendbetreuer')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'ausbildung', t('orgchart.position.ausbildung')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'atemschutzbeauftragter', t('orgchart.position.atemschutz')); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'geraetewart_gehilfe', t('orgchart.position.geraetewart_gehilfe')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'obermaschinist_gehilfe', t('orgchart.position.obermaschinist_gehilfe')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'jugendbetreuer_gehilfe', t('orgchart.position.jb_gehilfe')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'ausbildung_hoehensicherung', t('orgchart.position.ausb_hoehensicherung')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'atemschutz_gehilfe', t('orgchart.position.atemschutz_gehilfe')); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <?php orgBox2($org, $memberIdByName, 'funkbeauftragter', t('orgchart.position.funk')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'oeffentlichkeitsarbeit', t('orgchart.position.oeffentlichkeitsarbeit')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'nachschub_kantine_1', t('orgchart.position.nachschub_kantine')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_1', t('orgchart.position.fahne')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'bekleidung', t('orgchart.position.bekleidung')); ?>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberIdByName, 'nachschub_kantine_2', t('orgchart.position.nachschub_kantine')); ?>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_2', t('orgchart.position.fahne')); ?>
                                        <div class="orgchart-empty"></div>
                                    </div>
                                    <div class="orgchart-row-5">
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <div class="orgchart-empty"></div>
                                        <?php orgBox2($org, $memberIdByName, 'fahne_3', t('orgchart.position.fahne')); ?>
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
