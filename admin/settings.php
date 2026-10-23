<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/gate.php';
require_once __DIR__ . '/../config/analytics.php';
require_once __DIR__ . '/../config/berichte.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/env-writer.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/translate.php';
require_once __DIR__ . '/../config/settings.php';
requireLogin();

$pageTitle = 'Einstellungen';
$activePage = 'settings';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: settings.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_maintenance') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, den Wartungsmodus zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $enable = ($_POST['enable'] ?? '1') === '1';
        setMaintenanceMode($enable);
        logActivity($db, 'settings.maintenance_mode', $enable ? 'Aktiviert' : 'Deaktiviert');
        flash('success', $enable ? 'Wartungsmodus wurde aktiviert.' : 'Wartungsmodus wurde deaktiviert - die Website ist jetzt für alle frei zugänglich.');
        header('Location: settings.php');
        exit;
    }

    if ($action === 'change_site_password') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, das Zugangspasswort zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $newPw = $_POST['new_site_password'] ?? '';
        $confirmPw = $_POST['confirm_site_password'] ?? '';

        if (strlen($newPw) < 4) {
            flash('error', 'Das Zugangspasswort muss mindestens 4 Zeichen lang sein.');
        } elseif ($newPw !== $confirmPw) {
            flash('error', 'Passwörter stimmen nicht überein.');
        } else {
            setSitePassword($newPw);
            logActivity($db, 'settings.site_password');
            flash('success', 'Das Zugangspasswort der Website wurde geändert.');
        }
    }

    if ($action === 'save_ga_id') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Google Analytics einzurichten.');
            header('Location: settings.php');
            exit;
        }
        $gaId = trim($_POST['ga_measurement_id'] ?? '');
        if ($gaId !== '' && !preg_match('/^G-[A-Z0-9]+$/', $gaId)) {
            flash('error', 'Ungültiges Format. Eine Google-Analytics-4-Measurement-ID beginnt mit "G-" (z.B. G-ABC1234XYZ).');
        } else {
            setGaMeasurementId($gaId);
            logActivity($db, 'settings.ga_id', $gaId !== '' ? "Gesetzt auf $gaId" : 'Deaktiviert');
            flash('success', $gaId !== '' ? 'Google Analytics wurde eingerichtet.' : 'Google Analytics wurde deaktiviert.');
        }
    }

    if ($action === 'save_berichte_jahre') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, die Berichtsjahre zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $aktuellesJahr = (int) ($_POST['berichte_aktuelles_jahr'] ?? 0);
        $vorjahr = (int) ($_POST['berichte_vorjahr'] ?? 0);

        if ($aktuellesJahr < 2000 || $aktuellesJahr > 2100 || $vorjahr < 2000 || $vorjahr > 2100) {
            flash('error', 'Bitte gültige Jahreszahlen angeben.');
        } elseif ($vorjahr >= $aktuellesJahr) {
            flash('error', 'Das vergangene Jahr muss vor dem aktuellen Kalenderjahr liegen.');
        } else {
            setBerichteJahre($aktuellesJahr, $vorjahr);
            logActivity($db, 'settings.berichte_jahre', "Aktuelles Jahr: $aktuellesJahr, Vorjahr: $vorjahr");
            flash('success', 'Die Berichtsjahre wurden gespeichert.');
        }
    }

    if ($action === 'save_db_credentials') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Zugangsdaten zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $host = trim($_POST['db_host'] ?? '');
        $port = trim($_POST['db_port'] ?? '');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $newPassword = $_POST['db_password'] ?? '';

        if ($host === '' || $port === '' || $name === '' || $user === '') {
            flash('error', 'Bitte alle Datenbank-Felder ausfüllen.');
        } else {
            // Leeres Passwortfeld = aktuelles (entschlüsseltes) Passwort
            // unverändert weiterverwenden - nur zum Testen der anderen
            // geänderten Felder (Host/Port/Name/User).
            $passwordToTest = $newPassword !== '' ? $newPassword : decryptSecret(env('DB_PASSWORD', ''));

            $error = testDatabaseCredentials($host, $port, $name, $user, $passwordToTest);
            if ($error !== null) {
                flash('error', 'Verbindung mit diesen Zugangsdaten fehlgeschlagen - nichts wurde gespeichert, die Website läuft unverändert mit den bisherigen Zugangsdaten weiter. Fehlermeldung: ' . $error);
            } else {
                writeEnvLocalFile([
                    'DB_HOST' => $host,
                    'DB_PORT' => $port,
                    'DB_NAME' => $name,
                    'DB_USER' => $user,
                    'DB_PASSWORD' => encryptSecret($passwordToTest),
                ]);
                logActivity($db, 'settings.db_credentials', 'Datenbank-Zugangsdaten aktualisiert (env.local.php)');
                flash('success', 'Datenbank-Zugangsdaten wurden erfolgreich getestet und gespeichert.');
            }
        }
        header('Location: settings.php');
        exit;
    }

    if ($action === 'save_smtp_credentials') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Zugangsdaten zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpEncryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpNewPassword = $_POST['smtp_password'] ?? '';
        $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
        $smtpFromName = trim($_POST['smtp_from_name'] ?? '');
        $smtpToEmail = trim($_POST['smtp_to_email'] ?? '');

        if ($smtpFromEmail !== '' && !filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Die Absender-E-Mail-Adresse ist ungültig.');
        } elseif ($smtpToEmail !== '' && !filter_var($smtpToEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Die Empfänger-E-Mail-Adresse ist ungültig.');
        } else {
            $updates = [
                'SMTP_HOST' => $smtpHost,
                'SMTP_PORT' => $smtpPort,
                'SMTP_ENCRYPTION' => $smtpEncryption,
                'SMTP_USERNAME' => $smtpUsername,
                'SMTP_FROM_EMAIL' => $smtpFromEmail,
                'SMTP_FROM_NAME' => $smtpFromName,
                'SMTP_TO_EMAIL' => $smtpToEmail,
            ];
            // Leeres Passwortfeld = bestehendes Passwort unverändert lassen.
            if ($smtpNewPassword !== '') {
                $updates['SMTP_PASSWORD'] = encryptSecret($smtpNewPassword);
            }
            writeEnvLocalFile($updates);
            logActivity($db, 'settings.smtp_credentials', 'SMTP-Zugangsdaten aktualisiert (env.local.php)');
            flash('success', 'SMTP-Zugangsdaten wurden gespeichert. Tipp: mit "Test-E-Mail senden" prüfen, ob der Versand funktioniert.');
        }
        header('Location: settings.php');
        exit;
    }

    if ($action === 'test_smtp') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung dafür.');
            header('Location: settings.php');
            exit;
        }
        $testError = null;
        $to = env('SMTP_TO_EMAIL', 'reichenau@feuerwehr.tirol');
        $sent = sendMail($to, '', 'Test-E-Mail von der FF-Reichenau-Website', "Das ist eine Test-E-Mail, ausgelöst über Admin -> Einstellungen.\n\nWenn du diese E-Mail erhältst, funktioniert der Mailversand.", $testError);
        if ($sent) {
            logActivity($db, 'settings.smtp_test', "Test-E-Mail an $to gesendet");
            flash('success', "Test-E-Mail wurde an $to gesendet - bitte Posteingang prüfen.");
        } else {
            flash('error', 'Test-E-Mail konnte nicht gesendet werden: ' . ($testError ?? 'Unbekannter Fehler'));
        }
        header('Location: settings.php');
        exit;
    }

    if ($action === 'save_discord_webhooks') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Zugangsdaten zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $webhooks = [
            'DISCORD_WEBHOOK_ACTIVITY' => trim($_POST['discord_webhook_activity'] ?? ''),
            'DISCORD_WEBHOOK_LOGIN' => trim($_POST['discord_webhook_login'] ?? ''),
            'DISCORD_WEBHOOK_RATE_LIMIT' => trim($_POST['discord_webhook_rate_limit'] ?? ''),
        ];
        $invalid = [];
        foreach ($webhooks as $label => $url) {
            if ($url !== '' && !str_starts_with($url, 'https://discord.com/api/webhooks/') && !str_starts_with($url, 'https://discordapp.com/api/webhooks/')) {
                $invalid[] = $label;
            }
        }
        if (!empty($invalid)) {
            flash('error', 'Ungültige Webhook-URL (muss mit https://discord.com/api/webhooks/ beginnen oder leer sein): ' . implode(', ', $invalid));
        } else {
            writeEnvLocalFile($webhooks);
            logActivity($db, 'settings.discord_webhooks', 'Discord-Webhooks aktualisiert (env.local.php)');
            flash('success', 'Discord-Webhooks wurden gespeichert.');
        }
        header('Location: settings.php');
        exit;
    }

    if ($action === 'save_deepl_key') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Zugangsdaten zu ändern.');
            header('Location: settings.php');
            exit;
        }
        $newKey = trim($_POST['deepl_api_key'] ?? '');
        // Leeres Feld = bestehenden Key unverändert lassen (wie beim
        // SMTP-Passwort) - nur ein ausgefülltes Feld überschreibt ihn.
        if ($newKey !== '') {
            writeEnvLocalFile(['DEEPL_API_KEY' => encryptSecret($newKey)]);
            logActivity($db, 'settings.deepl_key', 'DeepL-API-Key aktualisiert (env.local.php)');
            flash('success', 'DeepL-API-Key wurde gespeichert.');
        }
        header('Location: settings.php');
        exit;
    }

    if ($action === 'remove_deepl_key') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, Zugangsdaten zu ändern.');
            header('Location: settings.php');
            exit;
        }
        writeEnvLocalFile(['DEEPL_API_KEY' => '']);
        logActivity($db, 'settings.deepl_key', 'DeepL-API-Key entfernt (env.local.php)');
        flash('success', 'DeepL-API-Key wurde entfernt - automatische Übersetzungen sind jetzt deaktiviert.');
        header('Location: settings.php');
        exit;
    }

    if ($action === 'backfill_translations') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung dafür.');
            header('Location: settings.php');
            exit;
        }
        if (!isDeepLConfigured()) {
            flash('error', 'Bitte zuerst einen DeepL-API-Key hinterlegen.');
        } else {
            $reportResult = backfillReportTranslations($db, 20);
            $memberResult = backfillMemberTranslations($db, 20);
            logActivity($db, 'translate.backfill', "Berichte: {$reportResult['done']} übersetzt, {$reportResult['remaining']} offen; Mitglieder: {$memberResult['done']} übersetzt, {$memberResult['remaining']} offen");

            $parts = [];
            $parts[] = "{$reportResult['done']} Bericht(e) übersetzt" . ($reportResult['remaining'] > 0 ? ", noch {$reportResult['remaining']} offen - Button erneut klicken" : '');
            $parts[] = "{$memberResult['done']} Mitgliederprofil(e) übersetzt" . ($memberResult['remaining'] > 0 ? ", noch {$memberResult['remaining']} offen - Button erneut klicken" : '');
            if ($reportResult['failed'] > 0 || $memberResult['failed'] > 0) {
                $parts[] = ($reportResult['failed'] + $memberResult['failed']) . ' Übersetzung(en) fehlgeschlagen (DeepL nicht erreichbar oder Kontingent aufgebraucht) - später erneut versuchen';
            }
            flash('success', implode('. ', $parts) . '.');
        }
        header('Location: settings.php#deepl-backfill');
        exit;
    }

    if ($action === 'toggle_weather_test_mode') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung dafür.');
            header('Location: settings.php#weather-warning');
            exit;
        }
        $enable = ($_POST['enable'] ?? '1') === '1';
        setSiteSetting('weather_warning_test_mode', $enable ? '1' : '0');
        logActivity($db, 'settings.weather_warning_test', $enable ? 'Testmodus aktiviert' : 'Testmodus deaktiviert');
        flash('success', $enable
            ? 'Testmodus ist jetzt aktiv - das Warn-Popup mit Beispielinhalt erscheint ab sofort bei jedem Seitenaufruf.'
            : 'Testmodus wurde deaktiviert.');
        header('Location: settings.php#weather-warning');
        exit;
    }

    if ($action === 'save_weather_warning') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, die Wetterwarnung zu ändern.');
            header('Location: settings.php#weather-warning');
            exit;
        }
        $enabled = ($_POST['weather_warning_manual_enabled'] ?? '0') === '1';
        $color = trim($_POST['weather_warning_manual_color'] ?? '#e74c3c');
        $area = trim($_POST['weather_warning_manual_area'] ?? '');
        $text = trim($_POST['weather_warning_manual_text'] ?? '');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#e74c3c';
        }

        setSiteSetting('weather_warning_manual_enabled', $enabled ? '1' : '0');
        setSiteSetting('weather_warning_manual_color', $color);
        setSiteSetting('weather_warning_manual_area', $area);
        setSiteSetting('weather_warning_manual_text', $text);

        logActivity($db, 'settings.weather_warning', $enabled ? "Manuelle Wetterwarnung aktiviert (Bereich: $area)" : 'Manuelle Wetterwarnung deaktiviert');
        flash('success', $enabled ? 'Die manuelle Wetterwarnung ist jetzt aktiv - das Popup erscheint ab sofort auf der Website.' : 'Die manuelle Wetterwarnung wurde deaktiviert.');
        header('Location: settings.php#weather-warning');
        exit;
    }

    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if (userHasPermission('settings.manage')): ?>
