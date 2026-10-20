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
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo htmlspecialchars($settings['titel']); ?></h1>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <div class="haussammlung-info-layout">
                <?php if ($settings['qr_image']): ?>
                    <div class="haussammlung-info-qr-card">
                        <img src="<?php echo htmlspecialchars($settings['qr_image']); ?>" alt="QR-Code zur Haussammlung" class="haussammlung-info-qr">
                        <p>QR-Code scannen zum Spenden</p>
                    </div>
                <?php endif; ?>
                <div class="content-card haussammlung-info-text">
                    <div class="content-card-body">
                        <?php if ($settings['infotext'] !== ''): ?>
                            <?php echo renderHaussammlungInfotext(formatHaussammlungText($settings['infotext'], $settings)); ?>
                        <?php else: ?>
                            <p><?php echo nl2br(htmlspecialchars(formatHaussammlungText($settings['kurztext'], $settings))); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
