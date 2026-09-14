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
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
        $_ENV[$key] = $value;
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
