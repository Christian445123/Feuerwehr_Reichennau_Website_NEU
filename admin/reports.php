<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Berichte';
$activePage = 'reports';

$db = getDB();

// Bericht löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int)$_GET['delete'];
        // Bilder löschen
        $images = $db->prepare("SELECT filename FROM report_images WHERE report_id = ?");
        $images->execute([$id]);
        foreach ($images->fetchAll() as $img) {
            $path = UPLOAD_PATH . $img['filename'];
            if (file_exists($path)) unlink($path);
        }
        $db->prepare("DELETE FROM report_images WHERE report_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM reports WHERE id = ?")->execute([$id]);
        flash('success', 'Bericht wurde gelöscht.');
    }
    header('Location: reports.php');
    exit;
}

// Filter
$category = $_GET['category'] ?? 'all';
$validCategories = ['all', 'einsatz', 'uebung', 'jugend', 'sonstige', 'archiv'];
if (!in_array($category, $validCategories, true)) $category = 'all';

$archivCutoff = date('Y-m-d', strtotime('-2 years'));

if ($category === 'all') {
    $reports = $db->query("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC")->fetchAll();
} elseif ($category === 'archiv') {
    $stmt = $db->prepare("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id WHERE r.date < ? GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC");
    $stmt->execute([$archivCutoff]);
    $reports = $stmt->fetchAll();
} else {
    $stmt = $db->prepare("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id WHERE r.category = ? GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC");
    $stmt->execute([$category]);
    $reports = $stmt->fetchAll();
}

$subcategoryLabels = [
    'brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC',
    'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges',
];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="report-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Neuer Bericht</a>
</div>

<!-- Filter -->
<div class="admin-filters">
    <a href="reports.php" class="filter-pill <?php echo $category === 'all' ? 'active' : ''; ?>">Alle</a>
    <a href="reports.php?category=einsatz" class="filter-pill <?php echo $category === 'einsatz' ? 'active' : ''; ?>">Einsatz</a>
    <a href="reports.php?category=uebung" class="filter-pill <?php echo $category === 'uebung' ? 'active' : ''; ?>">Übung</a>
    <a href="reports.php?category=jugend" class="filter-pill <?php echo $category === 'jugend' ? 'active' : ''; ?>">Jugend</a>
    <a href="reports.php?category=sonstige" class="filter-pill <?php echo $category === 'sonstige' ? 'active' : ''; ?>">Sonstige</a>
</div>

<?php if (empty($reports)): ?>
    <div class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>Keine Berichte vorhanden</h3>
        <p>Erstellen Sie Ihren ersten Bericht.</p>
        <a href="report-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Bericht erstellen</a>
    </div>
<?php else: ?>
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Kategorie</th>
                    <th>Datum</th>
                    <th>Fotos</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><strong><?php echo e($r['title']); ?></strong></td>
                        <td><span class="badge badge-<?php echo e($r['category']); ?>"><?php echo e(ucfirst($r['category'])); ?></span></td>
                        <td><?php echo e($r['date']); ?></td>
                        <td><i class="fas fa-images"></i> <?php echo $r['image_count']; ?></td>
                        <td>
                            <?php if ($r['published']): ?>
                                <span class="badge badge-success">Veröffentlicht</span>
                            <?php else: ?>
                                <span class="badge badge-draft">Entwurf</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="report-edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                            <a href="reports.php?delete=<?php echo $r['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                               class="btn btn-sm btn-icon btn-danger"
                               title="Löschen"
                               onclick="return confirm('Bericht wirklich löschen?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
