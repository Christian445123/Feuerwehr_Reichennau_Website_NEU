<?php
/**
 * Liefert ein kleines, statisches Vorschaubild der aktuellen
 * österreichweiten Unwetterwarnkarte von UWZ.AT (UBIMET GmbH) - server-
 * seitig zwischengespeichert, statt die große interaktive GeoSphere-
 * Kartenansicht per Iframe einzubetten (zu groß/unübersichtlich mit all
 * ihren Bedienelementen, siehe Nutzer-Feedback). Für Innsbruck-Stadt/-Land
 * im Detail bleibt die separate Liste zuständig (siehe warnungen-tirol.php).
 *
 * Läuft nach demselben Proxy-Muster wie tile-proxy.php: eigener
 * Server-Request mit User-Agent statt direktem Hotlink aus dem Besucher-
 * Browser, dazu Caching.
 *
 * Quelle: https://www.uwz.at/data/previews/AT_warning_today_all_desktop.png
 * (öffentlich von UWZ.AT auf der eigenen Startseite eingebunden).
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';

$db = getDB();
if (!checkRateLimit($db, 'uwz_map', 30, 300)) {
    http_response_code(429);
    exit;
}

$cacheDir = __DIR__ . '/cache/warnings';
$cacheFile = $cacheDir . '/uwz-map.png';
$maxAge = 15 * 60; // 15 Minuten

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=900');
    readfile($cacheFile);
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ch = curl_init('https://www.uwz.at/data/previews/AT_warning_today_all_desktop.png');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_USERAGENT => "FF-Reichenau-Website-WarnkartenProxy/1.0 (+https://$host/)",
]);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $status !== 200) {
    if (is_file($cacheFile)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=900');
        readfile($cacheFile);
        exit;
    }
    http_response_code(502);
    exit;
}

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $body);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=900');
echo $body;
