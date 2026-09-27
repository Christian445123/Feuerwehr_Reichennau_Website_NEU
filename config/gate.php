<?php
/**
 * Seitensperre - schützt die gesamte öffentliche Website mit einem
 * gemeinsamen Zugangspasswort, solange die Seite nicht offiziell ist.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logging.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function siteAccessGranted(): bool {
    return !empty($_SESSION['site_access_granted']);
}

function getSitePasswordHash(): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = ?");
    $stmt->execute(['site_password']);
    $row = $stmt->fetch();
    return $row['value'] ?? '';
}

function setSitePassword(string $plainPassword): void {
    $db = getDB();
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'site_password'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $stmt = $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = 'site_password'");
    } else {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES ('site_password', ?)");
    }
    $stmt->execute([$hash]);
}

/**
 * Ob der Wartungsmodus (Zugangssperre der öffentlichen Website) aktiv ist.
 * Ohne gespeicherten Wert gilt er als aktiv, damit sich das bisherige
 * Verhalten (Seite immer gesperrt) nicht unbemerkt ändert.
 */
function isMaintenanceModeEnabled(): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row === false || $row['value'] !== '0';
}

function setMaintenanceMode(bool $enabled): void {
    $db = getDB();
    $value = $enabled ? '1' : '0';
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $stmt = $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = 'maintenance_mode'");
    } else {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES ('maintenance_mode', ?)");
    }
    $stmt->execute([$value]);
}

/**
 * Bricht die aktuelle Anfrage ab und leitet zur Wartungs-/Zugangssperre um,
 * falls der Wartungsmodus aktiv ist und der Besucher das Seitenpasswort noch
 * nicht eingegeben hat. Ist der Wartungsmodus deaktiviert, ist die Website
 * für alle frei zugänglich.
 */
function requireSiteAccess(): void {
    // Wird pro Seitenaufruf mehrfach aufgerufen (einmal von index.php, einmal
    // von der jeweiligen Seite selbst) - Protokollierung und Rate-Limit
    // sollen aber nur einmal pro tatsächlichem Request greifen.
    static $tracked = false;
    if (!$tracked) {
        $tracked = true;
        $db = getDB();
        $page = $_GET['page'] ?? 'home';
        logSiteVisit($db, is_string($page) ? $page : 'home');

        if (!checkRateLimit($db, 'site_access', 20, 60)) {
            http_response_code(429);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Zu viele Anfragen</title></head>'
                . '<body style="font-family:sans-serif;text-align:center;padding:80px 20px;color:#333;">'
                . '<h1>Zu viele Anfragen</h1><p>Bitte warte kurz und versuche es erneut.</p></body></html>';
            exit;
        }
    }

    if (!isMaintenanceModeEnabled()) {
        return;
    }

    if (siteAccessGranted()) {
        return;
    }

    $requestedUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';
    $_SESSION['site_access_redirect'] = $requestedUrl;

    header('Location: ' . siteGateBaseUrl() . 'zugang.php');
    exit;
}

/**
 * Ermittelt den Pfad zur Projekt-Root relativ zur aktuell aufgerufenen Datei,
 * damit zugang.php auch aus /pages/ oder /admin/ heraus korrekt verlinkt wird.
 */
function siteGateBaseUrl(): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($scriptDir, '/pages') || str_ends_with($scriptDir, '/admin')) {
        return '../';
    }
    return '';
}