<div class="admin-card" style="max-width: 600px;">
    <div class="admin-card-header"><h2><i class="fab fa-google"></i> Google Analytics</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 8px; color: #6c757d;">Trage hier deine GA4-Measurement-ID ein (Format <code>G-XXXXXXXXXX</code>, zu finden in deinem <a href="https://analytics.google.com" target="_blank" rel="noopener">Google-Analytics-Konto</a> unter Verwaltung &rarr; Datenstreams). Analytics wird öffentlich erst geladen, nachdem ein Besucher im Cookie-Banner zugestimmt hat - ohne Einwilligung wird nichts geladen. Feld leer lassen, um Analytics wieder zu deaktivieren.</p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_ga_id">

            <div class="form-group">
                <label for="ga_measurement_id">Measurement-ID</label>
                <input type="text" id="ga_measurement_id" name="ga_measurement_id" value="<?php echo e(getGaMeasurementId()); ?>" placeholder="G-ABC1234XYZ">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-lock"></i> Website-Zugangssperre / Wartungsmodus</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Solange die Website nicht offiziell ist, müssen Besucher das Zugangspasswort eingeben, bevor sie die Seite sehen können. Statt der Passwort-Abfrage sehen sie dabei eine freundliche Wartungsmodus-Seite.</p>

        <div style="margin-bottom: 24px; padding: 14px 16px; background: <?php echo isMaintenanceModeEnabled() ? 'rgba(213,0,28,0.06)' : 'rgba(39,174,96,0.08)'; ?>; border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <strong><?php echo isMaintenanceModeEnabled() ? 'Wartungsmodus ist AKTIV' : 'Wartungsmodus ist DEAKTIVIERT'; ?></strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                    <?php echo isMaintenanceModeEnabled()
                        ? 'Besucher sehen die Wartungsseite und müssen das Zugangspasswort eingeben.'
                        : 'Die Website ist für alle Besucher frei zugänglich - keine Passwort-Abfrage.'; ?>
                </p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_maintenance">
                <input type="hidden" name="enable" value="<?php echo isMaintenanceModeEnabled() ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo isMaintenanceModeEnabled() ? 'btn-secondary' : 'btn-primary'; ?>">
                    <i class="fas fa-power-off"></i> <?php echo isMaintenanceModeEnabled() ? 'Deaktivieren' : 'Aktivieren'; ?>
                </button>
            </form>
        </div>

        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_site_password">

            <div class="form-group">
                <label for="new_site_password">Neues Zugangspasswort</label>
                <input type="password" id="new_site_password" name="new_site_password" required minlength="4">
            </div>

            <div class="form-group">
                <label for="confirm_site_password">Passwort bestätigen</label>
                <input type="password" id="confirm_site_password" name="confirm_site_password" required minlength="4">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Zugangspasswort ändern
            </button>
        </form>
    </div>
