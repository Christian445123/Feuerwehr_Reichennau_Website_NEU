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

    // --- Berichte Filter ---
    const filterTabs = document.querySelectorAll('.filter-tab');
    const berichteCards = document.querySelectorAll('.bericht-card');
    const berichteGrid = document.getElementById('berichteGrid');
    const filterResultCount = document.getElementById('filterResultCount');
    const noResultsMsg = document.getElementById('noResultsMsg');

    var filterLabels = { all: 'Alle', einsatz: 'Einsatz', uebung: 'Übung', jugend: 'Jugend', sonstige: 'Sonstige', archiv: 'Archiv' };

    filterTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            // Aktiven Tab setzen
            filterTabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            var filter = tab.getAttribute('data-filter');
            var visibleCount = 0;

            berichteCards.forEach(function (card) {
                var visible;
                if (filter === 'all') {
                    visible = true;
                } else if (filter === 'archiv') {
                    visible = card.getAttribute('data-archiv') === '1';
                } else {
                    visible = card.getAttribute('data-category') === filter;
                }
                card.classList.toggle('hidden', !visible);
                if (visible) visibleCount++;
            });

            // "Alle" zeigt einen Zeitstrahl, jeder Filter zeigt Kacheln
            if (berichteGrid) {
                berichteGrid.classList.toggle('timeline-view', filter === 'all');
                berichteGrid.style.display = visibleCount > 0 ? '' : 'none';
            }

            if (noResultsMsg) {
                noResultsMsg.style.display = visibleCount === 0 ? '' : 'none';
            }

            if (filterResultCount) {
                var label = filterLabels[filter] || filter;
                filterResultCount.textContent = visibleCount + (visibleCount === 1 ? ' Bericht' : ' Berichte') + (filter === 'all' ? '' : ' – ' + label);
            }
        });
    });

    // Initiale Anzeige beim Laden der Seite ("Alle")
    if (filterResultCount && berichteCards.length > 0) {
        filterResultCount.textContent = berichteCards.length + (berichteCards.length === 1 ? ' Bericht' : ' Berichte');
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
});
