<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('logs.manage');

$pageTitle = 'Login-Log';
$activePage = 'login-log';

$db = getDB();

$typeFilter = $_GET['type'] ?? 'all';
if (!in_array($typeFilter, ['all', 'admin', 'site_gate'], true)) $typeFilter = 'all';

$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'success', 'failed'], true)) $statusFilter = 'all';

$where = [];
$params = [];
if ($typeFilter !== 'all') {
    $where[] = 'login_type = ?';
    $params[] = $typeFilter;
}
if ($statusFilter !== 'all') {
    $where[] = 'success = ?';
    $params[] = $statusFilter === 'success' ? 1 : 0;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("SELECT * FROM login_log $whereSql ORDER BY created_at DESC LIMIT 300");
$stmt->execute($params);
$entries = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="margin-bottom: 20px;">
    <div class="admin-card-body">
        <form method="GET" class="admin-filters" style="flex-wrap:wrap; gap: 12px; align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;">
                <label for="type">Typ</label>
                <select name="type" id="type">
                    <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>Alle</option>
                    <option value="admin" <?php echo $typeFilter === 'admin' ? 'selected' : ''; ?>>Admin-Login</option>
                    <option value="site_gate" <?php echo $typeFilter === 'site_gate' ? 'selected' : ''; ?>>Website-Zugangssperre</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Alle</option>
                    <option value="success" <?php echo $statusFilter === 'success' ? 'selected' : ''; ?>>Erfolgreich</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Fehlgeschlagen</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filtern</button>
            <?php if ($typeFilter !== 'all' || $statusFilter !== 'all'): ?>
                <a href="login-log.php" class="btn btn-secondary"><i class="fas fa-times"></i> Zurücksetzen</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <i class="fas fa-right-to-bracket"></i>
        <h3>Keine Einträge</h3>
        <p>Es wurden noch keine Anmeldeversuche protokolliert.</p>
    </div>
<?php else: ?>
    <div class="admin-card">
        <div class="admin-card-body" style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Typ</th>
                        <th>Benutzername</th>
                        <th>Status</th>
                        <th>IP-Adresse</th>
                        <th>User-Agent</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?php echo e(date('d.m.Y H:i:s', strtotime($entry['created_at']))); ?></td>
                            <td><?php echo $entry['login_type'] === 'admin' ? 'Admin' : 'Zugangssperre'; ?></td>
                            <td><?php echo e($entry['username_attempted'] ?: '-'); ?></td>
                            <td>
                                <?php if ($entry['success']): ?>
                                    <span class="badge badge-success">Erfolgreich</span>
                                <?php else: ?>
                                    <span class="badge badge-error">Fehlgeschlagen</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($entry['ip_address']); ?></td>
                            <td style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo e($entry['user_agent']); ?>"><?php echo e($entry['user_agent']); ?></td>
                            <td>
                                <a href="rate-limit.php?block_ip=<?php echo urlencode($entry['ip_address']); ?>" class="btn-icon" title="IP sperren"><i class="fas fa-ban"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:12px; color: var(--gray-600); font-size:0.85rem;">Es werden maximal die letzten 300 Einträge angezeigt.</p>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
