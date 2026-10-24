<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/site-alert.php';
requireLogin();
requirePermission('settings.manage');

$pageTitle = 'Warnhinweis';
$activePage = 'warnhinweis';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: warnhinweis.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_site_alert_test_mode') {
        $enable = ($_POST['enable'] ?? '1') === '1';
        setSiteSetting('site_alert_test_mode', $enable ? '1' : '0');
        logActivity($db, 'settings.site_alert_test', $enable ? 'Testmodus aktiviert' : 'Testmodus deaktiviert');
        flash('success', $enable
            ? 'Testmodus ist jetzt aktiv - der Warnhinweis mit Beispielinhalt erscheint ab sofort oben auf jeder Seite.'
            : 'Testmodus wurde deaktiviert.');
        header('Location: warnhinweis.php#site-alert');
        exit;
    }

    if ($action === 'save_site_alert') {
        $enabled = ($_POST['site_alert_enabled'] ?? '0') === '1';
        $type = trim($_POST['site_alert_type'] ?? 'sonstiges');
        $color = trim($_POST['site_alert_color'] ?? '#e74c3c');
        $location = trim($_POST['site_alert_location'] ?? '');
        $area = trim($_POST['site_alert_area'] ?? '');
        $text = trim($_POST['site_alert_text'] ?? '');

        if (!array_key_exists($type, getSiteAlertTypes())) {
            $type = 'sonstiges';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#e74c3c';
        }

        saveManualSiteAlert($enabled, $type, $color, $location, $area, $text);

        logActivity($db, 'settings.site_alert', $enabled ? "Warnhinweis aktiviert (Typ: $type, Ort: $location, Bereich: $area)" : 'Warnhinweis deaktiviert');
        flash('success', $enabled ? 'Der Warnhinweis ist jetzt aktiv - er erscheint ab sofort oben auf jeder Seite.' : 'Der Warnhinweis wurde deaktiviert.');
        header('Location: warnhinweis.php#site-alert-manual');
        exit;
    }

    if ($action === 'save_site_alert_category') {
        $existingKey = trim($_POST['category_key'] ?? '');
        $label = trim($_POST['category_label'] ?? '');
        $icon = trim($_POST['category_icon'] ?? 'fa-triangle-exclamation');
        $tips = trim($_POST['category_tips'] ?? '');

        if ($existingKey !== '') {
            $key = $existingKey;
        } else {
            // Neue Kategorie: Schlüssel automatisch aus dem Label ableiten
            // (klein geschrieben, nur a-z/0-9/_), damit Admins keinen
            // technischen Schlüssel von Hand eingeben müssen.
            $key = strtolower(trim($label));
            $key = preg_replace('/[^a-z0-9]+/', '_', $key);
            $key = trim($key, '_');
        }

        if ($label === '' || $key === '') {
            flash('error', 'Bitte einen Namen für die Kategorie angeben.');
        } elseif (!preg_match('/^fa-[a-z0-9-]+$/', $icon)) {
            flash('error', 'Ungültiges Icon-Kürzel (muss mit "fa-" beginnen, z.B. fa-water).');
        } else {
            saveSiteAlertCategory($key, $label, $icon, $tips);
            logActivity($db, 'settings.site_alert_category', "Kategorie \"$label\" ($key) gespeichert");
            flash('success', "Kategorie \"$label\" wurde gespeichert.");
        }
        header('Location: warnhinweis.php#site-alert-categories');
        exit;
    }

    if ($action === 'delete_site_alert_category') {
        $key = trim($_POST['category_key'] ?? '');
        if ($key !== '') {
            deleteSiteAlertCategory($key);
            logActivity($db, 'settings.site_alert_category', "Kategorie \"$key\" gelöscht");
            flash('success', 'Kategorie wurde gelöscht.');
        }
        header('Location: warnhinweis.php#site-alert-categories');
        exit;
    }

    header('Location: warnhinweis.php');
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';

