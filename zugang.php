<?php
/**
 * Zugangssperre für die noch nicht offizielle Website.
 */
require_once __DIR__ . '/config/gate.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $hash = getSitePasswordHash();

    if ($hash !== '' && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['site_access_granted'] = true;

        $redirect = $_SESSION['site_access_redirect'] ?? 'index.php';
        unset($_SESSION['site_access_redirect']);

        // Nur interne, relative Ziele zulassen (Schutz vor Open-Redirect)
        if (!is_string($redirect) || $redirect === '' || $redirect[0] !== '/' || str_starts_with($redirect, '//')) {
            $redirect = 'index.php';
        } else {
            $redirect = ltrim($redirect, '/');
        }

        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Falsches Passwort. Bitte versuchen Sie es erneut.';
}

if (siteAccessGranted()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugang erforderlich - FF Reichenau</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .lock-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }
        .lock-card {
            background: #fff;
            border-radius: var(--radius-xl, 16px);
            padding: 48px 40px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }
        .lock-card img {
            width: 80px;
            margin-bottom: 20px;
        }
        .lock-card h1 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: var(--color-dark, #1a1a2e);
        }
        .lock-card p {
            color: var(--color-gray-600, #6c757d);
            font-size: 0.95rem;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .lock-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .lock-form input[type="password"] {
            padding: 14px 16px;
            border: 1px solid var(--color-gray-300, #ced4da);
            border-radius: 10px;
            font-size: 1rem;
            text-align: center;
        }
        .lock-form button {
            padding: 14px 16px;
            border: none;
            border-radius: 50px;
            background: var(--color-primary, #c0392b);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .lock-form button:hover {
            filter: brightness(1.08);
        }
        .lock-error {
            color: #c0392b;
            background: rgba(192, 57, 43, 0.08);
            border: 1px solid rgba(192, 57, 43, 0.25);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="lock-screen">
        <div class="lock-card">
            <img src="assets/images/logo.png" alt="FF Reichenau Logo">
            <h1>Interner Vorschau-Zugang</h1>
            <p>Diese Website befindet sich im Aufbau und ist noch nicht offiziell. Der Zugriff ist derzeit nur mit Passwort möglich.</p>
            <?php if ($error): ?>
                <div class="lock-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="lock-form">
                <input type="password" name="password" placeholder="Passwort" required autofocus>
                <button type="submit"><i class="fas fa-unlock"></i> Zugang freischalten</button>
            </form>
        </div>
    </div>
</body>
</html>
