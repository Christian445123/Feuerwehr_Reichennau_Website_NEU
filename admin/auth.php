<?php
/**
 * Admin - Authentifizierung
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/permissions.php';

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
    if (!empty($_SESSION['admin_must_change_password']) && !isPasswordChangeExempt()) {
        header('Location: change-password.php');
        exit;
    }
}

/**
 * Prüft Benutzername/Passwort. Rückgabe:
 * - 'ok'   Login abgeschlossen, Session vollständig gesetzt
 * - '2fa'  Passwort korrekt, Zwei-Faktor-Code wird noch benötigt
 * - false  Benutzername/Passwort falsch
 */
function login(string $username, string $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password, name, permissions, must_change_password, totp_enabled FROM users WHERE username = ?");
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
    unset($_SESSION['admin_2fa_pending_id']);
    logActivity($db, 'auth.login', 'Erfolgreich angemeldet', $user['id'], $user['name']);
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
