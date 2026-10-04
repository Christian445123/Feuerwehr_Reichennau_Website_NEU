<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/berichte.php';
requireLogin();
requirePermission('reports.manage');

$db = getDB();
$activePage = 'reports';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Bericht bearbeiten' : 'Neuer Bericht';

$report = [
    'title' => '', 'category' => 'einsatz', 'subcategory' => '', 'category2' => '', 'subcategory2' => '', 'content' => '',
    'date' => date('Y-m-d'), 'author' => '', 'published' => 1, 'links_hidden' => 0,
];
$images = [];
$links = [];

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
    $links = getReportLinks($report);

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
    // Wenn die Anfrage zusammen größer als post_max_size ist, leert PHP
    // $_POST/$_FILES komplett und OHNE Fehlermeldung - das sah bisher wie ein
    // ungültiger Sicherheits-Token oder ein fehlender Titel aus und war der
    // Hauptgrund, warum "keine Bilder hinzugefügt werden konnten".
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'Die ausgewählten Dateien sind zusammen zu groß für den Server (aktuelles Limit: ' . ini_get('post_max_size') . '). Bitte weniger Bilder auf einmal hochladen.');
        header("Location: report-edit.php" . ($isEdit ? "?id=$id" : ""));
        exit;
    }

    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header("Location: report-edit.php" . ($isEdit ? "?id=$id" : ""));
        exit;
    }

    $report['title'] = trim($_POST['title'] ?? '');
    $report['category'] = $_POST['category'] ?? 'einsatz';
    $report['subcategory'] = is_array($_POST['subcategory'] ?? null) ? $_POST['subcategory'] : [];
    $report['category2'] = $_POST['category2'] ?? '';
    $report['subcategory2'] = is_array($_POST['subcategory2'] ?? null) ? $_POST['subcategory2'] : [];
    $report['content'] = trim($_POST['content'] ?? '');
    $report['date'] = $_POST['date'] ?? date('Y-m-d');
    $report['author'] = trim($_POST['author'] ?? '');
    $report['published'] = isset($_POST['published']) ? 1 : 0;
    $report['links_hidden'] = isset($_POST['links_hidden']) ? 1 : 0;

    // Beliebig viele Links, je mit optionaler eigener Beschreibung - leere
    // URL-Felder werden ignoriert, jede ausgefüllte URL muss gültig sein.
    $links = [];
    $linkErrors = [];
    $linkLabels = $_POST['link_labels'] ?? [];
    foreach ($_POST['links'] ?? [] as $i => $rawLink) {
        $url = trim($rawLink);
        if ($url === '') continue;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $linkErrors[] = $url;
            continue;
        }
        $links[] = ['url' => $url, 'label' => trim($linkLabels[$i] ?? '')];
    }

    // Subkategorie gibt es bei Einsätzen (Brand/Technisch/ABC/Unterstützung/
    // Sonstiges) und bei Übungen (Brand/Technisch/ABC/Sonstiges - ohne
    // Unterstützung, das ist ein reiner Einsatz-Begriff). Bei allen anderen
    // Kategorien wird sie nicht gespeichert.
    [$report['category'], $report['subcategory']] = sanitizeReportCategory($report['category'], $report['subcategory']);
    // Die zweite Kategorie ist optional (z.B. ein Einsatz, der während einer
    // Übung stattfand) - "keine zweite Kategorie" ist ein gültiger Zustand.
    [$report['category2'], $report['subcategory2']] = sanitizeReportCategory($report['category2'], $report['subcategory2'], true);

    if (empty($report['title'])) {
        flash('error', 'Titel ist erforderlich.');
    } elseif (!empty($linkErrors)) {
        flash('error', 'Ungültige Link-URL: ' . implode(', ', $linkErrors) . ' (z.B. https://www.instagram.com/p/...).');
    } else {
        $linksJson = !empty($links) ? json_encode($links, JSON_UNESCAPED_UNICODE) : null;
        $wasCreate = !$isEdit;
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE reports SET title=?, category=?, subcategory=?, category2=?, subcategory2=?, content=?, date=?, author=?, published=?, links=?, links_hidden=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$report['title'], $report['category'], $report['subcategory'] ?: null, $report['category2'] ?: null, $report['subcategory2'] ?: null, $report['content'], $report['date'], $report['author'], $report['published'], $linksJson, $report['links_hidden'], $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO reports (title, category, subcategory, category2, subcategory2, content, date, author, published, links, links_hidden) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$report['title'], $report['category'], $report['subcategory'] ?: null, $report['category2'] ?: null, $report['subcategory2'] ?: null, $report['content'], $report['date'], $report['author'], $report['published'], $linksJson, $report['links_hidden']]);
            $id = $db->lastInsertId();
            $isEdit = true;
        }

        // Bilder hochladen (beliebig viele - jede Datei wird automatisch
        // verkleinert/komprimiert, siehe handleImageUpload()/resizeAndCompressImage())
        $uploadErrors = [];
        if (!empty($_FILES['images']['name'][0])) {
            $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM report_images WHERE report_id = ?");
            $maxSort->execute([$id]);
            $sortOrder = (int)$maxSort->fetchColumn();

            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];

                $uploadError = null;
                $filename = handleImageUpload($file, 'reports', $uploadError);
                if ($filename) {
                    $sortOrder++;
                    $caption = trim($_POST['image_captions'][$i] ?? '');
                    $stmt = $db->prepare("INSERT INTO report_images (report_id, filename, caption, sort_order) VALUES (?,?,?,?)");
                    $stmt->execute([$id, $filename, $caption, $sortOrder]);
                } else {
                    $uploadErrors[] = ($name ?: 'Bild ' . ($i + 1)) . ': ' . ($uploadError ?? 'Unbekannter Fehler');
                }
            }
        }

        logActivity($db, $wasCreate ? 'report.create' : 'report.update', $report['title']);
        if (!empty($uploadErrors)) {
            flash('error', 'Bericht gespeichert, aber einige Bilder konnten nicht hochgeladen werden: ' . implode('; ', $uploadErrors));
        } else {
            flash('success', $wasCreate ? 'Bericht wurde erstellt.' : 'Bericht wurde aktualisiert.');
        }
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
                    <?php $subcatValues = getReportSubcategoryList($report['subcategory'] ?? ''); ?>
                    <div class="form-group" id="subcategoryGroup" style="<?php echo $showSubcat ? '' : 'display:none;'; ?>">
                        <label id="subcategoryLabel"><?php echo $report['category'] === 'uebung' ? 'Übungsart(en)' : 'Einsatzart(en)'; ?></label>
                        <div class="subcat-checkboxes">
                            <label><input type="checkbox" name="subcategory[]" value="brand" <?php echo in_array('brand', $subcatValues, true) ? 'checked' : ''; ?>> Brand</label>
                            <label><input type="checkbox" name="subcategory[]" value="technisch" <?php echo in_array('technisch', $subcatValues, true) ? 'checked' : ''; ?>> Technisch</label>
                            <label><input type="checkbox" name="subcategory[]" value="abc" <?php echo in_array('abc', $subcatValues, true) ? 'checked' : ''; ?>> ABC</label>
                            <label class="subcat-unterstuetzung" style="<?php echo $report['category'] === 'uebung' ? 'display:none;' : ''; ?>"><input type="checkbox" name="subcategory[]" value="unterstuetzung" <?php echo in_array('unterstuetzung', $subcatValues, true) ? 'checked' : ''; ?> <?php echo $report['category'] === 'uebung' ? 'disabled' : ''; ?>> Unterstützung</label>
                            <label><input type="checkbox" name="subcategory[]" value="sonstiges" <?php echo in_array('sonstiges', $subcatValues, true) ? 'checked' : ''; ?>> Sonstiges</label>
                        </div>
                        <p class="form-hint" id="subcategoryHint">Mehrfachauswahl möglich (max. <?php echo $report['category'] === 'uebung' ? '3 bei Übung' : '2 bei Einsatz'; ?>) - z.&nbsp;B. bei einem Bezirksübungstag mit Brand-, Technisch- und ABC-Teil, oder einem Einsatz, der von Brand in ABC überging. Bestimmt die <?php echo $report['category'] === 'uebung' ? 'Übungsart(en)' : 'Einsatzart(en) für Statistik und Kennzeichnung'; ?>.</p>
                    </div>

                    <div class="form-row" style="border-top: 1px dashed var(--gray-300); padding-top: 16px; margin-top: 4px;">
                        <div class="form-group">
                            <label for="category2">Zweite Kategorie (optional)</label>
                            <select id="category2" name="category2">
                                <option value="" <?php echo ($report['category2'] ?? '') === '' ? 'selected' : ''; ?>>-- Keine --</option>
                                <option value="einsatz" <?php echo ($report['category2'] ?? '') === 'einsatz' ? 'selected' : ''; ?>>Einsatz</option>
                                <option value="uebung" <?php echo ($report['category2'] ?? '') === 'uebung' ? 'selected' : ''; ?>>Übung</option>
                                <option value="jugend" <?php echo ($report['category2'] ?? '') === 'jugend' ? 'selected' : ''; ?>>Jugend</option>
                                <option value="veranstaltungen" <?php echo ($report['category2'] ?? '') === 'veranstaltungen' ? 'selected' : ''; ?>>Veranstaltungen</option>
                                <option value="sonstige" <?php echo ($report['category2'] ?? '') === 'sonstige' ? 'selected' : ''; ?>>Sonstige</option>
                            </select>
                            <p class="form-hint">Falls z.&nbsp;B. während einer Übung tatsächlich ein Einsatz stattfand, hier zusätzlich eintragen. Beide Kategorien werden gleichwertig angezeigt.</p>
                        </div>
                    </div>

                    <?php $showSubcat2 = in_array($report['category2'] ?? '', ['einsatz', 'uebung'], true); ?>
                    <?php $subcatValues2 = getReportSubcategoryList($report['subcategory2'] ?? ''); ?>
                    <div class="form-group" id="subcategoryGroup2" style="<?php echo $showSubcat2 ? '' : 'display:none;'; ?>">
                        <label id="subcategoryLabel2"><?php echo ($report['category2'] ?? '') === 'uebung' ? 'Übungsart(en)' : 'Einsatzart(en)'; ?></label>
                        <div class="subcat-checkboxes">
                            <label><input type="checkbox" name="subcategory2[]" value="brand" <?php echo in_array('brand', $subcatValues2, true) ? 'checked' : ''; ?>> Brand</label>
                            <label><input type="checkbox" name="subcategory2[]" value="technisch" <?php echo in_array('technisch', $subcatValues2, true) ? 'checked' : ''; ?>> Technisch</label>
                            <label><input type="checkbox" name="subcategory2[]" value="abc" <?php echo in_array('abc', $subcatValues2, true) ? 'checked' : ''; ?>> ABC</label>
                            <label class="subcat2-unterstuetzung" style="<?php echo ($report['category2'] ?? '') === 'uebung' ? 'display:none;' : ''; ?>"><input type="checkbox" name="subcategory2[]" value="unterstuetzung" <?php echo in_array('unterstuetzung', $subcatValues2, true) ? 'checked' : ''; ?> <?php echo ($report['category2'] ?? '') === 'uebung' ? 'disabled' : ''; ?>> Unterstützung</label>
                            <label><input type="checkbox" name="subcategory2[]" value="sonstiges" <?php echo in_array('sonstiges', $subcatValues2, true) ? 'checked' : ''; ?>> Sonstiges</label>
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

                    <div class="form-group">
                        <label><i class="fas fa-link"></i> Links (Instagram, Facebook, externe Website, ...)</label>
                        <div id="linksList">
                            <?php $linkRows = !empty($links) ? $links : [['url' => '', 'label' => '']]; ?>
                            <?php foreach ($linkRows as $link): ?>
                                <div class="link-row" style="display:flex; gap:8px; margin-bottom:8px; flex-wrap:wrap;">
                                    <input type="url" name="links[]" value="<?php echo e($link['url']); ?>" placeholder="z.B. https://www.instagram.com/p/... oder eine externe Website" style="flex:2; min-width:220px;">
                                    <input type="text" name="link_labels[]" value="<?php echo e($link['label']); ?>" placeholder="Beschreibung (optional), z.B. &quot;TT-Bericht zu diesem Einsatz&quot;" style="flex:1; min-width:180px;">
                                    <button type="button" class="btn btn-sm btn-secondary remove-link-row" title="Link entfernen"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="addLinkRow" class="btn btn-sm btn-secondary"><i class="fas fa-plus"></i> Weiteren Link hinzufügen</button>
                        <p class="form-hint">Optional: beliebig viele Links zu Instagram-Beiträgen, Facebook, YouTube oder externen Websites (z.B. Zeitungsartikel, feuerwehr.tirol). Ohne eigene Beschreibung wird Icon &amp; Beschriftung automatisch anhand der URL erkannt; jeder Link erscheint beim veröffentlichten Bericht als eigener Button.</p>
                        <label style="display:flex; align-items:center; gap:8px; margin-top:10px; font-weight:400;">
                            <input type="checkbox" name="links_hidden" value="1" <?php echo !empty($report['links_hidden']) ? 'checked' : ''; ?> style="width:auto;">
                            Links nicht öffentlich anzeigen (bleiben gespeichert, erscheinen aber nicht auf der Website)
                        </label>
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
                        <p class="text-small">JPG, PNG, GIF, WebP - max. 70MB pro Bild - auch 10 oder mehr Bilder auf einmal möglich. Bilder werden automatisch für die Website verkleinert und komprimiert.</p>
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
// Mehrfachauswahl Einsatz-/Übungsart: max. 2 bei Einsatz (z.B. Brand, der in
// ABC überging), max. 3 bei Übung (z.B. Bezirksübungstag mit Brand-,
// Technisch- und ABC-Teil). Ist das Limit erreicht, werden die restlichen,
// nicht angehakten Kästchen gesperrt, bis man eines wieder abwählt.
var subcatLimits = { einsatz: 2, uebung: 3 };

