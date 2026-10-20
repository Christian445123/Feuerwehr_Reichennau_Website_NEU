<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/berichte.php';
requireLogin();
requirePermission('reports.manage');

$pageTitle = 'Berichte';
$activePage = 'reports';

$db = getDB();

// Bericht löschen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int)$_GET['delete'];
        $reportTitle = $db->prepare("SELECT title FROM reports WHERE id = ?");
        $reportTitle->execute([$id]);
        $titleVal = $reportTitle->fetchColumn();
        // Bilder löschen
        $images = $db->prepare("SELECT filename FROM report_images WHERE report_id = ?");
        $images->execute([$id]);
        foreach ($images->fetchAll() as $img) {
            $path = UPLOAD_PATH . $img['filename'];
            if (file_exists($path)) unlink($path);
        }
        $db->prepare("DELETE FROM report_images WHERE report_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM reports WHERE id = ?")->execute([$id]);
        logActivity($db, 'report.delete', $titleVal ?: "#$id");
        flash('success', 'Bericht wurde gelöscht.');
    }
    header('Location: reports.php');
    exit;
}

// Bericht manuell archivieren/dearchivieren
if (isset($_GET['toggle_archive']) && is_numeric($_GET['toggle_archive'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $id = (int) $_GET['toggle_archive'];
        $stmt = $db->prepare("SELECT title, archived FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $newState = $row['archived'] ? 0 : 1;
            $db->prepare("UPDATE reports SET archived = ? WHERE id = ?")->execute([$newState, $id]);
            logActivity($db, 'report.archive_toggle', ($newState ? 'Archiviert: ' : 'Aus Archiv geholt: ') . $row['title']);
            flash('success', $newState ? 'Bericht wurde archiviert.' : 'Bericht wurde aus dem Archiv geholt.');
        }
    }
    header('Location: reports.php');
    exit;
}

// Einsatznummer öffentlich ein-/ausblenden
if (isset($_GET['toggle_einsatznummer'])) {
    if (isset($_GET['token']) && hash_equals(csrfToken(), $_GET['token'])) {
        $newState = !isEinsatzNummerPublic();
        setEinsatzNummerPublic($newState);
        logActivity($db, 'reports.einsatznummer_public_toggle', $newState ? 'Öffentlich sichtbar' : 'Nur intern');
        flash('success', $newState ? 'Einsatznummer ist jetzt auch öffentlich auf der Website sichtbar.' : 'Einsatznummer ist jetzt nur noch intern im Admin-Bereich sichtbar.');
    }
    header('Location: reports.php');
    exit;
}

// Filter
$category = $_GET['category'] ?? 'all';
$validCategories = ['all', 'einsatz', 'uebung', 'jugend', 'veranstaltungen', 'sonstige', 'archiv'];
if (!in_array($category, $validCategories, true)) $category = 'all';

$berichteAktuellesJahr = getBerichteAktuellesJahr();
$berichteVorjahr = getBerichteVorjahr();

// "Alle" zeigt wirklich alle Berichte (auch archivierte). Die Archivierung
// ist nur eine automatische Kennzeichnung (Badge) anhand des Jahres (siehe
// Admin -> Einstellungen -> Berichtsjahre), kein Ausschlusskriterium -
// "Archiv" ist ein zusätzlicher Filter dafür.
if ($category === 'all') {
    $stmt = $db->query("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC");
    $reports = $stmt->fetchAll();
} elseif ($category === 'archiv') {
    // Zeigt automatisch archivierte (altes Jahr) UND manuell archivierte
    // Berichte (Archiv-Button, unabhängig vom Jahr).
    $stmt = $db->prepare("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id WHERE YEAR(r.date) NOT IN (?, ?) OR r.archived = 1 GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC");
    $stmt->execute([$berichteAktuellesJahr, $berichteVorjahr]);
    $reports = $stmt->fetchAll();
} else {
    // Ein Bericht erscheint unter einem Kategorie-Filter, wenn er die
    // Kategorie als erste ODER als zweite Kategorie trägt (z.B. eine
    // Übung mit Einsatz-Zuordnung erscheint auch unter "Einsatz").
    $stmt = $db->prepare("SELECT r.*, COUNT(ri.id) as image_count FROM reports r LEFT JOIN report_images ri ON r.id = ri.report_id WHERE r.category = ? OR r.category2 = ? GROUP BY r.id ORDER BY r.date DESC, r.created_at DESC");
    $stmt->execute([$category, $category]);
    $reports = $stmt->fetchAll();
}

$subcategoryLabels = [
    'brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC',
    'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges',
];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="page-actions">
    <a href="report-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Neuer Bericht</a>
    <a href="reports.php?toggle_einsatznummer=1&token=<?php echo e(csrfToken()); ?>"
       class="btn <?php echo isEinsatzNummerPublic() ? 'btn-secondary' : 'btn-outline-dark'; ?>"
       title="Die interne Einsatznummer (z.B. E07/26) ist im Admin-Bereich immer sichtbar - dieser Button steuert nur, ob sie auch auf der Website erscheint.">
        <i class="fas <?php echo isEinsatzNummerPublic() ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
        Einsatznummer <?php echo isEinsatzNummerPublic() ? 'öffentlich ausblenden' : 'öffentlich anzeigen'; ?>
    </a>
</div>

<!-- Filter -->
<div class="admin-filters">
    <a href="reports.php" class="filter-pill <?php echo $category === 'all' ? 'active' : ''; ?>">Alle</a>
    <a href="reports.php?category=einsatz" class="filter-pill <?php echo $category === 'einsatz' ? 'active' : ''; ?>">Einsatz</a>
    <a href="reports.php?category=uebung" class="filter-pill <?php echo $category === 'uebung' ? 'active' : ''; ?>">Übung</a>
    <a href="reports.php?category=jugend" class="filter-pill <?php echo $category === 'jugend' ? 'active' : ''; ?>">Jugend</a>
    <a href="reports.php?category=veranstaltungen" class="filter-pill <?php echo $category === 'veranstaltungen' ? 'active' : ''; ?>">Veranstaltungen</a>
    <a href="reports.php?category=sonstige" class="filter-pill <?php echo $category === 'sonstige' ? 'active' : ''; ?>">Sonstige</a>
    <a href="reports.php?category=archiv" class="filter-pill <?php echo $category === 'archiv' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archiv</a>
</div>

<?php if (empty($reports)): ?>
    <div class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>Keine Berichte vorhanden</h3>
        <p>Erstellen Sie Ihren ersten Bericht.</p>
        <a href="report-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Bericht erstellen</a>
    </div>
<?php else: ?>
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Kategorie</th>
                    <th>Datum</th>
                    <th>Fotos</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <?php $isArchiv = !empty($r['archived']) || !in_array((int) substr($r['date'], 0, 4), [$berichteAktuellesJahr, $berichteVorjahr], true); ?>
                    <tr>
                        <td>
                            <?php $einsatzNr = getEinsatzNummerLabel($r); ?>
                            <?php if ($einsatzNr): ?><span class="badge badge-sonstige" title="Interne Einsatznummer"><?php echo e($einsatzNr); ?></span> <?php endif; ?>
                            <strong><?php echo e($r['title']); ?></strong>
                        </td>
                        <td>
                            <?php foreach (getReportBadgeGroups($r) as $listGroup): ?>
                                <?php if ($listGroup['badges']): ?>
                                    <strong class="badge-group-label"><?php echo e($listGroup['label']); ?>:</strong>
                                    <?php foreach ($listGroup['badges'] as $listBadge): ?>
                                        <span class="badge <?php echo e($listBadge['class']); ?>"><?php echo e($listBadge['label']); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge badge-sonstige"><?php echo e($listGroup['label']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo e($r['date']); ?></td>
                        <td><i class="fas fa-images"></i> <?php echo $r['image_count']; ?></td>
                        <td>
                            <?php if ($r['published']): ?>
                                <span class="badge badge-success">Veröffentlicht</span>
                            <?php else: ?>
                                <span class="badge badge-draft">Entwurf</span>
                                <?php if (!empty($r['count_unpublished'])): ?>
                                    <span class="badge badge-sonstige" title="Zählt trotz Entwurf schon in der Einsatz-Statistik mit"><i class="fas fa-chart-column"></i> Zählt mit</span>
                                <?php endif; ?>
                                <?php if (!empty($r['show_in_alarmierungen'])): ?>
                                    <span class="badge badge-sonstige" title="Erscheint trotz Entwurf schon bei den letzten 5 Alarmierungen"><i class="fas fa-bell"></i> In Alarmierungen</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($r['archived'])): ?>
                                <span class="badge badge-archiv" title="Manuell archiviert"><i class="fas fa-archive"></i> Archiviert</span>
                            <?php elseif ($isArchiv): ?>
                                <span class="badge badge-archiv" title="Weder aktuelles Jahr noch Vorjahr - erscheint automatisch im Archiv"><i class="fas fa-archive"></i> Archiviert</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="report-edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-icon" title="Bearbeiten"><i class="fas fa-edit"></i></a>
                            <a href="reports.php?toggle_archive=<?php echo $r['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                               class="btn btn-sm btn-icon"
                               title="<?php echo !empty($r['archived']) ? 'Aus dem Archiv holen' : 'Manuell archivieren'; ?>">
                                <i class="fas <?php echo !empty($r['archived']) ? 'fa-box-open' : 'fa-archive'; ?>"></i>
                            </a>
                            <a href="reports.php?delete=<?php echo $r['id']; ?>&token=<?php echo e(csrfToken()); ?>"
                               class="btn btn-sm btn-icon btn-danger"
                               title="Löschen"
                               onclick="return confirm('Bericht wirklich löschen?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
