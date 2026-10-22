<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('nav.sicherheitstipps')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('sicherheitstipps.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-fire-extinguisher"></i></div>
                        <h2><?php echo e(t('sicherheitstipps.fire_heading')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <ul>
                            <li><?php echo t('sicherheitstipps.fire_1'); ?></li>
                            <li><?php echo t('sicherheitstipps.fire_2'); ?></li>
                            <li><?php echo t('sicherheitstipps.fire_3'); ?></li>
                            <li><?php echo t('sicherheitstipps.fire_4'); ?></li>
                            <li><?php echo t('sicherheitstipps.fire_5'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-hand-holding-water"></i></div>
                        <h2><?php echo e(t('sicherheitstipps.extinguish_heading')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <ul>
                            <li><?php echo t('sicherheitstipps.extinguish_1'); ?></li>
                            <li><?php echo t('sicherheitstipps.extinguish_2'); ?></li>
                            <li><?php echo t('sicherheitstipps.extinguish_3'); ?></li>
                            <li><?php echo t('sicherheitstipps.extinguish_4'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-house-flood-water"></i></div>
                        <h2><?php echo e(t('sicherheitstipps.storm_heading')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <ul>
                            <li><?php echo e(t('sicherheitstipps.storm_1')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.storm_2')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.storm_3')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.storm_4')); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-suitcase-medical"></i></div>
                        <h2><?php echo e(t('sicherheitstipps.emergency_kit_heading')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <ul>
                            <li><?php echo e(t('sicherheitstipps.kit_1')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.kit_2')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.kit_3')); ?></li>
                            <li><?php echo e(t('sicherheitstipps.kit_4')); ?></li>
                        </ul>
                    </div>
                </div>

            </div>

            <div class="cta-box" style="margin-top:40px;">
                <div class="cta-icon"><i class="fas fa-question-circle"></i></div>
                <h3><?php echo e(t('sicherheitstipps.cta_heading')); ?></h3>
                <p><?php echo e(t('sicherheitstipps.cta_text')); ?></p>
                <a href="mailto:reichenau@feuerwehr.tirol" class="btn btn-primary"><i class="fas fa-envelope"></i> reichenau@feuerwehr.tirol</a>
            </div>
        </div>
    </section>
