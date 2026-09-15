<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$latestEinsaetze = $db->query("SELECT title, subcategory, date FROM reports WHERE published = 1 AND category = 'einsatz' ORDER BY date DESC, created_at DESC LIMIT 5")->fetchAll();

$year = date('Y');
$brandCount = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND category = 'einsatz' AND subcategory = 'brand' AND date LIKE ?");
$brandCount->execute(["$year-%"]);
$brandCount = $brandCount->fetchColumn();

$technischCount = $db->prepare("SELECT COUNT(*) FROM reports WHERE published = 1 AND category = 'einsatz' AND subcategory IN ('technisch','abc','unterstuetzung') AND date LIKE ?");
$technischCount->execute(["$year-%"]);
$technischCount = $technischCount->fetchColumn();

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
                    <div class="stat-number"><?php echo (int)$brandCount; ?></div>
                    <div class="stat-label">Brandeinsätze <?php echo $year; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    <div class="stat-number"><?php echo (int)$technischCount; ?></div>
                    <div class="stat-label">Technische Einsätze <?php echo $year; ?></div>
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
                        <a href="index.php?page=berichte#einsatz" class="btn btn-outline-dark" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:1px solid #ced4da;border-radius:50px;color:#495057;">
                            <i class="fas fa-list"></i> Alle Einsatzberichte ansehen
                        </a>
                    </div>
                </div>
            </div>

            <div class="content-card" style="margin-top:30px;">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-satellite-dish"></i></div>
                    <h2>Alarmierungsübersicht Innsbruck Stadt</h2>
                </div>
                <div class="content-card-body">
                    <p>Eine bezirksweite Live-Übersicht aller Alarmierungen im Bezirk Innsbruck-Stadt stellt die Leitstelle Tirol nicht öffentlich zur Verfügung. Aktuelle Einsätze der FF Reichenau findest du bei uns immer zeitnah unter <a href="index.php?page=berichte#einsatz">Aktuelles → Einsatz</a>.</p>
                </div>
            </div>

        </div>
    </section>
