<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/password-reset.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$reset = is_string($token) ? findValidPasswordReset($db, $token) : null;
$error = '';

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkRateLimit($db, 'admin_reset_password', 10, 900)) {
        $error = 'Zu viele Versuche. Bitte versuche es später erneut.';
    } elseif (!verifyCsrf()) {
        $error = 'Ungültiger Sicherheits-Token. Bitte lade die Seite neu und versuche es erneut.';
    } else {
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if (strlen($newPw) < 6) {
            $error = 'Das neue Passwort muss mindestens 6 Zeichen lang sein.';
        } elseif ($newPw !== $confirmPw) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?")->execute([$hash, $reset['id']]);
            markPasswordResetUsed($db, (int) $reset['reset_id']);
            logActivity($db, 'auth.password_reset_completed', $reset['username'], null, 'Öffentliche Website');
            header('Location: login.php?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Passwort vergeben - FF Reichenau Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css?v=<?php echo @filemtime(__DIR__ . '/admin-style.css') ?: time(); ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau" class="login-logo">
                <h1>Neues Passwort vergeben</h1>
                <?php if ($reset): ?>
                    <p>Hallo <?php echo e($reset['name']); ?>, bitte vergib ein neues Passwort.</p>
                <?php endif; ?>
            </div>

            <?php if (!$reset): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Dieser Link ist ungültig, abgelaufen oder wurde schon verwendet. Links sind 60 Minuten lang und nur einmal gültig.
                </div>
                <a href="forgot-password.php" class="btn btn-primary btn-block"><i class="fas fa-rotate-right"></i> Neuen Link anfordern</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> Neues Passwort</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password" autofocus>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Passwort bestätigen</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Passwort festlegen
                    </button>
                </form>
            <?php endif; ?>

            <div class="login-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Zurück zum Login</a>
            </div>
        </div>
    </div>
</body>
</html>
