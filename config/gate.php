<?php
/**
 * Seitensperre - schützt die gesamte öffentliche Website mit einem
 * gemeinsamen Zugangspasswort, solange die Seite nicht offiziell ist.
 */

require_once __DIR__ . '/database.php';

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
 * Bricht die aktuelle Anfrage ab und leitet zur Zugangssperre um,
 * falls der Besucher das Seitenpasswort noch nicht eingegeben hat.
 */
function requireSiteAccess(): void {
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
