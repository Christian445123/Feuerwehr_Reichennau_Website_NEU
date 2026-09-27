<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('users.manage');

$pageTitle = 'Benutzer';
$activePage = 'users';

$db = getDB();

// Benutzer löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int)$_GET['delete'];
        $total = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $targetUsername = $db->prepare("SELECT username FROM users WHERE id = ?");
        $targetUsername->execute([$id]);
        $targetUsername = $targetUsername->fetchColumn();

        if ($targetUsername !== false && isProtectedAdminUsername($targetUsername)) {
            flash('error', 'Das Hauptkonto "admin" kann nicht gelöscht werden.');
        } elseif ($id === (int)$_SESSION['admin_user_id']) {
            flash('error', 'Du kannst dich nicht selbst löschen.');
        } elseif ($total <= 1) {
            flash('error', 'Der letzte Benutzer kann nicht gelöscht werden.');
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            logActivity($db, 'user.delete', $targetUsername);
            flash('success', 'Benutzer wurde gelöscht.');
        }
    }
    header('Location: users.php');
    exit;
}

$users = $db->query("SELECT id, username, name, email, permissions, must_change_password, totp_enabled, created_at FROM users ORDER BY name")->fetchAll();
$allPermissions = getAllPermissions();

require_once __DIR__ . '/includes/admin-header.php';

$newUserCredentials = $_SESSION['new_user_credentials'] ?? null;
unset($_SESSION['new_user_credentials']);
?>

<?php if ($newUserCredentials): ?>
    <div class="admin-card" style="border: 2px solid var(--primary); margin-bottom: 20px;">
        <div class="admin-card-header"><h2><i class="fas fa-key"></i> Zugangsdaten für neuen Benutzer</h2></div>
        <div class="admin-card-body">
            <?php if ($newUserCredentials['mail_sent']): ?>
                <p style="color:var(--success);"><i class="fas fa-check-circle"></i> Die Zugangsdaten wurden an <strong><?php echo e($newUserCredentials['email']); ?></strong> geschickt.</p>
            <?php else: ?>
                <p style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Der Mailversand ist fehlgeschlagen - bitte gib die Zugangsdaten manuell weiter.</p>
            <?php endif; ?>
            <p><strong>Benutzername:</strong> <code><?php echo e($newUserCredentials['username']); ?></code></p>
            <p><strong>Passwort:</strong> <code><?php echo e($newUserCredentials['password']); ?></code></p>
            <p class="form-hint">Diese Anzeige erscheint nur einmal - das Passwort wird nirgends im Klartext gespeichert. Der Benutzer muss es beim ersten Login sofort ändern.</p>
        </div>
    </div>
<?php endif; ?>

<div class="page-actions">
    <a href="user-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Neuer Benutzer</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th>Rechte</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $perms = json_decode($u['permissions'] ?? '[]', true) ?: []; ?>
                <?php $isProtected = isProtectedAdminUsername($u['username']); ?>
                <tr>
                    <td>
                        <strong><?php echo e($u['name']); ?></strong>
                        <?php if ((int)$u['id'] === (int)$_SESSION['admin_user_id']): ?><span class="badge badge-secondary">Du</span><?php endif; ?>
                        <?php if ($isProtected): ?><span class="badge badge-success" title="Hauptkonto mit fest eingebautem Vollzugriff"><i class="fas fa-shield-alt"></i> Geschützt</span><?php endif; ?>
                    </td>
                    <td><?php echo e($u['username']); ?></td>
                    <td><?php echo $u['email'] !== '' ? e($u['email']) : '<span style="color:var(--gray-600);">–</span>'; ?></td>
                    <td>
                        <?php if (in_array('*', $perms, true)): ?>
                            <span class="badge badge-success"><i class="fas fa-star"></i> Vollzugriff</span>
                        <?php elseif (empty($perms)): ?>
                            <span class="badge badge-draft">Keine Rechte</span>
                        <?php else: ?>
                            <?php foreach ($perms as $p): ?>
                                <span class="badge badge-secondary" title="<?php echo e($allPermissions[$p] ?? $p); ?>"><?php echo e(explode('.', $p)[0]); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($u['must_change_password'])): ?>
                            <span class="badge badge-draft" title="Muss beim nächsten Login ein neues Passwort vergeben"><i class="fas fa-key"></i> Passwortwechsel ausstehend</span>
                        <?php endif; ?>
                        <?php if (!empty($u['totp_enabled'])): ?>
                            <span class="badge badge-success" title="Zwei-Faktor-Authentifizierung aktiv"><i class="fas fa-shield-halved"></i> 2FA</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <a href="user-edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                        <?php if (!$isProtected): ?>
                            <a href="users.php?delete=<?php echo $u['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                               class="btn btn-sm btn-icon btn-danger"
                               title="Löschen"
                               onclick="return confirm('Benutzer wirklich löschen?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
