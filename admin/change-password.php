<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/logging.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Diese Seite ist nur für den erzwungenen Passwortwechsel nach dem ersten
// Login gedacht - wer freiwillig sein Passwort ändern will, macht das über
// die eigene Konto-Seite.
if (empty($_SESSION['admin_must_change_password'])) {
    header('Location: account.php');
    exit;
}

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Ungültiger Sicherheits-Token. Bitte lade die Seite neu.';
    } else {
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        if (strlen($newPw) < 6) {
            $error = 'Das neue Passwort muss mindestens 6 Zeichen lang sein.';
        } elseif ($newPw !== $confirmPw) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['admin_user_id']]);
            unset($_SESSION['admin_must_change_password']);
            logActivity($db, 'auth.password_change_forced', 'Passwort nach erstem Login geändert');
            header('Location: index.php');
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
    <title>Passwort ändern - FF Reichenau Admin</title>
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
                <h1>Neues Passwort erforderlich</h1>
                <p>Aus Sicherheitsgründen musst du bei diesem ersten Login ein eigenes Passwort vergeben, bevor es weitergeht.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <?php echo csrfField(); ?>
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
        </div>
    </div>
</body>
</html>
