<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/schutzbereich.php';
requireLogin();
requirePermission('schutzbereich.manage');

$pageTitle = 'Schutzbereich';
$activePage = 'schutzbereich';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: schutzbereich.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $decoded = json_decode($_POST['coords_json'] ?? '', true);
        if (!is_array($decoded) || count($decoded) < 3) {
            flash('error', 'Die Fläche braucht mindestens 3 Punkte. Nichts wurde gespeichert.');
            header('Location: schutzbereich.php');
            exit;
        }
        $coords = [];
        foreach ($decoded as $point) {
            if (!is_array($point) || count($point) !== 2) continue;
            $coords[] = [(float) $point[0], (float) $point[1]];
        }
        if (count($coords) < 3) {
            flash('error', 'Die Fläche braucht mindestens 3 gültige Punkte. Nichts wurde gespeichert.');
            header('Location: schutzbereich.php');
            exit;
        }
        setSchutzbereichPolygon($coords);
        logActivity($db, 'schutzbereich.change', count($coords) . ' Punkte gespeichert');
        flash('success', 'Die Schutzgebiets-Fläche wurde gespeichert.');
    }

    if ($action === 'reset') {
        resetSchutzbereichPolygon();
        logActivity($db, 'schutzbereich.reset');
        flash('success', 'Die Fläche wurde auf die ursprüngliche Form zurückgesetzt.');
    }

    if ($action === 'toggle_check') {
        $enable = ($_POST['enable'] ?? '0') === '1';
        setSchutzgebietCheckEnabled($enable);
        logActivity($db, 'schutzbereich.check_toggle', $enable ? 'Aktiviert' : 'Deaktiviert');
        flash('success', $enable ? 'Der Adress-Check ist jetzt auf der Mitmachen-Seite sichtbar.' : 'Der Adress-Check ist jetzt auf der Mitmachen-Seite ausgeblendet.');
    }

    header('Location: schutzbereich.php');
    exit;
}

$currentPolygon = getSchutzbereichPolygon();
$checkEnabled = isSchutzgebietCheckEnabled();

require_once __DIR__ . '/includes/admin-header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">

