<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/gate.php';
require_once __DIR__ . '/../config/analytics.php';
require_once __DIR__ . '/../config/berichte.php';
require_once __DIR__ . '/../config/instagram.php';
require_once __DIR__ . '/../config/logging.php';
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

    if ($action === 'save_instagram') {
        if (!userHasPermission('settings.manage')) {
            flash('error', 'Dir fehlt die Berechtigung, den Instagram-Feed einzurichten.');
            header('Location: settings.php');
            exit;
        }
        $token = trim($_POST['instagram_token'] ?? '');
        if ($token !== '') {
            saveInstagramToken($db, $token);
        }
        if (isset($_POST['instagram_remove'])) {
            saveInstagramToken($db, '');
            setHeroSetting($db, 'instagram_posts', '[]');
            logActivity($db, 'settings.instagram', 'Entfernt');
            flash('success', 'Instagram-Feed wurde deaktiviert.');
        } elseif (getInstagramToken($db) === '') {
            flash('error', 'Bitte ein Zugriffstoken eintragen.');
        } else {
            $err = refreshInstagramFeed($db);
            logActivity($db, 'settings.instagram', $err ?? 'Aktualisiert');
            flash($err ? 'error' : 'success', $err ?? 'Instagram-Feed wurde geladen.');
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

    if ($action === 'change_password') {
        $currentPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($currentPw, $user['password'])) {
            flash('error', 'Aktuelles Passwort ist falsch.');
        } elseif (strlen($newPw) < 6) {
            flash('error', 'Neues Passwort muss mindestens 6 Zeichen lang sein.');
        } elseif ($newPw !== $confirmPw) {
            flash('error', 'Passwörter stimmen nicht überein.');
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['admin_user_id']]);
            logActivity($db, 'settings.own_password');
            flash('success', 'Passwort wurde geändert.');
        }
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
<?php endif; ?>

<?php if (userHasPermission('settings.manage')): ?>
<?php if (userHasPermission('settings.manage')): ?>
<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fab fa-instagram"></i> Instagram-Feed (Startseite)</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 8px; color: #6c757d;">Zeigt die letzten Beiträge als Kacheln am Ende der Startseite. Die Bilder werden auf dem eigenen Server gespeichert, Besucher verbinden sich nicht mit Meta. Voraussetzung: Instagram-Business- oder Creator-Konto und ein Zugriffstoken (langlebig) aus einer Meta-Developer-App mit dem Produkt "Instagram API". Das Token wird automatisch verlängert.</p>
        <p style="margin-bottom: 16px; color: #6c757d;">Status: <strong><?php echo getInstagramToken($db) !== '' ? 'Token hinterlegt, ' . count(getInstagramPosts($db)) . ' Beiträge geladen' : 'nicht eingerichtet (Block bleibt unsichtbar)'; ?></strong></p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_instagram">
            <div class="form-group">
                <label for="instagram_token">Zugriffstoken</label>
                <input type="password" id="instagram_token" name="instagram_token" autocomplete="off" placeholder="<?php echo getInstagramToken($db) !== '' ? 'Gespeichert - leer lassen zum Behalten' : 'IGQ...'; ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-rotate"></i> Speichern &amp; jetzt laden</button>
            <?php if (getInstagramToken($db) !== ''): ?>
            <button type="submit" name="instagram_remove" value="1" class="btn btn-danger" onclick="return confirm('Instagram-Feed wirklich deaktivieren?');"><i class="fas fa-trash"></i> Entfernen</button>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

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

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-key"></i> Passwort ändern</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Aktuelles Passwort</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Neues Passwort</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Passwort bestätigen</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Passwort ändern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-info-circle"></i> System-Info</h2></div>
    <div class="admin-card-body">
        <table class="admin-table">
            <tr><td><strong>PHP Version</strong></td><td><?php echo phpversion(); ?></td></tr>
            <tr><td><strong>Datenbank</strong></td><td><?php echo 'MariaDB/MySQL ' . $db->query("SELECT VERSION()")->fetchColumn(); ?></td></tr>
            <tr><td><strong>Upload Max</strong></td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
            <tr><td><strong>Post Max</strong></td><td><?php echo ini_get('post_max_size'); ?></td></tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
