<?php
/**
 * Adress-Check für die Mitmachen-Seite: "Bin ich im Schutzgebiet der FF
 * Reichenau?"
 *
 * Nimmt Straße + Hausnummer entgegen, lässt die Adresse serverseitig über
 * Nominatim (OpenStreetMap-Geokodierung) in Koordinaten umwandeln und prüft
 * diese gegen die unter Admin -> Schutzbereich eingezeichnete Fläche
 * (Punkt-in-Polygon-Test, siehe config/schutzbereich.php). Läuft nach
 * demselben Muster wie tile-proxy.php: serverseitiger Aufruf mit
 * ordentlicher, identifizierender User-Agent-Kennung (Nominatims
 * Nutzungsrichtlinie verlangt das) statt direktem Aufruf aus dem Browser,
 * dazu Caching und Rate-Limit.
 *
 * Nur aktiv, wenn im Admin eingeschaltet (isSchutzgebietCheckEnabled()) -
 * die Funktion ist auf der Website noch nicht offiziell freigegeben.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';
require_once __DIR__ . '/config/schutzbereich.php';

header('Content-Type: application/json; charset=utf-8');

if (!isSchutzgebietCheckEnabled()) {
    http_response_code(404);
    echo json_encode(['error' => 'Diese Funktion ist derzeit nicht aktiv.']);
    exit;
}

$db = getDB();
if (!checkRateLimit($db, 'schutzgebiet_check', 20, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Zu viele Anfragen. Bitte später erneut versuchen.']);
    exit;
}

$strasse = trim($_GET['strasse'] ?? '');
$hausnummer = trim($_GET['hausnummer'] ?? '');

if ($strasse === '' || mb_strlen($strasse) > 100 || mb_strlen($hausnummer) > 20) {
    http_response_code(400);
    echo json_encode(['error' => 'Bitte eine gültige Straße angeben.']);
    exit;
}

$query = trim($strasse . ' ' . $hausnummer) . ', Innsbruck, Österreich';

$cacheKey = md5(mb_strtolower($query));
$cacheDir = __DIR__ . '/cache/geocode';
$cacheFile = "$cacheDir/$cacheKey.json";
$maxAge = 90 * 24 * 60 * 60; // Adressen ändern sich praktisch nie - 90 Tage cachen

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
    echo file_get_contents($cacheFile);
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Bounding Box grob um Innsbruck, damit mehrdeutige Straßennamen nicht in
// einem anderen Ort landen (Nominatim bevorzugt Treffer darin, schließt
// andere aber nicht hart aus - "bounded" würde das, ist hier aber bewusst
// nicht gesetzt, falls die Fläche doch über die Box hinausragt).
$url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&viewbox=11.33,47.32,11.50,47.22&q=' . urlencode($query);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
    // Nominatims Nutzungsrichtlinie verlangt einen aussagekräftigen User-Agent.
    CURLOPT_USERAGENT => "FF-Reichenau-Website-SchutzgebietCheck/1.0 (+https://$host/)",
]);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $status !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Die Adresssuche ist gerade nicht erreichbar. Bitte später erneut versuchen.']);
    exit;
}

$results = json_decode($body, true);
if (!is_array($results) || empty($results)) {
    $response = json_encode(['found' => false]);
} else {
    $lat = (float) $results[0]['lat'];
    $lng = (float) $results[0]['lon'];
    $inArea = isPointInPolygon($lat, $lng, getSchutzbereichPolygon());
    $response = json_encode([
        'found' => true,
        'in_schutzgebiet' => $inArea,
        'display_name' => $results[0]['display_name'] ?? '',
    ]);
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $response);

echo $response;
