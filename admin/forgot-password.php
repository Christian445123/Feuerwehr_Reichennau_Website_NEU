<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/password-reset.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $db = getDB();

    if (!verifyCsrf()) {
        $error = 'Ungültiger Sicherheits-Token. Bitte Seite neu laden und erneut versuchen.';
    } elseif (!checkRateLimit($db, 'admin_forgot_password', 5, 900)) {
        $error = 'Zu viele Anfragen. Bitte versuche es später erneut.';
    } elseif ($identifier === '') {
        $error = 'Bitte gib deinen Benutzernamen oder deine E-Mail-Adresse ein.';
    } else {
        // Antwort ist absichtlich IMMER dieselbe, egal ob das Konto
        // existiert oder eine E-Mail-Adresse hinterlegt ist - sonst könnte
        // man über die Formular-Antwort herausfinden, welche Benutzernamen/
        // E-Mails es im System gibt.
        $sent = true;
        $user = findUserForPasswordReset($db, $identifier);
        if ($user && trim($user['email'] ?? '') !== '') {
            $token = createPasswordResetToken($db, (int) $user['id']);
            $resetUrl = buildPasswordResetUrl($token);
            $mailError = null;
            try {
                $mailSent = sendPasswordResetEmail($user['email'], $user['name'], $resetUrl, $mailError);
            } catch (\Throwable $e) {
                error_log('Passwort-Reset-Mailversand fehlgeschlagen: ' . $e->getMessage());
                $mailSent = false;
            }
            logActivity(
                $db,
                $mailSent ? 'auth.password_reset_requested' : 'auth.password_reset_mail_failed',
                $user['username'] . ($mailSent ? '' : ' - ' . ($mailError ?? 'unbekannter Fehler')),
                null,
                'Öffentliche Website'
            );
        } elseif ($user) {
            // Konto gefunden, aber keine E-Mail hinterlegt - kann nichts
            // verschickt werden. Fürs Admin-Protokoll festhalten, damit das
            // nicht unbemerkt bleibt.
            logActivity($db, 'auth.password_reset_no_email', $user['username'], null, 'Öffentliche Website');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen - FF Reichenau Admin</title>
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
                <h1>Passwort vergessen</h1>
                <p>Gib deinen Benutzernamen oder deine E-Mail-Adresse ein, wir schicken dir einen Link zum Zurücksetzen.</p>
            </div>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Falls ein Konto mit diesen Angaben existiert und eine E-Mail-Adresse hinterlegt ist, wurde soeben eine E-Mail mit einem Link zum Zurücksetzen verschickt. Bitte auch im Spam-Ordner nachsehen.
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!$sent): ?>
            <form method="POST" class="login-form">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="identifier"><i class="fas fa-user"></i> Benutzername oder E-Mail</label>
                    <input type="text" id="identifier" name="identifier" required autofocus
                           value="<?php echo e($_POST['identifier'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Link anfordern
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
