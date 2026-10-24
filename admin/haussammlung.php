<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/haussammlung.php';
requireLogin();
requirePermission('haussammlung.manage');

$pageTitle = 'Haussammlung';
$activePage = 'haussammlung';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: haussammlung.php');
        exit;
    }

    $settings = getHaussammlungSettings();
    $data = [
        'enabled' => isset($_POST['enabled']),
        'von' => trim($_POST['von'] ?? ''),
        'bis' => trim($_POST['bis'] ?? ''),
        'titel' => trim($_POST['titel'] ?? ''),
        'kurztext' => trim($_POST['kurztext'] ?? ''),
        'infotext' => trim($_POST['infotext'] ?? ''),
    ];

    if ($data['von'] !== '' && $data['bis'] !== '' && $data['von'] > $data['bis']) {
        flash('error', 'Das "Von"-Datum darf nicht nach dem "Bis"-Datum liegen.');
        header('Location: haussammlung.php');
        exit;
    }

    if (!empty($_FILES['qr_image']['name'])) {
        $filename = handleImageUpload($_FILES['qr_image'], 'haussammlung');
        if ($filename) {
            $data['qr_image'] = UPLOAD_URL . $filename;
        } else {
            flash('error', 'QR-Code-Bild konnte nicht hochgeladen werden (nur JPG/PNG/GIF/WebP bis 70MB). Die übrigen Angaben wurden trotzdem gespeichert.');
        }
    }

    saveHaussammlungSettings($data);
    logActivity($db, 'haussammlung.save', $data['enabled'] ? 'Aktiviert' : 'Deaktiviert');
    flash('success', 'Die Einstellungen wurden gespeichert.');
    header('Location: haussammlung.php');
    exit;
}

$settings = getHaussammlungSettings();

require_once __DIR__ . '/includes/admin-header.php';
?>

<p style="margin-bottom:16px; color: var(--gray-600);">
    Blendet im eingestellten Zeitraum auf der ganzen Website ein kleines Popup mit QR-Code zur Haussammlung ein
    (unten rechts, schließbar, einmal pro Besuch). Ein Klick darauf führt zu einer eigenen Infoseite, die sonst
    nirgends verlinkt ist. Titel/Kurztext/Infotext werden beim Speichern automatisch ins Englische mitübersetzt
    (siehe Admin -> Einstellungen -> DeepL), damit sie auch bei umgeschalteter Sprache angezeigt werden. Aktueller Status:
    <strong><?php echo isHaussammlungActive($settings) ? 'Popup ist gerade aktiv' : 'Popup ist gerade nicht sichtbar'; ?></strong>.
</p>

<div class="admin-card">
    <div class="admin-card-header"><h2><i class="fas fa-qrcode"></i> Haussammlungs-Popup</h2></div>
    <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enabled" <?php echo $settings['enabled'] ? 'checked' : ''; ?>>
                    <span>Popup eingeschaltet</span>
                </label>
                <p class="form-hint">Wird nur im angegebenen Zeitraum tatsächlich angezeigt - außerhalb davon bleibt es unsichtbar, auch wenn diese Checkbox angehakt ist.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="von">Von</label>
                    <input type="date" id="von" name="von" value="<?php echo e($settings['von']); ?>">
                </div>
                <div class="form-group">
                    <label for="bis">Bis</label>
                    <input type="date" id="bis" name="bis" value="<?php echo e($settings['bis']); ?>">
                </div>
            </div>
            <p class="form-hint">Leer lassen für "kein Start-" bzw. "kein Enddatum".</p>

            <div class="form-group">
                <label for="titel">Titel (im Popup und auf der Infoseite)</label>
                <input type="text" id="titel" name="titel" value="<?php echo e($settings['titel']); ?>">
            </div>

            <div class="form-group">
                <label for="kurztext">Kurztext im Popup</label>
                <textarea id="kurztext" name="kurztext" rows="3"><?php echo e($settings['kurztext']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="infotext">Ausführlicher Text auf der Infoseite</label>
                <textarea id="infotext" name="infotext" rows="8"><?php echo e($settings['infotext']); ?></textarea>
                <p class="form-hint">Zeilenumbrüche werden übernommen.</p>
            </div>

            <div class="form-group">
                <label for="qr_image">QR-Code-Bild</label>
                <?php if ($settings['qr_image']): ?>
                    <div style="margin-bottom:10px;">
                        <img src="../<?php echo e($settings['qr_image']); ?>" alt="Aktueller QR-Code" style="width:140px; height:140px; object-fit:contain; border:1px solid var(--gray-200); border-radius:var(--radius);">
                    </div>
                <?php endif; ?>
                <input type="file" id="qr_image" name="qr_image" accept="image/*">
                <p class="form-hint">JPG/PNG/GIF/WebP, bis 70MB. Neues Bild ersetzt das alte beim Speichern.</p>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Speichern</button>
            <a href="../index.php?page=haussammlung" target="_blank" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> Infoseite ansehen</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
