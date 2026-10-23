<?php
/**
 * Einsatzbereitschafts-Status ("Einsatzbereit" / "Nicht einsatzbereit" /
 * "Übung").
 *
 * Wird in site_settings gespeichert (gleiches Muster wie maintenance_mode
 * etc.). Kann manuell über den Admin-Bereich umgeschaltet werden oder
 * automatisch über den Webhook (api-einsatzbereitschaft.php) per SMS-Gateway
 * oder künftiger eigener API - beide nutzen denselben geheimen Token.
 */

require_once __DIR__ . '/settings.php';

const EINSATZBEREITSCHAFT_STATUSSE = ['bereit', 'nicht_bereit', 'uebung'];

/**
 * Ohne gespeicherten Wert bzw. bei einem unbekannten Wert gilt die Wehr als
 * einsatzbereit (Normalzustand). Ältere, vor der Übung-Option gespeicherte
 * Werte ("1"/"0") werden weiterhin verstanden.
 */
function getEinsatzbereitschaftStatus(): string {
    $raw = getSiteSetting('einsatzbereitschaft_status', 'bereit');
    if ($raw === '1') return 'bereit';
    if ($raw === '0') return 'nicht_bereit';
    return in_array($raw, EINSATZBEREITSCHAFT_STATUSSE, true) ? $raw : 'bereit';
}

function isEinsatzbereit(): bool {
    return getEinsatzbereitschaftStatus() === 'bereit';
}

function getEinsatzbereitschaftStatusLabels(): array {
    return ['bereit' => 'Einsatzbereit', 'nicht_bereit' => 'Nicht einsatzbereit', 'uebung' => 'Übung'];
}

function getEinsatzbereitschaftMeta(): array {
    return [
        'status' => getEinsatzbereitschaftStatus(),
        'updated_at' => getSiteSetting('einsatzbereitschaft_updated_at', ''),
        'via' => getSiteSetting('einsatzbereitschaft_via', ''),
        'note' => getSiteSetting('einsatzbereitschaft_note', ''),
    ];
}

/**
 * $status: 'bereit' | 'nicht_bereit' | 'uebung'
 * $via: 'manual' | 'sms' | 'api'
 */
function setEinsatzbereitschaft(string $status, string $via, string $note = ''): void {
    if (!in_array($status, EINSATZBEREITSCHAFT_STATUSSE, true)) {
        $status = 'bereit';
    }
    setSiteSetting('einsatzbereitschaft_status', $status);
    setSiteSetting('einsatzbereitschaft_updated_at', date('Y-m-d H:i:s'));
    setSiteSetting('einsatzbereitschaft_via', $via);
    setSiteSetting('einsatzbereitschaft_note', mb_substr($note, 0, 255));
}

/**
 * Geheimer Token für den Webhook (SMS-Gateway/API). Wird beim ersten Zugriff
 * automatisch erzeugt.
 */
function getEinsatzbereitschaftApiToken(): string {
    $token = getSiteSetting('einsatzbereitschaft_api_token', '');
    if ($token === '') {
        $token = bin2hex(random_bytes(24));
        setSiteSetting('einsatzbereitschaft_api_token', $token);
    }
    return $token;
}

function regenerateEinsatzbereitschaftApiToken(): string {
    $token = bin2hex(random_bytes(24));
    setSiteSetting('einsatzbereitschaft_api_token', $token);
    return $token;
}

/**
 * Kommagetrennte Liste erlaubter Absender-Rufnummern für den SMS-Weg
 * (optional - leer bedeutet: keine Einschränkung, nur der Token zählt).
 */
function getEinsatzbereitschaftAllowedSenders(): string {
    return getSiteSetting('einsatzbereitschaft_allowed_senders', '');
}

function setEinsatzbereitschaftAllowedSenders(string $senders): void {
    setSiteSetting('einsatzbereitschaft_allowed_senders', trim($senders));
}

function isSenderAllowed(string $sender): bool {
    $allowed = getEinsatzbereitschaftAllowedSenders();
    if (trim($allowed) === '') {
        return true;
    }
    $normalize = fn(string $n) => preg_replace('/[^0-9+]/', '', $n);
    $sender = $normalize($sender);
    if ($sender === '') {
        return false;
    }
    foreach (explode(',', $allowed) as $number) {
        if ($normalize($number) === $sender) {
            return true;
        }
    }
    return false;
}

/**
 * Interpretiert einen SMS-/API-Text nach dem Muster "BEREIT" / "NICHT
 * BEREIT" / "EINSATZBEREIT" / "NICHT EINSATZBEREIT" / "ÜBUNG"
 * (Groß-/Kleinschreibung egal). Gibt null zurück, wenn kein eindeutiges
 * Schlüsselwort erkannt wird.
 *
 * @return 'bereit'|'nicht_bereit'|'uebung'|null
 */
function parseEinsatzbereitschaftMessage(string $message): ?string {
    $normalized = mb_strtoupper(trim($message));
    if ($normalized === '') {
        return null;
    }
    // "ÜBUNG" zuerst prüfen, damit es nicht fälschlich an "NICHT"/"BEREIT"
    // vorbeiläuft, falls eine Nachricht mehrere Wörter enthält.
    if (str_contains($normalized, 'UEBUNG') || str_contains($normalized, 'ÜBUNG')) {
        return 'uebung';
    }
    if (str_contains($normalized, 'NICHT')) {
        return 'nicht_bereit';
    }
    if (str_contains($normalized, 'BEREIT')) {
        return 'bereit';
    }
    return null;
}
