<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/haussammlung.php';
$db = getDB();

// Diese Seite ist bewusst nirgends in der Navigation verlinkt - sie ist nur
// über das Popup erreichbar (siehe includes/footer.php). Sie bleibt über
// die URL trotzdem für jeden aufrufbar, das ist bei einer öffentlichen
// Spendenaktion gewollt.
$settings = getHaussammlungSettings();
$zeitraum = '';
if ($settings['von'] !== '' || $settings['bis'] !== '') {
    $von = $settings['von'] !== '' ? date('d.m.Y', strtotime($settings['von'])) : '';
    $bis = $settings['bis'] !== '' ? date('d.m.Y', strtotime($settings['bis'])) : '';
    $zeitraum = $von && $bis ? "$von – $bis" : ($von ? "ab $von" : "bis $bis");
}
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo htmlspecialchars($settings['titel']); ?></h1>
            <?php if ($zeitraum): ?>
                <p class="page-subtitle"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($zeitraum); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <div class="content-card">
                <div class="content-card-body haussammlung-info-body">
                    <?php if ($settings['qr_image']): ?>
                        <img src="<?php echo htmlspecialchars($settings['qr_image']); ?>" alt="QR-Code zur Haussammlung" class="haussammlung-info-qr">
                    <?php endif; ?>
                    <?php if ($settings['infotext'] !== ''): ?>
                        <div class="haussammlung-info-text">
                            <?php echo nl2br(htmlspecialchars(formatHaussammlungText($settings['infotext'], $settings))); ?>
                        </div>
                    <?php else: ?>
                        <p><?php echo nl2br(htmlspecialchars(formatHaussammlungText($settings['kurztext'], $settings))); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
