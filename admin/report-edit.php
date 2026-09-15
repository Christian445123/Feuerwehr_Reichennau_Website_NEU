<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
requireLogin();
requirePermission('reports.manage');

$db = getDB();
$activePage = 'reports';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Bericht bearbeiten' : 'Neuer Bericht';

$report = [
    'title' => '', 'category' => 'einsatz', 'content' => '',
    'date' => date('Y-m-d'), 'author' => '', 'published' => 1
];
$images = [];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Bericht nicht gefunden.');
        header('Location: reports.php');
        exit;
    }
    $report = $found;

    $imgStmt = $db->prepare("SELECT * FROM report_images WHERE report_id = ? ORDER BY sort_order ASC");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll();
}

// Bild löschen
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image']) && $isEdit) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $imgId = (int)$_GET['delete_image'];
        $imgStmt = $db->prepare("SELECT filename FROM report_images WHERE id = ? AND report_id = ?");
        $imgStmt->execute([$imgId, $id]);
        $img = $imgStmt->fetch();
        if ($img) {
            $path = UPLOAD_PATH . $img['filename'];
            if (file_exists($path)) unlink($path);
            $db->prepare("DELETE FROM report_images WHERE id = ?")->execute([$imgId]);
            flash('success', 'Bild wurde gelöscht.');
        }
    }
    header("Location: report-edit.php?id=$id");
    exit;
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header("Location: report-edit.php" . ($isEdit ? "?id=$id" : ""));
        exit;
    }

    $report['title'] = trim($_POST['title'] ?? '');
    $report['category'] = $_POST['category'] ?? 'einsatz';
    $report['content'] = trim($_POST['content'] ?? '');
    $report['date'] = $_POST['date'] ?? date('Y-m-d');
    $report['author'] = trim($_POST['author'] ?? '');
    $report['published'] = isset($_POST['published']) ? 1 : 0;

    $validCats = ['einsatz', 'uebung', 'jugend', 'veranstaltungen', 'sonstige'];
    if (!in_array($report['category'], $validCats, true)) {
        $report['category'] = 'einsatz';
    }

    if (empty($report['title'])) {
        flash('error', 'Titel ist erforderlich.');
    } else {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE reports SET title=?, category=?, content=?, date=?, author=?, published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$report['title'], $report['category'], $report['content'], $report['date'], $report['author'], $report['published'], $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO reports (title, category, content, date, author, published) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$report['title'], $report['category'], $report['content'], $report['date'], $report['author'], $report['published']]);
            $id = $db->lastInsertId();
            $isEdit = true;
        }

        // Bilder hochladen
        if (!empty($_FILES['images']['name'][0])) {
            $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM report_images WHERE report_id = ?");
            $maxSort->execute([$id]);
            $sortOrder = (int)$maxSort->fetchColumn();

            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];

                $filename = handleImageUpload($file, 'reports');
                if ($filename) {
                    $sortOrder++;
                    $caption = trim($_POST['image_captions'][$i] ?? '');
                    $stmt = $db->prepare("INSERT INTO report_images (report_id, filename, caption, sort_order) VALUES (?,?,?,?)");
                    $stmt->execute([$id, $filename, $caption, $sortOrder]);
                }
            }
        }

        flash('success', $isEdit ? 'Bericht wurde aktualisiert.' : 'Bericht wurde erstellt.');
        header("Location: report-edit.php?id=$id");
        exit;
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="reports.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="form-grid">
        <div class="form-main">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-edit"></i> Bericht-Daten</h2></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label for="title">Titel *</label>
                        <input type="text" id="title" name="title" value="<?php echo e($report['title']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Kategorie</label>
                            <select id="category" name="category">
                                <option value="einsatz" <?php echo $report['category'] === 'einsatz' ? 'selected' : ''; ?>>Einsatz</option>
                                <option value="uebung" <?php echo $report['category'] === 'uebung' ? 'selected' : ''; ?>>Übung</option>
                                <option value="jugend" <?php echo $report['category'] === 'jugend' ? 'selected' : ''; ?>>Jugend</option>
                                <option value="veranstaltungen" <?php echo $report['category'] === 'veranstaltungen' ? 'selected' : ''; ?>>Veranstaltungen</option>
                                <option value="sonstige" <?php echo $report['category'] === 'sonstige' ? 'selected' : ''; ?>>Sonstige</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date">Datum</label>
                            <input type="date" id="date" name="date" value="<?php echo e($report['date']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="content">Inhalt</label>
                        <textarea id="content" name="content" rows="10"><?php echo e($report['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="author">Autor</label>
                        <input type="text" id="author" name="author" value="<?php echo e($report['author']); ?>">
                    </div>
                </div>
            </div>

            <!-- Bilder hochladen -->
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-images"></i> Fotos hinzufügen</h2></div>
                <div class="admin-card-body">
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Bilder hierher ziehen oder klicken zum Auswählen</p>
                        <p class="text-small">JPG, PNG, GIF, WebP - max. 10MB pro Bild</p>
                        <input type="file" name="images[]" id="imageInput" multiple accept="image/*" class="file-input">
                    </div>

                    <div id="previewContainer" class="image-preview-grid"></div>
                </div>
            </div>

            <!-- Vorhandene Bilder -->
            <?php if (!empty($images)): ?>
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-photo-video"></i> Vorhandene Fotos (<?php echo count($images); ?>)</h2></div>
                <div class="admin-card-body">
                    <div class="existing-images-grid">
                        <?php foreach ($images as $img): ?>
                            <div class="existing-image">
                                <img src="../<?php echo e(UPLOAD_URL . $img['filename']); ?>" alt="<?php echo e($img['caption']); ?>">
                                <div class="existing-image-overlay">
                                    <span class="existing-image-caption"><?php echo e($img['caption'] ?: 'Ohne Beschreibung'); ?></span>
                                    <a href="report-edit.php?id=<?php echo $id; ?>&delete_image=<?php echo $img['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Bild löschen?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-sidebar">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-cog"></i> Optionen</h2></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="published" <?php echo $report['published'] ? 'checked' : ''; ?>>
                            <span>Veröffentlicht</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Speichern' : 'Erstellen'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Drag & Drop + Preview
var uploadArea = document.getElementById('uploadArea');
var imageInput = document.getElementById('imageInput');
var previewContainer = document.getElementById('previewContainer');

uploadArea.addEventListener('click', function() { imageInput.click(); });

uploadArea.addEventListener('dragover', function(e) { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', function() { uploadArea.classList.remove('dragover'); });
uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    imageInput.files = e.dataTransfer.files;
    showPreviews();
});

imageInput.addEventListener('change', showPreviews);

function showPreviews() {
    previewContainer.innerHTML = '';
    Array.from(imageInput.files).forEach(function(file, i) {
        if (!file.type.startsWith('image/')) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'image-preview';
            div.innerHTML = '<img src="' + e.target.result + '" alt="Vorschau">' +
                '<input type="text" name="image_captions[]" placeholder="Beschreibung (optional)" class="preview-caption">';
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
