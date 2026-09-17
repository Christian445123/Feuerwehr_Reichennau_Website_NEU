<?php
/**
 * Wartungsmodus-Seite mit Zugangssperre für die noch nicht offizielle Website.
 */
require_once __DIR__ . '/config/gate.php';
require_once __DIR__ . '/config/logging.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $db = getDB();

    if (!checkRateLimit($db, 'site_gate', 10, 600)) {
        $error = 'Zu viele Versuche. Bitte versuchen Sie es später erneut.';
    } else {
        $hash = getSitePasswordHash();

        if ($hash !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['site_access_granted'] = true;
            logLoginAttempt($db, 'site_gate', '', true);

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

        logLoginAttempt($db, 'site_gate', '', false);
        $error = 'Falsches Passwort. Bitte versuchen Sie es erneut.';
    }
}

if (!isMaintenanceModeEnabled() || siteAccessGranted()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wartungsmodus - FF Reichenau</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/style.css') ?: time(); ?>">
    <style>
        .lock-screen {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #181818 0%, #1f1f1f 50%, #141414 100%);
            overflow: hidden;
        }
        /* Dezente diagonale Akzentstreifen im Hintergrund, wie im
           Organigramm/Referenzdesign - sorgt für Feuerwehr-Wiedererkennung
           auch auf dieser reinen Systemseite. */
        .lock-screen::before,
        .lock-screen::after {
            content: '';
            position: absolute;
            width: 260px;
            height: 46px;
            transform: skewX(-25deg);
            pointer-events: none;
        }
        .lock-screen::before {
            top: -40px;
            right: -60px;
            background: rgba(213, 0, 28, 0.35);
        }
        .lock-screen::after {
            bottom: -40px;
            left: -60px;
            background: rgba(255, 183, 0, 0.18);
        }
        .lock-card {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: var(--radius-xl, 16px);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }
        /* Warnstreifen wie ein Absperrband - verbindet "Baustelle/Wartung"
           mit dem Erscheinungsbild eines Einsatzfahrzeugs. */
        .maintenance-stripe {
            height: 10px;
            background: repeating-linear-gradient(
                135deg,
                var(--color-primary, #d5001c) 0px, var(--color-primary, #d5001c) 16px,
                #1a1a1a 16px, #1a1a1a 32px
            );
        }
        .lock-card-body {
            padding: 36px 40px 40px;
            text-align: center;
        }
        .maintenance-logo {
            width: 76px;
            margin-bottom: 10px;
        }
        .maintenance-org {
            font-family: 'Barlow Condensed', var(--font-family, sans-serif);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--color-gray-500, #adb5bd);
            margin-bottom: 22px;
        }
        .maintenance-icon-wrap {
            position: relative;
            width: 92px;
            height: 92px;
            margin: 0 auto 22px;
        }
        .maintenance-gear {
            font-size: 4.6rem;
            color: var(--color-primary, #d5001c);
            display: inline-block;
            animation: maintenance-spin 3.2s linear infinite;
        }
        .maintenance-badge {
            position: absolute;
            bottom: -2px;
            right: -10px;
            font-size: 1.4rem;
            color: #ff7a00;
            background: #fff;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.18);
        }
        @keyframes maintenance-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .lock-card h1 {
            font-family: 'Barlow Condensed', var(--font-family, sans-serif);
            font-size: 1.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 12px;
            color: var(--color-dark, #1a1a1a);
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
            background: var(--color-primary, #d5001c);
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
            color: #d5001c;
            background: rgba(213, 0, 28, 0.08);
            border: 1px solid rgba(213, 0, 28, 0.25);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }
        .lock-footer {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid var(--color-gray-200, #e9ecef);
            font-size: 0.78rem;
            color: var(--color-gray-500, #adb5bd);
        }
    </style>
</head>
<body>
    <div class="lock-screen">
        <div class="lock-card">
            <div class="maintenance-stripe"></div>
            <div class="lock-card-body">
                <img src="assets/images/logo.png?v=2" alt="FF Reichenau Wappen" class="maintenance-logo">
                <p class="maintenance-org">Freiwillige Feuerwehr Reichenau</p>

                <div class="maintenance-icon-wrap">
                    <i class="fas fa-gear maintenance-gear"></i>
                    <i class="fas fa-fire maintenance-badge"></i>
                </div>
                <h1>Wartungsmodus</h1>
                <p>Die Website ist in Wartung.<br>Solltest du dennoch darauf zugreifen wollen, bitte Passwort eingeben.</p>
                <?php if ($error): ?>
                    <div class="lock-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" class="lock-form">
                    <input type="password" name="password" placeholder="Passwort" required autofocus>
                    <button type="submit"><i class="fas fa-unlock"></i> Zugang freischalten</button>
                </form>
                <div class="lock-footer">Innsbruck Stadt &middot; Rossaugasse 4</div>
            </div>
        </div>
    </div>
</body>
</html>
