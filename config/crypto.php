<?php
/**
 * Verschlüsselung für sensible .env-Werte (z.B. Datenbank- und SMTP-Passwort).
 *
 * Der Schlüssel liegt bewusst NICHT in der .env (die genau die Datei ist, die
 * einmal versehentlich direkt abrufbar war), sondern in einer eigenen PHP-Datei
 * (config/secret_key.php). PHP-Dateien werden vom Server ausgeführt statt als
 * Klartext ausgeliefert - selbst wenn der .htaccess-Schutz einmal versagen
 * sollte, bleibt der Schlüssel dadurch sicher. Die Datei wird beim ersten
 * Aufruf automatisch erzeugt und ist in .gitignore ausgeschlossen.
 */

function getEncryptionKey(): string {
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $keyFile = __DIR__ . '/secret_key.php';
    if (!file_exists($keyFile)) {
        $newKey = bin2hex(random_bytes(32));
        $content = "<?php\n"
            . "// Automatisch generierter Schlüssel zur Verschlüsselung sensibler .env-Werte\n"
            . "// (Datenbank-/SMTP-Passwort). NICHT in Git einchecken, NICHT weitergeben und\n"
            . "// NICHT löschen - sonst können verschlüsselte Werte in der .env nicht mehr\n"
            . "// entschlüsselt werden.\n"
            . "return '" . $newKey . "';\n";
        file_put_contents($keyFile, $content, LOCK_EX);
        @chmod($keyFile, 0600);
    }

    $key = trim((string) include $keyFile);
    return $key;
}

/**
 * Verschlüsselt einen Klartext-Wert für die Speicherung in der .env bzw.
 * der Datenbank (z.B. TOTP-Secrets). Das Ergebnis beginnt mit "enc2:" und
 * kann direkt als Wert eingetragen werden.
 *
 * Nutzt AES-256-GCM (authentifizierte Verschlüsselung) statt des früheren
 * AES-256-CBC ohne Authentifizierung - verhindert, dass ein manipulierter
 * Geheimtext unbemerkt zu falschem statt gar keinem Klartext entschlüsselt
 * wird (openssl_decrypt() scheitert bei GCM kontrolliert, wenn das Tag nicht
 * passt).
 */
function encryptSecret(string $plaintext): string {
    if ($plaintext === '') {
        return '';
    }
    $key = hex2bin(getEncryptionKey());
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) {
        return $plaintext;
    }
    return 'enc2:' . base64_encode($iv . $tag . $ciphertext);
}

/**
 * Entschlüsselt einen gespeicherten Wert. Werte ohne bekanntes Präfix werden
 * unverändert zurückgegeben (Abwärtskompatibilität für noch nicht
 * verschlüsselte Werte). "enc:" (altes AES-256-CBC-Format) wird weiterhin
 * unterstützt, damit bereits gespeicherte .env-Werte und TOTP-Secrets nicht
 * ungültig werden - neu verschlüsselt wird aber nur noch mit "enc2:" (GCM).
 */
function decryptSecret(string $value): string {
    if ($value === '') {
        return $value;
    }

    if (str_starts_with($value, 'enc2:')) {
        $key = hex2bin(getEncryptionKey());
        $raw = base64_decode(substr($value, 5));
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain !== false ? $plain : '';
    }

    if (str_starts_with($value, 'enc:')) {
        $key = hex2bin(getEncryptionKey());
        $raw = base64_decode(substr($value, 4));
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $plain = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain !== false ? $plain : '';
    }

    return $value;
}
