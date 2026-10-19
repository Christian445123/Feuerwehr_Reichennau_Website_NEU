<?php
/**
 * Minimaler .env-Loader (ohne Composer-Abhängigkeit).
 * Liest KEY=VALUE-Zeilen aus der .env-Datei im Projektroot und
 * stellt sie über env() zur Verfügung.
 */

function loadEnv(): void {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    // Bevorzugt: config/env.local.php, falls vorhanden. Eine .php-Datei wird
    // vom Server immer ausgeführt statt als Text ausgeliefert - selbst wenn
    // der .htaccess-Schutz für Punkt-Dateien (wie .env) auf einem Hoster aus
    // irgendeinem Grund mal nicht greift (z.B. eingeschränktes
    // AllowOverride), bleibt eine .php-Datei sicher (siehe
    // config/secret_key.php, das nach demselben Prinzip funktioniert). Diese
    // Datei existiert nur lokal auf dem Server, liegt nicht in Git und
    // überschreibt bei Bedarf einzelne Werte aus der .env.
    //
    // Format: <?php return ['DB_PASSWORD' => 'enc:...', 'SMTP_PASSWORD' => '...'];
    $localPhpPath = __DIR__ . '/env.local.php';
    if (file_exists($localPhpPath)) {
        $overrides = include $localPhpPath;
        if (is_array($overrides)) {
            foreach ($overrides as $key => $value) {
                $key = (string) $key;
                if (getenv($key) === false) {
                    putenv("$key=$value");
                }
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = (string) $value;
                }
            }
        }
    }

    $path = __DIR__ . '/../.env';
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Umschließende Anführungszeichen entfernen, falls vorhanden
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && $value[-1] === '"') ||
            ($value[0] === "'" && $value[-1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }
        // env.local.php (oben) hat Vorrang - Werte, die von dort schon
        // gesetzt wurden, werden hier nicht überschrieben.
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Umgebungsvariable auslesen mit Fallback-Wert.
 */
function env(string $key, ?string $default = null): ?string {
    loadEnv();
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}
