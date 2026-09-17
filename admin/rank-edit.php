<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/ranks.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('ranks.manage');

$db = getDB();
$activePage = 'ranks';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Dienstgrad bearbeiten' : 'Neuer Dienstgrad';

$rank = [
    'abbr' => '', 'name' => '', 'category' => 'Mannschaft',
    'sort_order' => 0, 'badge' => '', 'active' => 1
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM ranks WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Dienstgrad nicht gefunden.');
        header('Location: ranks.php');
        exit;
    }
    $rank = $found;
}

// Bestehende Kategorien für Vorschläge laden
$existingCategories = $db->query("SELECT DISTINCT category FROM ranks ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header("Location: rank-edit.php" . ($isEdit ? "?id=$id" : ""));
        exit;
    }

    $rank['abbr'] = strtoupper(trim($_POST['abbr'] ?? ''));
    $rank['name'] = trim($_POST['name'] ?? '');
    $rank['category'] = trim($_POST['category'] ?? 'Mannschaft');
    $rank['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $rank['active'] = isset($_POST['active']) ? 1 : 0;

    if (empty($rank['abbr']) || empty($rank['name'])) {
        flash('error', 'Abkürzung und Bezeichnung sind erforderlich.');
    } else {
        // Prüfen ob Abkürzung bereits existiert (bei anderem Rang)
        $checkStmt = $db->prepare("SELECT id FROM ranks WHERE abbr = ? AND id != ?");
        $checkStmt->execute([$rank['abbr'], $isEdit ? $id : 0]);
        if ($checkStmt->fetch()) {
            flash('error', 'Diese Abkürzung wird bereits verwendet.');
        } else {
            // Badge-Upload
            $badgePath = $isEdit ? ($rank['badge'] ?? '') : '';
            if (!empty($_FILES['badge']) && $_FILES['badge']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['badge']['tmp_name']);
                if (in_array($mime, $allowed, true) && $_FILES['badge']['size'] <= 5 * 1024 * 1024) {
                    $ext = match ($mime) {
                        'image/png' => 'png', 'image/jpeg' => 'jpg',
                        'image/gif' => 'gif', 'image/webp' => 'webp',
                        'image/svg+xml' => 'svg', default => 'png',
                    };
                    $filename = strtolower($rank['abbr']) . '.' . $ext;
                    $targetDir = __DIR__ . '/../assets/images/ranks/';
                    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                    $target = $targetDir . $filename;
                    if (move_uploaded_file($_FILES['badge']['tmp_name'], $target)) {
                        $badgePath = 'assets/images/ranks/' . $filename;
                    }
                } else {
                    flash('error', 'Badge: Nur Bilder bis 5MB erlaubt (PNG, JPG, GIF, WebP, SVG).');
                }
            }

            // Bei neuem Rang: automatischen Badge-Pfad setzen falls keiner hochgeladen
            if (!$badgePath && !$isEdit) {
                $badgePath = 'assets/images/ranks/' . strtolower($rank['abbr']) . '.png';
            }

            if ($isEdit) {
                // Wenn Abkürzung geändert wurde, Mitglieder aktualisieren
                $oldAbbr = $db->prepare("SELECT abbr FROM ranks WHERE id = ?");
                $oldAbbr->execute([$id]);
                $oldAbbrVal = $oldAbbr->fetchColumn();

                $stmt = $db->prepare("UPDATE ranks SET abbr=?, name=?, category=?, sort_order=?, badge=?, active=? WHERE id=?");
                $stmt->execute([$rank['abbr'], $rank['name'], $rank['category'], $rank['sort_order'], $badgePath, $rank['active'], $id]);

                // Mitglieder-Ränge mit-aktualisieren wenn sich Abkürzung geändert hat
                if ($oldAbbrVal && $oldAbbrVal !== $rank['abbr']) {
                    $db->prepare("UPDATE members SET rank = ? WHERE rank = ?")->execute([$rank['abbr'], $oldAbbrVal]);
                }
            } else {
                $stmt = $db->prepare("INSERT INTO ranks (abbr, name, category, sort_order, badge, active) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$rank['abbr'], $rank['name'], $rank['category'], $rank['sort_order'], $badgePath, $rank['active']]);
                $id = $db->lastInsertId();
            }

            logActivity($db, $isEdit ? 'rank.update' : 'rank.create', $rank['abbr'] . ' - ' . $rank['name']);
            flash('success', $isEdit ? 'Dienstgrad wurde aktualisiert.' : 'Dienstgrad wurde hinzugefügt.');
            header("Location: ranks.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="ranks.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="form-grid">
        <div class="form-main">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-medal"></i> Dienstgrad-Daten</h2></div>
                <div class="admin-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="abbr">Abkürzung *</label>
                            <input type="text" id="abbr" name="abbr" value="<?php echo e($rank['abbr']); ?>" required placeholder="z.B. OBI, HFM, LM, ..." style="text-transform:uppercase;">
                            <small style="color:var(--gray-600);font-size:0.78rem;">Wird als Kennung verwendet und muss eindeutig sein.</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Bezeichnung *</label>
                            <input type="text" id="name" name="name" value="<?php echo e($rank['name']); ?>" required placeholder="z.B. Oberbrandinspektor">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Kategorie</label>
                            <input type="text" id="category" name="category" value="<?php echo e($rank['category']); ?>" list="categoryList" placeholder="z.B. Mannschaft, Chargen, Offiziere, ...">
                            <datalist id="categoryList">
                                <?php foreach ($existingCategories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <small style="color:var(--gray-600);font-size:0.78rem;">Bestimmt die Gruppierung im Dropdown. Neuen Namen eingeben für neue Gruppe.</small>
                        </div>
                        <div class="form-group">
                            <label for="sort_order">Sortierung</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo (int)$rank['sort_order']; ?>" placeholder="0">
                            <small style="color:var(--gray-600);font-size:0.78rem;">Niedrigere Zahlen werden zuerst angezeigt.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Badge -->
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-image"></i> Abzeichen (Badge)</h2></div>
                <div class="admin-card-body">
                    <?php if (!empty($rank['badge']) && file_exists(__DIR__ . '/../' . $rank['badge'])): ?>
                        <div class="current-badge" style="margin-bottom:16px;display:flex;align-items:center;gap:16px;">
                            <img src="../<?php echo e($rank['badge']); ?>" alt="<?php echo e($rank['abbr']); ?>" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--gray-200);border-radius:var(--radius);padding:4px;">
                            <div>
                                <span style="font-size:0.85rem;color:var(--gray-600);">Aktuelles Badge: <?php echo e(basename($rank['badge'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="upload-area upload-area-small" id="badgeUploadArea">
                        <i class="fas fa-image"></i>
                        <p><?php echo !empty($rank['badge']) ? 'Neues Badge hochladen (ersetzt das aktuelle)' : 'Badge-Bild auswählen'; ?></p>
                        <span class="text-small">PNG, JPG, SVG – max. 5MB</span>
                        <input type="file" name="badge" id="badgeInput" accept="image/*" class="file-input">
                    </div>
                    <div id="badgePreview"></div>
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-cog"></i> Optionen</h2></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="active" <?php echo $rank['active'] ? 'checked' : ''; ?>>
                            <span>Aktiver Dienstgrad</span>
                        </label>
                        <small style="color:var(--gray-600);font-size:0.78rem;display:block;margin-top:4px;">Inaktive Dienstgrade erscheinen nicht im Dropdown.</small>
                    </div>

                    <?php if ($isEdit): ?>
                        <?php
                        $memberCount = $db->prepare("SELECT COUNT(*) FROM members WHERE rank = ?");
                        $memberCount->execute([$rank['abbr']]);
                        $count = $memberCount->fetchColumn();
                        ?>
                        <?php if ($count > 0): ?>
                            <div style="margin-top:12px;padding:10px;background:rgba(52,152,219,0.08);border-radius:var(--radius);font-size:0.82rem;color:#2471a3;">
                                <i class="fas fa-info-circle"></i> Wird von <strong><?php echo $count; ?></strong> Mitglied<?php echo $count > 1 ? 'ern' : ''; ?> verwendet.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Speichern' : 'Hinzufügen'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
var badgeUploadArea = document.getElementById('badgeUploadArea');
var badgeInput = document.getElementById('badgeInput');
var badgePreview = document.getElementById('badgePreview');

badgeUploadArea.addEventListener('click', function() { badgeInput.click(); });

badgeInput.addEventListener('change', function() {
    badgePreview.innerHTML = '';
    if (badgeInput.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            badgePreview.innerHTML = '<div class="image-preview" style="margin-top:12px;"><img src="' + e.target.result + '" alt="Vorschau" style="max-width:80px;max-height:80px;object-fit:contain;"></div>';
        };
        reader.readAsDataURL(badgeInput.files[0]);
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
