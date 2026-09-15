<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/ranks.php';
require_once __DIR__ . '/../config/badges.php';
requireLogin();
requirePermission('members.manage');

// Rang, Verwendungs-/Funktionsabzeichen und Kommando-/Ausschuss-Funktionen sind
// eine eigene, granularere Berechtigung - nicht jeder, der Mitglieder pflegen
// darf, soll auch offizielle Funktionen/Rollen vergeben können.
$canEditFunctions = userHasPermission('members.functions');

$db = getDB();
$activePage = 'members';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Mitglied bearbeiten' : 'Neues Mitglied';

$member = [
    'firstname' => '', 'lastname' => '', 'rank' => '',
    'functions' => '[]', 'group_name' => 'Mannschaft',
    'badge1' => '', 'badge2' => '',
    'photo' => '',
    'sort_order' => 0, 'active' => 1,
    'entry_date' => '', 'phone' => '', 'email' => '', 'bio' => ''
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Mitglied nicht gefunden.');
        header('Location: members.php');
        exit;
    }
    $member = $found;
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header("Location: member-edit.php" . ($isEdit ? "?id=$id" : ""));
        exit;
    }

    $member['firstname'] = trim($_POST['firstname'] ?? '');
    $member['lastname'] = trim($_POST['lastname'] ?? '');
    $member['group_name'] = $_POST['group_name'] ?? 'Mannschaft';
    $member['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $member['active'] = isset($_POST['active']) ? 1 : 0;
    $member['entry_date'] = trim($_POST['entry_date'] ?? '');
    $member['phone'] = trim($_POST['phone'] ?? '');
    $member['email'] = trim($_POST['email'] ?? '');
    $member['bio'] = trim($_POST['bio'] ?? '');

    // Rang, Abzeichen und Funktionen dürfen nur mit der entsprechenden
    // Zusatzberechtigung geändert werden - sonst bleiben die bisherigen
    // Werte unangetastet (bzw. leer bei einem neuen Mitglied).
    if ($canEditFunctions) {
        $member['rank'] = trim($_POST['rank'] ?? '');
        $validBadges = array_keys(getAllBadges());
        $badge1 = trim($_POST['badge1'] ?? '');
        $badge2 = trim($_POST['badge2'] ?? '');
        $member['badge1'] = in_array($badge1, $validBadges, true) ? $badge1 : null;
        $member['badge2'] = (in_array($badge2, $validBadges, true) && $badge2 !== $member['badge1']) ? $badge2 : null;

        $funcSections = $_POST['func_section'] ?? [];
        $funcRoles = $_POST['func_role'] ?? [];
        $validSections = ['Kommando', 'Ausschuss', 'Beauftragter', 'Sonstige'];
        $functions = [];
        for ($i = 0; $i < count($funcSections); $i++) {
            $sec = trim($funcSections[$i] ?? '');
            $role = trim($funcRoles[$i] ?? '');
            if ($sec && $role && in_array($sec, $validSections, true)) {
                $functions[] = ['section' => $sec, 'role' => $role];
            }
        }
    } else {
        $member['rank'] = $member['rank'] ?? '';
        $member['badge1'] = $member['badge1'] ?? null;
        $member['badge2'] = $member['badge2'] ?? null;
        $functions = json_decode($member['functions'] ?? '[]', true) ?: [];
    }
    $member['functions'] = json_encode($functions, JSON_UNESCAPED_UNICODE);

    $validGroups = ['Mannschaft', 'Ehrenmitglieder', 'Jugend'];
    if (!in_array($member['group_name'], $validGroups, true)) {
        $member['group_name'] = 'Mannschaft';
    }

    if (empty($member['firstname']) || empty($member['lastname'])) {
        flash('error', 'Vorname und Nachname sind erforderlich.');
    } else {
        // Foto Upload
        $photoFilename = $isEdit ? ($member['photo'] ?? '') : '';
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $newPhoto = handleImageUpload($_FILES['photo'], 'members');
            if ($newPhoto) {
                // Altes Foto löschen
                if ($photoFilename) {
                    $oldPath = UPLOAD_PATH . $photoFilename;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $photoFilename = $newPhoto;
            }
        }

        // Foto entfernen
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
            if ($photoFilename) {
                $path = UPLOAD_PATH . $photoFilename;
                if (file_exists($path)) unlink($path);
            }
            $photoFilename = '';
        }

        if ($isEdit) {
            $stmt = $db->prepare("UPDATE members SET firstname=?, lastname=?, rank=?, functions=?, badge1=?, badge2=?, group_name=?, photo=?, sort_order=?, active=?, entry_date=?, phone=?, email=?, bio=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$member['firstname'], $member['lastname'], $member['rank'], $member['functions'], $member['badge1'], $member['badge2'], $member['group_name'], $photoFilename, $member['sort_order'], $member['active'], $member['entry_date'], $member['phone'], $member['email'], $member['bio'], $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO members (firstname, lastname, rank, functions, badge1, badge2, group_name, photo, sort_order, active, entry_date, phone, email, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$member['firstname'], $member['lastname'], $member['rank'], $member['functions'], $member['badge1'], $member['badge2'], $member['group_name'], $photoFilename, $member['sort_order'], $member['active'], $member['entry_date'], $member['phone'], $member['email'], $member['bio']]);
            $id = $db->lastInsertId();
        }

        flash('success', $isEdit ? 'Mitglied wurde aktualisiert.' : 'Mitglied wurde hinzugefügt.');
        header("Location: members.php");
        exit;
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="members.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?php echo csrfField(); ?>

    <div class="form-grid">
        <div class="form-main">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-user-edit"></i> Mitglied-Daten</h2></div>
                <div class="admin-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname">Vorname *</label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo e($member['firstname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastname">Nachname *</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo e($member['lastname']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="group_name">Grundgruppe</label>
                        <select id="group_name" name="group_name">
                            <option value="Mannschaft" <?php echo $member['group_name'] === 'Mannschaft' ? 'selected' : ''; ?>>Mannschaft</option>
                            <option value="Ehrenmitglieder" <?php echo $member['group_name'] === 'Ehrenmitglieder' ? 'selected' : ''; ?>>Ehrenmitglieder</option>
                            <option value="Jugend" <?php echo $member['group_name'] === 'Jugend' ? 'selected' : ''; ?>>Jugend</option>
                        </select>
                    </div>

                    <!-- Rang & Abzeichen -->
                    <div class="rang-abzeichen-section">
                        <h4 class="subsection-title"><i class="fas fa-medal"></i> Rang &amp; Abzeichen</h4>
                        <?php if (!$canEditFunctions): ?>
                            <p class="permission-note"><i class="fas fa-lock"></i> Dir fehlt die Berechtigung, Rang, Abzeichen oder Funktionen zu ändern. Diese Felder sind nur sichtbar.</p>
                        <?php endif; ?>

                        <div class="form-row form-row-3">
                            <div class="form-group">
                                <label for="rank">Dienstgrad</label>
                                <select id="rank" name="rank" class="rank-select" <?php echo $canEditFunctions ? '' : 'disabled'; ?>>
                                    <option value="">-- Kein Dienstgrad --</option>
                                    <?php foreach (getRanksGrouped() as $category => $ranks): ?>
                                        <optgroup label="<?php echo e($category); ?>">
                                            <?php foreach ($ranks as $abbr => $name): ?>
                                                <option value="<?php echo e($abbr); ?>" <?php echo $member['rank'] === $abbr ? 'selected' : ''; ?>>
                                                    <?php echo e($abbr); ?> – <?php echo e($name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="badge1">Verwendungs-/Funktionsabzeichen 1</label>
                                <select id="badge1" name="badge1" class="badge-select" <?php echo $canEditFunctions ? '' : 'disabled'; ?>>
                                    <option value="">-- Kein Abzeichen --</option>
                                    <?php foreach (getAllBadges() as $code => $b): ?>
                                        <option value="<?php echo e($code); ?>" data-name="<?php echo e($b['name']); ?>" data-color="<?php echo e($b['color']); ?>" <?php echo ($member['badge1'] ?? '') === $code ? 'selected' : ''; ?>>
                                            <?php echo e($code); ?> – <?php echo e($b['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="badge2">Verwendungs-/Funktionsabzeichen 2</label>
                                <select id="badge2" name="badge2" class="badge-select" <?php echo $canEditFunctions ? '' : 'disabled'; ?>>
                                    <option value="">-- Kein Abzeichen --</option>
                                    <?php foreach (getAllBadges() as $code => $b): ?>
                                        <option value="<?php echo e($code); ?>" data-name="<?php echo e($b['name']); ?>" data-color="<?php echo e($b['color']); ?>" <?php echo ($member['badge2'] ?? '') === $code ? 'selected' : ''; ?>>
                                            <?php echo e($code); ?> – <?php echo e($b['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Live-Vorschau: Rang + beide Abzeichen zusammen -->
                        <div class="rang-abzeichen-preview" id="rangAbzeichenPreview">
                            <div class="rank-preview" id="rankPreview" style="<?php echo ($member['rank'] && isset(getAllRanks()[$member['rank']])) ? '' : 'display:none;'; ?>">
                                <img src="../<?php echo $member['rank'] ? e(getRankBadgePath($member['rank'])) : ''; ?>" alt="" class="rank-preview-img">
                                <span><?php echo ($member['rank'] && isset(getAllRanks()[$member['rank']])) ? e($member['rank'] . ' – ' . getRankName($member['rank'])) : ''; ?></span>
                            </div>
                            <span class="member-badge-inline preview-badge" id="badgePreview1" style="<?php echo !empty($member['badge1']) ? 'background:' . e(getBadgeColor($member['badge1'])) : 'display:none;'; ?>" title="<?php echo !empty($member['badge1']) ? e(getBadgeName($member['badge1'])) : ''; ?>"><?php echo e($member['badge1'] ?? ''); ?></span>
                            <span class="member-badge-inline preview-badge" id="badgePreview2" style="<?php echo !empty($member['badge2']) ? 'background:' . e(getBadgeColor($member['badge2'])) : 'display:none;'; ?>" title="<?php echo !empty($member['badge2']) ? e(getBadgeName($member['badge2'])) : ''; ?>"><?php echo e($member['badge2'] ?? ''); ?></span>
                            <span class="preview-empty-hint" id="previewEmptyHint" style="<?php echo ($member['rank'] || !empty($member['badge1']) || !empty($member['badge2'])) ? 'display:none;' : ''; ?>">Noch kein Rang/Abzeichen gewählt</span>
                        </div>
                    </div>

                    <!-- Funktionen / Rollen (JSON) -->
                    <?php $currentFunctions = json_decode($member['functions'] ?? '[]', true) ?: []; ?>
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Funktionen / Rollen</label>
                        <p style="font-size:0.82rem;color:var(--gray-600);margin-bottom:10px;">
                            Weisen Sie dem Mitglied eine oder mehrere Funktionen zu. Jede Funktion bestimmt, in welcher Sektion das Mitglied auf der Website angezeigt wird.
                        </p>
                        <?php if (!$canEditFunctions): ?>
                            <p class="permission-note"><i class="fas fa-lock"></i> Dir fehlt die Berechtigung, Funktionen zu ändern.</p>
                        <?php endif; ?>
                        <div id="functionsContainer">
                            <?php if (!empty($currentFunctions)): ?>
                                <?php foreach ($currentFunctions as $i => $func): ?>
                                    <div class="function-row">
                                        <select name="func_section[]" class="func-section-select" <?php echo $canEditFunctions ? '' : 'disabled'; ?>>
                                            <option value="">-- Sektion --</option>
                                            <option value="Kommando" <?php echo ($func['section'] ?? '') === 'Kommando' ? 'selected' : ''; ?>>Kommando</option>
                                            <option value="Ausschuss" <?php echo ($func['section'] ?? '') === 'Ausschuss' ? 'selected' : ''; ?>>Ausschuss</option>
                                            <option value="Beauftragter" <?php echo ($func['section'] ?? '') === 'Beauftragter' ? 'selected' : ''; ?>>Beauftragter</option>
                                        </select>
                                        <input type="text" name="func_role[]" value="<?php echo e($func['role'] ?? ''); ?>" placeholder="Rolle (z.B. Kommandant, Kassier, ...)" class="func-role-input" <?php echo $canEditFunctions ? '' : 'disabled'; ?>>
                                        <?php if ($canEditFunctions): ?>
                                            <button type="button" class="btn btn-sm btn-danger func-remove" onclick="this.closest('.function-row').remove()"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($canEditFunctions): ?>
                            <button type="button" class="btn btn-sm btn-secondary" id="addFunctionBtn" style="margin-top:8px;">
                                <i class="fas fa-plus"></i> Funktion hinzufügen
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sort_order">Sortierung</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo (int)$member['sort_order']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-id-card"></i> Detail-Informationen</h2></div>
                <div class="admin-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entry_date">Eintrittsdatum</label>
                            <input type="text" id="entry_date" name="entry_date" value="<?php echo e($member['entry_date'] ?? ''); ?>" placeholder="z.B. 2015, März 2018, ...">
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" id="phone" name="phone" value="<?php echo e($member['phone'] ?? ''); ?>" placeholder="z.B. +43 512 345160">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-Mail</label>
                            <input type="email" id="email" name="email" value="<?php echo e($member['email'] ?? ''); ?>" placeholder="z.B. name@feuerwehr.tirol">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bio">Beschreibung / Info</label>
                        <textarea id="bio" name="bio" rows="4" placeholder="Persönliche Beschreibung, Ausbildungen, Spezialgebiete, ..."><?php echo e($member['bio'] ?? ''); ?></textarea>
                    </div>

            <!-- Foto -->
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-camera"></i> Foto</h2></div>
                <div class="admin-card-body">
                    <?php if (!empty($member['photo'])): ?>
                        <div class="current-photo">
                            <img src="../<?php echo e(UPLOAD_URL . $member['photo']); ?>" alt="Aktuelles Foto">
                            <label class="checkbox-label remove-photo-label">
                                <input type="checkbox" name="remove_photo" value="1">
                                <span>Foto entfernen</span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="upload-area upload-area-small" id="photoUploadArea">
                        <i class="fas fa-camera"></i>
                        <p><?php echo !empty($member['photo']) ? 'Neues Foto hochladen (ersetzt das aktuelle)' : 'Foto auswählen'; ?></p>
                        <input type="file" name="photo" id="photoInput" accept="image/*" class="file-input">
                    </div>
                    <div id="photoPreview"></div>
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="admin-card">
                <div class="admin-card-header"><h2><i class="fas fa-cog"></i> Optionen</h2></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="active" <?php echo $member['active'] ? 'checked' : ''; ?>>
                            <span>Aktives Mitglied</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Speichern' : 'Hinzufügen'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
var photoUploadArea = document.getElementById('photoUploadArea');
var photoInput = document.getElementById('photoInput');
var photoPreview = document.getElementById('photoPreview');

photoUploadArea.addEventListener('click', function() { photoInput.click(); });

photoInput.addEventListener('change', function() {
    photoPreview.innerHTML = '';
    if (photoInput.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            photoPreview.innerHTML = '<div class="image-preview"><img src="' + e.target.result + '" alt="Vorschau"></div>';
        };
        reader.readAsDataURL(photoInput.files[0]);
    }
});

// Rang & Abzeichen: gemeinsame Live-Vorschau
var rankSelect = document.getElementById('rank');
var rankPreview = document.getElementById('rankPreview');
var badge1Select = document.getElementById('badge1');
var badge2Select = document.getElementById('badge2');
var badgePreview1 = document.getElementById('badgePreview1');
var badgePreview2 = document.getElementById('badgePreview2');
var previewEmptyHint = document.getElementById('previewEmptyHint');

function updateEmptyHint() {
    var anySelected = (rankSelect && rankSelect.value) || (badge1Select && badge1Select.value) || (badge2Select && badge2Select.value);
    if (previewEmptyHint) previewEmptyHint.style.display = anySelected ? 'none' : '';
}

if (rankSelect && rankPreview) {
    rankSelect.addEventListener('change', function() {
        var val = this.value;
        if (val) {
            var img = rankPreview.querySelector('img');
            var span = rankPreview.querySelector('span');
            img.src = '../assets/images/ranks/' + val.toLowerCase() + '.png';
            img.alt = val;
            span.textContent = val + ' – ' + this.options[this.selectedIndex].text.trim().replace(val + ' – ', '');
            rankPreview.style.display = 'flex';
        } else {
            rankPreview.style.display = 'none';
        }
        updateEmptyHint();
    });
}

function wireBadgeSelect(select, previewEl) {
    if (!select || !previewEl) return;
    select.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (this.value) {
            previewEl.textContent = this.value;
            previewEl.style.background = opt.getAttribute('data-color');
            previewEl.title = opt.getAttribute('data-name');
            previewEl.style.display = 'inline-flex';
        } else {
            previewEl.style.display = 'none';
        }
        updateEmptyHint();
    });
}
wireBadgeSelect(badge1Select, badgePreview1);
wireBadgeSelect(badge2Select, badgePreview2);

// Dynamic function rows
var addFunctionBtn = document.getElementById('addFunctionBtn');
if (addFunctionBtn) {
    addFunctionBtn.addEventListener('click', function() {
        var container = document.getElementById('functionsContainer');
        var row = document.createElement('div');
        row.className = 'function-row';
        row.innerHTML = '<select name="func_section[]" class="func-section-select">' +
            '<option value="">-- Sektion --</option>' +
            '<option value="Kommando">Kommando</option>' +
            '<option value="Ausschuss">Ausschuss</option>' +
            '<option value="Beauftragter">Beauftragter</option>' +
            '</select>' +
            '<input type="text" name="func_role[]" placeholder="Rolle (z.B. Kommandant, Kassier, ...)" class="func-role-input">' +
            '<button type="button" class="btn btn-sm btn-danger func-remove" onclick="this.closest(\'.function-row\').remove()"><i class="fas fa-times"></i></button>';
        container.appendChild(row);
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
