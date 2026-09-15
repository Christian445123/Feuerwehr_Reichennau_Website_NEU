<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Einzelbericht anzeigen?
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reportId > 0) {
    $stmt = $db->prepare("SELECT * FROM reports WHERE id = ? AND published = 1");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    $imgStmt = $db->prepare("SELECT * FROM report_images WHERE report_id = ? ORDER BY sort_order");
    $imgStmt->execute([$reportId]);
    $reportImages = $imgStmt->fetchAll();
}

$categoryIcons = [
    'einsatz' => 'fa-fire', 'uebung' => 'fa-dumbbell',
    'jugend' => 'fa-child', 'veranstaltungen' => 'fa-calendar-alt', 'sonstige' => 'fa-newspaper'
];
$categoryBadges = [
    'einsatz' => 'badge-brand', 'uebung' => 'badge-uebung',
    'jugend' => 'badge-jugend', 'veranstaltungen' => 'badge-veranstaltungen', 'sonstige' => 'badge-sonstige'
];
$subcategoryLabels = [
    'brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC',
    'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges',
];
$subcategoryBadges = [
    'brand' => 'badge-brand', 'technisch' => 'badge-technisch', 'abc' => 'badge-abc',
    'unterstuetzung' => 'badge-unterstuetzung', 'sonstiges' => 'badge-sonstige',
];
$archivCutoff = date('Y-m-d', strtotime('-2 years')); // Älter als 2 Jahre gilt als Archiv
?>

