<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/stats.php';
$db = getDB();

$latestEinsaetze = $db->query("SELECT title, subcategory, date FROM reports WHERE published = 1 AND category = 'einsatz' ORDER BY date DESC, created_at DESC LIMIT 5")->fetchAll();
$stats = getEinsatzStats($db);

$subcategoryLabels = [
    'brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC',
    'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges',
];
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Alarmierungen</h1>
            <p class="page-subtitle">Unsere letzten Einsätze im Überblick</p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="stats-grid" style="margin-bottom:50px;">
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
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-bell"></i></div>
                    <h2>Die letzten 5 Alarmierungen</h2>
                </div>
                <div class="content-card-body">
                    <?php if (empty($latestEinsaetze)): ?>
                        <p class="text-muted-public">Aktuell keine Einsätze veröffentlicht.</p>
                    <?php else: ?>
                        <div class="alarm-timeline">
                            <?php foreach ($latestEinsaetze as $e): ?>
                                <div class="alarm-timeline-item">
                                    <div class="alarm-timeline-dot"></div>
                                    <div class="alarm-timeline-date"><?php echo htmlspecialchars($e['date']); ?></div>
                                    <div class="alarm-timeline-type"><?php echo htmlspecialchars($subcategoryLabels[$e['subcategory']] ?? 'Einsatz'); ?></div>
                                    <div class="alarm-timeline-title"><?php echo htmlspecialchars($e['title']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="section-cta">
                        <a href="index.php?page=berichte&category=einsatz" class="btn btn-outline-dark" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:1px solid #ced4da;border-radius:50px;color:#495057;">
                            <i class="fas fa-list"></i> Alle Einsatzberichte ansehen
                        </a>
                    </div>
                </div>
            </div>

            <div class="content-card" style="margin-top:30px;">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-satellite-dish"></i></div>
                    <h2>Aktuelle Alarmierungen Tirol</h2>
                </div>
                <div class="content-card-body">
                    <p>Live-Übersicht aller aktuellen Alarmierungen in Tirol, bereitgestellt vom Landes-Feuerwehrverband Tirol. Aktualisiert sich automatisch alle 5 Minuten. Aktuelle Einsätze der FF Reichenau findest du bei uns immer zeitnah unter <a href="index.php?page=berichte&category=einsatz">Aktuelles → Einsatz</a>.</p>
                    <div class="tirol-alarm-embed">
                        <iframe id="tirolAlarmFrame" src="https://feuerwehr.tirol/aktuelle-alarmierungen/" title="Aktuelle Alarmierungen Tirol" loading="lazy" scrolling="no"></iframe>
                    </div>
                    <div class="tirol-alarm-mobile-cta">
                        <div class="tirol-alarm-mobile-icon"><i class="fas fa-satellite-dish"></i></div>
                        <p>Die Live-Ansicht des Landes-Feuerwehrverbands lässt sich am Handy leider nicht sauber darstellen. Am schnellsten öffnest du sie direkt in einem neuen Tab:</p>
                        <a href="https://feuerwehr.tirol/aktuelle-alarmierungen/" target="_blank" rel="noopener" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Aktuelle Alarmierungen Tirol öffnen
                        </a>
                    </div>
                    <p class="tirol-alarm-source">
                        <a href="https://feuerwehr.tirol/aktuelle-alarmierungen/" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Direkt auf feuerwehr.tirol öffnen</a>
                    </p>
                </div>
            </div>

        </div>
    </section>
