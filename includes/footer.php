    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="assets/images/logo.png?v=2" alt="FF Reichenau Logo">
                        <h3>FF Reichenau</h3>
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

                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="index.php?page=home">Home</a></li>
                        <li><a href="index.php?page=ueber-uns">Über Uns</a></li>
                        <li><a href="index.php?page=ausruestung">Ausrüstung</a></li>
                        <li><a href="index.php?page=jugend">Jugend</a></li>
                        <li><a href="index.php?page=berichte">Berichte</a></li>
                        <li><a href="index.php?page=termine">Termine</a></li>
                        <li><a href="index.php?page=kontakt">Kontakt & Impressum</a></li>
                        <li><a href="index.php?page=datenschutz">Datenschutzerklärung</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Unsere Sponsoren</h4>
                    <ul class="footer-sponsors">
                        <li><a href="http://www.farbmacher-sanremo.at/" target="_blank" rel="noopener">Farbmacher</a></li>
                        <li><a href="http://www.pilser.at/" target="_blank" rel="noopener">Seat Pilser</a></li>
                        <li><a href="http://www.pw-design.at/" target="_blank" rel="noopener">Paul Weber Design</a></li>
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

    <script src="assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>"></script>
</body>
</html>
