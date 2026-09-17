<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/ranks.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('ranks.manage');

$pageTitle = 'Dienstgrade';
$activePage = 'ranks';

$db = getDB();

// Rang löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int)$_GET['delete'];
        // Prüfen ob Rang bei Mitgliedern in Verwendung
        $rank = $db->prepare("SELECT abbr FROM ranks WHERE id = ?");
        $rank->execute([$id]);
        $rankData = $rank->fetch();
        if ($rankData) {
            $inUse = $db->prepare("SELECT COUNT(*) FROM members WHERE rank = ?");
            $inUse->execute([$rankData['abbr']]);
            if ($inUse->fetchColumn() > 0) {
                flash('error', 'Dieser Dienstgrad ist bei Mitgliedern in Verwendung und kann nicht gelöscht werden.');
            } else {
                $db->prepare("DELETE FROM ranks WHERE id = ?")->execute([$id]);
                logActivity($db, 'rank.delete', $rankData['abbr']);
                flash('success', 'Dienstgrad wurde gelöscht.');
            }
        }
    }
    header('Location: ranks.php');
    exit;
}

// Filter nach Kategorie
$category = $_GET['category'] ?? 'all';
if ($category === 'all') {
    $ranks = $db->query("SELECT * FROM ranks ORDER BY sort_order, abbr")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM ranks WHERE category = ? ORDER BY sort_order, abbr");
    $stmt->execute([$category]);
    $ranks = $stmt->fetchAll();
}

// Alle Kategorien für Filter-Pills laden
$categories = $db->query("SELECT category, MIN(sort_order) as min_sort FROM ranks GROUP BY category ORDER BY min_sort")->fetchAll();

// Zählen wie viele Mitglieder jeden Rang nutzen
$usageCounts = [];
$usageStmt = $db->query("SELECT rank, COUNT(*) as cnt FROM members WHERE rank != '' GROUP BY rank");
foreach ($usageStmt->fetchAll() as $u) {
    $usageCounts[$u['rank']] = (int)$u['cnt'];
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="rank-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Neuer Dienstgrad</a>
</div>

<!-- Filter -->
<div class="admin-filters">
    <a href="ranks.php" class="filter-pill <?php echo $category === 'all' ? 'active' : ''; ?>">Alle (<?php echo $db->query("SELECT COUNT(*) FROM ranks")->fetchColumn(); ?>)</a>
    <?php foreach ($categories as $cat): ?>
        <a href="ranks.php?category=<?php echo urlencode($cat['category']); ?>" class="filter-pill <?php echo $category === $cat['category'] ? 'active' : ''; ?>">
            <?php echo e($cat['category']); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($ranks)): ?>
    <div class="empty-state">
        <i class="fas fa-medal"></i>
        <h3>Keine Dienstgrade vorhanden</h3>
        <p>Fügen Sie den ersten Dienstgrad hinzu.</p>
        <a href="rank-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Dienstgrad hinzufügen</a>
    </div>
<?php else: ?>
    <div class="admin-card">
        <div class="admin-card-body" style="padding:0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Badge</th>
                        <th>Abkürzung</th>
                        <th>Bezeichnung</th>
                        <th>Kategorie</th>
                        <th>Sortierung</th>
                        <th>Mitglieder</th>
                        <th>Status</th>
                        <th style="width:100px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranks as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['badge'] && file_exists(__DIR__ . '/../' . $r['badge'])): ?>
                                    <img src="../<?php echo e($r['badge']); ?>" alt="<?php echo e($r['abbr']); ?>" style="width:32px;height:32px;object-fit:contain;">
                                <?php else: ?>
                                    <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo e($r['abbr']); ?></strong></td>
                            <td><?php echo e($r['name']); ?></td>
                            <td><span class="badge"><?php echo e($r['category']); ?></span></td>
                            <td><?php echo (int)$r['sort_order']; ?></td>
                            <td>
                                <?php $count = $usageCounts[$r['abbr']] ?? 0; ?>
                                <?php if ($count > 0): ?>
                                    <span class="badge badge-success"><?php echo $count; ?></span>
                                <?php else: ?>
                                    <span style="color:var(--gray-400);">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['active']): ?>
                                    <span class="badge badge-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge badge-draft">Inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <a href="rank-edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                                    <?php if (($usageCounts[$r['abbr']] ?? 0) === 0): ?>
                                        <a href="ranks.php?delete=<?php echo $r['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                                           class="btn btn-sm btn-icon btn-danger"
                                           title="Löschen"
                                           onclick="return confirm('Dienstgrad wirklich löschen?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
