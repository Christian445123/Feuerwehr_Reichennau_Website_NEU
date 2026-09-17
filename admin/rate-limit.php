<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('logs.manage');

$pageTitle = 'Rate-Limit & IP-Sperren';
$activePage = 'rate-limit';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: rate-limit.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'block') {
        $ip = trim($_POST['ip_address'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $duration = $_POST['duration'] ?? 'permanent';

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            flash('error', 'Bitte eine gültige IP-Adresse angeben.');
        } else {
            $expiresAt = null;
            $durations = ['1h' => '+1 hour', '24h' => '+1 day', '7d' => '+7 days', '30d' => '+30 days'];
            if (isset($durations[$duration])) {
                $expiresAt = date('Y-m-d H:i:s', strtotime($durations[$duration]));
            }
            blockIp($db, $ip, $reason ?: 'Manuell gesperrt', $_SESSION['admin_user_name'] ?? 'Admin', false, $expiresAt);
            logActivity($db, 'ratelimit.block_ip', "$ip" . ($reason ? " ($reason)" : ''));
            flash('success', "IP-Adresse $ip wurde gesperrt.");
        }
    }

    if ($action === 'unblock') {
        $ip = trim($_POST['ip_address'] ?? '');
        unblockIp($db, $ip);
        logActivity($db, 'ratelimit.unblock_ip', $ip);
        flash('success', "IP-Adresse $ip wurde entsperrt.");
    }

    header('Location: rate-limit.php');
    exit;
}

$prefillIp = trim($_GET['block_ip'] ?? '');

$blockedIps = $db->query("SELECT * FROM blocked_ips ORDER BY created_at DESC")->fetchAll();
$events = $db->query("SELECT * FROM rate_limit_events ORDER BY created_at DESC LIMIT 200")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 640px; margin-bottom: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-ban"></i> IP-Adresse sperren</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="block">

            <div class="form-group">
                <label for="ip_address">IP-Adresse</label>
                <input type="text" id="ip_address" name="ip_address" required placeholder="z.B. 203.0.113.42" value="<?php echo e($prefillIp); ?>">
            </div>

            <div class="form-group">
                <label for="reason">Grund (optional)</label>
                <input type="text" id="reason" name="reason" placeholder="z.B. wiederholte Login-Versuche">
            </div>

            <div class="form-group">
                <label for="duration">Dauer</label>
                <select id="duration" name="duration">
                    <option value="permanent">Dauerhaft</option>
                    <option value="1h">1 Stunde</option>
                    <option value="24h">24 Stunden</option>
                    <option value="7d">7 Tage</option>
                    <option value="30d">30 Tage</option>
                </select>
            </div>

            <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Sperren</button>
        </form>
    </div>
</div>

<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-lock"></i> Gesperrte IP-Adressen (<?php echo count($blockedIps); ?>)</h2></div>
    <div class="admin-card-body" style="overflow-x:auto;">
        <?php if (empty($blockedIps)): ?>
            <p style="color: var(--gray-600);">Aktuell sind keine IP-Adressen gesperrt.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>IP-Adresse</th>
                        <th>Grund</th>
                        <th>Gesperrt von</th>
                        <th>Gesperrt am</th>
                        <th>Läuft ab</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blockedIps as $b): ?>
                        <tr>
                            <td><strong><?php echo e($b['ip_address']); ?></strong></td>
                            <td><?php echo e($b['reason']); ?> <?php if ($b['auto_blocked']): ?><span class="badge badge-draft">Automatisch</span><?php endif; ?></td>
                            <td><?php echo e($b['blocked_by']); ?></td>
                            <td><?php echo e(date('d.m.Y H:i', strtotime($b['created_at']))); ?></td>
                            <td><?php echo $b['expires_at'] ? e(date('d.m.Y H:i', strtotime($b['expires_at']))) : 'Dauerhaft'; ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('IP-Sperre für <?php echo e($b['ip_address']); ?> wirklich aufheben?');" style="display:inline;">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="unblock">
                                    <input type="hidden" name="ip_address" value="<?php echo e($b['ip_address']); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-unlock"></i> Entsperren</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h2><i class="fas fa-triangle-exclamation"></i> Rate-Limit-Ereignisse</h2></div>
    <div class="admin-card-body" style="overflow-x:auto;">
        <?php if (empty($events)): ?>
            <p style="color: var(--gray-600);">Bisher wurde kein Rate-Limit überschritten.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Aktion</th>
                        <th>IP-Adresse</th>
                        <th>Detail</th>
                        <th>Automatisch gesperrt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?php echo e(date('d.m.Y H:i:s', strtotime($ev['created_at']))); ?></td>
                            <td><code><?php echo e($ev['action_key']); ?></code></td>
                            <td><?php echo e($ev['ip_address']); ?></td>
                            <td><?php echo e($ev['detail']); ?></td>
                            <td><?php echo $ev['auto_blocked'] ? '<span class="badge badge-error">Ja</span>' : 'Nein'; ?></td>
                            <td>
                                <?php if (!$ev['auto_blocked']): ?>
                                    <a href="rate-limit.php?block_ip=<?php echo urlencode($ev['ip_address']); ?>#ip_address" class="btn-icon" title="IP sperren"><i class="fas fa-ban"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:12px; color: var(--gray-600); font-size:0.85rem;">Es werden maximal die letzten 200 Ereignisse angezeigt.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
