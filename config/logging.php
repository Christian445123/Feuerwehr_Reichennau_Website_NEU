<?php
/**
 * Aktivitäts-Log, Login-Log, IP-Sperren und Rate-Limiting.
 */

require_once __DIR__ . '/database.php';

/**
 * Ermittelt die Client-IP. X-Forwarded-For wird nur als Fallback genutzt und
 * validiert, damit ein Client den Header nicht beliebig fälschen kann, um
 * sich als andere IP auszugeben (z.B. um eine Sperre zu umgehen).
 */
function getClientIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }
    return $ip !== '' ? $ip : '0.0.0.0';
}

/**
 * Protokolliert eine Aktion im Admin-Bereich. Ohne explizite Angabe werden
 * Benutzer-ID/-Name aus der aktuellen Admin-Session übernommen.
 */
function logActivity(PDO $db, string $action, string $details = '', ?int $userId = null, ?string $userName = null): void {
    if ($userId === null && isset($_SESSION['admin_user_id'])) {
        $userId = (int) $_SESSION['admin_user_id'];
    }
    if ($userName === null) {
        $userName = $_SESSION['admin_user_name'] ?? 'System';
    }
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, user_name, action, details, ip_address) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $userName, $action, $details, getClientIp()]);
}

/**
 * Protokolliert einen Anmeldeversuch. $type ist 'admin' (Admin-Bereich) oder
 * 'site_gate' (Website-Zugangssperre).
 */
function logLoginAttempt(PDO $db, string $type, string $username, bool $success): void {
    $stmt = $db->prepare("INSERT INTO login_log (login_type, username_attempted, success, ip_address, user_agent) VALUES (?,?,?,?,?)");
    $stmt->execute([$type, $username, $success ? 1 : 0, getClientIp(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
}

function isIpBlocked(PDO $db, string $ip): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM blocked_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$ip]);
    return (bool) $stmt->fetchColumn();
}

function blockIp(PDO $db, string $ip, string $reason, string $blockedBy, bool $autoBlocked = false, ?string $expiresAt = null): void {
    $stmt = $db->prepare("INSERT INTO blocked_ips (ip_address, reason, blocked_by, auto_blocked, expires_at) VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_by = VALUES(blocked_by), auto_blocked = VALUES(auto_blocked), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP");
    $stmt->execute([$ip, $reason, $blockedBy, $autoBlocked ? 1 : 0, $expiresAt]);
}

function unblockIp(PDO $db, string $ip): void {
    $stmt = $db->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Rate-Limiting: erlaubt maximal $maxAttempts Aktionen von einer IP für
 * $actionKey innerhalb von $windowSeconds. Gibt false zurück, wenn das
 * Limit überschritten ist (und protokolliert das Ereignis für die
 * Admin-Ansicht). Ist die IP bereits gesperrt, wird sofort false
 * zurückgegeben, ohne einen neuen Treffer zu zählen.
 *
 * $autoBlockAfterMultiple: wird das Limit um mehr als das Vielfache dieser
 * Zahl überschritten (z.B. 3 = mehr als das 3-fache des Limits an
 * Anfragen im selben Fenster), wird die IP automatisch gesperrt.
 */
function checkRateLimit(PDO $db, string $actionKey, int $maxAttempts, int $windowSeconds, ?int $autoBlockAfterMultiple = 3): bool {
    $ip = getClientIp();

    if (isIpBlocked($db, $ip)) {
        return false;
    }

    // Alte Treffer aufräumen, damit die Tabelle nicht unbegrenzt wächst
    $db->exec("DELETE FROM rate_limit_hits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

    $insert = $db->prepare("INSERT INTO rate_limit_hits (action_key, ip_address) VALUES (?, ?)");
    $insert->execute([$actionKey, $ip]);

    $windowSeconds = max(1, $windowSeconds);
    $count = $db->prepare("SELECT COUNT(*) FROM rate_limit_hits WHERE action_key = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL $windowSeconds SECOND)");
    $count->execute([$actionKey, $ip]);
    $hits = (int) $count->fetchColumn();

    if ($hits > $maxAttempts) {
        $autoBlocked = false;
        if ($autoBlockAfterMultiple !== null && $hits > $maxAttempts * $autoBlockAfterMultiple) {
            blockIp($db, $ip, "Automatisch gesperrt: Rate-Limit für \"$actionKey\" wiederholt überschritten", 'System', true);
            $autoBlocked = true;
        }
        $log = $db->prepare("INSERT INTO rate_limit_events (action_key, ip_address, detail, auto_blocked) VALUES (?,?,?,?)");
        $log->execute([$actionKey, $ip, "$hits Anfragen innerhalb von {$windowSeconds}s (Limit: $maxAttempts)", $autoBlocked ? 1 : 0]);
        return false;
    }

    return true;
}
