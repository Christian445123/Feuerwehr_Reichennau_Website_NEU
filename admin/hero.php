<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/hero.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('media.manage');

$pageTitle = 'Startseiten-Hintergrund';
$activePage = 'hero';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: hero.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $images = getHeroImages($db);

    if ($action === 'save_settings') {
        $mode = ($_POST['hero_mode'] ?? '') === 'single' ? 'single' : 'slideshow';
        $interval = max(2, min(60, (int) ($_POST['hero_interval'] ?? 6)));
        setHeroSetting($db, 'hero_mode', $mode);
        setHeroSetting($db, 'hero_interval', (string) $interval);
        logActivity($db, 'media.hero_settings', $mode === 'single' ? 'Einzelbild' : "Diashow ($interval s)");
        flash('success', 'Einstellungen wurden gespeichert.');
    }

    if ($action === 'add_images') {
        $added = 0;
        $files = $_FILES['photos'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $filename = handleImageUpload([
                    'name' => $name,
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ], 'hero');
                if ($filename) {
                    $images[] = UPLOAD_URL . $filename;
                    $added++;
                }
            }
        }
        if ($added > 0) {
            saveHeroImages($db, $images);
            logActivity($db, 'media.hero_add', "$added Bild(er)");
            flash('success', $added . ' Bild(er) hinzugefügt.');
        } else {
            flash('error', 'Kein Bild hochgeladen (nur JPG/PNG/GIF/WebP bis 10MB).');
        }
    }

    if ($action === 'delete_image' || $action === 'move_up' || $action === 'make_first') {
        $idx = (int) ($_POST['index'] ?? -1);
        if (isset($images[$idx])) {
            if ($action === 'delete_image') {
                $path = $images[$idx];
                array_splice($images, $idx, 1);
                if (str_starts_with($path, 'uploads/hero/') && file_exists(__DIR__ . '/../' . $path)) {
                    unlink(__DIR__ . '/../' . $path);
                }
                logActivity($db, 'media.hero_delete', $path);
                flash('success', 'Bild wurde gelöscht.');
            } elseif ($action === 'move_up' && $idx > 0) {
                [$images[$idx - 1], $images[$idx]] = [$images[$idx], $images[$idx - 1]];
            } elseif ($action === 'make_first') {
                $img = array_splice($images, $idx, 1);
                array_unshift($images, $img[0]);
                flash('success', 'Bild steht jetzt an erster Stelle.');
            }
            saveHeroImages($db, $images);
        }
    }

    header('Location: hero.php');
    exit;
}

$images = getHeroImages($db);
$mode = getHeroMode($db);
$interval = getHeroInterval($db);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 700px;">
    <div class="admin-card-header"><h2><i class="fas fa-sliders"></i> Anzeige</h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_settings">
            <div class="form-group">
                <label><input type="radio" name="hero_mode" value="slideshow" <?php echo $mode === 'slideshow' ? 'checked' : ''; ?>> Diashow (alle Bilder wechseln automatisch)</label><br>
                <label><input type="radio" name="hero_mode" value="single" <?php echo $mode === 'single' ? 'checked' : ''; ?>> Stehendes Bild (nur das erste Bild der Liste)</label>
            </div>
            <div class="form-group">
                <label for="hero_interval">Sekunden pro Bild (Diashow)</label>
                <input type="number" id="hero_interval" name="hero_interval" min="2" max="60" value="<?php echo $interval; ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Speichern</button>
        </form>
    </div>
</div>

<div class="admin-card" style="max-width: 700px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-images"></i> Bilder (<?php echo count($images); ?>)</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: #6c757d;">Ohne Bilder bleibt der Standard-Hintergrund. Die Reihenfolge entspricht der Diashow; im Modus "Stehendes Bild" wird das erste Bild angezeigt.</p>
        <div class="vehicle-photo-grid">
            <?php foreach ($images as $i => $path): ?>
                <div class="vehicle-photo-item">
                    <img src="../<?php echo e($path); ?>" alt="Hintergrundbild <?php echo $i + 1; ?>">
                    <?php if ($i === 0): ?><span class="badge badge-success">Erstes Bild</span><?php endif; ?>
                    <?php if ($i > 0): ?>
                    <form method="POST" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="make_first">
                        <input type="hidden" name="index" value="<?php echo $i; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" title="An erste Stelle"><i class="fas fa-angles-up"></i></button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="move_up">
                        <input type="hidden" name="index" value="<?php echo $i; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Nach vorne"><i class="fas fa-arrow-up"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Dieses Bild wirklich löschen?');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="index" value="<?php echo $i; ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" class="admin-form" style="margin-top: 20px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add_images">
            <div class="form-group">
                <label for="photos">Bilder hinzufügen (mehrere möglich)</label>
                <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Hochladen</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