</div>

<?php
$dbPasswordSet = decryptSecret(env('DB_PASSWORD', '')) !== '';
$smtpPasswordSet = decryptSecret(env('SMTP_PASSWORD', '')) !== '';
?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-database"></i> Datenbank-Zugangsdaten</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Das Passwort wird automatisch verschlüsselt gespeichert (<code>config/env.local.php</code>) - du musst es nicht selbst verschlüsseln. <strong>Wichtig:</strong> Die Verbindung wird vor dem Speichern getestet - stimmen die Zugangsdaten nicht, wird nichts übernommen und die Website läuft mit den bisherigen Daten weiter, statt dich auszusperren. Hast du das Passwort in der Datenbank selbst schon geändert? Dann hier dasselbe neue Passwort eintragen.</p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_db_credentials">

            <div class="form-row">
                <div class="form-group">
                    <label for="db_host">Host</label>
                    <input type="text" id="db_host" name="db_host" required value="<?php echo e(env('DB_HOST', 'localhost')); ?>">
                </div>
                <div class="form-group">
                    <label for="db_port">Port</label>
                    <input type="text" id="db_port" name="db_port" required value="<?php echo e(env('DB_PORT', '3306')); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="db_name">Datenbankname</label>
                    <input type="text" id="db_name" name="db_name" required value="<?php echo e(env('DB_NAME', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="db_user">Benutzername</label>
                    <input type="text" id="db_user" name="db_user" required value="<?php echo e(env('DB_USER', '')); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="db_password">Neues Passwort <?php echo $dbPasswordSet ? '(aktuell gesetzt - leer lassen zum Beibehalten)' : '(noch nicht gesetzt)'; ?></label>
                <input type="password" id="db_password" name="db_password" placeholder="Leer lassen = unverändert lassen" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plug-circle-check"></i> Verbindung testen &amp; speichern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-envelope"></i> SMTP-Zugangsdaten (Mailversand)</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Für den Versand des Kontaktformulars. Das Passwort wird automatisch verschlüsselt gespeichert. Leer lassen (SMTP-Host leer), um stattdessen die einfache <code>mail()</code>-Funktion des Servers zu nutzen.</p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_smtp_credentials">

            <div class="form-row">
                <div class="form-group">
                    <label for="smtp_host">SMTP-Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="<?php echo e(env('SMTP_HOST', '')); ?>" placeholder="z.B. smtp.strato.de">
                </div>
                <div class="form-group">
                    <label for="smtp_port">Port</label>
                    <input type="text" id="smtp_port" name="smtp_port" value="<?php echo e(env('SMTP_PORT', '587')); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="smtp_encryption">Verschlüsselung</label>
                    <select id="smtp_encryption" name="smtp_encryption">
                        <option value="tls" <?php echo env('SMTP_ENCRYPTION', 'tls') === 'tls' ? 'selected' : ''; ?>>STARTTLS</option>
                        <option value="ssl" <?php echo env('SMTP_ENCRYPTION', 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="smtp_username">Benutzername</label>
                    <input type="text" id="smtp_username" name="smtp_username" value="<?php echo e(env('SMTP_USERNAME', '')); ?>" autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label for="smtp_password">Neues Passwort <?php echo $smtpPasswordSet ? '(aktuell gesetzt - leer lassen zum Beibehalten)' : '(noch nicht gesetzt)'; ?></label>
                <input type="password" id="smtp_password" name="smtp_password" placeholder="Leer lassen = unverändert lassen" autocomplete="new-password">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="smtp_from_email">Absender-E-Mail</label>
                    <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo e(env('SMTP_FROM_EMAIL', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_from_name">Absender-Name</label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo e(env('SMTP_FROM_NAME', 'FF Reichenau Website')); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="smtp_to_email">Empfänger-E-Mail (Kontaktformular)</label>
                <input type="email" id="smtp_to_email" name="smtp_to_email" value="<?php echo e(env('SMTP_TO_EMAIL', 'reichenau@feuerwehr.tirol')); ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>

        <form method="POST" style="margin-top: 12px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="test_smtp">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fas fa-paper-plane"></i> Test-E-Mail senden
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fab fa-discord"></i> Discord-Webhooks</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Aktivitäts-Log, Login-Log und Rate-Limit-Ereignisse werden bei Angabe zusätzlich live an diese Discord-Kanäle gemeldet. Leer lassen zum Deaktivieren.</p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_discord_webhooks">

            <div class="form-group">
                <label for="discord_webhook_activity">Aktivitäts-Log</label>
                <input type="text" id="discord_webhook_activity" name="discord_webhook_activity" value="<?php echo e(env('DISCORD_WEBHOOK_ACTIVITY', '')); ?>" placeholder="https://discord.com/api/webhooks/...">
            </div>
            <div class="form-group">
                <label for="discord_webhook_login">Login-Log</label>
                <input type="text" id="discord_webhook_login" name="discord_webhook_login" value="<?php echo e(env('DISCORD_WEBHOOK_LOGIN', '')); ?>" placeholder="https://discord.com/api/webhooks/...">
            </div>
            <div class="form-group">
                <label for="discord_webhook_rate_limit">Rate-Limit-Ereignisse</label>
                <input type="text" id="discord_webhook_rate_limit" name="discord_webhook_rate_limit" value="<?php echo e(env('DISCORD_WEBHOOK_RATE_LIMIT', '')); ?>" placeholder="https://discord.com/api/webhooks/...">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>

<?php
$deeplConfigured = isDeepLConfigured();
$deeplUsage = $deeplConfigured ? getDeepLUsage() : null;
?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-language"></i> Automatische Übersetzung (DeepL)</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Berichte und Mitgliederprofile (Beschreibung/Bio, Funktionen) werden beim Speichern automatisch ins Englische übersetzt - Deutsch bleibt immer die normale Anzeige, Englisch erscheint nur, wenn ein Besucher über den DE/EN-Umschalter wechselt. Kostenlosen API-Key auf <a href="https://www.deepl.com/pro-api" target="_blank" rel="noopener">deepl.com/pro-api</a> anlegen (endet auf ":fx"). Leer lassen zum Deaktivieren.</p>

        <div style="margin-bottom: 16px; padding: 14px 16px; background: <?php echo $deeplConfigured ? 'rgba(39,174,96,0.08)' : 'rgba(213,0,28,0.06)'; ?>; border-radius: var(--radius);">
            <strong><?php echo $deeplConfigured ? 'DeepL ist aktiv' : 'DeepL ist nicht konfiguriert'; ?></strong>
            <?php if ($deeplConfigured && $deeplUsage !== null): ?>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                    Kontingent diesen Monat: <?php echo number_format($deeplUsage['character_count'], 0, ',', '.'); ?> von <?php echo number_format($deeplUsage['character_limit'], 0, ',', '.'); ?> Zeichen (<?php echo round($deeplUsage['character_count'] / max(1, $deeplUsage['character_limit']) * 100, 1); ?> %).
                </p>
            <?php elseif ($deeplConfigured): ?>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">Kontingent konnte nicht abgerufen werden (DeepL nicht erreichbar oder Key ungültig).</p>
            <?php endif; ?>
        </div>

        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_deepl_key">

            <div class="form-group">
                <label for="deepl_api_key">API-Key <?php echo $deeplConfigured ? '(bereits hinterlegt - nur zum Ändern ausfüllen)' : ''; ?></label>
                <input type="text" id="deepl_api_key" name="deepl_api_key" placeholder="<?php echo $deeplConfigured ? '•••••••••••••••••••••• (unverändert lassen)' : 'z.B. 53a9....0120bda974e0:fx'; ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
        <?php if ($deeplConfigured): ?>
        <form method="POST" style="margin-top: 12px; display:inline-block;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="remove_deepl_key">
            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('DeepL-API-Key wirklich entfernen? Automatische Übersetzungen werden dadurch deaktiviert.');">
                <i class="fas fa-trash"></i> Key entfernen
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($deeplConfigured):
    $reportsMissingEn = (int) $db->query("SELECT COUNT(*) FROM reports WHERE (title_en IS NULL OR title_en = '') AND title != ''")->fetchColumn();
    $membersMissingEn = (int) $db->query("SELECT COUNT(*) FROM members WHERE (bio_en IS NULL OR bio_en = '') AND bio IS NOT NULL AND bio != ''")->fetchColumn();
?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="deepl-backfill">
    <div class="admin-card-header"><h2><i class="fas fa-rotate"></i> Bestehende Inhalte nachübersetzen</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Berichte und Mitgliederprofile, die es schon vor der automatischen Übersetzung gab (oder bei denen sie zuletzt fehlgeschlagen ist), bekommen beim nächsten Speichern automatisch eine englische Fassung. Mit diesem Button holst du das für alle bestehenden Inhalte auf einmal nach, ohne jeden einzeln öffnen und speichern zu müssen.</p>
        <p style="margin-bottom: 16px;">
            Noch ohne englische Fassung: <strong><?php echo $reportsMissingEn; ?></strong> Bericht(e), <strong><?php echo $membersMissingEn; ?></strong> Mitgliederprofil(e).
        </p>
        <?php if ($reportsMissingEn > 0 || $membersMissingEn > 0): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="backfill_translations">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-language"></i> Jetzt übersetzen (bis zu 20 Berichte + 20 Mitglieder)
                </button>
            </form>
            <p class="form-hint" style="margin-top: 10px;">Bei mehr als 20 offenen Einträgen je Bereich einfach erneut klicken, bis nichts mehr offen ist. Das schont das monatliche DeepL-Kontingent (siehe oben).</p>
        <?php else: ?>
            <p style="color: #27ae60;"><i class="fas fa-check-circle"></i> Alles bereits übersetzt.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if (userHasPermission('settings.manage')): ?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-calendar-days"></i> Berichtsjahre</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Legt fest, welche Berichte auf der Berichte-Seite unter "<?php echo getBerichteAktuellesJahr(); ?>" bzw. "<?php echo getBerichteVorjahr(); ?>" erscheinen. Alle anderen Jahre landen automatisch im Archiv. Ohne Eintrag wird automatisch das echte Kalenderjahr verwendet - die Felder müssen also nur zum Jahreswechsel angepasst werden.</p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_berichte_jahre">

            <div class="form-row">
                <div class="form-group">
                    <label for="berichte_aktuelles_jahr">Aktuelles Kalenderjahr</label>
                    <input type="number" id="berichte_aktuelles_jahr" name="berichte_aktuelles_jahr" required min="2000" max="2100" value="<?php echo getBerichteAktuellesJahr(); ?>">
                </div>
                <div class="form-group">
                    <label for="berichte_vorjahr">Vergangenes Jahr</label>
                    <input type="number" id="berichte_vorjahr" name="berichte_vorjahr" required min="2000" max="2100" value="<?php echo getBerichteVorjahr(); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (userHasPermission('settings.manage')):
    $weatherTestModeEnabled = getSiteSetting('weather_warning_test_mode', '0') === '1';
    $weatherWarningEnabled = getSiteSetting('weather_warning_manual_enabled', '0') === '1';
    $weatherWarningColor = getSiteSetting('weather_warning_manual_color', '#e74c3c');
    $weatherWarningArea = getSiteSetting('weather_warning_manual_area', '');
    $weatherWarningText = getSiteSetting('weather_warning_manual_text', '');
?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="weather-warning">
    <div class="admin-card-header"><h2><i class="fas fa-triangle-exclamation"></i> Wetterwarn-Popup: Testmodus</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Zeigt das Warn-Popup mit einem einfachen Beispielinhalt an, unabhängig von den echten GeoSphere-Austria-Daten - praktisch, um schnell zu prüfen, wie das Popup auf der Website aussieht, ohne extra Texte einzugeben. Die normale Übersicht im Footer (Innsbruck-Stadt/Land, Live-Daten von GeoSphere) läuft unabhängig davon weiter.</p>

        <div style="margin-bottom: 16px; padding: 14px 16px; background: <?php echo $weatherTestModeEnabled ? 'rgba(213,0,28,0.06)' : 'rgba(39,174,96,0.08)'; ?>; border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <strong><?php echo $weatherTestModeEnabled ? 'Testmodus ist AKTIV' : 'Testmodus ist AUS'; ?></strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                    <?php echo $weatherTestModeEnabled
                        ? 'Besucher sehen bei jedem Seitenaufruf das Warn-Popup mit Beispielinhalt.'
                        : 'Das Popup erscheint nur bei einer echten Warnung oder der manuellen Warnung unten.'; ?>
                </p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_weather_test_mode">
                <input type="hidden" name="enable" value="<?php echo $weatherTestModeEnabled ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo $weatherTestModeEnabled ? 'btn-secondary' : 'btn-primary'; ?>">
                    <i class="fas fa-power-off"></i> <?php echo $weatherTestModeEnabled ? 'Deaktivieren' : 'Aktivieren'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="weather-warning-manual">
    <div class="admin-card-header"><h2><i class="fas fa-pen"></i> Wetterwarn-Popup: manuell erzeugen</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Erzwingt das Warn-Popup mit einem eigenen Text, eigenem Bereich und eigener Farbe - z.B. für einen Hinweis wie "Bereich X meiden", der nichts mit dem Wetter zu tun haben muss.</p>

        <div style="margin-bottom: 20px; padding: 14px 16px; background: <?php echo $weatherWarningEnabled ? 'rgba(213,0,28,0.06)' : 'rgba(39,174,96,0.08)'; ?>; border-radius: var(--radius);">
            <strong><?php echo $weatherWarningEnabled ? 'Manuelle Warnung ist AKTIV' : 'Manuelle Warnung ist AUS'; ?></strong>
            <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                <?php echo $weatherWarningEnabled
                    ? 'Besucher sehen bei jedem Seitenaufruf das Warn-Popup mit dem unten eingetragenen Text.'
                    : 'Das Popup erscheint nur, wenn für Innsbruck-Stadt oder Innsbruck-Land eine echte GeoSphere-Warnung aktiv ist (oder der Testmodus oben an ist).'; ?>
            </p>
        </div>

        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_weather_warning">

            <div class="form-group">
                <label>
                    <input type="checkbox" name="weather_warning_manual_enabled" value="1" <?php echo $weatherWarningEnabled ? 'checked' : ''; ?>>
                    Popup erzwingen (manuelle Warnung aktiv)
                </label>
            </div>

            <div class="form-group">
                <label for="weather_warning_manual_color">Farbe (Rahmen/Markierung im Popup)</label>
                <input type="color" id="weather_warning_manual_color" name="weather_warning_manual_color" value="<?php echo e($weatherWarningColor); ?>" style="width: 70px; height: 38px; padding: 2px; cursor: pointer;">
            </div>

            <div class="form-group">
                <label for="weather_warning_manual_area">Betroffener Bereich / Gebiet</label>
                <input type="text" id="weather_warning_manual_area" name="weather_warning_manual_area" value="<?php echo e($weatherWarningArea); ?>" placeholder="z.B. Reichenau, Sillufer - Unterführung meiden">
            </div>

            <div class="form-group">
                <label for="weather_warning_manual_text">Hinweistext</label>
                <textarea id="weather_warning_manual_text" name="weather_warning_manual_text" rows="3" placeholder="z.B. Starkregen - Unterführung Sillufer ist überflutet, bitte Bereich meiden."><?php echo e($weatherWarningText); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-info-circle"></i> System-Info</h2></div>
    <div class="admin-card-body">
        <table class="admin-table">
            <tr><td><strong>PHP Version</strong></td><td><?php echo phpversion(); ?></td></tr>
            <tr><td><strong>Datenbank</strong></td><td><?php echo 'MariaDB/MySQL ' . $db->query("SELECT VERSION()")->fetchColumn(); ?></td></tr>
            <tr><td><strong>Upload Max (pro Datei)</strong></td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
            <tr><td><strong>Post Max (ganze Anfrage)</strong></td><td><?php echo ini_get('post_max_size'); ?></td></tr>
            <tr><td><strong>Max. Dateien pro Upload</strong></td><td><?php echo ini_get('max_file_uploads'); ?></td></tr>
            <tr><td><strong>Automatische Bildverkleinerung</strong></td><td><?php echo extension_loaded('gd') ? '<span class="badge badge-success">Aktiv (GD verfügbar)</span>' : '<span class="badge badge-danger">Inaktiv (GD-Erweiterung fehlt am Server)</span>'; ?></td></tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
