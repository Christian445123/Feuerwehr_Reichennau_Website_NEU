<?php
/**
 * Sitzung mit sicheren Cookie-Einstellungen starten (statt des nackten
 * session_start()) - verhindert, dass das Session-Cookie per JavaScript
 * ausgelesen werden kann (httponly) und schickt es bei Cross-Site-Anfragen
 * nicht automatisch mit (SameSite=Lax), was auch CSRF zusätzlich erschwert.
 * "secure" wird nur gesetzt, wenn die Seite tatsächlich über HTTPS läuft,
 * damit die lokale Entwicklung über HTTP weiterhin funktioniert.
 */
function secureSessionStart(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
