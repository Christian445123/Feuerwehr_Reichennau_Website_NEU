<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/stats.php';
require_once __DIR__ . '/../config/berichte.php';
$db = getDB();

// Berücksichtigt auch Berichte, bei denen "Einsatz" nur die zweite Kategorie
// ist (z.B. eine Übung, bei der tatsächlich ein Einsatz stattfand) - die
// Einsatzart (subcategory) kommt dann aus der passenden Kategorie-Spalte.
// Jeder Bericht, der irgendwo (erste oder zweite Kategorie) mit "Einsatz"
// markiert ist - auch eine Übung, bei der zusätzlich ein Einsatz stattfand.
$latestEinsaetze = $db->query("SELECT title, title_en, date, IF(category = 'einsatz', subcategory, subcategory2) as subcategory FROM reports WHERE (published = 1 OR show_in_alarmierungen = 1) AND (category = 'einsatz' OR category2 = 'einsatz') ORDER BY date DESC, created_at DESC LIMIT 5")->fetchAll();
$stats = getEinsatzStats($db);
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('nav.alarmierungen')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('alarmierungen.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="stats-grid" style="margin-bottom:50px;">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-fire"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['brand']; ?>"><?php echo $stats['brand']; ?></div>
                    <div class="stat-label"><?php echo e(t('alarmierungen.stat_brand')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['technisch']; ?>"><?php echo $stats['technisch']; ?></div>
                    <div class="stat-label"><?php echo e(t('alarmierungen.stat_technisch')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-biohazard"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['abc']; ?>"><?php echo $stats['abc']; ?></div>
                    <div class="stat-label"><?php echo e(t('alarmierungen.stat_abc')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['einsatz_gesamt']; ?>"><?php echo $stats['einsatz_gesamt']; ?></div>
                    <div class="stat-label"><?php echo e(t('alarmierungen.stat_total')); ?></div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-bell"></i></div>
                    <h2><?php echo e(t('alarmierungen.latest_heading')); ?></h2>
                </div>
                <div class="content-card-body">
                    <?php if (empty($latestEinsaetze)): ?>
                        <p class="text-muted-public"><?php echo e(t('alarmierungen.none_published')); ?></p>
                    <?php else: ?>
                        <div class="alarm-timeline">
                            <?php foreach ($latestEinsaetze as $e): ?>
                                <div class="alarm-timeline-item">
                                    <div class="alarm-timeline-dot"></div>
                                    <div class="alarm-timeline-date"><?php echo htmlspecialchars($e['date']); ?></div>
                                    <div class="alarm-timeline-type"><?php echo htmlspecialchars(getReportSubcategoryLabelText($e['subcategory'])); ?></div>
                                    <div class="alarm-timeline-title"><?php echo htmlspecialchars(getLocalizedReportTitle($e)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="section-cta">
                        <a href="index.php?page=berichte&category=einsatz" class="btn btn-outline-dark" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:1px solid #ced4da;border-radius:50px;color:#495057;">
                            <i class="fas fa-list"></i> <?php echo e(t('alarmierungen.all_reports')); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="content-card" style="margin-top:30px;">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-satellite-dish"></i></div>
                    <h2><?php echo e(t('alarmierungen.tirol_heading')); ?></h2>
                </div>
                <div class="content-card-body">
                    <p><?php echo t('alarmierungen.tirol_text', ['link' => '<a href="index.php?page=berichte&category=einsatz">' . e(t('alarmierungen.tirol_link_text')) . '</a>']); ?></p>
                    <div class="tirol-alarm-mobile-cta">
                        <div class="tirol-alarm-mobile-icon"><i class="fas fa-satellite-dish"></i></div>
                        <p><?php echo e(t('alarmierungen.embed_note')); ?></p>
                        <a href="https://feuerwehr.tirol/aktuelle-alarmierungen/" target="_blank" rel="noopener" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> <?php echo e(t('alarmierungen.open_tirol')); ?>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>
