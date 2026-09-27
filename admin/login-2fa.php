<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/totp.php';
require_once __DIR__ . '/../config/crypto.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_2fa_pending_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['cancel'])) {
    unset($_SESSION['admin_2fa_pending_id']);
    header('Location: login.php');
    exit;
}

$db = getDB();
$pendingId = (int) $_SESSION['admin_2fa_pending_id'];
$stmt = $db->prepare("SELECT id, username, name, permissions, must_change_password, totp_secret, totp_enabled FROM users WHERE id = ?");
$stmt->execute([$pendingId]);
$user = $stmt->fetch();

if (!$user || empty($user['totp_enabled']) || empty($user['totp_secret'])) {
    unset($_SESSION['admin_2fa_pending_id']);
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (!verifyCsrf()) {
        $error = 'Ungültiger Sicherheits-Token. Bitte lade die Seite neu.';
    } elseif (!checkRateLimit($db, 'admin_2fa', 8, 600)) {
        $error = 'Zu viele Versuche. Bitte versuche es später erneut.';
    } elseif (verifyTotpCode(decryptSecret($user['totp_secret']), $code)) {
        completeLogin($db, $user, $user['username']);
        header('Location: index.php');
        exit;
    } else {
        logLoginAttempt($db, 'admin', $user['username'], false);
        $error = 'Der Code ist ungültig oder abgelaufen.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestätigungscode - FF Reichenau Admin</title>
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
                <h1>Bestätigungscode</h1>
                <p>Öffne deine Authenticator-App und gib den 6-stelligen Code ein.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="code"><i class="fas fa-shield-halved"></i> Code</label>
                    <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-check"></i> Bestätigen
                </button>
            </form>

            <div class="login-footer">
                <a href="login-2fa.php?cancel=1"><i class="fas fa-arrow-left"></i> Abbrechen</a>
            </div>
        </div>
    </div>
</body>
</html>
