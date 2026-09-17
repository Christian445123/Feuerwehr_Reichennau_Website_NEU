/**
 * FF Reichenau - JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // --- Aktuelle Alarmierungen Tirol: alle 5 Minuten neu laden ---
    // (nur wenn der Iframe gerade sichtbar ist - am Handy wird stattdessen
    // eine "extern öffnen"-Karte angezeigt, siehe .tirol-alarm-mobile-cta)
    var tirolAlarmFrame = document.getElementById('tirolAlarmFrame');
    if (tirolAlarmFrame) {
        var tirolAlarmBaseSrc = tirolAlarmFrame.src;
        setInterval(function () {
            if (tirolAlarmFrame.offsetParent === null) return;
            tirolAlarmFrame.src = tirolAlarmBaseSrc + (tirolAlarmBaseSrc.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
        }, 5 * 60 * 1000);
    }

    // --- Mobile Navigation Toggle ---
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        function closeNavMenu() {
            navMenu.classList.remove('open');
            navToggle.classList.remove('active');
            document.body.classList.remove('nav-open');
        }

        navToggle.addEventListener('click', function () {
            var isOpen = navMenu.classList.toggle('open');
            navToggle.classList.toggle('active', isOpen);
            document.body.classList.toggle('nav-open', isOpen);
        });

        // Menü schließen bei Klick auf Link
        navMenu.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', closeNavMenu);
        });

        // Menü schließen mit Escape-Taste
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && navMenu.classList.contains('open')) {
                closeNavMenu();
            }
        });
    }

    // --- Navbar Scroll-Effekt ---
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // --- Back to Top Button ---
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- Vehicle Image Switcher ---
    // (Ausrüstung page)
    window.switchVehicleImg = function(thumb) {
        var card = thumb.closest('.vehicle-card');
        var mainImg = card.querySelector('.vehicle-main-img');
        mainImg.src = thumb.src;
        card.querySelectorAll('.vehicle-thumb').forEach(function(t) { t.classList.remove('active'); });
        thumb.classList.add('active');
    };

    // --- Bilder-Lightbox ---
    (function () {
        var overlay, imgEl, counterEl, prevBtn, nextBtn;
        var currentImages = [];
        var currentIndex = 0;

        function buildOverlay() {
            overlay = document.createElement('div');
            overlay.className = 'lightbox-overlay';
            overlay.innerHTML =
                '<button type="button" class="lightbox-close" aria-label="Schließen"><i class="fas fa-times"></i></button>' +
                '<button type="button" class="lightbox-nav lightbox-prev" aria-label="Vorheriges Bild"><i class="fas fa-chevron-left"></i></button>' +
                '<div class="lightbox-content">' +
                    '<img class="lightbox-image" alt="">' +
                    '<p class="lightbox-counter"></p>' +
                '</div>' +
                '<button type="button" class="lightbox-nav lightbox-next" aria-label="Nächstes Bild"><i class="fas fa-chevron-right"></i></button>';
            document.body.appendChild(overlay);

            imgEl = overlay.querySelector('.lightbox-image');
            counterEl = overlay.querySelector('.lightbox-counter');
            prevBtn = overlay.querySelector('.lightbox-prev');
            nextBtn = overlay.querySelector('.lightbox-next');

            overlay.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeLightbox();
            });
            prevBtn.addEventListener('click', function () { showIndex(currentIndex - 1); });
            nextBtn.addEventListener('click', function () { showIndex(currentIndex + 1); });
        }

        function showIndex(i) {
            var len = currentImages.length;
            currentIndex = (i + len) % len;
            var item = currentImages[currentIndex];
            imgEl.src = item.src;
            imgEl.alt = item.alt || '';

            var multiple = len > 1;
            prevBtn.style.display = multiple ? '' : 'none';
            nextBtn.style.display = multiple ? '' : 'none';
            counterEl.style.display = multiple ? '' : 'none';
            counterEl.textContent = multiple ? (currentIndex + 1) + ' / ' + len : '';
        }

        function openLightbox(images, index) {
            if (!overlay) buildOverlay();
            currentImages = images;
            showIndex(index);
            overlay.classList.add('active');
            document.body.classList.add('lightbox-open');
        }

        function closeLightbox() {
            if (!overlay) return;
            overlay.classList.remove('active');
            document.body.classList.remove('lightbox-open');
        }

        // Statische Gruppen: alle Elemente mit gleichem data-lightbox-group bilden eine Galerie
        var groups = {};
        document.querySelectorAll('[data-lightbox-group]').forEach(function (el) {
            var name = el.getAttribute('data-lightbox-group');
            (groups[name] = groups[name] || []).push(el);
        });
        Object.keys(groups).forEach(function (name) {
            var els = groups[name];
            var images = els.map(function (el) {
                return { src: el.getAttribute('data-lightbox-src') || el.src, alt: el.alt || '' };
            });
            els.forEach(function (el, idx) {
                el.style.cursor = 'zoom-in';
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLightbox(images, idx);
                });
            });
        });

        // Dynamische Galerien (z.B. Fahrzeug-Hauptbild): Bilderliste als JSON, Startindex anhand des aktuell angezeigten Bildes
        document.querySelectorAll('[data-lightbox-images]').forEach(function (el) {
            el.style.cursor = 'zoom-in';
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var paths;
                try {
                    paths = JSON.parse(el.getAttribute('data-lightbox-images'));
                } catch (err) {
                    paths = [el.getAttribute('src')];
                }
                var images = paths.map(function (p) { return { src: p, alt: el.alt || '' }; });
                var idx = paths.indexOf(el.getAttribute('src'));
                openLightbox(images, idx < 0 ? 0 : idx);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (!overlay || !overlay.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showIndex(currentIndex - 1);
            if (e.key === 'ArrowRight') showIndex(currentIndex + 1);
        });
    })();

    // --- Scroll-Animation (Intersection Observer) ---
    var observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Karten animieren
    document.querySelectorAll('.card, .content-card, .bericht-card, .info-card, .quick-link-card').forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // --- Zähler-Animation (Statistik-Kacheln zählen beim Sichtbarwerden hoch) ---
    var counterEls = document.querySelectorAll('.stat-number[data-count]');
    if (counterEls.length > 0) {
        function animateCounter(el) {
            var target = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(target)) return;
            var duration = 1400;
            var startTime = null;

            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(eased * target);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target;
                }
            }
            requestAnimationFrame(step);
        }

        counterEls.forEach(function (el) { el.textContent = '0'; });

        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counterEls.forEach(function (el) { counterObserver.observe(el); });
    }
});
