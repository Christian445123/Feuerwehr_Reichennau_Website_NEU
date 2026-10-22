<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('datenschutz.title')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('datenschutz.subtitle')); ?></p>
        </div>
    </section>

    <?php if (isEnglish()): ?>
    <section class="section" style="padding-bottom: 0;">
        <div class="container">
            <div class="legal-content">
                <p class="form-alert" style="background: rgba(243,156,18,0.1); border: 1px solid rgba(243,156,18,0.3); border-radius: var(--radius); padding: 14px 18px;"><i class="fas fa-circle-info"></i> <?php echo e(t('datenschutz.legal_language_notice')); ?></p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section">
        <div class="container">

            <!-- Datenschutz -->
            <div class="legal-content">

                <?php
                require_once __DIR__ . '/../config/legal.php';
                renderLegalSections(getLegalSections(getDB(), 'datenschutz'));
                ?>

                <p class="legal-last-updated"><i class="fas fa-clock"></i> <?php echo e(t('datenschutz.last_updated')); ?> <?php echo date('F Y'); ?></p>

            </div>

        </div>
    </section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var revokeBtn = document.getElementById('revokeGaConsent');
    if (!revokeBtn) return;
    revokeBtn.addEventListener('click', function () {
        // Einwilligung nicht nur auf "abgelehnt" setzen, sondern komplett
        // zurücksetzen - so erscheint beim Neuladen wieder der
        // Cookie-Banner und man kann erneut frei wählen (statt für immer
        // stillschweigend auf "abgelehnt" festzustecken).
        try { localStorage.removeItem('ga_consent'); } catch (e) {}
        alert(<?php echo json_encode(t('datenschutz.consent_reset_alert')); ?>);
        window.location.reload();
    });
});
</script>