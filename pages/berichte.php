<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/berichte.php';
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

$categoryIcons = getReportCategoryIcons();
$categoryBadges = getReportCategoryBadges();
$subcategoryLabels = getReportSubcategoryLabels();
$subcategoryBadges = getReportSubcategoryBadges();
$berichteAktuellesJahr = getBerichteAktuellesJahr();
$berichteVorjahr = getBerichteVorjahr();
?>

<?php if ($reportId > 0 && $report): ?>
    <!-- Einzelbericht-Ansicht -->
    <section class="page-header bericht-detail-page">
        <div class="container bericht-detail-container">
            <h1 class="page-title">
                <?php $einsatzNr = getPublicEinsatzNummerLabel($report); ?>
                <?php if ($einsatzNr): ?><span class="bericht-einsatznummer"><?php echo htmlspecialchars($einsatzNr); ?></span><?php endif; ?>
                <?php echo htmlspecialchars(getLocalizedReportTitle($report)); ?>
            </h1>
            <p class="page-subtitle">
                <?php foreach (getLocalizedReportBadgeGroups($report) as $group): ?>
                    <span class="bericht-badge-group">
                        <?php if ($group['badges']): ?>
                            <span class="bericht-badge-group-label"><?php echo htmlspecialchars($group['label']); ?>:</span>
                            <?php foreach ($group['badges'] as $badge): ?>
                                <span class="bericht-badge <?php echo $badge['class']; ?>"><?php echo htmlspecialchars($badge['label']); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="bericht-badge badge-sonstige"><?php echo htmlspecialchars($group['label']); ?></span>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
                &middot; <?php echo htmlspecialchars($report['date']); ?>
                <?php if ($report['author']): ?>
                    &middot; <?php echo htmlspecialchars($report['author']); ?>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container bericht-detail-container">
            <a href="index.php?page=berichte" class="btn btn-outline-dark" style="margin-bottom: 24px;">
                <i class="fas fa-arrow-left"></i> <?php echo e(t('berichte.back_to_all')); ?>
            </a>

            <?php $localizedContent = getLocalizedReportContent($report); ?>
            <?php if ($localizedContent): ?>
                <div class="content-card">
                    <div class="content-card-body">
                        <div class="bericht-content"><?php echo nl2br(htmlspecialchars($localizedContent)); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($reportImages)): ?>
                <div class="bericht-gallery">
                    <?php foreach ($reportImages as $img): ?>
                        <div class="gallery-item">
                            <?php $localizedCaption = getLocalizedImageCaption($img); ?>
                            <a href="uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                               data-lightbox-group="bericht-<?php echo (int) $report['id']; ?>"
                               data-lightbox-src="uploads/<?php echo htmlspecialchars($img['filename']); ?>">
                                <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>"
                                     alt="<?php echo htmlspecialchars($localizedCaption ?: getLocalizedReportTitle($report)); ?>">
                            </a>
                            <?php if ($localizedCaption): ?>
                                <p class="gallery-caption"><?php echo htmlspecialchars($localizedCaption); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $reportLinks = getLocalizedReportLinks($report); ?>
            <?php if (!empty($reportLinks) && areReportLinksVisible($report)): ?>
                <div class="bericht-social-link">
                    <?php foreach ($reportLinks as $link): ?>
                        <?php $linkMeta = detectReportLinkIcon($link['url']); ?>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener" class="btn btn-outline-dark">
                            <i class="<?php echo $linkMeta['icon']; ?>"></i> <?php echo htmlspecialchars(getReportLinkLabel($link)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php else: ?>
    <!-- Berichte-Liste -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('berichte.title')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('berichte.subtitle')); ?></p>
        </div>
    </section>

    <?php
    // Filterung läuft serverseitig über die URL-Parameter ?year= und
    // ?category=, genau wie im Admindashboard (admin/reports.php) - kein
    // Client-JS nötig.
    $year = $_GET['year'] ?? 'current';
    $validYears = ['current', 'previous', 'archiv'];
    if (!in_array($year, $validYears, true)) $year = 'current';

    $category = $_GET['category'] ?? 'all';
    $validCategories = ['all', 'einsatz', 'uebung', 'jugend', 'veranstaltungen', 'sonstige'];
    if (!in_array($category, $validCategories, true)) $category = 'all';

    // "Aktuelles Jahr" und "Vorjahr" sind über Admin -> Einstellungen
    // konfigurierbar (config/berichte.php). "Archiv" fasst automatisch alle
    // übrigen Jahre zusammen, egal welche Jahre das gerade sind - dazu
    // zusätzlich jeden Bericht, der im Admin manuell archiviert wurde
    // (r.archived = 1), auch wenn er eigentlich ins aktuelle Jahr fiele.
    if ($year === 'current') {
        $yearCondition = 'AND YEAR(r.date) = ? AND r.archived = 0';
        $yearParams = [$berichteAktuellesJahr];
    } elseif ($year === 'previous') {
        $yearCondition = 'AND YEAR(r.date) = ? AND r.archived = 0';
        $yearParams = [$berichteVorjahr];
    } else {
        $yearCondition = 'AND (YEAR(r.date) NOT IN (?, ?) OR r.archived = 1)';
        $yearParams = [$berichteAktuellesJahr, $berichteVorjahr];
    }

    if ($category === 'all') {
        $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 $yearCondition ORDER BY r.date DESC, r.created_at DESC");
        $stmt->execute($yearParams);
    } else {
        // Ein Bericht erscheint unter einem Kategorie-Filter, wenn er die
        // Kategorie als erste ODER als zweite Kategorie trägt.
        $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 AND (r.category = ? OR r.category2 = ?) $yearCondition ORDER BY r.date DESC, r.created_at DESC");
        $stmt->execute(array_merge([$category, $category], $yearParams));
    }
    $reports = $stmt->fetchAll();
    ?>

    <section class="section">
        <div class="container">

            <!-- Jahres-Tabs -->
            <div class="filter-tabs filter-tabs-years">
                <a href="index.php?page=berichte&year=current&category=<?php echo urlencode($category); ?>" class="filter-tab <?php echo $year === 'current' ? 'active' : ''; ?>"><?php echo $berichteAktuellesJahr; ?></a>
                <a href="index.php?page=berichte&year=previous&category=<?php echo urlencode($category); ?>" class="filter-tab <?php echo $year === 'previous' ? 'active' : ''; ?>"><?php echo $berichteVorjahr; ?></a>
                <a href="index.php?page=berichte&year=archiv&category=<?php echo urlencode($category); ?>" class="filter-tab <?php echo $year === 'archiv' ? 'active' : ''; ?>"><i class="fas fa-archive"></i> <?php echo e(t('berichte.archive')); ?></a>
            </div>

            <!-- Kategorie-Tabs -->
            <div class="filter-tabs">
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=all" class="filter-tab <?php echo $category === 'all' ? 'active' : ''; ?>"><?php echo e(t('berichte.category.all')); ?></a>
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=einsatz" class="filter-tab <?php echo $category === 'einsatz' ? 'active' : ''; ?>"><?php echo e(getLocalizedReportCategoryLabel('einsatz')); ?></a>
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=uebung" class="filter-tab <?php echo $category === 'uebung' ? 'active' : ''; ?>"><?php echo e(getLocalizedReportCategoryLabel('uebung')); ?></a>
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=jugend" class="filter-tab <?php echo $category === 'jugend' ? 'active' : ''; ?>"><?php echo e(getLocalizedReportCategoryLabel('jugend')); ?></a>
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=veranstaltungen" class="filter-tab <?php echo $category === 'veranstaltungen' ? 'active' : ''; ?>"><?php echo e(getLocalizedReportCategoryLabel('veranstaltungen')); ?></a>
                <a href="index.php?page=berichte&year=<?php echo urlencode($year); ?>&category=sonstige" class="filter-tab <?php echo $category === 'sonstige' ? 'active' : ''; ?>"><?php echo e(getLocalizedReportCategoryLabel('sonstige')); ?></a>
            </div>
            <p class="filter-result-count"><?php echo count($reports); ?> <?php echo e(count($reports) === 1 ? t('berichte.count_singular') : t('berichte.count_plural')); ?></p>

            <?php if (empty($reports)): ?>
                <div class="archiv-section">
                    <h2><i class="fas fa-folder-open"></i> <?php echo e(t('berichte.none_title')); ?></h2>
                    <p><?php echo e(t('berichte.none_text')); ?></p>
                </div>
            <?php else: ?>
                <div class="berichte-grid <?php echo $category === 'all' ? 'timeline-view' : ''; ?>">
                    <?php foreach ($reports as $r): ?>
                        <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="bericht-card bericht-card-link">
                            <?php $cardTitle = getLocalizedReportTitle($r); ?>
                            <?php if ($r['thumb']): ?>
                                <div class="bericht-thumb">
                                    <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($cardTitle); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="bericht-card-body">
                                <div class="bericht-badge-row">
                                    <?php foreach (getLocalizedReportBadgeGroups($r) as $cardGroup): ?>
                                        <div class="bericht-badge-group">
                                            <?php if ($cardGroup['badges']): ?>
                                                <span class="bericht-badge-group-label"><?php echo htmlspecialchars($cardGroup['label']); ?>:</span>
                                                <?php foreach ($cardGroup['badges'] as $cardBadge): ?>
                                                    <span class="bericht-badge <?php echo $cardBadge['class']; ?>"><?php echo htmlspecialchars($cardBadge['label']); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="bericht-badge badge-sonstige"><?php echo htmlspecialchars($cardGroup['label']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <h3>
                                    <i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i>
                                    <?php $cardEinsatzNr = getPublicEinsatzNummerLabel($r); ?>
                                    <?php if ($cardEinsatzNr): ?><span class="bericht-einsatznummer"><?php echo htmlspecialchars($cardEinsatzNr); ?></span><?php endif; ?>
                                    <?php echo htmlspecialchars($cardTitle); ?>
                                </h3>
                                <p class="bericht-date"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($r['date']); ?></p>
                                <?php $cardContent = getLocalizedReportContent($r); ?>
                                <?php if ($cardContent): ?>
                                    <p><?php echo htmlspecialchars(mb_substr($cardContent, 0, 120)) . (mb_strlen($cardContent) > 120 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <?php $previewLinks = getLocalizedReportLinks($r); ?>
                                <?php if (!empty($previewLinks) && areReportLinksVisible($r)): ?>
                                    <div class="bericht-card-links-preview">
                                        <?php foreach ($previewLinks as $pl): ?>
                                            <?php $plMeta = detectReportLinkIcon($pl['url']); ?>
                                            <span title="<?php echo htmlspecialchars(getReportLinkLabel($pl)); ?>"><i class="<?php echo $plMeta['icon']; ?>"></i></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
