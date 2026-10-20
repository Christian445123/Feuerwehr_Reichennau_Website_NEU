<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/einsatzlog.php';
requireLogin();
requirePermission('einsatzlog.manage');

$pageTitle = 'Einsatzübersicht';
$activePage = 'einsatzlog';

$db = getDB();
$types = getEinsatzlogTypes();

// Eintrag löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int) $_GET['delete'];
        $db->prepare("DELETE FROM einsatzlog WHERE id = ?")->execute([$id]);
        logActivity($db, 'einsatzlog.delete', "#$id");
        flash('success', 'Eintrag wurde gelöscht.');
    }
    header('Location: einsatzlog.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$entry = [
    'id' => 0, 'alarmzeit' => date('Y-m-d\TH:i'), 'typ' => 'einsatz',
    'einsatztext' => '', 'strasse' => '', 'ort' => '', 'kurzbericht' => '',
    'report_id' => null, 'dauer_minuten' => 0,
];
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM einsatzlog WHERE id = ?");
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if ($found) {
        $entry = $found;
        $entry['alarmzeit'] = str_replace(' ', 'T', substr($entry['alarmzeit'], 0, 16));
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: einsatzlog.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $alarmzeit = str_replace('T', ' ', trim($_POST['alarmzeit'] ?? ''));
    $typ = ($_POST['typ'] ?? '') === 'uebung' ? 'uebung' : 'einsatz';
    $einsatztext = trim($_POST['einsatztext'] ?? '');
    $strasse = trim($_POST['strasse'] ?? '');
    $ort = trim($_POST['ort'] ?? '');
    $kurzbericht = trim($_POST['kurzbericht'] ?? '');
    $reportId = trim($_POST['report_id'] ?? '') !== '' ? (int) $_POST['report_id'] : null;
    $dauerMinuten = max(0, (int) ($_POST['dauer_minuten'] ?? 0));

    if ($alarmzeit === '' || !strtotime($alarmzeit)) {
        flash('error', 'Bitte eine gültige Alarmzeit angeben.');
    } elseif ($einsatztext === '') {
        flash('error', 'Bitte einen Einsatztext angeben.');
    } else {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE einsatzlog SET alarmzeit=?, typ=?, einsatztext=?, strasse=?, ort=?, kurzbericht=?, report_id=?, dauer_minuten=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$alarmzeit, $typ, $einsatztext, $strasse, $ort, $kurzbericht, $reportId, $dauerMinuten, $id]);
            logActivity($db, 'einsatzlog.update', $einsatztext);
            flash('success', 'Eintrag wurde aktualisiert.');
        } else {
            $stmt = $db->prepare("INSERT INTO einsatzlog (alarmzeit, typ, einsatztext, strasse, ort, kurzbericht, report_id, dauer_minuten) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$alarmzeit, $typ, $einsatztext, $strasse, $ort, $kurzbericht, $reportId, $dauerMinuten]);
            logActivity($db, 'einsatzlog.create', $einsatztext);
            flash('success', 'Eintrag wurde angelegt.');
        }
        header('Location: einsatzlog.php');
        exit;
    }
}

$filterYear = isset($_GET['jahr']) && $_GET['jahr'] !== '' ? (int) $_GET['jahr'] : null;
$entries = getEinsatzlogEntries($db, $filterYear);
$years = getEinsatzlogYears($db);
$stats = getEinsatzlogStats($db);
$reportTitles = $db->query("SELECT id, title FROM reports ORDER BY date DESC")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<p style="margin-bottom:16px; color: var(--gray-600);">
    Schlichte Einsatzliste nach Vorbild anderer Feuerwehr-Websites (Alarmzeit, Einsatztext, Adresse, Kurzbericht) -
    getrennt von den bebilderten <a href="reports.php">Berichten</a>. Erscheint öffentlich unter
    <a href="../index.php?page=einsatzuebersicht" target="_blank">Service &rarr; Einsatzübersicht</a>.
    Aktuell: <strong><?php echo $stats['alarmierungen']; ?></strong> Alarmierungen,
    <strong><?php echo $stats['uebungen']; ?></strong> Übungen,
    <strong><?php echo $stats['stunden']; ?></strong> geleistete Stunden.
