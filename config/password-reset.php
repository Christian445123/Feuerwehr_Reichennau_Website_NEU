<?php
/**
 * "Passwort vergessen"-Funktion für den Admin-Login (siehe
 * admin/forgot-password.php, admin/reset-password.php).
 *
 * Ablauf: Benutzername/E-Mail eingeben -> Token wird erzeugt und als Link
 * per E-Mail verschickt -> Klick auf den Link führt zu reset-password.php,
 * wo ein neues Passwort vergeben werden kann. Es wird nur der Hash des
 * Tokens in der Datenbank gespeichert (wie bei Passwörtern selbst), damit
 * ein Datenbankzugriff allein keinen gültigen Reset-Link ergibt.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mail.php';

const PASSWORD_RESET_TOKEN_TTL_MINUTES = 60;

/**
 * Erzeugt einen neuen Reset-Token für einen Benutzer und gibt das
 * (unverschlüsselte) Token zurück, das in den E-Mail-Link kommt. Bereits
 * bestehende, noch nicht verwendete Tokens desselben Benutzers werden
 * ungültig gemacht, damit niemals mehrere Links gleichzeitig gültig sind.
 */
function createPasswordResetToken(PDO $db, int $userId): string {
    $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")->execute([$userId]);

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTime())->modify('+' . PASSWORD_RESET_TOKEN_TTL_MINUTES . ' minutes')->format('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $tokenHash, $expiresAt]);

    return $token;
}

/**
 * Prüft ein Token aus dem E-Mail-Link. Gibt bei Gültigkeit die Reset- und
 * Benutzerdaten zusammen zurück, sonst null (abgelaufen, schon verwendet
 * oder unbekannt - aus Sicherheitsgründen wird nicht unterschieden, welcher
 * Fall vorliegt).
 */
function findValidPasswordReset(PDO $db, string $token): ?array {
    if ($token === '') {
        return null;
    }
    $tokenHash = hash('sha256', $token);
    $stmt = $db->prepare("
        SELECT pr.id AS reset_id, u.*
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = ? AND pr.used = 0 AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function markPasswordResetUsed(PDO $db, int $resetId): void {
    $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$resetId]);
}

/**
 * Sucht den Benutzer anhand von Benutzername ODER E-Mail-Adresse (das
 * Formular hat nur ein Feld für beides, damit man sich nicht erst erinnern
 * muss, welches man beim Anlegen verwendet hat).
 */
function findUserForPasswordReset(PDO $db, string $usernameOrEmail): ?array {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    return $stmt->fetch() ?: null;
}

/**
 * Baut den vollständigen Link zu reset-password.php, unabhängig davon, von
 * welchem Pfad aus die aktuelle Anfrage kam.
 */
function buildPasswordResetUrl(string $token): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/forgot-password.php'));
    return "$scheme://$host$dir/reset-password.php?token=" . urlencode($token);
}

function sendPasswordResetEmail(string $toEmail, string $toName, string $resetUrl, ?string &$error = null): bool {
    $subject = 'Passwort zurücksetzen - FF Reichenau Admin-Bereich';
    $body = "Hallo $toName,\n\n"
        . "du (oder jemand anderes) hat angefordert, das Passwort für deinen Admin-Zugang der FF-Reichenau-Website zurückzusetzen.\n\n"
        . "Klicke auf folgenden Link, um ein neues Passwort zu vergeben:\n"
        . "$resetUrl\n\n"
        . "Dieser Link ist " . PASSWORD_RESET_TOKEN_TTL_MINUTES . " Minuten lang gültig und kann nur einmal verwendet werden.\n\n"
        . "Falls du das nicht warst, kannst du diese E-Mail einfach ignorieren - es ändert sich nichts an deinem Zugang.";

    return sendMail($toEmail, $toName, $subject, $body, $error);
}
