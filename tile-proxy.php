<?php
/**
 * Server-seitiger Cache-Proxy für OpenStreetMap-Kartenkacheln.
 *
 * Grund: der Browser der Besucher bekam beim direkten Einbinden von
 * tile.openstreetmap.org teils "403 Access blocked" (OSM blockt eingebettete
 * Karten ohne erkennbare, richtlinienkonforme Kennung recht aggressiv). OSMs
 * eigene Nutzungsrichtlinie (operations.osmfoundation.org/policies/tiles/)
 * empfiehlt für mehr als nur gelegentliche Nutzung ausdrücklich einen
 * eigenen Cache-Proxy statt direktem Hotlinking im Browser - genau das
 * macht dieses Skript: jede Kachel wird serverseitig mit einer
 * ordentlichen, identifizierenden User-Agent-Kennung genau einmal von OSM
 * geholt, lokal zwischengespeichert und danach direkt von hier ausgeliefert.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';

$db = getDB();
if (!checkRateLimit($db, 'tile_proxy', 400, 300, null)) {
    http_response_code(429);
    exit;
}

$z = isset($_GET['z']) ? (int) $_GET['z'] : -1;
$x = isset($_GET['x']) ? (int) $_GET['x'] : -1;
$y = isset($_GET['y']) ? (int) $_GET['y'] : -1;

$maxTile = $z >= 0 ? (2 ** $z) - 1 : -1;
if ($z < 0 || $z > 19 || $x < 0 || $x > $maxTile || $y < 0 || $y > $maxTile) {
    http_response_code(400);
    exit;
}

$cacheDir = __DIR__ . "/cache/tiles/$z/$x";
$cacheFile = "$cacheDir/$y.png";

// Kartenkacheln ändern sich praktisch nie - 30 Tage lokal cachen hält den
// Speicherplatz klein und erspart wiederholte Anfragen an OSM.
$maxAge = 30 * 24 * 60 * 60;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=' . $maxAge);
    readfile($cacheFile);
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$upstreamUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";

$ch = curl_init($upstreamUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
    // OSMs Richtlinie verlangt einen aussagekräftigen, identifizierenden
    // User-Agent statt eines generischen/leeren - siehe Kommentar oben.
    CURLOPT_USERAGENT => "FF-Reichenau-Website-TileProxy/1.0 (+https://$host/)",
    CURLOPT_HTTPHEADER => ["Referer: https://$host/"],
]);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($data === false || $httpCode !== 200) {
    // Lieber eine ältere gecachte Kachel als gar keine ausliefern.
    if (is_file($cacheFile)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        readfile($cacheFile);
        exit;
    }
    http_response_code(502);
    exit;
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $data);

// Nur gelegentlich (nicht bei jedem Request) alte Kacheln aufräumen, damit
// der Cache-Ordner nicht unbegrenzt wächst.
if (random_int(1, 300) === 1) {
    $tilesRoot = __DIR__ . '/cache/tiles';
    if (is_dir($tilesRoot)) {
        $now = time();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tilesRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && ($now - $file->getMTime()) > $maxAge) {
                @unlink($file->getPathname());
            }
        }
    }
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=' . $maxAge);
echo $data;
