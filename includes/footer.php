    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau">
                    </div>
                    <p><?php echo e(t('footer.org_name')); ?><br><?php echo e(t('footer.org_location')); ?></p>
                    <div class="footer-social">
                        <a href="http://facebook.ffr.at/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/ffreichenau_innsbruck/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4><?php echo e(t('nav.kontakt')); ?></h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> Rossaugasse 4, A-6020 Innsbruck</li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+43512345160">+43 (0)512 / 345160</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a></li>
                        <li><i class="fas fa-globe"></i> <a href="http://www.ff-reichennau.at">www.ff-reichennau.at</a></li>
                    </ul>
                </div>

            </div>

            <div class="footer-weather">
                <div class="footer-weather-map">
                    <iframe src="https://warnungen.zamg.at/wsapp/de/alle/heute/" title="GeoSphere Austria - Unwetterwarnungen Österreich" loading="lazy"></iframe>
                </div>
                <div class="footer-weather-list">
                    <h4><?php echo e(t('footer.weather_warnings')); ?></h4>
                    <div class="footer-warnlist" id="footerWarnlist">
                        <div class="footer-warnlist-row footer-warnlist-loading">
                            <span><i class="fas fa-spinner fa-spin"></i> <?php echo e(t('footer.weather_warnings_loading')); ?></span>
                        </div>
                    </div>
                    <a href="https://warnungen.zamg.at/" target="_blank" rel="noopener" class="footer-warnlist-source">
                        <?php echo e(t('footer.weather_warnings_link')); ?> <i class="fas fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
            </div>

            <div class="footer-bottom footer-bottom-split">
                <p class="footer-bottom-copy">&copy; <?php echo e(t('footer.copyright_by')); ?> <?php echo date('Y'); ?> FF Reichenau</p>
                <p class="footer-bottom-legal">
                    <a href="index.php?page=kontakt"><?php echo e(t('footer.imprint')); ?></a> &middot; <a href="index.php?page=datenschutz"><?php echo e(t('footer.privacy')); ?></a>
                </p>
                <div class="footer-bottom-social">
                    <span><?php echo e(t('footer.follow_us')); ?></span>
                    <a href="http://facebook.ffr.at/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/ffreichenau_innsbruck/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <a href="admin/login.php" class="footer-admin-access" aria-label="<?php echo e(t('footer.admin_login')); ?>" title="<?php echo e(t('footer.admin_login')); ?>"><i class="fas fa-lock"></i></a>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop" aria-label="<?php echo e(t('footer.scroll_to_top')); ?>">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Wetterwarn-Popup: erscheint beim Seitenaufruf nur, wenn für Innsbruck-Stadt
         oder Innsbruck-Land aktuell eine GeoSphere-Austria-Warnung aktiv ist
         (Daten/Logik siehe warnungen-tirol.php + assets/js/main.js) -->
    <div class="weather-warn-popup" id="weatherWarnPopup" hidden>
        <button type="button" class="weather-warn-popup-close" aria-label="<?php echo e(t('common.close')); ?>">&times;</button>
        <div class="weather-warn-popup-head">
            <i class="fas fa-triangle-exclamation"></i>
            <strong><?php echo e(t('footer.weather_warning_popup_title')); ?></strong>
        </div>
        <div class="weather-warn-popup-body"></div>
        <a href="#footerWarnlist" class="weather-warn-popup-link" onclick="document.getElementById('footerWarnlist').scrollIntoView({behavior:'smooth'});">
            <?php echo e(t('footer.weather_warnings_link')); ?> <i class="fas fa-arrow-up-right-from-square"></i>
        </a>
    </div>

    <?php
    require_once __DIR__ . '/../config/haussammlung.php';
    $haussammlungSettings = getHaussammlungSettings();
    ?>
    <?php if (($current_page ?? '') !== 'haussammlung' && isHaussammlungActive($haussammlungSettings)): ?>
    <!-- Haussammlungs-Popup (unten links, einmal pro Besuch, siehe Admin -> Haussammlung) -->
    <div class="haussammlung-popup" id="haussammlungPopup" hidden>
        <button type="button" class="haussammlung-popup-close" id="haussammlungPopupClose" aria-label="<?php echo e(t('common.close')); ?>">&times;</button>
        <div class="haussammlung-popup-body">
            <?php if ($haussammlungSettings['qr_image']): ?>
                <img src="<?php echo htmlspecialchars($haussammlungSettings['qr_image']); ?>" alt="QR-Code zur Haussammlung" class="haussammlung-popup-qr">
            <?php endif; ?>
            <div class="haussammlung-popup-text">
                <strong><?php echo htmlspecialchars($haussammlungSettings['titel']); ?></strong>
                <p><?php echo nl2br(htmlspecialchars(formatHaussammlungText($haussammlungSettings['kurztext'], $haussammlungSettings))); ?></p>
                <a href="index.php?page=haussammlung" class="btn btn-primary btn-sm"><i class="fas fa-circle-info"></i> <?php echo e(t('footer.more_info')); ?></a>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var popup = document.getElementById('haussammlungPopup');
        if (!popup) return;
        var SEEN_KEY = 'haussammlung_popup_seen';
        var alreadySeen = false;
        try { alreadySeen = sessionStorage.getItem(SEEN_KEY) === '1'; } catch (e) {}
        if (alreadySeen) return;

        setTimeout(function () {
            popup.hidden = false;
            requestAnimationFrame(function () { popup.classList.add('visible'); });
            try { sessionStorage.setItem(SEEN_KEY, '1'); } catch (e) {}
        }, 1200);

        document.getElementById('haussammlungPopupClose').addEventListener('click', function () {
            popup.classList.remove('visible');
            setTimeout(function () { popup.hidden = true; }, 300);
        });
    });
    </script>
    <?php endif; ?>

    <?php
    require_once __DIR__ . '/../config/analytics.php';
    $gaMeasurementId = getGaMeasurementId();
    ?>
    <?php if ($gaMeasurementId !== ''): ?>
    <!-- Cookie-Consent-Banner (Google Analytics lädt erst nach Zustimmung) -->
    <div class="cookie-banner" id="cookieBanner" hidden>
        <p><?php echo t('footer.cookie_banner_text', ['link' => '<a href="index.php?page=datenschutz">' . e(t('footer.privacy_policy_link')) . '</a>']); ?></p>
        <div class="cookie-banner-actions">
            <button type="button" class="btn btn-sm btn-secondary" id="cookieReject"><?php echo e(t('footer.cookie_reject')); ?></button>
            <button type="button" class="btn btn-sm btn-primary" id="cookieAccept"><?php echo e(t('footer.cookie_accept')); ?></button>
        </div>
    </div>
    <script>
    (function () {
        var GA_ID = <?php echo json_encode($gaMeasurementId); ?>;
        var CONSENT_KEY = 'ga_consent';

        function loadGa() {
            if (window.gaLoaded) return;
            window.gaLoaded = true;
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            function gtag() { window.dataLayer.push(arguments); }
            window.gtag = gtag;
            gtag('js', new Date());
            gtag('config', GA_ID, { anonymize_ip: true });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var consent = null;
            try { consent = localStorage.getItem(CONSENT_KEY); } catch (e) {}

            if (consent === 'granted') {
                loadGa();
                return;
            }
            if (consent === 'denied') {
                return;
            }

            var banner = document.getElementById('cookieBanner');
            if (!banner) return;
            banner.hidden = false;

            document.getElementById('cookieAccept').addEventListener('click', function () {
                try { localStorage.setItem(CONSENT_KEY, 'granted'); } catch (e) {}
                banner.hidden = true;
                loadGa();
            });
            document.getElementById('cookieReject').addEventListener('click', function () {
                try { localStorage.setItem(CONSENT_KEY, 'denied'); } catch (e) {}
                banner.hidden = true;
            });
        });
    })();
    </script>
    <?php endif; ?>

    <script src="assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>"></script>
</body>
</html>
