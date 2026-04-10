<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$db = getDB();

$reportCount = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$memberCount = $db->query("SELECT COUNT(*) FROM members WHERE active = 1")->fetchColumn();
$imageCount = $db->query("SELECT COUNT(*) FROM report_images")->fetchColumn();
$recentReports = $db->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentMembers = $db->query("SELECT * FROM members WHERE active = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="stats-row">
    <div class="admin-stat">
        <div class="admin-stat-icon bg-red"><i class="fas fa-newspaper"></i></div>
        <div class="admin-stat-info">
            <span class="admin-stat-number"><?php echo $reportCount; ?></span>
            <span class="admin-stat-label">Berichte</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon bg-blue"><i class="fas fa-users"></i></div>
        <div class="admin-stat-info">
            <span class="admin-stat-number"><?php echo $memberCount; ?></span>
            <span class="admin-stat-label">Mitglieder</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon bg-green"><i class="fas fa-images"></i></div>
        <div class="admin-stat-info">
            <span class="admin-stat-number"><?php echo $imageCount; ?></span>
            <span class="admin-stat-label">Fotos</span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fas fa-newspaper"></i> Letzte Berichte</h2>
            <a href="reports.php" class="btn btn-sm">Alle anzeigen</a>
        </div>
        <div class="admin-card-body">
            <?php if (empty($recentReports)): ?>
                <p class="text-muted">Noch keine Berichte vorhanden.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Titel</th><th>Kategorie</th><th>Datum</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReports as $r): ?>
                            <tr>
                                <td><a href="report-edit.php?id=<?php echo $r['id']; ?>"><?php echo e($r['title']); ?></a></td>
                                <td><span class="badge badge-<?php echo e($r['category']); ?>"><?php echo e(ucfirst($r['category'])); ?></span></td>
                                <td><?php echo e($r['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fas fa-users"></i> Neue Mitglieder</h2>
            <a href="members.php" class="btn btn-sm">Alle anzeigen</a>
        </div>
        <div class="admin-card-body">
            <?php if (empty($recentMembers)): ?>
                <p class="text-muted">Noch keine Mitglieder vorhanden.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Name</th><th>Dienstgrad</th><th>Funktion</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMembers as $m): ?>
                            <tr>
                                <td><a href="member-edit.php?id=<?php echo $m['id']; ?>"><?php echo e($m['firstname'] . ' ' . $m['lastname']); ?></a></td>
                                <td><?php echo e($m['rank']); ?></td>
                                <td><?php echo e($m['function']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="quick-actions">
    <h3>Schnellaktionen</h3>
    <div class="quick-actions-grid">
        <a href="report-edit.php" class="quick-action">
            <i class="fas fa-plus-circle"></i>
            <span>Neuer Bericht</span>
        </a>
        <a href="member-edit.php" class="quick-action">
            <i class="fas fa-user-plus"></i>
            <span>Neues Mitglied</span>
        </a>
        <a href="../index.php" class="quick-action" target="_blank">
            <i class="fas fa-globe"></i>
            <span>Website ansehen</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
