<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Hero Section -->
    <?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/hero.php';
    $heroDb = getDB();
    $heroImages = getActiveHeroImages($heroDb);
    $heroInterval = getHeroInterval($heroDb);
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
            <h1 class="hero-title">Herzlich Willkommen</h1>
            <p class="hero-subtitle">bei der</p>
            <h2 class="hero-heading">Freiwilligen Feuerwehr Reichenau</h2>
            <p class="hero-location"><i class="fas fa-map-marker-alt"></i> Innsbruck Stadt</p>
            <p class="hero-tagline">Ob Brandeinsatz, technische Hilfe oder Gefahrguteinsatz – wir sind rund um die Uhr für die Reichenau, Pradl und die Rossau im Einsatz.</p>
            <div class="hero-buttons">
                <a href="index.php?page=kontakt" class="btn btn-primary"><i class="fas fa-hands-helping"></i> Mitmachen</a>
                <a href="index.php?page=ueber-uns" class="btn btn-outline"><i class="fas fa-info-circle"></i> Mehr erfahren</a>
            </div>
        </div>
    </section>

    <?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/stats.php';
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
                    <div class="stat-label">Brandeinsätze</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['technisch']; ?>"><?php echo $stats['technisch']; ?></div>
                    <div class="stat-label">Technische Einsätze</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-biohazard"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['abc']; ?>"><?php echo $stats['abc']; ?></div>
                    <div class="stat-label">ABC-Einsätze</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['einsatz_gesamt']; ?>"><?php echo $stats['einsatz_gesamt']; ?></div>
                    <div class="stat-label">Einsätze gesamt</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Einsatzbereit</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Aktuelle Einsätze & Berichte -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Aktuelle Einsätze &amp; Berichte</h2>
                <p class="section-subtitle">Die neuesten Aktivitäten unserer Feuerwehr</p>
            </div>

            <?php
            $recentReports = $db->query("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 ORDER BY r.date DESC, r.created_at DESC LIMIT 6")->fetchAll();
            $categoryIcons = ['einsatz' => 'fa-fire', 'uebung' => 'fa-dumbbell', 'jugend' => 'fa-child', 'veranstaltungen' => 'fa-calendar-alt', 'sonstige' => 'fa-newspaper'];
            $categoryLabels = ['einsatz' => 'Einsatz', 'uebung' => 'Übung', 'jugend' => 'Jugend', 'veranstaltungen' => 'Veranstaltung', 'sonstige' => 'Sonstiges'];
            $subcategoryLabelsHome = ['brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC', 'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges'];
            ?>

            <?php if (!empty($recentReports)): ?>
            <div class="news-grid">
                <?php foreach ($recentReports as $r): ?>
                <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="news-card">
                    <div class="news-card-media<?php echo !$r['thumb'] ? ' news-card-media-fallback' : ''; ?>">
                        <?php if ($r['thumb']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-tags">
                        <span class="news-card-badge news-badge-<?php echo htmlspecialchars($r['category']); ?>"><?php echo htmlspecialchars($categoryLabels[$r['category']] ?? ucfirst($r['category'])); ?></span>
                        <?php if (!empty($r['subcategory']) && isset($subcategoryLabelsHome[$r['subcategory']])): ?>
                            <span class="news-card-badge news-badge-sub"><?php echo htmlspecialchars($subcategoryLabelsHome[$r['subcategory']]); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-overlay">
                        <h3 class="news-card-title"><?php echo htmlspecialchars($r['title']); ?></h3>
                        <div class="news-card-date"><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($r['date'])); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="text-align:center; color: #6c757d;">Noch keine Berichte vorhanden.</p>
            <?php endif; ?>

            <div class="section-cta">
                <a href="index.php?page=berichte" class="btn btn-primary"><i class="fas fa-newspaper"></i> Alle Berichte ansehen</a>
            </div>
        </div>
    </section>

    <!-- Letzte Alarmierungen -->
    <?php
    $subcategoryLabels = ['brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC', 'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges'];
    $latestEinsaetze = $db->query("SELECT title, subcategory, date FROM reports WHERE published = 1 AND category = 'einsatz' ORDER BY date DESC, created_at DESC LIMIT 5")->fetchAll();
    ?>
    <?php if (!empty($latestEinsaetze)): ?>
    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Die letzten 5 Alarmierungen</h2>
                <p class="section-subtitle">Immer aktuell informiert</p>
            </div>
            <div class="alarm-timeline alarm-timeline-dark">
                <?php foreach ($latestEinsaetze as $e): ?>
                    <div class="alarm-timeline-item">
                        <div class="alarm-timeline-dot"></div>
                        <div class="alarm-timeline-date"><?php echo htmlspecialchars($e['date']); ?></div>
                        <div class="alarm-timeline-type"><?php echo htmlspecialchars($subcategoryLabels[$e['subcategory']] ?? 'Einsatz'); ?></div>
                        <div class="alarm-timeline-title"><?php echo htmlspecialchars($e['title']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="index.php?page=alarmierungen" class="btn btn-primary"><i class="fas fa-bell"></i> Alle Alarmierungen ansehen</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social Media -->
    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Folge uns auf Social Media</h2>
                <p class="section-subtitle">Aktuelle Einsätze, Übungen &amp; Neuigkeiten direkt auf Instagram und Facebook</p>
            </div>
            <div class="social-cta-grid">
                <a href="https://www.instagram.com/ffreichenau_innsbruck/" target="_blank" rel="noopener" class="social-cta-card social-cta-instagram">
                    <div class="social-cta-icon"><i class="fab fa-instagram"></i></div>
                    <h3>Instagram</h3>
                    <p>@ffreichenau_innsbruck</p>
                </a>
                <a href="http://facebook.ffr.at/" target="_blank" rel="noopener" class="social-cta-card social-cta-facebook">
                    <div class="social-cta-icon"><i class="fab fa-facebook-f"></i></div>
                    <h3>Facebook</h3>
                    <p>FF Reichenau</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Schnellzugriff -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Unsere Feuerwehr</h2>
                <p class="section-subtitle">Erfahren Sie mehr über uns</p>
            </div>
            <div class="quick-links-grid">
                <a href="index.php?page=ueber-uns" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-users"></i></div>
                    <h3>Über Uns</h3>
                    <p>Kommando, Mannschaft &amp; Geschichte</p>
                </a>
                <a href="index.php?page=ausruestung" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-truck"></i></div>
                    <h3>Ausrüstung</h3>
                    <p>Fuhrpark &amp; Wache</p>
                </a>
                <a href="index.php?page=jugend" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-child"></i></div>
                    <h3>Jugend</h3>
                    <p>Unsere Jugendfeuerwehr</p>
                </a>
                <a href="index.php?page=termine" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Termine</h3>
                    <p>Veranstaltungen &amp; Übungen</p>
                </a>
            </div>
        </div>
    </section>

