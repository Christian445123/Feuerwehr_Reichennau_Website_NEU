<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
requireLogin();
requirePermission('orgchart.manage');

$pageTitle = 'Organigramm';
$activePage = 'orgchart';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: orgchart.php');
        exit;
    }

    $names = $_POST['name'] ?? [];
    $stmt = $db->prepare("UPDATE org_chart_positions SET name = ? WHERE position_key = ?");
    foreach ($names as $key => $name) {
        $stmt->execute([trim($name), $key]);
    }

    flash('success', 'Organigramm wurde aktualisiert.');
    header('Location: orgchart.php');
    exit;
}

$positions = $db->query("SELECT * FROM org_chart_positions ORDER BY sort_order")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 720px;">
    <div class="admin-card-header"><h2><i class="fas fa-sitemap"></i> Organigramm</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom:16px;color:var(--gray-600);">
            Die Struktur des Organigramms (Positionen, Layout) ist fest in der Website hinterlegt.
            Hier trägst du nur ein, welcher Name aktuell in welcher Position steht. Ein Feld leer lassen,
            um eine Position als „derzeit nicht besetzt“ anzuzeigen.
        </p>
        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <?php foreach ($positions as $p): ?>
                <div class="form-group">
                    <label for="pos_<?php echo e($p['position_key']); ?>"><?php echo e($p['label']); ?></label>
                    <input type="text" id="pos_<?php echo e($p['position_key']); ?>"
                           name="name[<?php echo e($p['position_key']); ?>]"
                           value="<?php echo e($p['name']); ?>"
                           placeholder="derzeit nicht besetzt">
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
