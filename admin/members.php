<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/ranks.php';
requireLogin();

$pageTitle = 'Mannschaft';
$activePage = 'members';

$db = getDB();

// Mitglied löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int)$_GET['delete'];
        $stmt = $db->prepare("SELECT photo FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();
        if ($member && $member['photo']) {
            $path = UPLOAD_PATH . $member['photo'];
            if (file_exists($path)) unlink($path);
        }
        $db->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
        flash('success', 'Mitglied wurde gelöscht.');
    }
    header('Location: members.php');
    exit;
}

// Filter
$group = $_GET['group'] ?? 'all';
$validGroups = ['all', 'Kommando', 'Ausschuss', 'Mannschaft', 'Ehrenmitglieder', 'Jugend'];
if (!in_array($group, $validGroups, true)) $group = 'all';

if ($group === 'all') {
    $members = $db->query("SELECT * FROM members WHERE active = 1 ORDER BY group_name, sort_order, lastname")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM members WHERE active = 1 AND group_name = ? ORDER BY sort_order, lastname");
    $stmt->execute([$group]);
    $members = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="member-edit.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Neues Mitglied</a>
</div>

<!-- Filter -->
<div class="admin-filters">
    <a href="members.php" class="filter-pill <?php echo $group === 'all' ? 'active' : ''; ?>">Alle</a>
    <a href="members.php?group=Kommando" class="filter-pill <?php echo $group === 'Kommando' ? 'active' : ''; ?>">Kommando</a>
    <a href="members.php?group=Ausschuss" class="filter-pill <?php echo $group === 'Ausschuss' ? 'active' : ''; ?>">Ausschuss</a>
    <a href="members.php?group=Mannschaft" class="filter-pill <?php echo $group === 'Mannschaft' ? 'active' : ''; ?>">Mannschaft</a>
    <a href="members.php?group=Ehrenmitglieder" class="filter-pill <?php echo $group === 'Ehrenmitglieder' ? 'active' : ''; ?>">Ehrenmitglieder</a>
    <a href="members.php?group=Jugend" class="filter-pill <?php echo $group === 'Jugend' ? 'active' : ''; ?>">Jugend</a>
</div>

<?php if (empty($members)): ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h3>Keine Mitglieder vorhanden</h3>
        <p>Fügen Sie Ihr erstes Mannschaftsmitglied hinzu.</p>
        <a href="member-edit.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Mitglied hinzufügen</a>
    </div>
<?php else: ?>
    <div class="members-grid">
        <?php foreach ($members as $m): ?>
            <div class="member-card-admin">
                <div class="member-photo">
                    <?php if ($m['photo']): ?>
                        <img src="../<?php echo e(UPLOAD_URL . $m['photo']); ?>" alt="<?php echo e($m['firstname'] . ' ' . $m['lastname']); ?>">
                    <?php else: ?>
                        <div class="member-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>
                <div class="member-info">
                    <h3><?php echo e($m['firstname'] . ' ' . $m['lastname']); ?></h3>
                    <?php if ($m['rank']): ?>
                        <span class="member-rank">
                            <img src="../<?php echo e(getRankBadgePath($m['rank'])); ?>" alt="<?php echo e($m['rank']); ?>" style="width:20px;height:20px;vertical-align:middle;margin-right:4px;">
                            <?php echo e($m['rank']); ?> – <?php echo e(getRankName($m['rank'])); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($m['function']): ?>
                        <span class="member-function"><?php echo e($m['function']); ?></span>
                    <?php endif; ?>
                    <span class="member-group badge"><?php echo e($m['group_name']); ?></span>
                    <?php
                    $extraG = array_filter(explode(',', $m['extra_groups'] ?? ''));
                    foreach ($extraG as $eg): ?>
                        <span class="member-group badge badge-secondary"><?php echo e($eg); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="member-actions">
                    <a href="member-edit.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                    <a href="members.php?delete=<?php echo $m['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                       class="btn btn-sm btn-icon btn-danger"
                       title="Löschen"
                       onclick="return confirm('Mitglied wirklich löschen?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
