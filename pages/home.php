<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="assets/images/logo.png" alt="FF Reichenau Logo" class="hero-logo">
            <h1 class="hero-title">Herzlich Willkommen</h1>
            <p class="hero-subtitle">bei der</p>
            <h2 class="hero-heading">Freiwilligen Feuerwehr Reichenau</h2>
            <p class="hero-location"><i class="fas fa-map-marker-alt"></i> Innsbruck Stadt</p>
            <div class="hero-buttons">
                <a href="index.php?page=kontakt" class="btn btn-primary"><i class="fas fa-hands-helping"></i> Mitmachen</a>
                <a href="index.php?page=ueber-uns" class="btn btn-outline"><i class="fas fa-info-circle"></i> Mehr erfahren</a>
            </div>
        </div>
    </section>

    <!-- Einsatz-Statistik -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-fire"></i></div>
                    <div class="stat-number" data-count="7">7</div>
                    <div class="stat-label">Brandeinsätze</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-number" data-count="3">3</div>
                    <div class="stat-label">Technische Einsätze</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-number" data-count="10">10</div>
                    <div class="stat-label">Alarmierungen Gesamt</div>
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
            require_once __DIR__ . '/../config/database.php';
            $db = getDB();
            $recentReports = $db->query("SELECT * FROM reports WHERE published = 1 ORDER BY date DESC, created_at DESC LIMIT 6")->fetchAll();
            $categoryIcons = ['einsatz' => 'fa-fire', 'uebung' => 'fa-dumbbell', 'jugend' => 'fa-child', 'sonstige' => 'fa-newspaper'];
            $categoryBadges = ['einsatz' => 'badge-brand', 'uebung' => 'badge-uebung', 'jugend' => 'badge-jugend', 'sonstige' => 'badge-sonstige'];
            ?>

            <?php if (!empty($recentReports)): ?>
            <div class="cards-grid">
                <?php foreach ($recentReports as $r): ?>
                <div class="card">
                    <div class="card-badge <?php echo $categoryBadges[$r['category']] ?? 'badge-sonstige'; ?>"><?php echo htmlspecialchars(ucfirst($r['category'])); ?></div>
                    <div class="card-icon"><i class="fas <?php echo $categoryIcons[$r['category']] ?? 'fa-newspaper'; ?>"></i></div>
                    <h3 class="card-title"><?php echo htmlspecialchars($r['title']); ?></h3>
                    <p class="card-text"><?php echo htmlspecialchars(mb_substr($r['content'], 0, 100)) . (mb_strlen($r['content']) > 100 ? '...' : ''); ?></p>
                    <a href="index.php?page=berichte&id=<?php echo $r['id']; ?>" class="card-link">Details <i class="fas fa-arrow-right"></i></a>
                </div>
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

    <!-- Info-Bereich -->
    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Jahreshauptversammlung</h2>
                <p class="section-subtitle">41. Jahreshauptversammlung der FF Reichenau</p>
            </div>
            <div class="info-highlight">
                <div class="info-icon"><i class="fas fa-gavel"></i></div>
                <p>Die 41. Jahreshauptversammlung unserer Feuerwehr hat stattgefunden. Für Details und Berichte klicken Sie auf den untenstehenden Button.</p>
                <a href="index.php?page=berichte#sonstige" class="btn btn-primary"><i class="fas fa-info-circle"></i> Details ansehen</a>
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

    <!-- Sponsoren -->
    <section class="section section-sponsors">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Unsere Unterstützer</h2>
            </div>
            <div class="sponsors-grid">
                <a href="http://www.farbmacher-sanremo.at/" target="_blank" rel="noopener" class="sponsor-link">
                    <img src="assets/images/sponsor_farbmacher.jpg" alt="Farbmacher" class="sponsor-logo">
                    <span>Farbmacher</span>
                </a>
                <a href="http://www.pilser.at/" target="_blank" rel="noopener" class="sponsor-link">
                    <img src="assets/images/sponsor_pilser.gif" alt="Seat Pilser" class="sponsor-logo">
                    <span>Seat Pilser</span>
                </a>
                <a href="http://www.pw-design.at/" target="_blank" rel="noopener" class="sponsor-link">
                    <img src="assets/images/sponsor_weber.jpg" alt="Paul Weber" class="sponsor-logo">
                    <span>Paul Weber</span>
                </a>
            </div>
        </div>
    </section>
