<?php
/**
 * Admin - Rechte-/Berechtigungssystem
 *
 * Jeder Benutzer hat eine Liste von Berechtigungs-Schlüsseln (JSON-Array in
 * users.permissions). '*' bedeutet Vollzugriff auf alles (auch künftige,
 * neue Berechtigungen) - wichtig für den bestehenden Admin, damit niemand
 * sich selbst aussperrt.
 */

/**
 * Der Benutzername "admin" ist das fest geschützte Hauptkonto: er hat immer
 * und unveränderlich Vollzugriff, unabhängig davon, was in der Datenbank
 * steht. Damit kann sich niemand - versehentlich oder absichtlich - selbst
 * oder das Hauptkonto aussperren.
 */
function isProtectedAdminUsername(string $username): bool {
    return $username === 'admin';
}

function getAllPermissions(): array {
    return [
        'reports.manage'    => 'Berichte verwalten (erstellen, bearbeiten, löschen, veröffentlichen)',
        'members.manage'    => 'Mitglieder verwalten (erstellen, bearbeiten, löschen, Fotos)',
        'members.functions' => 'Funktionen &amp; Abzeichen zuweisen (Kommando, Ausschuss, Dienstgrad, Verwendungsabzeichen)',
        'ranks.manage'      => 'Dienstgrade verwalten',
        'settings.manage'   => 'Website-Einstellungen verwalten (Zugangspasswort der Seite)',
        'users.manage'      => 'Benutzer &amp; Rechte verwalten',
        'deploy.manage'     => 'Deployment: neuesten Stand von GitHub auf den Server holen (git pull)',
        'orgchart.manage'   => 'Organigramm verwalten (Namen den Positionen zuordnen)',
        'logs.manage'       => 'Protokolle einsehen &amp; IP-Adressen sperren (Aktivitäts-Log, Login-Log, Rate-Limit)',
        'media.manage'      => 'Fahrzeug- und Wache-Fotos austauschen',
    ];
}

/**
 * Berechtigungen des aktuell eingeloggten Benutzers (aus der Session).
 */
function getCurrentUserPermissions(): array {
    return $_SESSION['admin_permissions'] ?? [];
}

function currentUserIsSuperadmin(): bool {
    return in_array('*', getCurrentUserPermissions(), true);
}

function userHasPermission(string $key): bool {
    $perms = getCurrentUserPermissions();
    return in_array('*', $perms, true) || in_array($key, $perms, true);
}

/**
 * Bricht die Anfrage mit einer Fehlermeldung ab, falls die Berechtigung fehlt.
 */
function requirePermission(string $key): void {
    if (!userHasPermission($key)) {
        http_response_code(403);
        require_once __DIR__ . '/includes/admin-header.php';
        echo '<div class="admin-card"><div class="admin-card-body">';
        echo '<h2 style="margin-bottom:12px;"><i class="fas fa-lock"></i> Kein Zugriff</h2>';
        echo '<p>Für diesen Bereich fehlt dir die nötige Berechtigung. Bitte wende dich an einen Administrator mit Benutzerverwaltungsrechten.</p>';
        echo '</div></div>';
        require_once __DIR__ . '/includes/admin-footer.php';
        exit;
    }
}
