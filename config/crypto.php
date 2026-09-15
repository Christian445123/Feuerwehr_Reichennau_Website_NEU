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
 * Verschlüsselt einen Klartext-Wert für die Speicherung in der .env.
 * Das Ergebnis beginnt mit "enc:" und kann direkt als .env-Wert eingetragen werden.
 */
function encryptSecret(string $plaintext): string {
    if ($plaintext === '') {
        return '';
    }
    $key = hex2bin(getEncryptionKey());
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        return $plaintext;
    }
    return 'enc:' . base64_encode($iv . $ciphertext);
}

/**
 * Entschlüsselt einen .env-Wert. Werte ohne "enc:"-Präfix werden unverändert
 * zurückgegeben (Abwärtskompatibilität für noch nicht verschlüsselte Werte).
 */
function decryptSecret(string $value): string {
    if ($value === '' || !str_starts_with($value, 'enc:')) {
        return $value;
    }
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
