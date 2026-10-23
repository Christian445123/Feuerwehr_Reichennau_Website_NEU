<?php
/**
 * Liefert die aktuell aktiven GeoSphere-Austria-Wetterwarnungen für
 * Innsbruck-Stadt und den Bezirk Innsbruck-Land als schlankes JSON - für die
 * kleine Statusliste im Footer (siehe includes/footer.php).
 *
 * Die eigentliche Abruf-/Filterlogik (inkl. Caching + Rate-Limit-Gedanke)
 * liegt gemeinsam mit config/site-alert.php in
 * config/geosphere-warnings.php, damit eine echte GeoSphere-Warnung
 * denselben Vollbild-Warnhinweis auslöst wie eine manuelle Warnung.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';
require_once __DIR__ . '/config/geosphere-warnings.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
if (!checkRateLimit($db, 'warnungen_tirol', 30, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Zu viele Anfragen.']);
    exit;
}

$data = getGeoSphereInnsbruckWarnings();
if ($data === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Warnungen gerade nicht abrufbar.']);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