$siteAlertTestModeEnabled = getSiteSetting('site_alert_test_mode', '0') === '1';
$siteAlertEnabled = getSiteSetting('site_alert_enabled', '0') === '1';
$siteAlertType = getSiteSetting('site_alert_type', 'sonstiges');
$siteAlertColor = getSiteSetting('site_alert_color', '#e74c3c');
$siteAlertLocation = getSiteSetting('site_alert_location', '');
$siteAlertArea = getSiteSetting('site_alert_area', '');
$siteAlertText = getSiteSetting('site_alert_text', '');
$siteAlertTypeOptions = getSiteAlertTypes();
?>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="site-alert">
    <div class="admin-card-header"><h2><i class="fas fa-triangle-exclamation"></i> Seiten-Warnhinweis: Testmodus</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Zeigt den Warnhinweis vollflächig über die gesamte Website und den gesamten Bildschirm an (Besucher müssen ihn mit "Verstanden" bestätigen, bevor sie die Seite sehen), mit einem einfachen Beispielinhalt - praktisch, um schnell zu prüfen, wie er aussieht, ohne extra Texte einzugeben. Die Wetterübersicht im Footer (Innsbruck-Stadt/Land/Tirol, Live-Daten von GeoSphere) läuft unabhängig davon weiter.</p>

        <div style="margin-bottom: 16px; padding: 14px 16px; background: <?php echo $siteAlertTestModeEnabled ? 'rgba(213,0,28,0.06)' : 'rgba(39,174,96,0.08)'; ?>; border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <strong><?php echo $siteAlertTestModeEnabled ? 'Testmodus ist AKTIV' : 'Testmodus ist AUS'; ?></strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                    <?php echo $siteAlertTestModeEnabled
                        ? 'Besucher sehen bei jedem Seitenaufruf den Warnhinweis mit Beispielinhalt.'
                        : 'Der Vollbild-Hinweis erscheint nur bei der manuellen Warnung unten.'; ?>
                </p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_site_alert_test_mode">
                <input type="hidden" name="enable" value="<?php echo $siteAlertTestModeEnabled ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo $siteAlertTestModeEnabled ? 'btn-secondary' : 'btn-primary'; ?>">
                    <i class="fas fa-power-off"></i> <?php echo $siteAlertTestModeEnabled ? 'Deaktivieren' : 'Aktivieren'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="admin-card" style="max-width: 600px; margin-top: 24px;" id="site-alert-manual">
    <div class="admin-card-header"><h2><i class="fas fa-pen"></i> Seiten-Warnhinweis: manuell erzeugen</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Zeigt einen eigenen Warnhinweis vollflächig über die gesamte Website und den gesamten Bildschirm - mit frei wählbarem Typ, eigenem Text, eigenem Bereich und eigener Farbe. Nicht an Wetter gebunden - z.B. für einen Hinweis wie "Bereich X meiden".</p>

        <div style="margin-bottom: 20px; padding: 14px 16px; background: <?php echo $siteAlertEnabled ? 'rgba(213,0,28,0.06)' : 'rgba(39,174,96,0.08)'; ?>; border-radius: var(--radius);">
            <strong><?php echo $siteAlertEnabled ? 'Manueller Warnhinweis ist AKTIV' : 'Manueller Warnhinweis ist AUS'; ?></strong>
            <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                <?php echo $siteAlertEnabled
                    ? 'Besucher sehen bei jedem Seitenaufruf den Warnhinweis mit den unten eingetragenen Angaben.'
                    : 'Der Vollbild-Hinweis erscheint nur, wenn aktiviert (oder der Testmodus oben an ist).'; ?>
            </p>
        </div>

        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_site_alert">

            <div class="form-group">
                <label>
                    <input type="checkbox" name="site_alert_enabled" value="1" <?php echo $siteAlertEnabled ? 'checked' : ''; ?>>
                    Warnhinweis erzwingen (manuell aktiv)
                </label>
            </div>

            <div class="form-group">
                <label for="site_alert_type">Art der Warnung</label>
                <select id="site_alert_type" name="site_alert_type">
                    <?php foreach ($siteAlertTypeOptions as $key => $opt): ?>
                        <option value="<?php echo e($key); ?>" <?php echo $siteAlertType === $key ? 'selected' : ''; ?>><?php echo e($opt['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="site_alert_color">Farbe (Rahmen/Markierung im Vollbild-Hinweis)</label>
                <input type="color" id="site_alert_color" name="site_alert_color" value="<?php echo e($siteAlertColor); ?>" style="width: 70px; height: 38px; padding: 2px; cursor: pointer;">
            </div>

            <div class="form-group">
                <label for="site_alert_location">Ort</label>
                <input type="text" id="site_alert_location" name="site_alert_location" value="<?php echo e($siteAlertLocation); ?>" placeholder="z.B. Reichenau">
            </div>

            <div class="form-group">
                <label for="site_alert_area">Bereich, der gemieden werden soll</label>
                <input type="text" id="site_alert_area" name="site_alert_area" value="<?php echo e($siteAlertArea); ?>" placeholder="z.B. Sillufer - Unterführung meiden">
            </div>

            <div class="form-group">
                <label for="site_alert_text">Hinweistext</label>
                <textarea id="site_alert_text" name="site_alert_text" rows="3" placeholder="z.B. Starkregen - Unterführung Sillufer ist überflutet, bitte Bereich meiden."><?php echo e($siteAlertText); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 700px; margin-top: 24px;" id="site-alert-categories">
    <div class="admin-card-header"><h2><i class="fas fa-list-check"></i> Warnhinweis-Kategorien & Tipps</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 20px; color: #6c757d;">Jede Kategorie hat eigene Verhaltenstipps, die im Vollbild-Warnhinweis zusätzlich zum freien Hinweistext angezeigt werden (und dauerhaft auf der öffentlichen Sicherheitstipps-Seite). Bestehende Kategorien lassen sich hier bearbeiten (Name, Icon, Tipps), eigene weitere Kategorien lassen sich unten hinzufügen.</p>

        <?php foreach ($siteAlertTypeOptions as $catKey => $cat): ?>
        <details style="margin-bottom: 12px; border: 1px solid var(--color-gray-200); border-radius: var(--radius); padding: 0;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <i class="fas <?php echo e($cat['icon']); ?>"></i> <?php echo e($cat['label']); ?>
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--color-gray-200);">
                <form method="POST" class="admin-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_site_alert_category">
                    <input type="hidden" name="category_key" value="<?php echo e($catKey); ?>">

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="category_label" value="<?php echo e($cat['label']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Icon (Font Awesome Kürzel)</label>
                        <input type="text" name="category_icon" value="<?php echo e($cat['icon']); ?>" placeholder="z.B. fa-water">
                    </div>
                    <div class="form-group">
                        <label>Verhaltenstipps (eine Zeile = ein Tipp, Zeilen mit "- " werden als Liste angezeigt)</label>
                        <textarea name="category_tips" rows="8"><?php echo e($cat['tips'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Speichern
                    </button>
                </form>
                <form method="POST" style="margin-top: 10px;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete_site_alert_category">
                    <input type="hidden" name="category_key" value="<?php echo e($catKey); ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" data-confirm-title="Kategorie löschen?" data-confirm-text="Die Kategorie &quot;<?php echo e($cat['label']); ?>&quot; inkl. ihrer Tipps wird endgültig gelöscht. Ist sie gerade als manueller Warnhinweis aktiv, wird dieser automatisch deaktiviert." data-confirm-ok="Löschen">
                        <i class="fas fa-trash"></i> Kategorie löschen
                    </button>
                </form>
            </div>
        </details>
        <?php endforeach; ?>

        <details style="margin-top: 20px; border: 1px dashed var(--color-gray-300, var(--color-gray-200)); border-radius: var(--radius);">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-plus"></i> Neue Kategorie hinzufügen
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--color-gray-200);">
                <form method="POST" class="admin-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_site_alert_category">
                    <input type="hidden" name="category_key" value="">

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="category_label" placeholder="z.B. Lawine" required>
                    </div>
                    <div class="form-group">
                        <label>Icon (Font Awesome Kürzel)</label>
                        <input type="text" name="category_icon" value="fa-triangle-exclamation" placeholder="z.B. fa-mountain">
                    </div>
                    <div class="form-group">
                        <label>Verhaltenstipps</label>
                        <textarea name="category_tips" rows="6" placeholder="- Tipp 1&#10;- Tipp 2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Kategorie anlegen
                    </button>
                </form>
            </div>
        </details>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
