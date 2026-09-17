<?php
/**
 * Minimaler Mailversand fürs Kontaktformular (ohne Composer/PHPMailer).
 *
 * Ist in der .env ein SMTP_HOST hinterlegt, wird direkt per SMTP-Socket
 * versendet (inkl. STARTTLS/SSL + AUTH LOGIN). Andernfalls wird auf die
 * eingebaute PHP mail()-Funktion zurückgegriffen, damit das Formular auch
 * ohne SMTP-Zugangsdaten auf einem Standard-Webhosting funktioniert.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/crypto.php';

/**
 * Sendet eine E-Mail. Gibt bei Erfolg true zurück, sonst false
 * (Fehlermeldung wird über $error als Referenz zurückgegeben).
 */
function sendContactMail(string $subject, string $body, string $replyToEmail, string $replyToName, ?string &$error = null): bool {
    $to = env('SMTP_TO_EMAIL', 'reichenau@feuerwehr.tirol');
    $fromEmail = env('SMTP_FROM_EMAIL') ?: $to;
    $fromName = env('SMTP_FROM_NAME', 'FF Reichenau Website');
    $host = env('SMTP_HOST');

    if ($host) {
        return sendViaSmtp($host, $to, $fromEmail, $fromName, $subject, $body, $replyToEmail, $replyToName, $error);
    }

    return sendViaPhpMail($to, $fromEmail, $fromName, $subject, $body, $replyToEmail, $replyToName, $error);
}

function sendViaPhpMail(string $to, string $fromEmail, string $fromName, string $subject, string $body, string $replyToEmail, string $replyToName, ?string &$error): bool {
    // Viele Hoster deaktivieren mail() serverseitig (disable_functions) gegen
    // Spam-Missbrauch. Ein Aufruf einer deaktivierten Funktion ist ein
    // fataler Fehler, den @ NICHT unterdrückt - ohne diese Prüfung würde das
    // Kontaktformular dann mit einer leeren weißen Seite abstürzen statt
    // eine Fehlermeldung anzuzeigen.
    if (!function_exists('mail')) {
        $error = 'Der Mailversand (mail()) ist auf diesem Server deaktiviert.';
        return false;
    }

    $headers = [
        'From: ' . encodeHeaderWord($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . encodeHeaderWord($replyToName) . ' <' . $replyToEmail . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ];

    $ok = @mail($to, encodeHeaderWord($subject), $body, implode("\r\n", $headers));
    if (!$ok) {
        $error = 'mail() ist auf diesem Server fehlgeschlagen oder nicht verfügbar.';
    }
    return $ok;
}

function sendViaSmtp(string $host, string $to, string $fromEmail, string $fromName, string $subject, string $body, string $replyToEmail, string $replyToName, ?string &$error): bool {
    $port = (int)env('SMTP_PORT', '587');
    $encryption = strtolower((string)env('SMTP_ENCRYPTION', 'tls'));
    $username = env('SMTP_USERNAME', '');
    $password = decryptSecret(env('SMTP_PASSWORD', ''));

    $transport = $encryption === 'ssl' ? 'ssl://' : '';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 10);
    if (!$socket) {
        $error = "Verbindung zum SMTP-Server fehlgeschlagen: $errstr ($errno)";
        return false;
    }

    $readResponse = function () use ($socket): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };

    $sendCommand = function (string $command) use ($socket, $readResponse): string {
        fwrite($socket, $command . "\r\n");
        return $readResponse();
    };

    $expect = function (string $response, array $codes) use (&$error): bool {
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            $error = "Unerwartete SMTP-Antwort: " . trim($response);
            return false;
        }
        return true;
    };

    $readResponse(); // Greeting

    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $resp = $sendCommand("EHLO $hostname");
    if (!$expect($resp, [250])) { fclose($socket); return false; }

    if ($encryption === 'tls') {
        $resp = $sendCommand('STARTTLS');
        if (!$expect($resp, [220])) { fclose($socket); return false; }
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $error = 'TLS-Verschlüsselung (STARTTLS) konnte nicht aufgebaut werden.';
            fclose($socket);
            return false;
        }
        $resp = $sendCommand("EHLO $hostname");
        if (!$expect($resp, [250])) { fclose($socket); return false; }
    }

    if ($username !== '') {
        $resp = $sendCommand('AUTH LOGIN');
        if (!$expect($resp, [334])) { fclose($socket); return false; }
        $resp = $sendCommand(base64_encode($username));
        if (!$expect($resp, [334])) { fclose($socket); return false; }
        $resp = $sendCommand(base64_encode($password));
        if (!$expect($resp, [235])) { fclose($socket); return false; }
    }

    $resp = $sendCommand("MAIL FROM:<$fromEmail>");
    if (!$expect($resp, [250])) { fclose($socket); return false; }

    $resp = $sendCommand("RCPT TO:<$to>");
    if (!$expect($resp, [250, 251])) { fclose($socket); return false; }

    $resp = $sendCommand('DATA');
    if (!$expect($resp, [354])) { fclose($socket); return false; }

    $headers = [
        'From: ' . encodeHeaderWord($fromName) . " <$fromEmail>",
        'Reply-To: ' . encodeHeaderWord($replyToName) . " <$replyToEmail>",
        "To: <$to>",
        'Subject: ' . encodeHeaderWord($subject),
        'Date: ' . date('r'),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    // Zeilen, die nur aus einem Punkt bestehen, müssen im SMTP-Datenblock
    // per Byte-Stuffing verdoppelt werden.
    $escapedBody = preg_replace('/^\./m', '..', $body);

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.";
    $resp = $sendCommand($message);
    if (!$expect($resp, [250])) { fclose($socket); return false; }

    $sendCommand('QUIT');
    fclose($socket);
    return true;
}

/**
 * Kodiert Betreff/Namen mit Umlauten korrekt für Mail-Header (RFC 2047).
 */
function encodeHeaderWord(string $text): string {
    if (preg_match('/^[\x20-\x7E]*$/', $text)) {
        return $text;
    }
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}
