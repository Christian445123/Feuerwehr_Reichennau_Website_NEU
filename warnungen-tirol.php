<?php
/**
 * Liefert die aktuell aktiven GeoSphere-Austria-Wetterwarnungen für
 * Innsbruck und Tirol gesamt (gefiltert aus der öffentlichen Warn-API) als
 * schlankes JSON - für die kleine Statusliste im Footer (siehe
 * includes/footer.php).
 *
 * Läuft nach demselben Muster wie tile-proxy.php/schutzgebiet-check.php:
 * serverseitiger Aufruf mit ordentlicher User-Agent-Kennung statt direktem
 * Aufruf aus dem Browser, dazu Caching (Warnungen ändern sich nicht
 * minütlich) und Rate-Limit.
 *
 * Quelle: GeoSphere Austria Warn API (CC BY 4.0),
 * https://warnungen.zamg.at/wsapp/api/getWarnstatus - liefert alle
 * österreichweit aktiven Warnungen je Gemeinde; hier wird auf Tirol
 * (Gemeindekennzahl beginnt mit "7") bzw. Innsbruck (Gemeindekennzahl
 * 70101) eingeschränkt und zu je einer Zusammenfassung verdichtet.
 *
 * wtype (Warnart): 1=Sturm, 2=Regen, 3=Schnee, 4=Glatteis, 5=Gewitter,
 * 6=Hitze, 7=Kälte. wlevel (Warnstufe): 1=gelb/Vorwarnung, 2=orange/Warnung,
 * 3=rot/Warnung (höchste Stufe).
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
if (!checkRateLimit($db, 'warnungen_tirol', 30, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Zu viele Anfragen.']);
    exit;
}

$cacheDir = __DIR__ . '/cache/warnings';
$cacheFile = $cacheDir . '/tirol.json';
$maxAge = 10 * 60; // 10 Minuten - Warnungen ändern sich nicht minütlich,
                    // aber auch nicht tagelang gleich.

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
    readfile($cacheFile);
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ch = curl_init('https://warnungen.zamg.at/wsapp/api/getWarnstatus');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_USERAGENT => "FF-Reichenau-Website-WarnungenProxy/1.0 (+https://$host/)",
]);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $status !== 200) {
    // Bei einem Fehler lieber die zuletzt bekannte (ggf. ältere) Fassung
    // ausliefern als eine Fehlermeldung, falls eine existiert.
    if (is_file($cacheFile)) {
        readfile($cacheFile);
        exit;
    }
    http_response_code(502);
    echo json_encode(['error' => 'Warnungen gerade nicht abrufbar.']);
    exit;
}

$wtypeLabels = [1 => 'Sturm', 2 => 'Regen', 3 => 'Schnee', 4 => 'Glatteis', 5 => 'Gewitter', 6 => 'Hitze', 7 => 'Kälte'];

/**
 * Verdichtet eine Liste von Gemeinde-Features auf die schlimmste aktuell
 * aktive Warnung (höchste Stufe) - mehrere gleich hohe Warnarten werden mit
 * "/" zusammengefasst, z.B. "Sturm / Regen".
 */
$summarize = function (array $features) use ($wtypeLabels): array {
    $maxLevel = 0;
    $types = [];
    foreach ($features as $f) {
        foreach (($f['properties']['warnings'] ?? []) as $w) {
            $level = (int) ($w['wlevel'] ?? 0);
            $type = $wtypeLabels[(int) ($w['wtype'] ?? 0)] ?? null;
            if ($level <= 0 || $type === null) continue;
            if ($level > $maxLevel) {
                $maxLevel = $level;
                $types = [$type];
            } elseif ($level === $maxLevel && !in_array($type, $types, true)) {
                $types[] = $type;
            }
        }
    }
    return ['level' => $maxLevel, 'type' => implode(' / ', $types)];
};

$data = json_decode($body, true);
$allFeatures = (is_array($data) && isset($data['features'])) ? $data['features'] : [];

$tirolFeatures = array_values(array_filter($allFeatures, function ($f) {
    return str_starts_with((string) ($f['properties']['location']['gemeindenr'] ?? ''), '7');
}));
$innsbruckFeatures = array_values(array_filter($tirolFeatures, function ($f) {
    return (string) ($f['properties']['location']['gemeindenr'] ?? '') === '70101';
}));

$response = json_encode([
    'fetched_at' => date('c'),
    'regions' => [
        ['name' => 'Innsbruck', 'summary' => $summarize($innsbruckFeatures)],
        ['name' => 'Tirol', 'summary' => $summarize($tirolFeatures)],
    ],
], JSON_UNESCAPED_UNICODE);

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $response);

echo $response;
