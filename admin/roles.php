<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('users.manage');

$pageTitle = 'Rollen';
$activePage = 'roles';

$db = getDB();

// Rolle löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int) $_GET['delete'];
        $name = $db->prepare("SELECT name FROM roles WHERE id = ?");
        $name->execute([$id]);
        $name = $name->fetchColumn();
        if ($name !== false) {
            $db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
            logActivity($db, 'role.delete', $name);
            flash('success', 'Rolle wurde gelöscht.');
        }
    }
    header('Location: roles.php');
    exit;
}

$roles = $db->query("SELECT * FROM roles ORDER BY name")->fetchAll();
$allPermissions = getAllPermissions();

require_once __DIR__ . '/includes/admin-header.php';
?>

<p style="margin-bottom: 16px; color: var(--gray-600);">Rollen sind wiederverwendbare Berechtigungs-Vorlagen: beim Anlegen oder Bearbeiten eines Benutzers kann eine Rolle übernommen werden, um die passenden Rechte automatisch vorzubelegen. Spätere Änderungen an einer Rolle wirken sich nicht rückwirkend auf bereits angelegte Benutzer aus.</p>

<div class="page-actions">
    <a href="role-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Neue Rolle</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Rechte</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($roles)): ?>
                <tr><td colspan="3" style="color: var(--gray-600);">Noch keine Rollen angelegt.</td></tr>
            <?php endif; ?>
            <?php foreach ($roles as $r): ?>
                <?php $perms = json_decode($r['permissions'] ?? '[]', true) ?: []; ?>
                <tr>
                    <td><strong><?php echo e($r['name']); ?></strong></td>
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
                    <td class="actions-cell">
                        <a href="role-edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                        <a href="roles.php?delete=<?php echo $r['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                           class="btn btn-sm btn-icon btn-danger"
                           title="Löschen"
                           onclick="return confirm('Rolle wirklich löschen? Bereits angelegte Benutzer behalten ihre Rechte.')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
