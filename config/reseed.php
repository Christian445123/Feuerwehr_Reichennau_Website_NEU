<?php
/**
 * Löscht ALLE Mitglieder unwiderruflich und befüllt die Demo-Daten neu.
 * Absichtlich nur über die Kommandozeile nutzbar (nicht über den Browser) -
 * das ist das einzige, was einen Aufruf durch Dritte verhindert, falls die
 * .htaccess-Sperre für config/ aus irgendeinem Grund mal nicht greift
 * (z.B. Hosting ohne mod_rewrite/AllowOverride, nginx).
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Dieses Werkzeug ist nur über die Kommandozeile nutzbar.');
}

require_once __DIR__ . '/database.php';
$db = getDB();
$db->exec('DELETE FROM members');
echo "Members gelöscht.\n";
require __DIR__ . '/seed.php';
