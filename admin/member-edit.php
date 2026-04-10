<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/ranks.php';
requireLogin();

$db = getDB();
$activePage = 'members';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Mitglied bearbeiten' : 'Neues Mitglied';

$member = [
    'firstname' => '', 'lastname' => '', 'rank' => '',
    'functions' => '[]', 'group_name' => 'Mannschaft',
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
    $member['rank'] = trim($_POST['rank'] ?? '');
    $member['group_name'] = $_POST['group_name'] ?? 'Mannschaft';
    $member['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $member['active'] = isset($_POST['active']) ? 1 : 0;
    $member['entry_date'] = trim($_POST['entry_date'] ?? '');
    $member['phone'] = trim($_POST['phone'] ?? '');
    $member['email'] = trim($_POST['email'] ?? '');
    $member['bio'] = trim($_POST['bio'] ?? '');

    // Funktionen als JSON verarbeiten
    $funcSections = $_POST['func_section'] ?? [];
    $funcRoles = $_POST['func_role'] ?? [];
    $validSections = ['Kommando', 'Ausschuss', 'Beauftragter'];
    $functions = [];
    for ($i = 0; $i < count($funcSections); $i++) {
        $sec = trim($funcSections[$i] ?? '');
        $role = trim($funcRoles[$i] ?? '');
        if ($sec && $role && in_array($sec, $validSections, true)) {
            $functions[] = ['section' => $sec, 'role' => $role];
        }
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
            $stmt = $db->prepare("UPDATE members SET firstname=?, lastname=?, rank=?, functions=?, group_name=?, photo=?, sort_order=?, active=?, entry_date=?, phone=?, email=?, bio=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$member['firstname'], $member['lastname'], $member['rank'], $member['functions'], $member['group_name'], $photoFilename, $member['sort_order'], $member['active'], $member['entry_date'], $member['phone'], $member['email'], $member['bio'], $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO members (firstname, lastname, rank, functions, group_name, photo, sort_order, active, entry_date, phone, email, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$member['firstname'], $member['lastname'], $member['rank'], $member['functions'], $member['group_name'], $photoFilename, $member['sort_order'], $member['active'], $member['entry_date'], $member['phone'], $member['email'], $member['bio']]);
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

                    <div class="form-row">
                        <div class="form-group">
                            <label for="rank">Dienstgrad</label>
                            <select id="rank" name="rank" class="rank-select">
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
                            <?php if ($member['rank'] && isset(getAllRanks()[$member['rank']])): ?>
                                <div class="rank-preview" id="rankPreview">
                                    <img src="../<?php echo e(getRankBadgePath($member['rank'])); ?>" alt="<?php echo e($member['rank']); ?>" class="rank-preview-img">
                                    <span><?php echo e($member['rank']); ?> – <?php echo e(getRankName($member['rank'])); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="rank-preview" id="rankPreview" style="display:none;">
                                    <img src="" alt="" class="rank-preview-img">
                                    <span></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="group_name">Grundgruppe</label>
                            <select id="group_name" name="group_name">
                                <option value="Mannschaft" <?php echo $member['group_name'] === 'Mannschaft' ? 'selected' : ''; ?>>Mannschaft</option>
                                <option value="Ehrenmitglieder" <?php echo $member['group_name'] === 'Ehrenmitglieder' ? 'selected' : ''; ?>>Ehrenmitglieder</option>
                                <option value="Jugend" <?php echo $member['group_name'] === 'Jugend' ? 'selected' : ''; ?>>Jugend</option>
                            </select>
                        </div>
                    </div>

                    <!-- Funktionen / Rollen (JSON) -->
                    <?php $currentFunctions = json_decode($member['functions'] ?? '[]', true) ?: []; ?>
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Funktionen / Rollen</label>
                        <p style="font-size:0.82rem;color:var(--gray-600);margin-bottom:10px;">
                            Weisen Sie dem Mitglied eine oder mehrere Funktionen zu. Jede Funktion bestimmt, in welcher Sektion das Mitglied auf der Website angezeigt wird.
                        </p>
                        <div id="functionsContainer">
                            <?php if (!empty($currentFunctions)): ?>
                                <?php foreach ($currentFunctions as $i => $func): ?>
                                    <div class="function-row">
                                        <select name="func_section[]" class="func-section-select">
                                            <option value="">-- Sektion --</option>
                                            <option value="Kommando" <?php echo ($func['section'] ?? '') === 'Kommando' ? 'selected' : ''; ?>>Kommando</option>
                                            <option value="Ausschuss" <?php echo ($func['section'] ?? '') === 'Ausschuss' ? 'selected' : ''; ?>>Ausschuss</option>
                                            <option value="Beauftragter" <?php echo ($func['section'] ?? '') === 'Beauftragter' ? 'selected' : ''; ?>>Beauftragter</option>
                                        </select>
                                        <input type="text" name="func_role[]" value="<?php echo e($func['role'] ?? ''); ?>" placeholder="Rolle (z.B. Kommandant, Kassier, ...)" class="func-role-input">
                                        <button type="button" class="btn btn-sm btn-danger func-remove" onclick="this.closest('.function-row').remove()"><i class="fas fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" id="addFunctionBtn" style="margin-top:8px;">
                            <i class="fas fa-plus"></i> Funktion hinzufügen
                        </button>
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

// Rank selector preview
var rankSelect = document.getElementById('rank');
var rankPreview = document.getElementById('rankPreview');
if (rankSelect && rankPreview) {
    rankSelect.addEventListener('change', function() {
        var val = this.value;
        if (val) {
            var img = rankPreview.querySelector('img');
            var span = rankPreview.querySelector('span');
            img.src = '../assets/images/ranks/' + val.toLowerCase() + '.png';
            img.alt = val;
            span.textContent = val + ' – ' + this.options[this.selectedIndex].text.trim();
            rankPreview.style.display = 'flex';
        } else {
            rankPreview.style.display = 'none';
        }
    });
}

// Dynamic function rows
document.getElementById('addFunctionBtn').addEventListener('click', function() {
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
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
