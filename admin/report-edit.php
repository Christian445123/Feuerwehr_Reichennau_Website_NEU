<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('reports.manage');

$db = getDB();
$activePage = 'reports';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Bericht bearbeiten' : 'Neuer Bericht';

$report = [
    'title' => '', 'category' => 'einsatz', 'subcategory' => '', 'content' => '',
    'date' => date('Y-m-d'), 'author' => '', 'published' => 1, 'social_link' => ''
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

// Bild als Titelbild (Startfoto) festlegen: bekommt die kleinste Reihenfolge-Nummer
if (isset($_GET['cover_image']) && is_numeric($_GET['cover_image']) && $isEdit) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $imgId = (int)$_GET['cover_image'];
        $chk = $db->prepare("SELECT id FROM report_images WHERE id = ? AND report_id = ?");
        $chk->execute([$imgId, $id]);
        if ($chk->fetch()) {
            $min = $db->prepare("SELECT COALESCE(MIN(sort_order), 0) FROM report_images WHERE report_id = ?");
            $min->execute([$id]);
            $db->prepare("UPDATE report_images SET sort_order = ? WHERE id = ?")->execute([((int)$min->fetchColumn()) - 1, $imgId]);
            logActivity($db, 'report.image_cover', "Bericht #$id: Bild #$imgId");
            flash('success', 'Titelbild wurde festgelegt.');
        }
    }
    header("Location: report-edit.php?id=$id");
    exit;
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
            logActivity($db, 'report.image_delete', "Bericht #$id: " . $img['filename']);
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
    $report['subcategory'] = $_POST['subcategory'] ?? '';
    $report['content'] = trim($_POST['content'] ?? '');
    $report['date'] = $_POST['date'] ?? date('Y-m-d');
    $report['author'] = trim($_POST['author'] ?? '');
    $report['published'] = isset($_POST['published']) ? 1 : 0;
    $report['social_link'] = trim($_POST['social_link'] ?? '');

    $validCats = ['einsatz', 'uebung', 'jugend', 'veranstaltungen', 'sonstige'];
    if (!in_array($report['category'], $validCats, true)) {
        $report['category'] = 'einsatz';
    }

    // Subkategorie gibt es bei Einsätzen (Brand/Technisch/ABC/Unterstützung/
    // Sonstiges) und bei Übungen (Brand/Technisch/ABC/Sonstiges - ohne
    // Unterstützung, das ist ein reiner Einsatz-Begriff). Bei allen anderen
    // Kategorien wird sie nicht gespeichert.
    $validSubcatsByCategory = [
        'einsatz' => ['brand', 'technisch', 'abc', 'unterstuetzung', 'sonstiges'],
        'uebung' => ['brand', 'technisch', 'abc', 'sonstiges'],
    ];
    $allowedSubcats = $validSubcatsByCategory[$report['category']] ?? [];
    if (!in_array($report['subcategory'], $allowedSubcats, true)) {
        $report['subcategory'] = '';
    }

    if (empty($report['title'])) {
        flash('error', 'Titel ist erforderlich.');
    } elseif ($report['social_link'] !== '' && !filter_var($report['social_link'], FILTER_VALIDATE_URL)) {
        flash('error', 'Der Social-Media-Link ist keine gültige URL (z.B. https://www.instagram.com/p/...).');
    } else {
        $wasCreate = !$isEdit;
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE reports SET title=?, category=?, subcategory=?, content=?, date=?, author=?, published=?, social_link=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$report['title'], $report['category'], $report['subcategory'] ?: null, $report['content'], $report['date'], $report['author'], $report['published'], $report['social_link'] ?: null, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO reports (title, category, subcategory, content, date, author, published, social_link) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$report['title'], $report['category'], $report['subcategory'] ?: null, $report['content'], $report['date'], $report['author'], $report['published'], $report['social_link'] ?: null]);
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

        logActivity($db, $wasCreate ? 'report.create' : 'report.update', $report['title']);
        flash('success', $wasCreate ? 'Bericht wurde erstellt.' : 'Bericht wurde aktualisiert.');
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

                    <?php $showSubcat = in_array($report['category'], ['einsatz', 'uebung'], true); ?>
                    <div class="form-group" id="subcategoryGroup" style="<?php echo $showSubcat ? '' : 'display:none;'; ?>">
                        <label for="subcategory" id="subcategoryLabel"><?php echo $report['category'] === 'uebung' ? 'Übungsart' : 'Einsatzart'; ?></label>
                        <select id="subcategory" name="subcategory">
                            <option value="">-- Bitte wählen --</option>
                            <option value="brand" <?php echo ($report['subcategory'] ?? '') === 'brand' ? 'selected' : ''; ?>>Brand</option>
                            <option value="technisch" <?php echo ($report['subcategory'] ?? '') === 'technisch' ? 'selected' : ''; ?>>Technisch</option>
                            <option value="abc" <?php echo ($report['subcategory'] ?? '') === 'abc' ? 'selected' : ''; ?>>ABC</option>
                            <option value="unterstuetzung" class="subcat-unterstuetzung" <?php echo $report['category'] === 'uebung' ? 'style="display:none;" disabled' : ''; ?> <?php echo ($report['subcategory'] ?? '') === 'unterstuetzung' ? 'selected' : ''; ?>>Unterstützung</option>
                            <option value="sonstiges" <?php echo ($report['subcategory'] ?? '') === 'sonstiges' ? 'selected' : ''; ?>>Sonstiges</option>
                        </select>
                        <p class="form-hint" id="subcategoryHint">Bestimmt die <?php echo $report['category'] === 'uebung' ? 'Übungsart' : 'Einsatzart für Statistik und Kennzeichnung'; ?> (Brand/Technisch/ABC<?php echo $report['category'] === 'uebung' ? '' : '/Unterstützung'; ?>/Sonstiges).</p>
                    </div>

                    <div class="form-group">
                        <label for="content">Inhalt</label>
                        <textarea id="content" name="content" rows="10"><?php echo e($report['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="author">Autor</label>
                        <input type="text" id="author" name="author" value="<?php echo e($report['author']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="social_link"><i class="fab fa-instagram"></i> Instagram-Beitrag oder Website verlinken</label>
                        <input type="url" id="social_link" name="social_link" value="<?php echo e($report['social_link'] ?? ''); ?>" placeholder="z.B. https://www.instagram.com/p/... oder eine externe Website">
                        <p class="form-hint">Optional: Link zu einem Instagram-Beitrag oder einer externen Website (z.B. Zeitungsartikel, feuerwehr.tirol). Wird beim veröffentlichten Bericht als Button angezeigt.</p>
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
                                <?php if ($img === $images[0]): ?><span class="badge badge-success" style="position:absolute;top:6px;left:6px;z-index:2;">Titelbild</span><?php endif; ?>
                                <img src="../<?php echo e(UPLOAD_URL . $img['filename']); ?>" alt="<?php echo e($img['caption']); ?>">
                                <div class="existing-image-overlay">
                                    <span class="existing-image-caption"><?php echo e($img['caption'] ?: 'Ohne Beschreibung'); ?></span>
                                    <?php if ($img !== $images[0]): ?>
                                    <a href="report-edit.php?id=<?php echo $id; ?>&cover_image=<?php echo $img['id']; ?>&token=<?php echo e(csrfToken()); ?>" class="btn btn-sm btn-primary" title="Als Titelbild verwenden"><i class="fas fa-star"></i></a>
                                    <?php endif; ?>
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
// Einsatz-/Übungsart-Feld nur bei Kategorie "Einsatz" oder "Übung" anzeigen.
// Bei "Übung" gibt es keine Option "Unterstützung" (reiner Einsatz-Begriff).
var categorySelect = document.getElementById('category');
var subcategoryGroup = document.getElementById('subcategoryGroup');
var subcategoryLabel = document.getElementById('subcategoryLabel');
var subcategoryHint = document.getElementById('subcategoryHint');
var subcategorySelect = document.getElementById('subcategory');
var subcatUnterstuetzung = document.querySelector('.subcat-unterstuetzung');

if (categorySelect && subcategoryGroup) {
    categorySelect.addEventListener('change', function() {
        var isUebung = this.value === 'uebung';
        var showSubcat = this.value === 'einsatz' || isUebung;
        subcategoryGroup.style.display = showSubcat ? '' : 'none';

        if (subcategoryLabel) subcategoryLabel.textContent = isUebung ? 'Übungsart' : 'Einsatzart';
        if (subcategoryHint) {
            subcategoryHint.textContent = 'Bestimmt die ' + (isUebung ? 'Übungsart' : 'Einsatzart für Statistik und Kennzeichnung') +
                ' (Brand/Technisch/ABC' + (isUebung ? '' : '/Unterstützung') + '/Sonstiges).';
        }
        if (subcatUnterstuetzung) {
            subcatUnterstuetzung.style.display = isUebung ? 'none' : '';
            subcatUnterstuetzung.disabled = isUebung;
            if (isUebung && subcategorySelect.value === 'unterstuetzung') {
                subcategorySelect.value = '';
            }
        }
    });
}

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
