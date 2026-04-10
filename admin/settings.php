<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Einstellungen';
$activePage = 'settings';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: settings.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $currentPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($currentPw, $user['password'])) {
            flash('error', 'Aktuelles Passwort ist falsch.');
        } elseif (strlen($newPw) < 6) {
            flash('error', 'Neues Passwort muss mindestens 6 Zeichen lang sein.');
        } elseif ($newPw !== $confirmPw) {
            flash('error', 'Passwörter stimmen nicht überein.');
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['admin_user_id']]);
            flash('success', 'Passwort wurde geändert.');
        }
    }

    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 600px;">
    <div class="admin-card-header"><h2><i class="fas fa-key"></i> Passwort ändern</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Aktuelles Passwort</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Neues Passwort</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Passwort bestätigen</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Passwort ändern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-info-circle"></i> System-Info</h2></div>
    <div class="admin-card-body">
        <table class="admin-table">
            <tr><td><strong>PHP Version</strong></td><td><?php echo phpversion(); ?></td></tr>
            <tr><td><strong>Datenbank</strong></td><td><?php
                if (DB_DRIVER === 'mysql') {
                    echo 'MariaDB/MySQL ' . $db->query("SELECT VERSION()")->fetchColumn();
                } else {
                    echo 'SQLite ' . $db->query("SELECT sqlite_version()")->fetchColumn();
                }
            ?></td></tr>
            <tr><td><strong>DB-Treiber</strong></td><td><?php echo strtoupper(DB_DRIVER); ?></td></tr>
            <tr><td><strong>Upload Max</strong></td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
            <tr><td><strong>Post Max</strong></td><td><?php echo ini_get('post_max_size'); ?></td></tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