function enforceSubcatLimit(groupEl, categorySelectEl) {
    if (!groupEl || !categorySelectEl) return;
    var max = subcatLimits[categorySelectEl.value] || 99;
    var allBoxes = groupEl.querySelectorAll('input[type=checkbox]');
    var checkedCount = groupEl.querySelectorAll('input[type=checkbox]:checked').length;
    allBoxes.forEach(function (cb) {
        // Bereits anderweitig gesperrte Kästchen (z.B. "Unterstützung" bei
        // Übung) unangetastet lassen, nur die eigene Limit-Sperre verwalten.
        if (cb.disabled && !cb.hasAttribute('data-was-limited')) return;
        if (!cb.checked && checkedCount >= max) {
            cb.disabled = true;
            cb.setAttribute('data-was-limited', '1');
        } else if (cb.hasAttribute('data-was-limited')) {
            cb.disabled = false;
            cb.removeAttribute('data-was-limited');
        }
    });
}

function setupSubcatLimitGroup(groupId, categorySelectEl) {
    var groupEl = document.getElementById(groupId);
    if (!groupEl) return;
    groupEl.addEventListener('change', function (e) {
        if (e.target.type === 'checkbox') enforceSubcatLimit(groupEl, categorySelectEl);
    });
}

// Einsatz-/Übungsart-Feld nur bei Kategorie "Einsatz" oder "Übung" anzeigen.
// Bei "Übung" gibt es keine Option "Unterstützung" (reiner Einsatz-Begriff).
var categorySelect = document.getElementById('category');
var subcategoryGroup = document.getElementById('subcategoryGroup');
var subcategoryLabel = document.getElementById('subcategoryLabel');
var subcategoryHint = document.getElementById('subcategoryHint');
var subcatUnterstuetzung = document.querySelector('.subcat-unterstuetzung');
var subcatUnterstuetzungInput = subcatUnterstuetzung ? subcatUnterstuetzung.querySelector('input') : null;

