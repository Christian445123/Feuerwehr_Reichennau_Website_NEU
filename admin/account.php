<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/crypto.php';
require_once __DIR__ . '/../config/totp.php';
requireLogin();

$pageTitle = 'Mein Konto';
$activePage = 'account';

$db = getDB();
$userId = (int) $_SESSION['admin_user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me) {
    logout();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: account.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($firstname === '' || $lastname === '') {
            flash('error', 'Vorname und Nachname sind erforderlich.');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Bitte gib eine gültige E-Mail-Adresse an.');
        } else {
            $name = trim($firstname . ' ' . $lastname);
            $stmt = $db->prepare("UPDATE users SET firstname=?, lastname=?, name=?, email=? WHERE id=?");
            $stmt->execute([$firstname, $lastname, $name, $email, $userId]);
            $_SESSION['admin_user_name'] = $name;
            logActivity($db, 'account.update_profile');
            flash('success', 'Deine Daten wurden aktualisiert.');
        }
        header('Location: account.php');
        exit;
    }

    if ($action === 'change_password') {
        $currentPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPw, $me['password'])) {
            flash('error', 'Aktuelles Passwort ist falsch.');
        } elseif (strlen($newPw) < 6) {
            flash('error', 'Neues Passwort muss mindestens 6 Zeichen lang sein.');
        } elseif ($newPw !== $confirmPw) {
            flash('error', 'Passwörter stimmen nicht überein.');
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            unset($_SESSION['admin_must_change_password']);
            logActivity($db, 'account.change_password');
            flash('success', 'Passwort wurde geändert.');
        }
        header('Location: account.php');
        exit;
    }

    if ($action === 'enable_2fa_start') {
        $_SESSION['pending_2fa_secret'] = generateTotpSecret();
        header('Location: account.php#twofa');
        exit;
    }

    if ($action === 'enable_2fa_cancel') {
        unset($_SESSION['pending_2fa_secret']);
        header('Location: account.php');
        exit;
    }

    if ($action === 'enable_2fa_confirm') {
        $secret = $_SESSION['pending_2fa_secret'] ?? '';
        $code = $_POST['code'] ?? '';

        if ($secret === '') {
            flash('error', 'Bitte starte die Einrichtung erneut.');
        } elseif (!verifyTotpCode($secret, $code)) {
            flash('error', 'Der Code ist ungültig. Bitte versuche es erneut.');
        } else {
            $stmt = $db->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?");
            $stmt->execute([encryptSecret($secret), $userId]);
            unset($_SESSION['pending_2fa_secret']);
            logActivity($db, 'account.2fa_enable');
            flash('success', 'Zwei-Faktor-Authentifizierung wurde aktiviert.');
            header('Location: account.php');
            exit;
        }
        header('Location: account.php#twofa');
        exit;
    }

    if ($action === 'disable_2fa') {
        $currentPw = $_POST['current_password_2fa'] ?? '';
        if (!password_verify($currentPw, $me['password'])) {
            flash('error', 'Aktuelles Passwort ist falsch.');
        } else {
            $stmt = $db->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity($db, 'account.2fa_disable');
            flash('success', 'Zwei-Faktor-Authentifizierung wurde deaktiviert.');
        }
        header('Location: account.php#twofa');
        exit;
    }

    header('Location: account.php');
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';

$pendingSecret = $_SESSION['pending_2fa_secret'] ?? null;
?>

<div class="admin-card" style="max-width: 600px;">
    <div class="admin-card-header"><h2><i class="fas fa-user"></i> Meine Daten</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">Vorname</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo e($me['firstname'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Nachname</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo e($me['lastname'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" value="<?php echo e($me['username']); ?>" disabled>
                <p class="form-hint">Der Benutzername kann nur von einem Administrator mit Benutzerverwaltungsrechten geändert werden.</p>
            </div>

            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" value="<?php echo e($me['email'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-key"></i> Passwort ändern</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Aktuelles Passwort</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="new_password">Neues Passwort</label>
                <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Passwort bestätigen</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Passwort ändern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="twofa">
    <div class="admin-card-header"><h2><i class="fas fa-shield-halved"></i> Zwei-Faktor-Authentifizierung</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Optionaler zusätzlicher Schutz für dein Konto: Nach der Passworteingabe wird beim Login zusätzlich ein 6-stelliger Code aus einer Authenticator-App (z.B. Google Authenticator, Microsoft Authenticator, Authy) verlangt. Die Aktivierung ist freiwillig.</p>

        <?php if (!empty($me['totp_enabled'])): ?>
            <div style="margin-bottom: 20px; padding: 14px 16px; background: rgba(39,174,96,0.08); border-radius: var(--radius); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.3rem;"></i>
                <strong>Zwei-Faktor-Authentifizierung ist aktiv.</strong>
            </div>
            <form method="POST" class="admin-form" onsubmit="return confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?');">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="disable_2fa">
                <div class="form-group">
                    <label for="current_password_2fa">Aktuelles Passwort zur Bestätigung</label>
                    <input type="password" id="current_password_2fa" name="current_password_2fa" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-shield-halved"></i> Deaktivieren
                </button>
            </form>
        <?php elseif ($pendingSecret): ?>
            <p style="margin-bottom: 12px;">1. Scanne den QR-Code mit deiner Authenticator-App (oder gib das Secret manuell ein):</p>
            <div id="qrcode" style="margin-bottom: 12px;"></div>
            <p style="font-family: monospace; background: var(--gray-100, #f4f4f4); padding: 8px 12px; border-radius: var(--radius-sm); display: inline-block; margin-bottom: 20px;"><?php echo e($pendingSecret); ?></p>

            <form method="POST" class="admin-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="enable_2fa_confirm">
                <p style="margin-bottom: 8px;">2. Gib den 6-stelligen Code aus der App ein, um die Aktivierung zu bestätigen:</p>
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Aktivieren</button>
            </form>
            <form method="POST" style="margin-top: 8px;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="enable_2fa_cancel">
                <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Abbrechen</button>
            </form>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
            new QRCode(document.getElementById('qrcode'), {
                text: <?php echo json_encode(getTotpUri($pendingSecret, $me['username']), JSON_UNESCAPED_SLASHES); ?>,
                width: 180,
                height: 180
            });
            </script>
        <?php else: ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="enable_2fa_start">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-shield-halved"></i> Zwei-Faktor-Authentifizierung einrichten
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
