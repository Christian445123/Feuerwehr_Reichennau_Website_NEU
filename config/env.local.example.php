<?php
/**
 * Vorlage für config/env.local.php auf dem PRODUKTIVSERVER.
 *
 * So geht's:
 *   1. Diese Datei auf dem Server (per FTP/Datei-Manager) nach
 *      "env.local.php" kopieren (im selben config/-Ordner).
 *   2. Unten die echten (neu rotierten) Zugangsdaten eintragen.
 *   3. Die alte .env auf dem Server leeren oder löschen.
 *
 * NICHT per Git hochladen/einchecken - env.local.php (ohne ".example")
 * ist in .gitignore eingetragen und wird nie übertragen. Diese
 * Beispieldatei hier enthält absichtlich keine echten Werte und kann
 * bedenkenlos in Git bleiben.
 *
 * DB_PASSWORD und SMTP_PASSWORD verschlüsselt eintragen (Präfix "enc:"):
 *   php config/encrypt-tool.php "mein-echtes-passwort"
 * Die Ausgabe (inkl. "enc:"-Präfix) hier eintragen. Ein unverschlüsselter
 * Klartext-Wert funktioniert übergangsweise weiterhin.
 */

return [
    // ── Datenbank (MySQL/MariaDB) ──
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'ffrdb',
    'DB_USER' => 'ffr',
    'DB_PASSWORD' => 'enc:',

    // ── SMTP (für das Kontaktformular) ──
    'SMTP_HOST' => '',
    'SMTP_PORT' => '587',
    'SMTP_ENCRYPTION' => 'tls',
    'SMTP_USERNAME' => '',
    'SMTP_PASSWORD' => 'enc:',
    'SMTP_FROM_EMAIL' => '',
    'SMTP_FROM_NAME' => 'FF Reichenau Website',
    'SMTP_TO_EMAIL' => 'reichenau@feuerwehr.tirol',

    // ── Discord-Webhooks für Logs (optional, leer lassen zum Deaktivieren) ──
    'DISCORD_WEBHOOK_ACTIVITY' => '',
    'DISCORD_WEBHOOK_LOGIN' => '',
    'DISCORD_WEBHOOK_RATE_LIMIT' => '',
];
