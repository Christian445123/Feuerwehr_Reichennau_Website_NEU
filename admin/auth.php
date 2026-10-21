<?php
/**
 * Admin - Authentifizierung
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/security-headers.php';
require_once __DIR__ . '/permissions.php';

sendSecurityHeaders();
secureSessionStart();

function isLoggedIn(): bool {
    return isset($_SESSION['admin_user_id']);
}

/**
 * Seiten, die auch in einem "eingeloggt, aber noch nicht fertig"-Zustand
 * erreichbar bleiben müssen (erzwungener Passwortwechsel, Logout selbst) -
 * sonst würde requireLogin() eine Endlos-Weiterleitung erzeugen.
 */
function isPasswordChangeExempt(): bool {
    return in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['change-password.php', 'logout.php'], true);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if (isSessionTimedOut()) {
        logout();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
    if (!empty($_SESSION['admin_must_change_password']) && !isPasswordChangeExempt()) {
        header('Location: change-password.php');
        exit;
    }
}

/**
 * Rein optionale, selbst gewählte Sitzungsdauer (Admin -> Mein Konto) -
 * ohne eigene Einstellung (0) hat das keine Wirkung, es gilt weiter das
 * normale Verhalten (Sitzung endet mit dem Browser).
 */
function isSessionTimedOut(): bool {
    $timeoutMinutes = (int) ($_SESSION['admin_session_timeout_minutes'] ?? 0);
    if ($timeoutMinutes <= 0) {
        return false;
    }
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? time());
    return (time() - $lastActivity) > ($timeoutMinutes * 60);
}

/**
 * Prüft Benutzername/Passwort. Rückgabe:
 * - 'ok'   Login abgeschlossen, Session vollständig gesetzt
 * - '2fa'  Passwort korrekt, Zwei-Faktor-Code wird noch benötigt
 * - false  Benutzername/Passwort falsch
 */
function login(string $username, string $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password, name, permissions, must_change_password, totp_enabled, email, notify_on_login, session_timeout_minutes FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (!empty($user['totp_enabled'])) {
            session_regenerate_id(true);
            $_SESSION['admin_2fa_pending_id'] = $user['id'];
            logLoginAttempt($db, 'admin', $username, true);
            return '2fa';
        }
        completeLogin($db, $user, $username);
        return 'ok';
    }
    logLoginAttempt($db, 'admin', $username, false);
    return false;
}

/**
 * Setzt die vollständige Admin-Session (nach Passwort + ggf. 2FA-Code).
 */
function completeLogin(PDO $db, array $user, string $username): void {
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_user_name'] = $user['name'];
    $_SESSION['admin_permissions'] = isProtectedAdminUsername($username)
        ? ['*']
        : (json_decode($user['permissions'] ?? '[]', true) ?: []);
    $_SESSION['admin_must_change_password'] = !empty($user['must_change_password']);
    // Rein optional, pro Benutzer unter "Mein Konto" einschaltbar (siehe
    // admin/account.php) - 0 bedeutet "aus"/Standardverhalten.
    $_SESSION['admin_session_timeout_minutes'] = (int) ($user['session_timeout_minutes'] ?? 0);
    $_SESSION['admin_last_activity'] = time();
    unset($_SESSION['admin_2fa_pending_id']);
    logActivity($db, 'auth.login', 'Erfolgreich angemeldet', $user['id'], $user['name']);

    notifyAdminOfSensitiveAccessBlocks($db);
    sendLoginNotificationIfEnabled($user);
}

/**
 * Optionale E-Mail-Benachrichtigung bei jedem Login (Admin -> Mein Konto,
 * Kästchen "Bei jedem Login per E-Mail benachrichtigen") - rein informativ,
 * damit ein fremder Login auffällt. Ein Fehlschlagen des Mailversands darf
 * den Login selbst nicht verhindern, deshalb wird der Fehler nur geloggt.
 */
function sendLoginNotificationIfEnabled(array $user): void {
    if (empty($user['notify_on_login']) || empty($user['email'])) {
        return;
    }
    require_once __DIR__ . '/../config/mail.php';
    $ip = getClientIp();
    $when = date('d.m.Y \u\m H:i \U\h\r');
    $body = "Hallo {$user['name']},\n\nes gab soeben eine erfolgreiche Anmeldung an deinem Admin-Konto der FF-Reichenau-Website:\n\nZeitpunkt: $when\nIP-Adresse: $ip\n\nWarst du das nicht selbst, ändere umgehend dein Passwort und informiere einen Administrator mit Benutzerverwaltungsrechten.\n\n(Diese Benachrichtigung kannst du unter Admin -> Mein Konto wieder abschalten.)";
    $mailError = null;
    if (!sendMail($user['email'], $user['name'], 'Login-Benachrichtigung: Anmeldung an deinem Admin-Konto', $body, $mailError)) {
        error_log('Login-Benachrichtigung konnte nicht versendet werden (User-ID ' . $user['id'] . '): ' . $mailError);
    }
}

/**
 * Zeigt beim Login einmalig eine Warnung, wenn seit dem letzten Login
 * jemand wegen eines Zugriffsversuchs auf eine sensible Datei (.env, .git,
 * ...) automatisch gesperrt wurde (siehe config/security-block.php). Jede
 * Sperre wird nur einmal angezeigt (admin_notified wird danach gesetzt).
 */
function notifyAdminOfSensitiveAccessBlocks(PDO $db): void {
    $rows = $db->query("SELECT ip_address, reason, created_at FROM blocked_ips WHERE admin_notified = 0 ORDER BY created_at DESC")->fetchAll();
    if (empty($rows)) {
        return;
    }
    foreach ($rows as $row) {
        $when = date('d.m.Y \u\m H:i', strtotime($row['created_at']));
        flash('warning', "🚨 Sicherheitshinweis: Die IP {$row['ip_address']} hat am $when versucht, auf eine geschützte Datei zuzugreifen, und wurde automatisch gesperrt. Grund: {$row['reason']}");
    }
    $db->exec("UPDATE blocked_ips SET admin_notified = 1 WHERE admin_notified = 0");
}

/**
 * Erzeugt ein zufälliges, gut lesbares Passwort (ohne leicht verwechselbare
 * Zeichen wie 0/O oder 1/l/I) mit 10 bis 12 Zeichen Länge.
 */
function generateRandomPassword(): string {
    $length = random_int(10, 12);
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%&*';
    $max = strlen($chars) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

/**
 * Entfernt Umlaute/Sonderzeichen für einen sauberen Benutzernamen.
 */
function transliterateForUsername(string $text): string {
    $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss'];
    $text = strtr($text, $map);
    return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $text));
}

/**
 * Generiert einen Benutzernamen aus Nachname+Vorname (in dieser Reihenfolge)
 * und hängt bei Kollisionen eine fortlaufende Nummer an, damit er eindeutig
 * bleibt (username ist UNIQUE).
 */
function generateUsernameFromName(PDO $db, string $firstname, string $lastname, ?int $excludeId = null): string {
    $base = transliterateForUsername($lastname) . transliterateForUsername($firstname);
    if ($base === '') {
        $base = 'benutzer';
    }
    $username = $base;
    $suffix = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() === 0) {
            return $username;
        }
        $username = $base . $suffix;
        $suffix++;
    }
}

function logout(): void {
    if (isLoggedIn()) {
        logActivity(getDB(), 'auth.logout', 'Abgemeldet');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrfToken(), $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