<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-location-crosshairs"></i> Adress-Check auf der Mitmachen-Seite</h2></div>
    <div class="admin-card-body">
        <p style="color: var(--gray-600); margin-bottom: 16px;">
            Blendet auf der Mitmachen-Seite ein Feld ein, in dem Interessierte ihre Straße und Hausnummer eingeben können.
            Die Website sagt ihnen dann, ob sie im Schutzgebiet der FF Reichenau liegen oder sich an eine andere
            Freiwillige Feuerwehr wenden müssen (anhand der oben eingezeichneten Fläche). Noch nicht offiziell freigegeben,
            deshalb standardmäßig ausgeblendet.
        </p>
        <div style="padding: 14px 16px; background: <?php echo $checkEnabled ? 'rgba(39,174,96,0.08)' : 'rgba(213,0,28,0.06)'; ?>; border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <strong><?php echo $checkEnabled ? 'Adress-Check ist AKTIV' : 'Adress-Check ist AUSGEBLENDET'; ?></strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #6c757d;">
                    <?php echo $checkEnabled
                        ? 'Besucher sehen das Feld auf der Mitmachen-Seite.'
                        : 'Das Feld ist auf der Website derzeit nicht zu sehen.'; ?>
                </p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_check">
                <input type="hidden" name="enable" value="<?php echo $checkEnabled ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo $checkEnabled ? 'btn-secondary' : 'btn-primary'; ?>">
                    <i class="fas fa-power-off"></i> <?php echo $checkEnabled ? 'Ausblenden' : 'Auf der Website anzeigen'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header"><h2><i class="fas fa-map-location-dot"></i> Schutzgebiets-Fläche bearbeiten</h2></div>
    <div class="admin-card-body">
        <p style="color: var(--gray-600); margin-bottom: 16px;">
            Mit dem Sechseck-Werkzeug links auf der Karte kannst du die eingezeichnete Fläche verschieben:
            klicke auf einen Eckpunkt und ziehe ihn an die gewünschte Stelle, ziehe an den kleinen Punkten
            in der Mitte einer Kante, um dort einen neuen Eckpunkt einzufügen, und klicke einen Eckpunkt bei
            gedrückter <kbd>Alt</kbd>-Taste an, um ihn zu löschen. Über das Papierkorb-Symbol kann die ganze
            Fläche gelöscht und mit dem Sechseck-Symbol eine komplett neue gezeichnet werden. Nicht vergessen:
            am Ende auf <strong>„Speichern"</strong> klicken, sonst geht die Änderung beim Verlassen der Seite verloren.
        </p>

        <div id="schutzbereich-admin-map" style="height: 560px; border-radius: var(--radius); border: 1px solid var(--gray-200);"></div>

        <form method="POST" id="schutzbereichForm" style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="coords_json" id="coords_json" value="">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Speichern</button>
            <span id="pointCount" style="color: var(--gray-600); font-size: 0.85rem;"></span>
        </form>

        <form method="POST" onsubmit="return confirm('Wirklich auf die ursprüngliche Form zurücksetzen? Die aktuelle Fläche geht dabei verloren.');" style="margin-top: 12px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="reset">
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-rotate-left"></i> Auf ursprüngliche Form zurücksetzen</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var initialCoords = <?php echo json_encode($currentPolygon); ?>;

    var map = L.map('schutzbereich-admin-map');
    L.tileLayer('../tile-proxy.php?z={z}&x={x}&y={y}', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende'
    }).addTo(map);

    var drawnItems = new L.FeatureGroup().addTo(map);

    var polygon = L.polygon(initialCoords, {
        color: '#e4002b',
        weight: 3,
        fillColor: '#e4002b',
        fillOpacity: 0.15
    });
    drawnItems.addLayer(polygon);
    map.fitBounds(polygon.getBounds(), { padding: [20, 20] });

    L.marker([47.27245, 11.43098]).addTo(map)
        .bindPopup('<strong>Feuerwache Reichenau</strong><br>Rossaugasse 4');

    var drawControl = new L.Control.Draw({
        edit: {
            featureGroup: drawnItems,
            poly: { allowIntersection: false },
            edit: {},
        },
        draw: {
            polygon: {
                allowIntersection: false,
                showArea: true,
                shapeOptions: { color: '#e4002b', weight: 3, fillColor: '#e4002b', fillOpacity: 0.15 }
            },
            polyline: false,
            rectangle: false,
            circle: false,
            circlemarker: false,
            marker: false
        }
    });
    map.addControl(drawControl);

    function updateHiddenField() {
        var coords = [];
        drawnItems.eachLayer(function (layer) {
            if (layer instanceof L.Polygon) {
                var latlngs = layer.getLatLngs()[0];
                latlngs.forEach(function (ll) {
                    coords.push([Math.round(ll.lat * 1e6) / 1e6, Math.round(ll.lng * 1e6) / 1e6]);
                });
            }
        });
        document.getElementById('coords_json').value = JSON.stringify(coords);
        document.getElementById('pointCount').textContent = coords.length + ' Eckpunkte';
    }

    map.on(L.Draw.Event.CREATED, function (e) {
        drawnItems.clearLayers();
        drawnItems.addLayer(e.layer);
        updateHiddenField();
    });
    map.on(L.Draw.Event.EDITED, updateHiddenField);
    map.on(L.Draw.Event.DELETED, updateHiddenField);
    map.on(L.Draw.Event.EDITVERTEX, updateHiddenField);

    updateHiddenField();

    document.getElementById('schutzbereichForm').addEventListener('submit', function (e) {
        updateHiddenField();
        if (document.getElementById('coords_json').value === '[]') {
            e.preventDefault();
            alert('Es ist keine Fläche eingezeichnet. Bitte zuerst ein Sechseck-Werkzeug nutzen oder die Seite neu laden.');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
