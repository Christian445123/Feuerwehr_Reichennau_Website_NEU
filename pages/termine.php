<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('termine.title')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('termine.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="termine-info">
                <div class="termin-regular">
                    <div class="termin-icon"><i class="fas fa-sync-alt"></i></div>
                    <h3><?php echo e(t('termine.regular_heading')); ?></h3>
                    <div class="termin-list">
                        <div class="termin-item">
                            <div class="termin-day">
                                <span class="termin-day-name"><?php echo e(t('termine.monday')); ?></span>
                                <span class="termin-day-time">19:00 <?php echo e(t('termine.time_unit')); ?></span>
                            </div>
                            <div class="termin-details">
                                <strong><?php echo e(t('termine.jugendfeuerwehr_label')); ?></strong>
                                <p><?php echo e(t('termine.jugend_desc')); ?></p>
                            </div>
                        </div>
                        <div class="termin-item">
                            <div class="termin-day">
                                <span class="termin-day-name"><?php echo e(t('termine.friday')); ?></span>
                                <span class="termin-day-time">19:15 <?php echo e(t('termine.time_unit')); ?></span>
                            </div>
                            <div class="termin-details">
                                <strong><?php echo e(t('termine.mannschaft_label')); ?></strong>
                                <p><?php echo e(t('termine.mannschaft_desc')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="termine-note">
                <i class="fas fa-info-circle"></i>
                <p><?php echo t('termine.note', ['link' => '<a href="http://facebook.ffr.at/" target="_blank" rel="noopener">' . e(t('termine.facebook_link_text')) . '</a>']); ?></p>
            </div>

        </div>
    </section>
