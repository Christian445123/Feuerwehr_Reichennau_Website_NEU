<?php
/**
 * Sicherheits-Header direkt per PHP setzen - zusätzlich zu den gleichen
 * Headern in .htaccess.
 *
 * Grund für die Dopplung: .htaccess wird nur verarbeitet, wenn der
 * Webserver das überhaupt tut. Live-Test gegen die echte Seite hat gezeigt,
 * dass die dort konfigurierten .htaccess-Regeln (Datei-Sperren,
 * Sicherheits-Header) NICHT greifen - vermutlich läuft der Server hinter
 * Cloudflare auf einem Setup, das .htaccess ignoriert oder nur
 * eingeschränkt verarbeitet (z.B. nginx als Origin, oder Apache mit
 * eingeschränktem AllowOverride). Diese PHP-Variante funktioniert dagegen
 * unabhängig vom eingesetzten Webserver, solange die Anfrage überhaupt PHP
 * erreicht - bei einer Anfrage auf eine statische Datei wie .env greift
 * das allerdings nicht (dafür ist config/env.local.php zuständig, siehe
 * config/env.php).
 */
function sendSecurityHeaders(): void {
    static $sent = false;
    if ($sent || headers_sent()) {
        return;
    }
    $sent = true;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.googletagmanager.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
        . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; "
        . "img-src 'self' data: https:; "
        . "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; "
        . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'"
    );
}
