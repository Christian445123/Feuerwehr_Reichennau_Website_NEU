<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/mail.php';
requireLogin();
requirePermission('users.manage');

$db = getDB();
$roles = $db->query("SELECT id, name, permissions FROM roles ORDER BY name")->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer';
$activePage = 'users';

$user = ['username' => '', 'name' => '', 'firstname' => '', 'lastname' => '', 'email' => '', 'permissions' => [], 'must_change_password' => 0, 'totp_enabled' => 0];

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
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $name = trim($firstname . ' ' . $lastname);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $forceChangePassword = isset($_POST['force_change_password']);
    $resetTwofa = isset($_POST['reset_2fa']);
    $isSuperadmin = isset($_POST['is_superadmin']);
    $selectedPerms = $_POST['permissions'] ?? [];

    $validKeys = array_keys(getAllPermissions());
    $selectedPerms = array_values(array_intersect($selectedPerms, $validKeys));
    $permissions = $isProtected ? ['*'] : ($isSuperadmin ? ['*'] : $selectedPerms);

    $errors = [];
    if ($firstname === '' || $lastname === '') {
        $errors[] = 'Vorname und Nachname sind erforderlich.';
    }
    if (!$isEdit && $email === '') {
        $errors[] = 'Für einen neuen Benutzer ist eine E-Mail-Adresse erforderlich (für die Zugangsdaten).';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }

    // Benutzername automatisch aus Nachname+Vorname, falls leer gelassen
    if (!$isProtected && $username === '' && $firstname !== '' && $lastname !== '') {
        $username = generateUsernameFromName($db, $firstname, $lastname, $isEdit ? $id : null);
    }
    if ($username === '') {
        $errors[] = 'Benutzername ist erforderlich.';
    }

    $generatedPassword = null;
    if (!$isEdit) {
        // Für neue Benutzer wird das Passwort immer automatisch erzeugt und
        // dem Benutzer per Mail zugeschickt - keine manuelle Eingabe.
        $generatedPassword = generateRandomPassword();
    } else {
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
        }
        if ($password !== '' && $password !== $confirmPassword) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
    }

    if ($username !== '') {
        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }
    }

    // Verhindern, dass sich der letzte Vollzugriff-Benutzer selbst die Benutzerverwaltung entzieht
    $keepsUsersManage = in_array('*', $permissions, true) || in_array('users.manage', $permissions, true);
    if ($isEdit && $id === (int)$_SESSION['admin_user_id'] && !$keepsUsersManage) {
        $errors[] = 'Du kannst dir selbst nicht die Benutzerverwaltungs-Berechtigung entziehen.';
    }

    if (empty($errors)) {
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);

        if ($isEdit) {
            $twofaSql = $resetTwofa ? ", totp_enabled = 0, totp_secret = NULL" : "";
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username=?, name=?, firstname=?, lastname=?, email=?, password=?, permissions=?, must_change_password=?$twofaSql WHERE id=?");
                $stmt->execute([$username, $name, $firstname, $lastname, $email, $hash, $permissionsJson, $forceChangePassword ? 1 : 0, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?, name=?, firstname=?, lastname=?, email=?, permissions=?, must_change_password=?$twofaSql WHERE id=?");
                $stmt->execute([$username, $name, $firstname, $lastname, $email, $permissionsJson, $forceChangePassword ? 1 : 0, $id]);
            }
            // Session aktualisieren, falls der eigene Account bearbeitet wurde
            if ($id === (int)$_SESSION['admin_user_id']) {
                $_SESSION['admin_user_name'] = $name;
                $_SESSION['admin_permissions'] = $permissions;
                $_SESSION['admin_must_change_password'] = $forceChangePassword;
            }
        } else {
            $hash = password_hash($generatedPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, name, firstname, lastname, email, permissions, must_change_password) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([$username, $hash, $name, $firstname, $lastname, $email, $permissionsJson]);

            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $loginUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $scriptDir . '/login.php';
            $mailBody = "Hallo $firstname,\n\n"
                . "für dich wurde ein Zugang zum Admin-Bereich der Website der Freiwilligen Feuerwehr Reichenau angelegt.\n\n"
                . "Benutzername: $username\n"
                . "Passwort: $generatedPassword\n\n"
                . "Anmelden kannst du dich hier: $loginUrl\n"
                . "Beim ersten Login wirst du aufgefordert, dir ein eigenes Passwort zu vergeben.\n\n"
                . "Freiwillige Feuerwehr Reichenau";
            $mailSent = false;
            try {
                $mailSent = sendMail($email, $name, 'Dein Zugang zum FF-Reichenau-Admin-Bereich', $mailBody, $mailError);
            } catch (\Throwable $e) {
                error_log('Zugangsdaten-Mail fehlgeschlagen: ' . $e->getMessage());
            }

            // Einmalige Anzeige der Zugangsdaten auf der Übersichtsseite - falls
            // der Mailversand fehlschlägt, gehen sie dem Admin sonst verloren.
            $_SESSION['new_user_credentials'] = [
                'username' => $username,
                'password' => $generatedPassword,
                'email' => $email,
                'mail_sent' => $mailSent,
            ];
        }

        logActivity($db, $isEdit ? 'user.update' : 'user.create', $username);
        flash('success', $isEdit ? 'Benutzer wurde aktualisiert.' : 'Benutzer wurde angelegt.');
        header('Location: users.php');
        exit;
    } else {
        foreach ($errors as $err) { flash('error', $err); }
        $user['username'] = $username;
        $user['name'] = $name;
        $user['firstname'] = $firstname;
        $user['lastname'] = $lastname;
        $user['email'] = $email;
        $user['permissions'] = $permissions;
        $user['must_change_password'] = $forceChangePassword ? 1 : 0;
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
                    <label for="firstname">Vorname *</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo e($user['firstname'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Nachname *</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo e($user['lastname'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Benutzername <?php echo $isEdit ? '*' : ''; ?></label>
                    <input type="text" id="username" name="username" value="<?php echo e($user['username']); ?>" <?php echo $isEdit ? 'required' : ''; ?> autocomplete="off" <?php echo $isProtected ? 'disabled' : ''; ?>>
                    <?php if (!$isEdit): ?>
                        <p class="form-hint">Wird automatisch aus Nachname + Vorname erstellt (Nachname zuerst), kann hier aber angepasst werden.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email">E-Mail-Adresse <?php echo $isEdit ? '' : '*'; ?></label>
                    <input type="email" id="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" <?php echo $isEdit ? '' : 'required'; ?>>
                    <?php if (!$isEdit): ?>
                        <p class="form-hint">Zugangsdaten (Benutzername + automatisch generiertes Passwort) werden an diese Adresse geschickt.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isEdit): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Neues Passwort (optional)</label>
                        <input type="password" id="password" name="password" minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Passwort bestätigen</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-secondary btn-sm" id="generatePwBtn"><i class="fas fa-dice"></i> Zufälliges Passwort generieren</button>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="force_change_password" id="force_change_password" <?php echo !empty($user['must_change_password']) ? 'checked' : ''; ?>>
                        <span>Passwortänderung beim nächsten Login erzwingen</span>
                    </label>
                </div>
                <?php if (!empty($user['totp_enabled'])): ?>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="reset_2fa" id="reset_2fa">
                            <span><i class="fas fa-shield-halved"></i> Zwei-Faktor-Authentifizierung dieses Benutzers deaktivieren (z.B. bei Verlust des Geräts)</span>
                        </label>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h2><i class="fas fa-key"></i> Berechtigungen</h2></div>
        <div class="admin-card-body">
            <?php if ($isProtected): ?>
                <p class="permission-note"><i class="fas fa-shield-alt"></i> Das Hauptkonto <strong>admin</strong> hat fest eingebauten Vollzugriff und kann nicht eingeschränkt werden.</p>
            <?php endif; ?>

            <?php if (!empty($roles) && !$isProtected): ?>
                <div class="form-group">
                    <label for="role_template">Rolle übernehmen (setzt die Rechte unten als Vorschlag)</label>
                    <div style="display:flex; gap:8px;">
                        <select id="role_template">
                            <option value="">-- Rolle wählen --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo (int) $r['id']; ?>"><?php echo e($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-secondary btn-sm" id="applyRoleBtn"><i class="fas fa-arrow-down"></i> Übernehmen</button>
                    </div>
                    <p class="form-hint">Setzt einmalig die Häkchen unten entsprechend der Rolle - danach frei anpassbar. Spätere Änderungen an der Rolle wirken sich nicht automatisch aus.</p>
                </div>
                <hr>
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

var roleData = <?php echo json_encode(array_column(array_map(function ($r) {
    return ['id' => (int) $r['id'], 'permissions' => json_decode($r['permissions'] ?? '[]', true) ?: []];
}, $roles), 'permissions', 'id'), JSON_UNESCAPED_UNICODE); ?>;
var applyRoleBtn = document.getElementById('applyRoleBtn');
if (applyRoleBtn) {
    applyRoleBtn.addEventListener('click', function () {
        var roleSelect = document.getElementById('role_template');
        var perms = roleData[roleSelect.value];
        if (!perms) return;
        if (perms.indexOf('*') !== -1) {
            superadminCheckbox.checked = true;
        } else {
            superadminCheckbox.checked = false;
            permCheckboxes.forEach(function (cb) { cb.checked = perms.indexOf(cb.value) !== -1; });
        }
        togglePermCheckboxes();
    });
}

<?php if (!$isEdit): ?>
// Benutzername live aus Vor-/Nachname vorschlagen (Nachname zuerst), solange
// der Admin das Feld nicht selbst angefasst hat.
(function () {
    var firstnameEl = document.getElementById('firstname');
    var lastnameEl = document.getElementById('lastname');
    var usernameEl = document.getElementById('username');
    var usernameTouched = usernameEl.value !== '';

    usernameEl.addEventListener('input', function () { usernameTouched = true; });

    function transliterate(text) {
        var map = {'ä':'ae','ö':'oe','ü':'ue','Ä':'Ae','Ö':'Oe','Ü':'Ue','ß':'ss'};
        text = text.replace(/[äöüÄÖÜß]/g, function (m) { return map[m]; });
        return text.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    }

    function updateUsername() {
        if (usernameTouched) return;
        usernameEl.value = transliterate(lastnameEl.value) + transliterate(firstnameEl.value);
    }
    firstnameEl.addEventListener('input', updateUsername);
    lastnameEl.addEventListener('input', updateUsername);
})();
<?php else: ?>
var generatePwBtn = document.getElementById('generatePwBtn');
if (generatePwBtn) {
    generatePwBtn.addEventListener('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%&*';
        var length = 10 + Math.floor(Math.random() * 3);
        var array = new Uint32Array(length);
        window.crypto.getRandomValues(array);
        var pw = '';
        for (var i = 0; i < length; i++) { pw += chars[array[i] % chars.length]; }
        var pwEl = document.getElementById('password');
        var confirmEl = document.getElementById('confirm_password');
        pwEl.type = 'text';
        confirmEl.type = 'text';
        pwEl.value = pw;
        confirmEl.value = pw;
    });
}
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
