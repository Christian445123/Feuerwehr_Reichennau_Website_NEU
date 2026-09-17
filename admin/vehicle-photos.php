<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/media.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('media.manage');

$pageTitle = 'Fahrzeug- & Wache-Fotos';
$activePage = 'vehicle-photos';

$db = getDB();
$vehicleLabels = getVehicleLabels();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: vehicle-photos.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_vehicle_photo') {
        $vehicleKey = $_POST['vehicle_key'] ?? '';
        if (!isset($vehicleLabels[$vehicleKey])) {
            flash('error', 'Unbekanntes Fahrzeug.');
        } elseif (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Bitte ein Foto auswählen.');
        } else {
            $filename = handleImageUpload($_FILES['photo'], 'vehicles');
            if ($filename) {
                addVehiclePhoto($db, $vehicleKey, UPLOAD_URL . $filename);
                logActivity($db, 'media.vehicle_photo_add', $vehicleLabels[$vehicleKey]);
                flash('success', 'Foto wurde hinzugefügt.');
            } else {
                flash('error', 'Foto konnte nicht hochgeladen werden (nur JPG/PNG/GIF/WebP bis 10MB).');
            }
        }
    }

    if ($action === 'delete_vehicle_photo') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        deleteVehiclePhoto($db, $photoId);
        logActivity($db, 'media.vehicle_photo_delete', "Foto-ID $photoId");
        flash('success', 'Foto wurde gelöscht.');
    }

    if ($action === 'replace_media_slot') {
        $slotKey = $_POST['slot_key'] ?? '';
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Bitte ein Foto auswählen.');
        } else {
            $filename = handleImageUpload($_FILES['photo'], 'wache');
            if ($filename) {
                setMediaSlotPhoto($db, $slotKey, UPLOAD_URL . $filename);
                logActivity($db, 'media.slot_replace', $slotKey);
                flash('success', 'Foto wurde ausgetauscht.');
            } else {
                flash('error', 'Foto konnte nicht hochgeladen werden (nur JPG/PNG/GIF/WebP bis 10MB).');
            }
        }
    }

    header('Location: vehicle-photos.php');
    exit;
}

$allVehiclePhotos = getAllVehiclePhotos($db);
$mediaSlots = getAllMediaSlots($db);

require_once __DIR__ . '/includes/admin-header.php';
?>

<p style="margin-bottom: 20px; color: var(--gray-600);">Hier können die Fotos der Fahrzeuge (Ausrüstung -&gt; Fuhrpark) und der Wache ausgetauscht werden, ohne den Code zu bearbeiten. Das erste Foto je Fahrzeug ist das Hauptbild, alle weiteren erscheinen als Vorschaubilder.</p>

<?php foreach ($vehicleLabels as $key => $label): ?>
    <div class="admin-card" style="margin-bottom: 24px;">
        <div class="admin-card-header"><h2><i class="fas fa-truck"></i> <?php echo e($label); ?></h2></div>
        <div class="admin-card-body">
            <div class="vehicle-photo-grid">
                <?php foreach (($allVehiclePhotos[$key] ?? []) as $i => $photo): ?>
                    <div class="vehicle-photo-item">
                        <img src="../<?php echo e($photo['photo_path']); ?>" alt="<?php echo e($label); ?>">
                        <?php if ($i === 0): ?><span class="badge badge-success">Hauptbild</span><?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Dieses Foto wirklich löschen?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_vehicle_photo">
                            <input type="hidden" name="photo_id" value="<?php echo (int) $photo['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="admin-form" style="margin-top: 16px;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add_vehicle_photo">
                <input type="hidden" name="vehicle_key" value="<?php echo e($key); ?>">
                <div class="form-group" style="max-width: 400px;">
                    <input type="file" name="photo" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Foto hinzufügen</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-building"></i> Wache-Fotos</h2></div>
    <div class="admin-card-body">
        <div class="vehicle-photo-grid">
            <?php foreach ($mediaSlots as $slot): ?>
                <div class="vehicle-photo-item">
                    <img src="../<?php echo e($slot['photo_path']); ?>" alt="<?php echo e($slot['label']); ?>">
                    <span class="badge badge-draft"><?php echo e($slot['label']); ?></span>
                    <form method="POST" enctype="multipart/form-data" class="vehicle-photo-replace-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="replace_media_slot">
                        <input type="hidden" name="slot_key" value="<?php echo e($slot['slot_key']); ?>">
                        <input type="file" name="photo" accept="image/*" required onchange="this.form.submit()">
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-arrows-rotate"></i> Austauschen</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
