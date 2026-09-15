/**
 * FF Reichenau - JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // --- Mobile Navigation Toggle ---
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function () {
            navMenu.classList.toggle('open');
            navToggle.classList.toggle('active');
        });

        // Menü schließen bei Klick auf Link
        navMenu.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
            });
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
