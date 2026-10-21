<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('logs.manage');

$pageTitle = 'Gesamtprotokoll';
$activePage = 'gesamtprotokoll';

$db = getDB();

/**
 * Führt alle einzelnen Protokolle (Admin-Aktionen, Login-Versuche,
 * Rate-Limit-Ereignisse, IP-Sperren) zu einer einzigen, chronologisch
 * sortierten Übersicht zusammen - "ein umfassendes Log" statt vier
 * getrennter Seiten. Jede Quelle liefert ihre letzten 150 Einträge, danach
 * wird gemeinsam sortiert und auf 300 Zeilen gekappt.
 */
$entries = [];

foreach ($db->query("SELECT created_at, user_name, action, details, ip_address FROM activity_log ORDER BY created_at DESC LIMIT 150")->fetchAll() as $r) {
    $entries[] = [
        'created_at' => $r['created_at'],
        'quelle' => 'Admin-Aktion',
        'icon' => 'fa-user-gear',
        'wer' => $r['user_name'] ?: 'System',
        'was' => $r['action'],
        'details' => $r['details'],
        'ip' => $r['ip_address'],
    ];
}

foreach ($db->query("SELECT created_at, login_type, username_attempted, success, ip_address FROM login_log ORDER BY created_at DESC LIMIT 150")->fetchAll() as $r) {
    $entries[] = [
        'created_at' => $r['created_at'],
        'quelle' => $r['success'] ? 'Login erfolgreich' : 'Login fehlgeschlagen',
        'icon' => $r['success'] ? 'fa-right-to-bracket' : 'fa-triangle-exclamation',
        'wer' => $r['username_attempted'] ?: '-',
        'was' => $r['login_type'] === 'admin' ? 'Admin-Bereich' : 'Website-Zugangssperre',
        'details' => '',
        'ip' => $r['ip_address'],
    ];
}

foreach ($db->query("SELECT created_at, action_key, ip_address, detail, auto_blocked FROM rate_limit_events ORDER BY created_at DESC LIMIT 150")->fetchAll() as $r) {
    $entries[] = [
        'created_at' => $r['created_at'],
        'quelle' => $r['auto_blocked'] ? 'IP automatisch gesperrt' : 'Rate-Limit überschritten',
        'icon' => 'fa-shield-halved',
        'wer' => '-',
        'was' => $r['action_key'],
        'details' => $r['detail'],
        'ip' => $r['ip_address'],
    ];
}

foreach ($db->query("SELECT created_at, ip_address, reason, blocked_by FROM blocked_ips ORDER BY created_at DESC LIMIT 100")->fetchAll() as $r) {
    $entries[] = [
        'created_at' => $r['created_at'],
        'quelle' => 'IP gesperrt',
        'icon' => 'fa-ban',
        'wer' => $r['blocked_by'] ?: 'System',
        'was' => 'Sperre eingetragen/aktualisiert',
        'details' => $r['reason'],
        'ip' => $r['ip_address'],
    ];
}

usort($entries, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
$entries = array_slice($entries, 0, 300);

// Kurzüberblick über die öffentliche Website (ohne Einzel-Zeilen, sonst
// würde die Liste von reinen Seitenaufrufen überflutet).
$visitsToday = (int) $db->query("SELECT COUNT(*) FROM site_visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn();
$visitsWeek = (int) $db->query("SELECT COUNT(*) FROM site_visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$topPages = $db->query("SELECT page, COUNT(*) as n FROM site_visits WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY page ORDER BY n DESC LIMIT 8")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<p style="margin-bottom:16px; color: var(--gray-600);">
    Fasst Admin-Aktionen, Login-Versuche, Rate-Limit-Ereignisse und IP-Sperren in einer einzigen, zeitlich sortierten
    Liste zusammen. Für die Detailansicht je Bereich gibt es weiterhin <a href="logs.php">Aktivitäts-Log</a>,
    <a href="login-log.php">Login-Log</a> und <a href="rate-limit.php">Rate-Limit &amp; IPs</a>.
</p>

<div class="admin-card" style="margin-bottom:20px;">
    <div class="admin-card-header"><h2><i class="fas fa-chart-line"></i> Öffentliche Website - Überblick</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom:12px;">
            <strong><?php echo $visitsToday; ?></strong> Seitenaufrufe in den letzten 24 Stunden,
            <strong><?php echo $visitsWeek; ?></strong> in den letzten 7 Tagen.
        </p>
        <?php if (!empty($topPages)): ?>
            <p style="margin-bottom:6px; color: var(--gray-600); font-size:0.85rem;">Meistaufgerufene Seiten (7 Tage):</p>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php foreach ($topPages as $tp): ?>
                    <span class="badge badge-sonstige"><?php echo e($tp['page'] ?: '(Startseite)'); ?>: <?php echo (int) $tp['n']; ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Noch keine Einträge</h3>
    </div>
<?php else: ?>
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Zeitpunkt</th>
                    <th>Art</th>
                    <th>Wer</th>
                    <th>Was</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $en): ?>
                    <tr>
                        <td style="white-space:nowrap;"><?php echo e(date('d.m.Y H:i:s', strtotime($en['created_at']))); ?></td>
                        <td style="white-space:nowrap;"><i class="fas <?php echo $en['icon']; ?>"></i> <?php echo e($en['quelle']); ?></td>
                        <td><?php echo e($en['wer']); ?></td>
                        <td><code><?php echo e($en['was']); ?></code></td>
                        <td><?php echo e(mb_substr($en['details'], 0, 150)); ?></td>
                        <td><?php echo e($en['ip']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
