<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
requireLogin();
requirePermission('users.manage');

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer';
$activePage = 'users';

$user = ['username' => '', 'name' => '', 'permissions' => []];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Benutzer nicht gefunden.');
        header('Location: users.php');
        exit;
    }
    $user = $found;
    $user['permissions'] = json_decode($user['permissions'] ?? '[]', true) ?: [];
}

$isProtected = $isEdit && isProtectedAdminUsername($user['username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: user-edit.php' . ($isEdit ? "?id=$id" : ''));
        exit;
    }

    // Das geschützte Hauptkonto "admin" behält immer Benutzername + Vollzugriff -
    // eingereichte Änderungen daran werden ignoriert, egal was im Formular stand.
    $username = $isProtected ? $user['username'] : trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $isSuperadmin = isset($_POST['is_superadmin']);
    $selectedPerms = $_POST['permissions'] ?? [];

    $validKeys = array_keys(getAllPermissions());
    $selectedPerms = array_values(array_intersect($selectedPerms, $validKeys));
    $permissions = $isProtected ? ['*'] : ($isSuperadmin ? ['*'] : $selectedPerms);

    $errors = [];
    if ($username === '' || $name === '') {
        $errors[] = 'Benutzername und Name sind erforderlich.';
    }
    if (!$isEdit && $password === '') {
        $errors[] = 'Für einen neuen Benutzer ist ein Passwort erforderlich.';
    }
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
    }
    if ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Die Passwörter stimmen nicht überein.';
    }

    // Prüfen, ob der Benutzername bereits vergeben ist
    if ($username !== '') {
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }
    }

    // Verhindern, dass sich der letzte Vollzugriff-Benutzer selbst die Benutzerverwaltung entzieht
    if ($isEdit && $id === (int)$_SESSION['admin_user_id'] && !in_array('users.manage', $permissions, true)) {
        $errors[] = 'Du kannst dir selbst nicht die Benutzerverwaltungs-Berechtigung entziehen.';
    }

    if (empty($errors)) {
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);

        if ($isEdit) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username=?, name=?, password=?, permissions=? WHERE id=?");
                $stmt->execute([$username, $name, $hash, $permissionsJson, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?, name=?, permissions=? WHERE id=?");
                $stmt->execute([$username, $name, $permissionsJson, $id]);
            }
            // Session aktualisieren, falls der eigene Account bearbeitet wurde
            if ($id === (int)$_SESSION['admin_user_id']) {
                $_SESSION['admin_user_name'] = $name;
                $_SESSION['admin_permissions'] = $permissions;
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, name, permissions) VALUES (?,?,?,?)");
            $stmt->execute([$username, $hash, $name, $permissionsJson]);
        }

        flash('success', $isEdit ? 'Benutzer wurde aktualisiert.' : 'Benutzer wurde angelegt.');
        header('Location: users.php');
        exit;
    } else {
        foreach ($errors as $err) { flash('error', $err); }
        $user['username'] = $username;
        $user['name'] = $name;
        $user['permissions'] = $permissions;
    }
}

require_once __DIR__ . '/includes/admin-header.php';
$isSuperadmin = in_array('*', $user['permissions'], true);
?>

<div class="page-actions">
    <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
</div>

<form method="POST" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="admin-card">
        <div class="admin-card-header"><h2><i class="fas fa-user"></i> Benutzer-Daten</h2></div>
        <div class="admin-card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Benutzername *</label>
                    <input type="text" id="username" name="username" value="<?php echo e($user['username']); ?>" required autocomplete="off" <?php echo $isProtected ? 'disabled' : ''; ?>>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password"><?php echo $isEdit ? 'Neues Passwort (optional)' : 'Passwort *'; ?></label>
                    <input type="password" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?> minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Passwort bestätigen</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h2><i class="fas fa-key"></i> Berechtigungen</h2></div>
        <div class="admin-card-body">
            <?php if ($isProtected): ?>
                <p class="permission-note"><i class="fas fa-shield-alt"></i> Das Hauptkonto <strong>admin</strong> hat fest eingebauten Vollzugriff und kann nicht eingeschränkt werden.</p>
            <?php endif; ?>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_superadmin" id="is_superadmin" <?php echo $isSuperadmin ? 'checked' : ''; ?> <?php echo $isProtected ? 'disabled' : ''; ?>>
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
                                <?php echo in_array($key, $user['permissions'], true) ? 'checked' : ''; ?>
                                <?php echo ($isSuperadmin || $isProtected) ? 'disabled' : ''; ?>>
                            <span><?php echo $label; ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Speichern' : 'Benutzer anlegen'; ?>
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
