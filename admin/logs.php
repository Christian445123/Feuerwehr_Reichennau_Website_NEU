<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('logs.manage');

$pageTitle = 'Aktivitäts-Log';
$activePage = 'logs';

$db = getDB();

$userFilter = trim($_GET['user'] ?? '');
$actionFilter = trim($_GET['action'] ?? '');

$where = [];
$params = [];
if ($userFilter !== '') {
    $where[] = 'user_name = ?';
    $params[] = $userFilter;
}
if ($actionFilter !== '') {
    $where[] = 'action LIKE ?';
    $params[] = '%' . $actionFilter . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("SELECT * FROM activity_log $whereSql ORDER BY created_at DESC LIMIT 300");
$stmt->execute($params);
$entries = $stmt->fetchAll();

$users = $db->query("SELECT DISTINCT user_name FROM activity_log ORDER BY user_name")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="margin-bottom: 20px;">
    <div class="admin-card-body">
        <form method="GET" class="admin-filters" style="flex-wrap:wrap; gap: 12px; align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;">
                <label for="user">Benutzer</label>
                <select name="user" id="user">
                    <option value="">Alle</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo e($u); ?>" <?php echo $userFilter === $u ? 'selected' : ''; ?>><?php echo e($u); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label for="action">Aktion enthält</label>
                <input type="text" name="action" id="action" value="<?php echo e($actionFilter); ?>" placeholder="z.B. report.">
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filtern</button>
            <?php if ($userFilter !== '' || $actionFilter !== ''): ?>
                <a href="logs.php" class="btn btn-secondary"><i class="fas fa-times"></i> Zurücksetzen</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Keine Einträge</h3>
        <p>Es wurden noch keine Aktivitäten protokolliert.</p>
    </div>
<?php else: ?>
    <div class="admin-card">
        <div class="admin-card-body" style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Benutzer</th>
                        <th>Aktion</th>
                        <th>Details</th>
                        <th>IP-Adresse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?php echo e(date('d.m.Y H:i:s', strtotime($entry['created_at']))); ?></td>
                            <td><?php echo e($entry['user_name']); ?></td>
                            <td><code><?php echo e($entry['action']); ?></code></td>
                            <td><?php echo e($entry['details']); ?></td>
                            <td><?php echo e($entry['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:12px; color: var(--gray-600); font-size:0.85rem;">Es werden maximal die letzten 300 Einträge angezeigt.</p>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
