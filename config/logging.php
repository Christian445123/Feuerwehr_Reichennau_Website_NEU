<?php
/**
 * Aktivitäts-Log, Login-Log, IP-Sperren, Rate-Limiting und Discord-Meldungen.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/env.php';

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
 * Sendet einen Discord-Embed an einen Webhook. Schlägt niemals sichtbar fehl
 * (kein Discord konfiguriert / Discord nicht erreichbar darf die Website nie
 * beeinträchtigen) und hat ein kurzes Timeout, damit Admin-Aktionen nicht
 * spürbar verzögert werden.
 */
function sendDiscordWebhook(string $webhookUrl, array $embed): void {
    if ($webhookUrl === '' || !function_exists('curl_init')) {
        return;
    }

    $payload = json_encode(['embeds' => [$embed]], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return;
    }

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 3,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}

function discordActivityWebhookUrl(): string {
    return env('DISCORD_WEBHOOK_ACTIVITY', '') ?? '';
}

function discordLoginWebhookUrl(): string {
    return env('DISCORD_WEBHOOK_LOGIN', '') ?? '';
}

function discordRateLimitWebhookUrl(): string {
    return env('DISCORD_WEBHOOK_RATE_LIMIT', '') ?? '';
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
    $ip = getClientIp();
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, user_name, action, details, ip_address) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $userName, $action, $details, $ip]);

    sendDiscordWebhook(discordActivityWebhookUrl(), [
        'title' => '📋 Aktivität im Admin-Bereich',
        'color' => 3447003,
        'fields' => array_filter([
            ['name' => 'Benutzer', 'value' => $userName, 'inline' => true],
            ['name' => 'Aktion', 'value' => "`$action`", 'inline' => true],
            ['name' => 'IP-Adresse', 'value' => $ip, 'inline' => true],
            $details !== '' ? ['name' => 'Details', 'value' => mb_substr($details, 0, 1000), 'inline' => false] : null,
        ]),
        'timestamp' => date('c'),
    ]);
}

/**
 * Protokolliert einen Anmeldeversuch. $type ist 'admin' (Admin-Bereich) oder
 * 'site_gate' (Website-Zugangssperre).
 */
function logLoginAttempt(PDO $db, string $type, string $username, bool $success): void {
    $ip = getClientIp();
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $db->prepare("INSERT INTO login_log (login_type, username_attempted, success, ip_address, user_agent) VALUES (?,?,?,?,?)");
    $stmt->execute([$type, $username, $success ? 1 : 0, $ip, $userAgent]);

    $typeLabel = $type === 'admin' ? 'Admin-Bereich' : 'Website-Zugangssperre';
    sendDiscordWebhook(discordLoginWebhookUrl(), [
        'title' => $success ? '🔓 Erfolgreiche Anmeldung' : '⛔ Fehlgeschlagene Anmeldung',
        'color' => $success ? 3066993 : 15158332,
        'fields' => array_filter([
            ['name' => 'Bereich', 'value' => $typeLabel, 'inline' => true],
            $username !== '' ? ['name' => 'Benutzername', 'value' => $username, 'inline' => true] : null,
            ['name' => 'IP-Adresse', 'value' => $ip, 'inline' => true],
        ]),
        'timestamp' => date('c'),
    ]);
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

    if ($autoBlocked) {
        sendDiscordWebhook(discordRateLimitWebhookUrl(), [
            'title' => '🚫 IP automatisch gesperrt',
            'color' => 10038562,
            'fields' => [
                ['name' => 'IP-Adresse', 'value' => $ip, 'inline' => true],
                ['name' => 'Grund', 'value' => $reason, 'inline' => false],
            ],
            'timestamp' => date('c'),
        ]);
    }
}

function unblockIp(PDO $db, string $ip): void {
    $stmt = $db->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Rate-Limiting: erlaubt maximal $maxAttempts Aktionen von einer IP für
 * $actionKey innerhalb von $windowSeconds. Gibt false zurück, wenn das
 * Limit überschritten ist (und protokolliert das Ereignis für die
 * Admin-Ansicht und Discord). Ist die IP bereits gesperrt, wird sofort
 * false zurückgegeben, ohne einen neuen Treffer zu zählen.
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
        $detail = "$hits Anfragen innerhalb von {$windowSeconds}s (Limit: $maxAttempts)";
        $log = $db->prepare("INSERT INTO rate_limit_events (action_key, ip_address, detail, auto_blocked) VALUES (?,?,?,?)");
        $log->execute([$actionKey, $ip, $detail, $autoBlocked ? 1 : 0]);

        sendDiscordWebhook(discordRateLimitWebhookUrl(), [
            'title' => $autoBlocked ? '🚫 Rate-Limit überschritten (IP gesperrt)' : '⚠️ Rate-Limit überschritten',
            'color' => $autoBlocked ? 10038562 : 15105570,
            'fields' => [
                ['name' => 'Aktion', 'value' => "`$actionKey`", 'inline' => true],
                ['name' => 'IP-Adresse', 'value' => $ip, 'inline' => true],
                ['name' => 'Detail', 'value' => $detail, 'inline' => false],
            ],
            'timestamp' => date('c'),
        ]);

        return false;
    }

    return true;
}
