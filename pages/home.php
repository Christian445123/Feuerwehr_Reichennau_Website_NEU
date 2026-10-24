<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Hero Section -->
    <?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/hero.php';
    require_once __DIR__ . '/../config/einsatzbereitschaft.php';
    require_once __DIR__ . '/../config/site-alert.php';
    $heroDb = getDB();
    $heroImages = getActiveHeroImages($heroDb);
    $heroInterval = getHeroInterval($heroDb);
    $bereitschaftStatus = getEinsatzbereitschaftStatus();
    $homeSiteAlert = getActiveSiteAlert();
    ?>
    <section class="hero<?php echo $heroImages ? ' hero-has-images' : ''; ?>">
        <?php if ($heroImages): ?>
        <div class="hero-slides" data-interval="<?php echo $heroInterval * 1000; ?>">
            <?php foreach ($heroImages as $i => $img): ?>
            <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" style="background-image: url('<?php echo htmlspecialchars($img, ENT_QUOTES); ?>');"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau" class="hero-logo">
            <p class="hero-location"><i class="fas fa-map-marker-alt"></i> <?php echo e(t('home.location')); ?></p>
            <p class="hero-tagline"><?php echo e(t('home.tagline')); ?></p>
            <div class="hero-buttons">
                <a href="index.php?page=kontakt" class="btn btn-primary"><i class="fas fa-hands-helping"></i> <?php echo e(t('nav.mitmachen')); ?></a>
                <a href="index.php?page=mannschaft" class="btn btn-outline"><i class="fas fa-info-circle"></i> <?php echo e(t('home.learn_more')); ?></a>
            </div>

            <?php
            $readinessClass = ['bereit' => 'is-ready', 'nicht_bereit' => 'is-not-ready', 'uebung' => 'is-training'][$bereitschaftStatus];
            $readinessIcon = ['bereit' => 'fa-check', 'nicht_bereit' => 'fa-xmark', 'uebung' => 'fa-dumbbell'][$bereitschaftStatus];
            $readinessLabel = ['bereit' => t('home.ready'), 'nicht_bereit' => t('home.not_ready'), 'uebung' => t('home.training')][$bereitschaftStatus];
            ?>
            <div class="hero-readiness" role="img" aria-label="<?php echo e(t('home.status_label', ['status' => $readinessLabel])); ?>">
                <span class="hero-readiness-track <?php echo $readinessClass; ?>">
                    <span class="hero-readiness-thumb"><i class="fas <?php echo $readinessIcon; ?>"></i></span>
                </span>
                <span class="hero-readiness-label <?php echo $readinessClass; ?>"><?php echo e($readinessLabel); ?></span>
            </div>

            <?php if ($homeSiteAlert !== null): ?>
            <a href="#" class="hero-alert" onclick="var c=document.getElementById('siteAlertChip'); if(c){c.click();} return false;" style="border-color: <?php echo htmlspecialchars($homeSiteAlert['color']); ?>;">
                <i class="fas <?php echo htmlspecialchars($homeSiteAlert['icon']); ?>" style="color: <?php echo htmlspecialchars($homeSiteAlert['color']); ?>;"></i>
                <span><?php echo htmlspecialchars($homeSiteAlert['label']); ?><?php echo $homeSiteAlert['location'] !== '' ? ' - ' . htmlspecialchars($homeSiteAlert['location']) : ''; ?></span>
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Info-Balken am Übergang Hero -> Statistik -->
    <div class="hero-bar">
        <div class="hero-bar-item"><strong data-count="24">24</strong><span><?php echo e(t('home.hours_per_day')); ?></span></div>
        <div class="hero-bar-item"><strong data-count="7">7</strong><span><?php echo e(t('home.days_per_week')); ?></span></div>
        <div class="hero-bar-item"><strong data-count="365">365</strong><span><?php echo e(t('home.days_per_year')); ?></span></div>
        <a href="tel:122" class="hero-bar-call"><i class="fas fa-phone"></i> <?php echo e(t('home.emergency_call')); ?></a>
    </div>

    <?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/stats.php';
    require_once __DIR__ . '/../config/berichte.php';
    $db = getDB();
    $stats = getEinsatzStats($db);
    ?>

    <!-- Einsatz-Statistik -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
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
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number">24/7</div>
                    <div class="stat-label"><?php echo e(t('home.ready')); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Aktuelle Einsätze & Berichte -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo e(t('home.recent_heading')); ?></h2>
                <p class="section-subtitle"><?php echo e(t('home.recent_subtitle')); ?></p>
            </div>

            <?php
            $recentReports = $db->query("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 ORDER BY r.date DESC, r.created_at DESC LIMIT 6")->fetchAll();
            $categoryIcons = ['einsatz' => 'fa-fire', 'uebung' => 'fa-dumbbell', 'jugend' => 'fa-child', 'veranstaltungen' => 'fa-calendar-alt', 'sonstige' => 'fa-newspaper'];
            ?>

            <?php if (!empty($recentReports)): ?>
            <div class="news-grid">
                <?php foreach ($recentReports as $r): ?>
                <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="news-card">
                    <?php $homeTitle = getLocalizedReportTitle($r); ?>
                    <div class="news-card-media<?php echo !$r['thumb'] ? ' news-card-media-fallback' : ''; ?>">
                        <?php if ($r['thumb']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($homeTitle); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-tags">
                        <span class="news-card-badge news-badge-<?php echo htmlspecialchars($r['category']); ?>"><?php echo e(getLocalizedReportCategoryLabel($r['category'])); ?></span>
                        <?php foreach (getReportSubcategoryList($r['subcategory'] ?? '') as $sub): ?>
                            <span class="news-card-badge news-badge-sub"><?php echo e(getLocalizedReportSubcategoryLabel($sub)); ?></span>
                        <?php endforeach; ?>
                        <?php if (!empty($r['category2'])): ?>
                            <span class="news-card-badge news-badge-<?php echo htmlspecialchars($r['category2']); ?>"><?php echo e(getLocalizedReportCategoryLabel($r['category2'])); ?></span>
                            <?php foreach (getReportSubcategoryList($r['subcategory2'] ?? '') as $sub2): ?>
                                <span class="news-card-badge news-badge-sub"><?php echo e(getLocalizedReportSubcategoryLabel($sub2)); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-overlay">
                        <h3 class="news-card-title"><?php echo htmlspecialchars($homeTitle); ?></h3>
                        <div class="news-card-date"><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($r['date'])); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="text-align:center; color: #6c757d;"><?php echo e(t('home.no_reports_yet')); ?></p>
            <?php endif; ?>

            <div class="section-cta">
                <a href="index.php?page=berichte" class="btn btn-primary"><i class="fas fa-newspaper"></i> <?php echo e(t('einsatzgebiet.all_reports')); ?></a>
            </div>
        </div>
    </section>

    <!-- Letzte Alarmierungen -->
    <?php
    // Berücksichtigt auch Berichte, bei denen "Einsatz" nur die zweite
    // Kategorie ist (z.B. eine Übung mit tatsächlichem Einsatz).
    // Jeder Bericht, der irgendwo (erste oder zweite Kategorie) mit
    // "Einsatz" markiert ist - auch eine Übung, bei der zusätzlich ein
    // Einsatz stattfand.
    $latestEinsaetze = $db->query("SELECT title, title_en, date, IF(category = 'einsatz', subcategory, subcategory2) as subcategory FROM reports WHERE (published = 1 OR show_in_alarmierungen = 1) AND (category = 'einsatz' OR category2 = 'einsatz') ORDER BY date DESC, created_at DESC LIMIT 5")->fetchAll();
    ?>
    <?php if (!empty($latestEinsaetze)): ?>
    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo e(t('alarmierungen.latest_heading')); ?></h2>
                <p class="section-subtitle"><?php echo e(t('home.always_informed')); ?></p>
            </div>
            <div class="alarm-timeline alarm-timeline-dark">
                <?php foreach ($latestEinsaetze as $e): ?>
                    <div class="alarm-timeline-item">
                        <div class="alarm-timeline-dot"></div>
                        <div class="alarm-timeline-date"><?php echo htmlspecialchars($e['date']); ?></div>
                        <div class="alarm-timeline-type"><?php echo htmlspecialchars(getReportSubcategoryLabelText($e['subcategory'])); ?></div>
                        <div class="alarm-timeline-title"><?php echo htmlspecialchars(getLocalizedReportTitle($e)); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="index.php?page=alarmierungen" class="btn btn-primary"><i class="fas fa-bell"></i> <?php echo e(t('home.all_alerts')); ?></a>
            </div>
        </div>
    </section>
    <?php endif; ?>

