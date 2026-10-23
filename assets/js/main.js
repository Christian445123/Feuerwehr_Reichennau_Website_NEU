/**
 * FF Reichenau - JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

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

    function revealCard(el) {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                revealCard(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    var revealEls = document.querySelectorAll('.card, .content-card, .bericht-card, .info-card, .quick-link-card');

    // Karten animieren
    revealEls.forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Absicherung für mobile Browser (vor allem iOS Safari): der Observer
    // feuert dort beim ersten Laden manchmal nicht zuverlässig für Elemente,
    // die bereits im sichtbaren Bereich liegen - sie blieben dadurch
    // unsichtbar, bis man die Seite kurz scrollt. Deshalb kurz nach dem
    // Laden einmal von Hand nachprüfen, ...
    function manualRevealCheck() {
        revealEls.forEach(function (el) {
            if (el.style.opacity === '1') return;
            var rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                revealCard(el);
                observer.unobserve(el);
            }
        });
    }
    window.addEventListener('load', manualRevealCheck);
    window.addEventListener('pageshow', manualRevealCheck);
    setTimeout(manualRevealCheck, 300);

    // ... und als letzte Absicherung nach 1.5s wirklich jede noch
    // unsichtbare Karte einblenden, falls der Observer es nirgends geschafft
    // hat - lieber ohne Einblend-Effekt sichtbar als dauerhaft unsichtbar.
    setTimeout(function () {
        revealEls.forEach(function (el) {
            if (el.style.opacity !== '1') revealCard(el);
        });
    }, 1500);

    // --- Zähler-Animation (Statistik-Kacheln zählen beim Sichtbarwerden hoch) ---
    var counterEls = document.querySelectorAll('.stat-number[data-count], .hero-bar-item strong[data-count]');
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

// Startseiten-Diashow: blendet die Hintergrundbilder nacheinander ein
(function () {
    var box = document.querySelector('.hero-slides');
    if (!box) return;
    var slides = box.querySelectorAll('.hero-slide');
    if (slides.length < 2) return;
    var current = 0;
    var interval = parseInt(box.getAttribute('data-interval'), 10) || 6000;
    setInterval(function () {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, interval);
})();

// Wetterwarnungen Innsbruck-Stadt/Innsbruck-Land: Footer-Liste + Warn-Popup
// beim Seitenaufruf (siehe warnungen-tirol.php)
(function () {
    var box = document.getElementById('footerWarnlist');
    var popup = document.getElementById('weatherWarnPopup');
    if (!box && !popup) return;

    fetch('warnungen-tirol.php').then(function (r) { return r.json(); }).then(function (data) {
        if (!data || !Array.isArray(data.regions)) throw new Error('invalid response');

        if (box) {
            box.innerHTML = data.regions.map(function (region) {
                var level = region.summary && region.summary.level > 0 ? region.summary.level : 0;
                var type = (region.summary && region.summary.type) || '';
                var statusText = level > 0 ? type : 'Keine Warnungen';
                return '<div class="footer-warnlist-row">' +
                    '<span class="footer-warnlist-region"><span class="footer-warnlist-dot level-' + level + '"></span>' + region.name + '</span>' +
                    '<span class="footer-warnlist-type">' + statusText + '</span>' +
                    '</div>';
            }).join('');
        }

        if (popup && data.has_warning) {
            // einmal pro Browser-Sitzung anzeigen, nicht bei jedem Seitenwechsel erneut
            var SEEN_KEY = 'weather_warn_popup_seen';
            var alreadySeen = false;
            try { alreadySeen = sessionStorage.getItem(SEEN_KEY) === '1'; } catch (e) {}
            if (alreadySeen) return;

            var warned = data.regions.filter(function (r) { return r.summary && r.summary.level > 0; });
            var body = popup.querySelector('.weather-warn-popup-body');
            body.innerHTML = warned.map(function (region) {
                var level = region.summary.level;
                return '<div class="weather-warn-popup-row">' +
                    '<span class="footer-warnlist-dot level-' + level + '"></span>' +
                    '<strong>' + region.name + ':</strong> ' + region.summary.type +
                    '</div>';
            }).join('');

            popup.hidden = false;
            requestAnimationFrame(function () { popup.classList.add('visible'); });
            try { sessionStorage.setItem(SEEN_KEY, '1'); } catch (e) {}

            var close = popup.querySelector('.weather-warn-popup-close');
            if (close) {
                close.addEventListener('click', function () {
                    popup.classList.remove('visible');
                    setTimeout(function () { popup.hidden = true; }, 300);
                });
            }
        }
    }).catch(function () {
        if (box) {
            box.innerHTML = '<div class="footer-warnlist-row footer-warnlist-error"><span><i class="fas fa-triangle-exclamation"></i> Warnungen gerade nicht abrufbar</span></div>';
        }
    });
})();
