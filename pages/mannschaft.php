<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/members.php';
$db = getDB();

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

$membersJson = buildMembersJson($db);
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Mannschaft</h1>
            <p class="page-subtitle">Die Menschen hinter der Freiwilligen Feuerwehr Reichenau</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <?php
                foreach (['Kommando', 'Mannschaft', 'Ehrenmitglieder'] as $group) {
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
                            <div class="members-public-grid grid-static">
                                <?php foreach ($jugendMembers as $m): ?>
                                    <div class="member-public-card" data-member-id="<?php echo (int)$m['id']; ?>">
                                        <div class="member-card-flip">
                                            <div class="member-card-face member-card-front">
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
                                            <?php $hasJugendBackDetails = !empty($m['entry_date']) || !empty($m['bio']); ?>
                                            <div class="member-card-face member-card-back">
                                                <?php if ($hasJugendBackDetails): ?>
                                                    <strong class="member-card-back-name"><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                                    <span class="member-card-back-line"><i class="fas fa-briefcase"></i> Jugend</span>
                                                    <?php if (!empty($m['entry_date'])): ?>
                                                        <span class="member-card-back-line"><i class="fas fa-calendar-alt"></i> Seit <?php echo htmlspecialchars($m['entry_date']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($m['bio'])): ?>
                                                        <p class="member-card-back-bio"><?php echo htmlspecialchars(mb_substr($m['bio'], 0, 90)) . (mb_strlen($m['bio']) > 90 ? '…' : ''); ?></p>
                                                    <?php endif; ?>
                                                    <span class="member-card-back-more"><i class="fas fa-circle-info"></i> Für mehr Details klicken</span>
                                                <?php else: ?>
                                                    <div class="member-card-back-emptystate">
                                                        <i class="fas fa-fire-extinguisher"></i>
                                                        <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                                        <span>Aktives Mitglied der<br>Jugendfeuerwehr</span>
                                                    </div>
                                                    <span class="member-card-back-more"><i class="fas fa-circle-info"></i> Für mehr Details klicken</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted-public"><em>Mitglieder werden in Kürze ergänzt.</em></p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/../includes/member-detail-modal.php'; ?>
