    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau">
                    </div>
                    <p>Freiwillige Feuerwehr Reichenau<br>Innsbruck Stadt</p>
                    <div class="footer-social">
                        <a href="http://facebook.ffr.at/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/ffreichenau_innsbruck/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Kontakt</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> Rossaugasse 4, A-6020 Innsbruck</li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+43512345160">+43 (0)512 / 345160</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a></li>
                        <li><i class="fas fa-globe"></i> <a href="http://www.ffr.at">www.ffr.at</a></li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom footer-bottom-split">
                <p class="footer-bottom-copy">&copy; Copyrights by <?php echo date('Y'); ?> FF Reichenau</p>
                <p class="footer-bottom-legal">
                    <a href="index.php?page=kontakt">Impressum</a> &middot; <a href="index.php?page=datenschutz">Datenschutz</a>
                </p>
                <div class="footer-bottom-social">
                    <span>Folgt uns:</span>
                    <a href="http://facebook.ffr.at/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/ffreichenau_innsbruck/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop" aria-label="Nach oben scrollen">
        <i class="fas fa-chevron-up"></i>
    </button>

    <?php
    require_once __DIR__ . '/../config/analytics.php';
    $gaMeasurementId = getGaMeasurementId();
    ?>
    <?php if ($gaMeasurementId !== ''): ?>
    <!-- Cookie-Consent-Banner (Google Analytics lädt erst nach Zustimmung) -->
    <div class="cookie-banner" id="cookieBanner" hidden>
        <p>Diese Website möchte <strong>Google Analytics</strong> zur anonymisierten Reichweitenmessung nutzen. Dabei werden Daten an Google übertragen. Du kannst zustimmen oder ablehnen – die Website funktioniert in beiden Fällen normal. Details in unserer <a href="index.php?page=datenschutz">Datenschutzerklärung</a>.</p>
        <div class="cookie-banner-actions">
            <button type="button" class="btn btn-sm btn-secondary" id="cookieReject">Ablehnen</button>
            <button type="button" class="btn btn-sm btn-primary" id="cookieAccept">Akzeptieren</button>
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