<?php if ($reportId > 0 && $report): ?>
    <!-- Einzelbericht-Ansicht -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo htmlspecialchars($report['title']); ?></h1>
            <p class="page-subtitle">
                <?php if (!empty($report['subcategory']) && isset($subcategoryLabels[$report['subcategory']])): ?>
                    <span class="bericht-badge <?php echo $subcategoryBadges[$report['subcategory']]; ?>">
                        <?php echo htmlspecialchars($subcategoryLabels[$report['subcategory']]); ?>
                    </span>
                <?php else: ?>
                    <span class="bericht-badge <?php echo $categoryBadges[$report['category']] ?? 'badge-sonstige'; ?>">
                        <?php echo htmlspecialchars(ucfirst($report['category'])); ?>
                    </span>
                <?php endif; ?>
                &middot; <?php echo htmlspecialchars($report['date']); ?>
                <?php if ($report['author']): ?>
                    &middot; <?php echo htmlspecialchars($report['author']); ?>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <a href="index.php?page=berichte" class="btn btn-outline-dark" style="margin-bottom: 24px; display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border: 1px solid #ced4da; border-radius: 50px; color: #495057; font-size: 0.9rem;">
                <i class="fas fa-arrow-left"></i> Zurück zu allen Berichten
            </a>

            <?php if ($report['content']): ?>
                <div class="content-card">
                    <div class="content-card-body">
                        <div class="bericht-content"><?php echo nl2br(htmlspecialchars($report['content'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($reportImages)): ?>
                <div class="bericht-gallery">
                    <?php foreach ($reportImages as $img): ?>
                        <div class="gallery-item">
                            <a href="uploads/<?php echo htmlspecialchars($img['filename']); ?>" target="_blank">
                                <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                                     alt="<?php echo htmlspecialchars($img['caption'] ?: $report['title']); ?>">
                            </a>
                            <?php if ($img['caption']): ?>
                                <p class="gallery-caption"><?php echo htmlspecialchars($img['caption']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php else: ?>
    <!-- Berichte-Liste -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Berichte</h1>
            <p class="page-subtitle">Einsätze, Übungen und Aktivitäten</p>
        </div>
    </section>

    <?php
    // Filterung läuft serverseitig über den URL-Parameter ?category=,
    // genau wie im Admindashboard (admin/reports.php) - kein Client-JS mehr.
    $category = $_GET['category'] ?? 'all';
    $validCategories = ['all', 'einsatz', 'uebung', 'jugend', 'veranstaltungen', 'sonstige', 'archiv'];
    if (!in_array($category, $validCategories, true)) $category = 'all';

    // Archivierte Berichte (vor dem Stichtag) erscheinen ausschließlich unter
    // dem Archiv-Filter, nicht mehr zusätzlich in "Alle" oder ihrer Kategorie.
    if ($category === 'all') {
        $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 AND r.date >= ? ORDER BY r.date DESC, r.created_at DESC");
        $stmt->execute([$archivCutoff]);
    } elseif ($category === 'archiv') {
        $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 AND r.date < ? ORDER BY r.date DESC, r.created_at DESC");
        $stmt->execute([$archivCutoff]);
    } else {
        $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 AND r.category = ? AND r.date >= ? ORDER BY r.date DESC, r.created_at DESC");
        $stmt->execute([$category, $archivCutoff]);
    }
    $reports = $stmt->fetchAll();
    ?>

    <section class="section">
        <div class="container">

            <!-- Filter-Tabs -->
            <div class="filter-tabs">
                <a href="index.php?page=berichte&category=all" class="filter-tab <?php echo $category === 'all' ? 'active' : ''; ?>">Alle</a>
                <a href="index.php?page=berichte&category=einsatz" class="filter-tab <?php echo $category === 'einsatz' ? 'active' : ''; ?>">Einsatz</a>
                <a href="index.php?page=berichte&category=uebung" class="filter-tab <?php echo $category === 'uebung' ? 'active' : ''; ?>">Übung</a>
                <a href="index.php?page=berichte&category=jugend" class="filter-tab <?php echo $category === 'jugend' ? 'active' : ''; ?>">Jugend</a>
                <a href="index.php?page=berichte&category=veranstaltungen" class="filter-tab <?php echo $category === 'veranstaltungen' ? 'active' : ''; ?>">Veranstaltungen</a>
                <a href="index.php?page=berichte&category=sonstige" class="filter-tab <?php echo $category === 'sonstige' ? 'active' : ''; ?>">Sonstige</a>
                <a href="index.php?page=berichte&category=archiv" class="filter-tab <?php echo $category === 'archiv' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> Archiv</a>
            </div>
            <p class="filter-result-count"><?php echo count($reports); ?> <?php echo count($reports) === 1 ? 'Bericht' : 'Berichte'; ?></p>

            <?php if (empty($reports)): ?>
                <div class="archiv-section">
                    <h2><i class="fas fa-folder-open"></i> Keine Berichte</h2>
                    <p>Für diesen Filter sind aktuell keine Berichte vorhanden.</p>
                </div>
            <?php else: ?>
                <div class="berichte-grid <?php echo $category === 'all' ? 'timeline-view' : ''; ?>">
                    <?php foreach ($reports as $r): ?>
                        <?php $isArchiv = $r['date'] < $archivCutoff; ?>
                        <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="bericht-card bericht-card-link">
                            <?php if ($r['thumb']): ?>
                                <div class="bericht-thumb">
                                    <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($r['subcategory']) && isset($subcategoryLabels[$r['subcategory']])): ?>
                                <div class="bericht-badge <?php echo $subcategoryBadges[$r['subcategory']]; ?>">
                                    <?php echo htmlspecialchars($subcategoryLabels[$r['subcategory']]); ?>
                                </div>
                            <?php else: ?>
                                <div class="bericht-badge <?php echo $categoryBadges[$r['category']] ?? 'badge-sonstige'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($r['category'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isArchiv): ?>
                                <div class="bericht-badge badge-archiv"><i class="fas fa-archive"></i> Archiviert</div>
                            <?php endif; ?>
                            <h3><i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i> <?php echo htmlspecialchars($r['title']); ?></h3>
                            <p class="bericht-date"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($r['date']); ?></p>
                            <?php if ($r['content']): ?>
                                <p><?php echo htmlspecialchars(mb_substr($r['content'], 0, 120)) . (mb_strlen($r['content']) > 120 ? '...' : ''); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