if (categorySelect && subcategoryGroup) {
    categorySelect.addEventListener('change', function() {
        var isUebung = this.value === 'uebung';
        var showSubcat = this.value === 'einsatz' || isUebung;
        subcategoryGroup.style.display = showSubcat ? '' : 'none';

        if (subcategoryLabel) subcategoryLabel.textContent = isUebung ? 'Übungsart(en)' : 'Einsatzart(en)';
        if (subcategoryHint) {
            subcategoryHint.textContent = 'Mehrfachauswahl möglich - z. B. bei einem Bezirksübungstag mit mehreren Übungsarten (Brand, Technisch, ABC). Bestimmt die ' +
                (isUebung ? 'Übungsart(en)' : 'Einsatzart(en) für Statistik und Kennzeichnung') + '.';
        }
        if (subcatUnterstuetzung && subcatUnterstuetzungInput) {
            subcatUnterstuetzung.style.display = isUebung ? 'none' : '';
            subcatUnterstuetzungInput.disabled = isUebung;
            if (isUebung) subcatUnterstuetzungInput.checked = false;
        }
        enforceSubcatLimit(subcategoryGroup, categorySelect);
    });
}
setupSubcatLimitGroup('subcategoryGroup', categorySelect);
enforceSubcatLimit(subcategoryGroup, categorySelect);

