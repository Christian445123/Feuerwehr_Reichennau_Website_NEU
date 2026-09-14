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
    'jugend' => 'fa-child', 'sonstige' => 'fa-newspaper'
];
$categoryBadges = [
    'einsatz' => 'badge-brand', 'uebung' => 'badge-uebung',
    'jugend' => 'badge-jugend', 'sonstige' => 'badge-sonstige'
];
?>

<?php if ($reportId > 0 && $report): ?>
    <!-- Einzelbericht-Ansicht -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo htmlspecialchars($report['title']); ?></h1>
            <p class="page-subtitle">
                <span class="bericht-badge <?php echo $categoryBadges[$report['category']] ?? 'badge-sonstige'; ?>">
                    <?php echo htmlspecialchars(ucfirst($report['category'])); ?>
                </span>
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

    <section class="section">
        <div class="container">

            <!-- Filter-Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Alle</button>
                <button class="filter-tab" data-filter="einsatz">Einsatz</button>
                <button class="filter-tab" data-filter="uebung">Übung</button>
                <button class="filter-tab" data-filter="jugend">Jugend</button>
                <button class="filter-tab" data-filter="sonstige">Sonstige</button>
            </div>

            <?php
            $reports = $db->query("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 ORDER BY r.date DESC, r.created_at DESC")->fetchAll();
            ?>

            <?php if (empty($reports)): ?>
                <div class="archiv-section">
                    <h2><i class="fas fa-newspaper"></i> Noch keine Berichte</h2>
                    <p>Es wurden noch keine Berichte veröffentlicht.</p>
                </div>
            <?php else: ?>
                <div class="berichte-grid" id="berichteGrid">
                    <?php foreach ($reports as $r): ?>
                        <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="bericht-card bericht-card-link" data-category="<?php echo htmlspecialchars($r['category']); ?>">
                            <?php if ($r['thumb']): ?>
                                <div class="bericht-thumb">
                                    <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="bericht-badge <?php echo $categoryBadges[$r['category']] ?? 'badge-sonstige'; ?>">
                                <?php echo htmlspecialchars(ucfirst($r['category'])); ?>
                            </div>
                            <h3><i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i> <?php echo htmlspecialchars($r['title']); ?></h3>
                            <p class="bericht-date"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($r['date']); ?></p>
                            <?php if ($r['content']): ?>
                                <p><?php echo htmlspecialchars(mb_substr($r['content'], 0, 120)) . (mb_strlen($r['content']) > 120 ? '...' : ''); ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Archiv -->
            <div class="archiv-section" id="archiv">
                <h2><i class="fas fa-archive"></i> Archiv</h2>
                <p>Ältere Berichte und Einsatzberichte finden Sie in unserem Archiv. Bitte kontaktieren Sie uns für weitere Informationen.</p>
            </div>
        </div>
    </section>
<?php endif; ?>
