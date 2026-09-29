<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/schutzbereich.php';
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Schutzbereich</h1>
            <p class="page-subtitle">Das Einsatzgebiet der Freiwilligen Feuerwehr Reichenau</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-shield-alt"></i></div>
                        <h2>Schutzbereich</h2>
                    </div>
                    <div class="content-card-body">
                        <h4>Einwohnerzahl im Schutzgebiet der FF Reichenau:</h4>
                        <p>Derzeit sind im Schutzgebiet der FF Reichenau <strong>27.575 Einwohner</strong> mit Hauptwohnsitz und <strong>2.760 Einwohner</strong> mit Nebenwohnsitz gemeldet. Das Schutzgebiet der FF Reichenau umfasst somit ca. <strong>14.000 Haushalte</strong> (Umrechnungsschlüssel: es wird mit 2,2 Personen pro Haushalt gerechnet).</p>

                        <h4>Karte des Schutzgebiets:</h4>
                        <div id="schutzbereich-map" class="schutzbereich-map" role="img" aria-label="Interaktive Karte des Schutzbereichs der FF Reichenau"></div>
                        <p class="schutzbereich-map-note"><i class="fas fa-circle-info"></i> Der eingezeichnete Bereich ist eine Annäherung an das offizielle Schutzgebiet.</p>

                        <div class="schutzbereich-images">
                            <img src="assets/images/schutzgebiet_karte.jpg" alt="Karte Schutzgebiet" class="content-image" data-lightbox-group="schutzbereich">
                            <img src="assets/images/schutzgebiet.jpg" alt="Schutzgebiet der FF Reichenau" class="content-image" data-lightbox-group="schutzbereich">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

<!-- Schutzbereich-Karte (Leaflet + OpenStreetMap). Die Grenze wurde von Hand
     anhand von realen Straßen/Orten (Feuerwache Rossaugasse 4, Hauptbahnhof,
     Pradl, Pradler Saggen, Gewerbegebiet Rossau, Amraser-See-Straße)
     nachgezogen und ist keine exakte amtliche Grenze - siehe Hinweistext auf
     der Seite. Die Koordinaten kommen aus site_settings (schutzbereich_polygon)
     und sind über Admin -> Schutzbereich mit einer interaktiven Karte
     bearbeitbar (siehe config/schutzbereich.php und admin/schutzbereich.php).
     Die Kartenkacheln laufen über tile-proxy.php (siehe dort):
     OpenStreetMap blockt direktes Einbinden von tile.openstreetmap.org im
     Browser ohne erkennbare, richtlinienkonforme Kennung ("403 Access
     blocked") - der Proxy holt jede Kachel stattdessen serverseitig mit
     korrektem User-Agent und liefert sie danach aus dem eigenen Cache aus,
     genau wie es OSMs Nutzungsrichtlinie für mehr als gelegentliche Nutzung
     vorsieht. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('schutzbereich-map');
    if (!mapEl || typeof L === 'undefined') return;

    var schutzbereichCoords = <?php echo json_encode(getSchutzbereichPolygon()); ?>;

    var map = L.map('schutzbereich-map', { scrollWheelZoom: false });

    L.tileLayer('tile-proxy.php?z={z}&x={x}&y={y}', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende'
    }).addTo(map);

    var schutzbereichPolygon = L.polygon(schutzbereichCoords, {
        color: '#e4002b',
        weight: 3,
        fillColor: '#e4002b',
        fillOpacity: 0.15
    }).addTo(map);

    L.marker([47.27245, 11.43098]).addTo(map)
        .bindPopup('<strong>Feuerwache Reichenau</strong><br>Rossaugasse 4');

    map.fitBounds(schutzbereichPolygon.getBounds(), { padding: [20, 20] });

    mapEl.addEventListener('click', function () {
        map.scrollWheelZoom.enable();
    });
    mapEl.addEventListener('mouseleave', function () {
        map.scrollWheelZoom.disable();
    });
});
</script>
