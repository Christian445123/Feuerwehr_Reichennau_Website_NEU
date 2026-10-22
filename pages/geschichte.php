<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('nav.geschichte')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('geschichte.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-landmark"></i></div>
                        <h2><?php echo e(t('nav.geschichte')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <p><?php echo e(t('geschichte.p1')); ?></p>
                        <p><?php echo e(t('geschichte.p2')); ?></p>
                        <p><?php echo e(t('geschichte.p3')); ?></p>
                        <p><?php echo e(t('geschichte.p4')); ?></p>
                        <p><?php echo e(t('geschichte.p5')); ?></p>
                        <p><?php echo e(t('geschichte.p6')); ?></p>
                        <p><?php echo e(t('geschichte.p7')); ?></p>
                        <p><?php echo e(t('geschichte.p8')); ?></p>
                        <p><?php echo e(t('geschichte.p9')); ?></p>

                        <div class="geschichte-gallery">
                            <img src="assets/images/geschichte1.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
                            <img src="assets/images/geschichte2.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
                            <img src="assets/images/geschichte3.jpg" alt="Geschichte der FF Reichenau" class="content-image" data-lightbox-group="geschichte">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
