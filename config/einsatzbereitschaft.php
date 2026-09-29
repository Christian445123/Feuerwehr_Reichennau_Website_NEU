<?php
/**
 * Einsatzbereitschafts-Status ("Einsatzbereit" / "Nicht einsatzbereit").
 *
 * Wird in site_settings gespeichert (gleiches Muster wie maintenance_mode
 * etc.). Kann manuell über den Admin-Bereich umgeschaltet werden oder
 * automatisch über den Webhook (api-einsatzbereitschaft.php) per SMS-Gateway
 * oder künftiger eigener API - beide nutzen denselben geheimen Token.
 */

require_once __DIR__ . '/settings.php';

/**
 * Ohne gespeicherten Wert gilt die Wehr als einsatzbereit (Normalzustand).
 */
function isEinsatzbereit(): bool {
    return getSiteSetting('einsatzbereitschaft_status', '1') !== '0';
}

function getEinsatzbereitschaftMeta(): array {
    return [
        'status' => isEinsatzbereit(),
        'updated_at' => getSiteSetting('einsatzbereitschaft_updated_at', ''),
        'via' => getSiteSetting('einsatzbereitschaft_via', ''),
        'note' => getSiteSetting('einsatzbereitschaft_note', ''),
    ];
}

/**
 * $via: 'manual' | 'sms' | 'api'
 */
function setEinsatzbereitschaft(bool $bereit, string $via, string $note = ''): void {
    setSiteSetting('einsatzbereitschaft_status', $bereit ? '1' : '0');
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
 * BEREIT" / "EINSATZBEREIT" / "NICHT EINSATZBEREIT" (Groß-/Kleinschreibung
 * egal). Gibt null zurück, wenn kein eindeutiges Schlüsselwort erkannt wird.
 */
function parseEinsatzbereitschaftMessage(string $message): ?bool {
    $normalized = mb_strtoupper(trim($message));
    if ($normalized === '') {
        return null;
    }
    if (str_contains($normalized, 'NICHT')) {
        return false;
    }
    if (str_contains($normalized, 'BEREIT')) {
        return true;
    }
    return null;
}
