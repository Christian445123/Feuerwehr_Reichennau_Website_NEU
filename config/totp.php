<?php
/**
 * Zeitbasierte Einmalpasswörter (TOTP, RFC 6238) für die optionale
 * Zwei-Faktor-Authentifizierung im Admin-Bereich. Komplett von Hand
 * implementiert (kein Composer/Vendor-Paket im Projekt), kompatibel mit
 * Google Authenticator, Microsoft Authenticator, Authy usw.
 */

function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $encoded .= $alphabet[bindec($chunk)];
    }
    return $encoded;
}

function base32Decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $data));
    $bits = '';
    foreach (str_split($data) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) < 8) continue;
        $bytes .= chr(bindec($byte));
    }
    return $bytes;
}

/**
 * Erzeugt ein neues, zufälliges Base32-Secret für einen Benutzer.
 */
function generateTotpSecret(): string {
    return base32Encode(random_bytes(20));
}

/**
 * Berechnet den 6-stelligen TOTP-Code für ein Secret zu einem Zeitpunkt
 * (Standard: jetzt), 30-Sekunden-Schritte wie bei allen gängigen Apps.
 */
function getTotpCode(string $base32Secret, ?int $timestamp = null): string {
    $key = base32Decode($base32Secret);
    $counter = intdiv($timestamp ?? time(), 30);
    $binCounter = pack('N2', 0, $counter);
    $hash = hash_hmac('sha1', $binCounter, $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8)
        | (ord($hash[$offset + 3]) & 0xFF);
    return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
}

/**
 * Prüft einen vom Benutzer eingegebenen Code gegen das Secret, mit etwas
 * Toleranz für leichte Uhr-Abweichungen (±1 Zeitfenster = ±30s).
 */
function verifyTotpCode(string $base32Secret, string $code, int $window = 1): bool {
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(getTotpCode($base32Secret, $now + ($i * 30)), $code)) {
            return true;
        }
    }
    return false;
}

/**
 * otpauth://-URI zum Einscannen (QR-Code wird rein clientseitig im Browser
 * aus dieser URI gerendert, das Secret verlässt dabei nie den Server an
 * Dritte).
 */
function getTotpUri(string $base32Secret, string $accountName, string $issuer = 'FF Reichenau Admin'): string {
    $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
    return 'otpauth://totp/' . $label
        . '?secret=' . $base32Secret
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}
