<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('users.manage');

$db = getDB();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Rolle bearbeiten' : 'Neue Rolle';
$activePage = 'roles';

$role = ['name' => '', 'permissions' => []];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Rolle nicht gefunden.');
        header('Location: roles.php');
        exit;
    }
    $role = $found;
    $role['permissions'] = json_decode($role['permissions'] ?? '[]', true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: role-edit.php' . ($isEdit ? "?id=$id" : ''));
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $isSuperadmin = isset($_POST['is_superadmin']);
    $selectedPerms = $_POST['permissions'] ?? [];
    $validKeys = array_keys(getAllPermissions());
    $selectedPerms = array_values(array_intersect($selectedPerms, $validKeys));
    $permissions = $isSuperadmin ? ['*'] : $selectedPerms;

    $errors = [];
    if ($name === '') {
        $errors[] = 'Ein Name für die Rolle ist erforderlich.';
    } else {
        $check = $db->prepare("SELECT COUNT(*) FROM roles WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Eine Rolle mit diesem Namen existiert bereits.';
        }
    }

    if (empty($errors)) {
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE roles SET name=?, permissions=? WHERE id=?");
            $stmt->execute([$name, $permissionsJson, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO roles (name, permissions) VALUES (?,?)");
            $stmt->execute([$name, $permissionsJson]);
        }
        logActivity($db, $isEdit ? 'role.update' : 'role.create', $name);
        flash('success', $isEdit ? 'Rolle wurde aktualisiert.' : 'Rolle wurde angelegt.');
        header('Location: roles.php');
        exit;
    } else {
        foreach ($errors as $err) { flash('error', $err); }
        $role['name'] = $name;
        $role['permissions'] = $permissions;
    }
}

require_once __DIR__ . '/includes/admin-header.php';
$isSuperadmin = in_array('*', $role['permissions'], true);
?>

<div class="page-actions">
    <a href="roles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
</div>

<form method="POST" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="admin-card">
        <div class="admin-card-header"><h2><i class="fas fa-user-tag"></i> Rollen-Name</h2></div>
        <div class="admin-card-body">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?php echo e($role['name']); ?>" required placeholder="z.B. Redakteur, Kassier, Jugendbetreuer">
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h2><i class="fas fa-key"></i> Standard-Berechtigungen dieser Rolle</h2></div>
        <div class="admin-card-body">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_superadmin" id="is_superadmin" <?php echo $isSuperadmin ? 'checked' : ''; ?>>
                    <span><strong>Vollzugriff</strong> (alle aktuellen und zukünftigen Berechtigungen)</span>
                </label>
            </div>

            <hr>

            <p style="font-size:0.85rem;color:var(--gray-600);margin-bottom:10px;">Oder einzelne Berechtigungen auswählen:</p>

            <div id="permissionsList">
                <?php foreach (getAllPermissions() as $key => $label): ?>
                    <div class="form-group">
                        <label class="checkbox-label permission-checkbox">
                            <input type="checkbox" name="permissions[]" value="<?php echo e($key); ?>"
                                <?php echo in_array($key, $role['permissions'], true) ? 'checked' : ''; ?>
                                <?php echo $isSuperadmin ? 'disabled' : ''; ?>>
                            <span><?php echo $label; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Speichern' : 'Rolle anlegen'; ?>
    </button>
</form>

<script>
var superadminCheckbox = document.getElementById('is_superadmin');
var permCheckboxes = document.querySelectorAll('#permissionsList input[type="checkbox"]');

function togglePermCheckboxes() {
    permCheckboxes.forEach(function (cb) { cb.disabled = superadminCheckbox.checked; });
}
superadminCheckbox.addEventListener('change', togglePermCheckboxes);
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
