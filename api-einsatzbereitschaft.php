<?php
/**
 * Webhook zum automatischen Umschalten der Einsatzbereitschaft - gedacht zum
 * Eintragen als Ziel-URL in einem SMS-Gateway (eingehende SMS werden
 * weitergeleitet) oder einer künftigen eigenen API. Authentifizierung über
 * einen geheimen Token (siehe Admin -> Einsatzbereitschaft), keine
 * Admin-Anmeldung nötig, da SMS-Gateways/APIs keine Cookies mitschicken
 * können.
 *
 * Aufruf per GET oder POST mit:
 *   token   = geheimer Token (Pflicht)
 *   status  = "bereit"/"1" oder "nicht_bereit"/"0" (optional, hat Vorrang)
 *   message/text/body/sms = Freitext, wird nach "BEREIT"/"NICHT BEREIT"
 *             durchsucht, falls kein status-Feld angegeben ist
 *   from/sender/msisdn = Absender-Rufnummer (optional, nur relevant, wenn
 *             unter Admin -> Einsatzbereitschaft erlaubte Nummern hinterlegt sind)
 *
 * Antwort: einfacher Text "OK" bei Erfolg, sonst eine Fehlermeldung mit
 * passendem HTTP-Status - absichtlich kein JSON, damit es mit möglichst
 * vielen einfachen SMS-Gateways kompatibel ist.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';
require_once __DIR__ . '/config/einsatzbereitschaft.php';

header('Content-Type: text/plain; charset=UTF-8');

$input = array_merge($_GET, $_POST);
$db = getDB();

if (!checkRateLimit($db, 'einsatzbereitschaft_webhook', 20, 600)) {
    http_response_code(429);
    echo 'Zu viele Anfragen.';
    exit;
}

$token = $input['token'] ?? '';
$expectedToken = getEinsatzbereitschaftApiToken();
if ($token === '' || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    logActivity($db, 'einsatzbereitschaft.webhook_denied', 'Ungültiger oder fehlender Token', 0, 'Webhook');
    echo 'Ungueltiger Token.';
    exit;
}

$sender = $input['from'] ?? $input['sender'] ?? $input['msisdn'] ?? '';
if ($sender !== '' && !isSenderAllowed($sender)) {
    http_response_code(403);
    logActivity($db, 'einsatzbereitschaft.webhook_denied', "Absender nicht erlaubt: $sender", 0, 'Webhook');
    echo 'Absender nicht erlaubt.';
    exit;
}

$status = null;
if (isset($input['status'])) {
    $raw = mb_strtolower(trim((string) $input['status']));
    if (in_array($raw, ['bereit', 'einsatzbereit', '1', 'ja', 'true'], true)) {
        $status = true;
    } elseif (in_array($raw, ['nicht_bereit', 'nicht_einsatzbereit', '0', 'nein', 'false'], true)) {
        $status = false;
    }
}

$message = $input['message'] ?? $input['text'] ?? $input['body'] ?? $input['sms'] ?? '';
if ($status === null && $message !== '') {
    $status = parseEinsatzbereitschaftMessage((string) $message);
}

if ($status === null) {
    http_response_code(400);
    echo 'Status nicht erkannt. Erwartet wird ein "status"-Feld oder eine Nachricht mit "BEREIT"/"NICHT BEREIT".';
    exit;
}

$via = isset($input['from']) || isset($input['sender']) || isset($input['msisdn']) ? 'sms' : 'api';
$note = $sender !== '' ? "Ausgelöst von $sender" : '';
setEinsatzbereitschaft($status, $via, $note);
logActivity($db, 'einsatzbereitschaft.change', ($status ? 'Einsatzbereit' : 'Nicht einsatzbereit') . " (via $via" . ($sender !== '' ? ", $sender" : '') . ')', 0, 'Webhook');

echo 'OK';
