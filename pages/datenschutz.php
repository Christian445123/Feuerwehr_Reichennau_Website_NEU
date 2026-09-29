<?php require_once __DIR__ . '/../config/gate.php'; requireSiteAccess(); ?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Datenschutzerklärung</h1>
            <p class="page-subtitle">Informationen zum Schutz Ihrer persönlichen Daten</p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <!-- Datenschutz -->
            <div class="legal-content">

                <?php
                require_once __DIR__ . '/../config/legal.php';
                renderLegalSections(getLegalSections(getDB(), 'datenschutz'));
                ?>

                <p class="legal-last-updated"><i class="fas fa-clock"></i> Stand: <?php echo date('F Y'); ?></p>

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
        alert('Deine Cookie-Einwilligung wurde zurückgesetzt. Die Seite wird neu geladen, damit du erneut wählen kannst.');
        window.location.reload();
    });
});
</script>