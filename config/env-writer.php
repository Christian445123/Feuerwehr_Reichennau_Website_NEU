<?php
/**
 * Liest/schreibt config/env.local.php programmatisch - damit Admins
 * Zugangsdaten (DB, SMTP, Discord-Webhooks) über Admin -> Einstellungen
 * ändern können, ohne manuell eine Datei auf dem Server bearbeiten oder
 * Werte selbst verschlüsseln zu müssen (siehe admin/settings.php).
 */

require_once __DIR__ . '/crypto.php';

function getEnvLocalFilePath(): string {
    return __DIR__ . '/env.local.php';
}

/**
 * Liest die aktuell in env.local.php hinterlegten Werte (roh, so wie
 * gespeichert - verschlüsselte Werte bleiben mit "enc:"-Präfix). Gibt ein
 * leeres Array zurück, wenn die Datei noch nicht existiert.
 */
function readEnvLocalFile(): array {
    $path = getEnvLocalFilePath();
    if (!file_exists($path)) {
        return [];
    }
    $values = include $path;
    return is_array($values) ? $values : [];
}

/**
 * Schreibt env.local.php neu - übernimmt bestehende Werte und überschreibt
 * nur die in $updates übergebenen Schlüssel. Nutzt var_export(), damit
 * Sonderzeichen in Passwörtern (Anführungszeichen, Backslashes, ...) nicht
 * versehentlich PHP-Code erzeugen können.
 */
function writeEnvLocalFile(array $updates): bool {
    $current = readEnvLocalFile();
    $merged = array_merge($current, $updates);
    // Leere Werte trotzdem behalten (z.B. SMTP_HOST bewusst leer lassen),
    // nur echte "Schlüssel löschen"-Wünsche (null) werden entfernt.
    foreach ($merged as $key => $value) {
        if ($value === null) {
            unset($merged[$key]);
        }
    }
    ksort($merged);

    $php = "<?php\n"
        . "/**\n"
        . " * Lokale Zugangsdaten - wird NIE in Git eingecheckt (siehe .gitignore).\n"
        . " * Automatisch erzeugt/aktualisiert über Admin -> Einstellungen.\n"
        . " * Hat Vorrang vor der .env (siehe config/env.php).\n"
        . " */\n\n"
        . "return " . var_export($merged, true) . ";\n";

    return file_put_contents(getEnvLocalFilePath(), $php, LOCK_EX) !== false;
}

/**
 * Prüft, ob eine Datenbankverbindung mit den angegebenen Zugangsdaten
 * funktioniert, OHNE die aktuell laufende Verbindung zu verändern - damit
 * ein Tippfehler beim Ändern des DB-Passworts nicht die ganze Website
 * (inkl. Admin-Bereich) aussperrt. Gibt im Fehlerfall die Fehlermeldung
 * zurück, sonst null.
 */
function testDatabaseCredentials(string $host, string $port, string $name, string $user, string $password): ?string {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $testDb = new PDO($dsn, $user, $password, [PDO::ATTR_TIMEOUT => 5]);
        $testDb = null;
        return null;
    } catch (\PDOException $e) {
        return $e->getMessage();
    }
}