</p>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-header"><h2><i class="fas <?php echo $editId ? 'fa-pen' : 'fa-plus'; ?>"></i> <?php echo $editId ? 'Eintrag bearbeiten' : 'Neuer Eintrag'; ?></h2></div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $entry['id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="alarmzeit">Alarmzeit *</label>
                    <input type="datetime-local" id="alarmzeit" name="alarmzeit" value="<?php echo e($entry['alarmzeit']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="typ">Art</label>
                    <select id="typ" name="typ">
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $entry['typ'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dauer_minuten">Dauer (Minuten)</label>
                    <input type="number" id="dauer_minuten" name="dauer_minuten" min="0" value="<?php echo (int) $entry['dauer_minuten']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="einsatztext">Einsatztext *</label>
                <input type="text" id="einsatztext" name="einsatztext" value="<?php echo e($entry['einsatztext']); ?>" placeholder="z.B. THL - Verkehrsunfall" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="strasse">Straße</label>
                    <input type="text" id="strasse" name="strasse" value="<?php echo e($entry['strasse']); ?>">
                </div>
                <div class="form-group">
                    <label for="ort">Ort</label>
                    <input type="text" id="ort" name="ort" value="<?php echo e($entry['ort']); ?>" placeholder="z.B. 6020 Innsbruck">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="kurzbericht">Kurzbericht</label>
                    <input type="text" id="kurzbericht" name="kurzbericht" value="<?php echo e($entry['kurzbericht']); ?>" placeholder="z.B. kein Notstand, Storno, ...">
                </div>
                <div class="form-group">
                    <label for="report_id">Verlinkter Bericht (optional)</label>
                    <select id="report_id" name="report_id">
                        <option value="">-- Keiner --</option>
                        <?php foreach ($reportTitles as $rt): ?>
                            <option value="<?php echo (int) $rt['id']; ?>" <?php echo (int) ($entry['report_id'] ?? 0) === (int) $rt['id'] ? 'selected' : ''; ?>><?php echo e($rt['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">Zeigt statt des Kurzberichts den Link "Zum ausführlichen Bericht".</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editId ? 'Speichern' : 'Anlegen'; ?></button>
            <?php if ($editId): ?><a href="einsatzlog.php" class="btn btn-secondary">Abbrechen</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="admin-filters">
    <a href="einsatzlog.php" class="filter-pill <?php echo $filterYear === null ? 'active' : ''; ?>">Alle Jahre</a>
    <?php foreach ($years as $y): ?>
        <a href="einsatzlog.php?jahr=<?php echo $y; ?>" class="filter-pill <?php echo $filterYear === (int) $y ? 'active' : ''; ?>"><?php echo $y; ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <i class="fas fa-list"></i>
        <h3>Keine Einträge vorhanden</h3>
    </div>
<?php else: ?>
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nr.</th>
                    <th>Alarmzeit</th>
                    <th>Art</th>
                    <th>Einsatztext</th>
                    <th>Adresse</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $en): ?>
                    <tr>
                        <td><?php echo (int) $en['nr']; ?></td>
                        <td><?php echo date('d.m.Y, H:i', strtotime($en['alarmzeit'])); ?></td>
                        <td><span class="badge badge-<?php echo $en['typ'] === 'uebung' ? 'uebung' : 'brand'; ?>"><?php echo e($types[$en['typ']] ?? $en['typ']); ?></span></td>
                        <td><?php echo e($en['einsatztext']); ?></td>
                        <td><?php echo e(trim($en['strasse'] . ', ' . $en['ort'], ', ')); ?></td>
                        <td>
                            <a href="einsatzlog.php?edit=<?php echo (int) $en['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
                            <a href="einsatzlog.php?delete=<?php echo (int) $en['id']; ?>&token=<?php echo e(csrfToken()); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Diesen Eintrag wirklich löschen?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
