<?php
/**
 * Automatische Sperre + Spott-Seite für Zugriffsversuche auf sensible
 * Dateien (.env, .git, ...). Wird aufgerufen, sobald so ein Pfad erkannt
 * wird (siehe index.php) - sperrt die IP sofort für die ganze Website
 * (nicht nur die eine Datei) und merkt die Sperre für eine Meldung beim
 * nächsten Admin-Login vor (siehe admin/auth.php, completeLogin()).
 */

require_once __DIR__ . '/logging.php';

function blockForSensitiveAccess(string $attemptedPath): void {
    $db = getDB();
    $ip = getClientIp();

    if (!isIpBlocked($db, $ip)) {
        blockIp(
            $db,
            $ip,
            "Automatisch gesperrt: Zugriffsversuch auf sensible Datei \"$attemptedPath\"",
            'System (automatisch)',
            true,
            null,
            true // Beim nächsten Admin-Login anzeigen
        );
        logActivity(
            $db,
            'security.sensitive_file_blocked',
            "IP $ip hat versucht, auf \"$attemptedPath\" zuzugreifen und wurde automatisch gesperrt.",
            0,
            'System'
        );
    }

    renderHackerBlockedPage();
    exit;
}

function renderHackerBlockedPage(): void {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚒 Erwischt! - FF Reichenau</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #1a0000 0%, #1f1f1f 50%, #1a0a00 100%);
            overflow: hidden;
            position: relative;
        }
        /* Blaulicht-Flackern im Hintergrund */
        body::before, body::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            animation: siren 1.4s infinite;
        }
        body::before { background: radial-gradient(circle at 15% 20%, rgba(213,0,28,0.35), transparent 55%); animation-delay: 0s; }
        body::after { background: radial-gradient(circle at 85% 80%, rgba(0,90,255,0.3), transparent 55%); animation-delay: 0.7s; }
        @keyframes siren { 0%, 100% { opacity: 0; } 50% { opacity: 1; } }

        .card {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 18px;
            max-width: 520px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0,0,0,0.5);
            text-align: center;
        }
        .stripe {
            height: 10px;
            background: repeating-linear-gradient(135deg, #d5001c 0px, #d5001c 16px, #1a1a1a 16px, #1a1a1a 32px);
        }
        .body { padding: 38px 36px 34px; }
        .siren-icon {
            font-size: 3.4rem;
            color: #d5001c;
            margin-bottom: 14px;
            animation: shake 0.4s infinite;
        }
        @keyframes shake { 0%,100% { transform: rotate(-6deg); } 50% { transform: rotate(6deg); } }
        h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            text-transform: uppercase;
            margin: 0 0 10px;
            color: #1a1a1a;
        }
        .lead { color: #495057; font-size: 1rem; line-height: 1.6; margin: 0 0 24px; }
        .report {
            text-align: left;
            background: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 10px;
            padding: 16px 18px;
            font-size: 0.85rem;
            color: #495057;
            margin-bottom: 24px;
            font-family: 'Courier New', monospace;
        }
        .report strong { color: #1a1a1a; }
        .report-row { display: flex; justify-content: space-between; gap: 12px; padding: 3px 0; }
        .footer-note { font-size: 0.82rem; color: #868e96; border-top: 1px solid #e9ecef; padding-top: 16px; margin-top: 4px; }
        .footer-note a { color: #d5001c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="stripe"></div>
        <div class="body">
            <div class="siren-icon"><i class="fas fa-truck-fire"></i></div>
            <h1>Volltreffer! 🧯</h1>
            <p class="lead">
                Die Freiwillige Feuerwehr Reichenau führt einen sehr effizienten Löschangriff durch –
                und zwar gerade auf <strong>dich</strong>. Dein Zugriffsversuch wurde erkannt, bekämpft
                und gelöscht. Im wahrsten Sinne des Wortes.
            </p>
            <div class="report">
                <div class="report-row"><span>Einsatzstichwort:</span> <strong>VERDÄCHTIGER ZUGRIFF</strong></div>
                <div class="report-row"><span>Status:</span> <strong>Gelöscht ✅</strong></div>
                <div class="report-row"><span>Einsatzdauer:</span> <strong>0,02 Sekunden</strong></div>
                <div class="report-row"><span>Nachlöscharbeiten:</span> <strong>IP dauerhaft gesperrt</strong></div>
            </div>
            <p class="footer-note">
                Kein Hacker, sondern nur neugierig und aus Versehen hier gelandet?
                Schreib uns einfach: <a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a>
            </p>
        </div>
    </div>
</body>
</html>
    <?php
}