// Dieselbe Logik wie oben, nur für die optionale zweite Kategorie - zusätzlich
// wird das Einsatz-/Übungsart-Feld komplett ausgeblendet, wenn "Keine" gewählt ist.
var categorySelect2 = document.getElementById('category2');
var subcategoryGroup2 = document.getElementById('subcategoryGroup2');
var subcategoryLabel2 = document.getElementById('subcategoryLabel2');
var subcatUnterstuetzung2 = document.querySelector('.subcat2-unterstuetzung');
var subcatUnterstuetzung2Input = subcatUnterstuetzung2 ? subcatUnterstuetzung2.querySelector('input') : null;

if (categorySelect2 && subcategoryGroup2) {
    categorySelect2.addEventListener('change', function() {
        var isUebung = this.value === 'uebung';
        var showSubcat = this.value === 'einsatz' || isUebung;
        subcategoryGroup2.style.display = showSubcat ? '' : 'none';
        if (!showSubcat) {
            subcategoryGroup2.querySelectorAll('input[type=checkbox]').forEach(function (cb) { cb.checked = false; });
        }

        if (subcategoryLabel2) subcategoryLabel2.textContent = isUebung ? 'Übungsart(en)' : 'Einsatzart(en)';
        if (subcatUnterstuetzung2 && subcatUnterstuetzung2Input) {
            subcatUnterstuetzung2.style.display = isUebung ? 'none' : '';
            subcatUnterstuetzung2Input.disabled = isUebung;
            if (isUebung) subcatUnterstuetzung2Input.checked = false;
        }
        enforceSubcatLimit(subcategoryGroup2, categorySelect2);
    });
}
setupSubcatLimitGroup('subcategoryGroup2', categorySelect2);
enforceSubcatLimit(subcategoryGroup2, categorySelect2);

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

