<?php
/**
 * Hilfswerkzeug: verschlüsselt ein Passwort für die .env-Datei.
 *
 * Nutzung (nur über die Kommandozeile, nicht über den Browser aufrufbar):
 *   php config/encrypt-tool.php "mein-echtes-passwort"
 *
 * Die Ausgabe (beginnt mit "enc:") wird 1:1 als Wert für DB_PASSWORD oder
 * SMTP_PASSWORD in die .env eingetragen, z.B.:
 *   DB_PASSWORD=enc:AbCdEf...
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Dieses Werkzeug ist nur über die Kommandozeile nutzbar.');
}

require_once __DIR__ . '/crypto.php';

$plaintext = $argv[1] ?? null;
if ($plaintext === null || $plaintext === '') {
    fwrite(STDERR, "Bitte das zu verschlüsselnde Passwort als Argument übergeben:\n");
    fwrite(STDERR, "  php config/encrypt-tool.php \"mein-echtes-passwort\"\n");
    exit(1);
}

echo encryptSecret($plaintext) . "\n";
