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
    'Kommando' => t('mannschaft.desc.kommando'),
    'Ausschuss' => t('mannschaft.desc.ausschuss'),
    'Mannschaft' => t('mannschaft.desc.mannschaft'),
    'Ehrenmitglieder' => t('mannschaft.desc.ehrenmitglieder'),
];

$membersJson = buildMembersJson($db);
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('nav.mannschaft')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('mannschaft.subtitle')); ?></p>
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
                        <h2><?php echo e(t('nav.jugend')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <p><?php echo e(t('mannschaft.desc.jugend')); ?></p>
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
                                            <?php $jugendBio = getLocalizedBio($m); ?>
                                            <?php $hasJugendBackDetails = !empty($m['entry_date']) || !empty($jugendBio); ?>
                                            <div class="member-card-face member-card-back">
                                                <?php if ($hasJugendBackDetails): ?>
                                                    <strong class="member-card-back-name"><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                                    <span class="member-card-back-line"><i class="fas fa-briefcase"></i> <?php echo e(t('nav.jugend')); ?></span>
                                                    <?php if (!empty($m['entry_date'])): ?>
                                                        <span class="member-card-back-line"><i class="fas fa-calendar-alt"></i> <?php echo e(t('members.since')); ?> <?php echo htmlspecialchars($m['entry_date']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($jugendBio)): ?>
                                                        <p class="member-card-back-bio"><?php echo htmlspecialchars(mb_substr($jugendBio, 0, 90)) . (mb_strlen($jugendBio) > 90 ? '…' : ''); ?></p>
                                                    <?php endif; ?>
                                                    <span class="member-card-back-more"><i class="fas fa-circle-info"></i> <?php echo e(t('members.click_for_more')); ?></span>
                                                <?php else: ?>
                                                    <div class="member-card-back-emptystate">
                                                        <i class="fas fa-fire-extinguisher"></i>
                                                        <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                                        <span><?php echo e(t('members.active_member_of')); ?><br><?php echo e(t('mannschaft.jugendfeuerwehr')); ?></span>
                                                    </div>
                                                    <span class="member-card-back-more"><i class="fas fa-circle-info"></i> <?php echo e(t('members.click_for_more')); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted-public"><em><?php echo e(t('members.coming_soon')); ?></em></p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/../includes/member-detail-modal.php'; ?>
