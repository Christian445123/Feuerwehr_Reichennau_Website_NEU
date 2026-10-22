<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/media.php';
$db = getDB();


$wacheSlots = [];
foreach (getAllMediaSlots($db) as $slot) {
    $wacheSlots[$slot['slot_key']] = $slot;
}
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('nav.geraetehaus')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('geraetehaus.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <!-- Wache -->
                <div class="content-card" id="wache">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-building"></i></div>
                        <h2><?php echo e(t('geraetehaus.wache')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <p><?php echo e(t('geraetehaus.intro')); ?></p>

                        <div class="wache-gallery">
                            <?php foreach (['wache_umkleide1', 'wache_umkleide2', 'wache_ats', 'wache_funk'] as $slotKey): ?>
                                <?php if (isset($wacheSlots[$slotKey])): ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo htmlspecialchars($wacheSlots[$slotKey]['photo_path']); ?>" alt="<?php echo htmlspecialchars($wacheSlots[$slotKey]['label']); ?>" data-lightbox-group="wache">
                                        <p class="gallery-caption"><?php echo htmlspecialchars($wacheSlots[$slotKey]['label']); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="address-card">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong><?php echo e(t('geraetehaus.address_label')); ?></strong><br>
                                Rossaugasse 4<br>
                                A-6020 Innsbruck
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