// Links: weitere Zeilen hinzufügen/entfernen
var linksList = document.getElementById('linksList');

function makeLinkRow() {
    var div = document.createElement('div');
    div.className = 'link-row';
    div.style.cssText = 'display:flex; gap:8px; margin-bottom:8px; flex-wrap:wrap;';
    div.innerHTML = '<input type="url" name="links[]" placeholder="z.B. https://www.instagram.com/p/... oder eine externe Website" style="flex:2; min-width:220px;">' +
        '<input type="text" name="link_labels[]" placeholder="Beschreibung (optional), z.B. &quot;TT-Bericht zu diesem Einsatz&quot;" style="flex:1; min-width:180px;">' +
        '<button type="button" class="btn btn-sm btn-secondary remove-link-row" title="Link entfernen"><i class="fas fa-times"></i></button>';
    return div;
}

document.getElementById('addLinkRow').addEventListener('click', function() {
    linksList.appendChild(makeLinkRow());
});

linksList.addEventListener('click', function(e) {
    var btn = e.target.closest('.remove-link-row');
    if (!btn) return;
    var rows = linksList.querySelectorAll('.link-row');
    if (rows.length > 1) {
        btn.closest('.link-row').remove();
    } else {
        btn.closest('.link-row').querySelector('input').value = '';
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
