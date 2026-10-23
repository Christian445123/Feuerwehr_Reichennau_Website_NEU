<?php
/**
 * Liefert die aktuell aktiven GeoSphere-Austria-Wetterwarnungen für
 * Innsbruck-Stadt und den Bezirk Innsbruck-Land (gefiltert aus der
 * öffentlichen Warn-API) als schlankes JSON - für die kleine Statusliste im
 * Footer sowie das Warn-Popup beim Seitenaufruf (siehe includes/footer.php).
 *
 * Läuft nach demselben Muster wie tile-proxy.php/schutzgebiet-check.php:
 * serverseitiger Aufruf mit ordentlicher User-Agent-Kennung statt direktem
 * Aufruf aus dem Browser, dazu Caching (Warnungen ändern sich nicht
 * minütlich) und Rate-Limit.
 *
 * Quelle: GeoSphere Austria Warn API (CC BY 4.0),
 * https://warnungen.zamg.at/wsapp/api/getWarnstatus - liefert alle
 * österreichweit aktiven Warnungen je Gemeinde. Die Gemeindekennzahl (GKZ)
 * ist "7" (Bundesland Tirol) + 2-stelliger Bezirk + 2-stellige Gemeinde;
 * Innsbruck-Stadt ist Bezirk "01" (Gemeinde Innsbruck selbst = 70101),
 * Innsbruck-Land ist Bezirk "03" (GKZ-Präfix "703", z.B. Hall in Tirol
 * 70354, Zirl 70369 - per Live-Abfrage gegen getWarningsForCoords
 * verifiziert). Nur diese beiden Gebiete werden ausgewertet.
 *
 * wtype (Warnart): 1=Sturm, 2=Regen, 3=Schnee, 4=Glatteis, 5=Gewitter,
 * 6=Hitze, 7=Kälte. wlevel (Warnstufe): 1=gelb/Vorwarnung, 2=orange/Warnung,
 * 3=rot/Warnung (höchste Stufe).
 *
 * Zusätzlich gibt es im Admin-Bereich (Einstellungen) zwei unabhängige
 * Schalter, die das Popup unabhängig von der echten GeoSphere-Lage
 * erzwingen:
 * - Testmodus: zeigt das Popup mit einem festen Beispielinhalt (zum
 *   schnellen Prüfen, wie es aussieht, ohne eigene Texte einzugeben)
 * - Manuelle Warnung: zeigt das Popup mit frei eingegebenem Text, Bereich
 *   und Farbe (z.B. für einen Hinweis wie "Bereich X meiden")
 * Ist der Testmodus aktiv, hat er Vorrang vor der manuellen Warnung. Beides
 * wird bewusst NICHT mitgecacht, sondern bei jeder Anfrage frisch aus den
 * Website-Einstellungen gelesen, damit Ein-/Ausschalten sofort wirkt.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/logging.php';
require_once __DIR__ . '/config/settings.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Mischt die manuelle Test-/Hinweiswarnung aus den Website-Einstellungen in
 * die fertige Antwort ein, egal ob diese live abgerufen oder aus dem Cache
 * kam.
 */
function applyManualWeatherWarning(string $jsonResponse): string {
    $data = json_decode($jsonResponse, true);
    if (!is_array($data)) {
        return $jsonResponse;
    }

    if (getSiteSetting('weather_warning_test_mode', '0') === '1') {
        $data['has_warning'] = true;
        $data['manual'] = [
            'enabled' => true,
            'color' => '#e67e22',
            'area' => 'Testbereich',
            'text' => 'Dies ist ein Test des Warn-Popups (Testmodus in Admin -> Einstellungen aktiv) - keine echte Warnung.',
        ];
    } elseif (getSiteSetting('weather_warning_manual_enabled', '0') === '1') {
        $data['has_warning'] = true;
        $data['manual'] = [
            'enabled' => true,
            'color' => getSiteSetting('weather_warning_manual_color', '#e74c3c'),
            'area' => getSiteSetting('weather_warning_manual_area', ''),
            'text' => getSiteSetting('weather_warning_manual_text', ''),
        ];
    } else {
        $data['manual'] = ['enabled' => false];
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

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
    echo applyManualWeatherWarning(file_get_contents($cacheFile));
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
        echo applyManualWeatherWarning(file_get_contents($cacheFile));
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

$innsbruckStadtFeatures = array_values(array_filter($allFeatures, function ($f) {
    return (string) ($f['properties']['location']['gemeindenr'] ?? '') === '70101';
}));
$innsbruckLandFeatures = array_values(array_filter($allFeatures, function ($f) {
    return str_starts_with((string) ($f['properties']['location']['gemeindenr'] ?? ''), '703');
}));

$regions = [
    ['name' => 'Innsbruck Stadt', 'summary' => $summarize($innsbruckStadtFeatures)],
    ['name' => 'Innsbruck Land', 'summary' => $summarize($innsbruckLandFeatures)],
];

$response = json_encode([
    'fetched_at' => date('c'),
    'has_warning' => array_reduce($regions, function ($carry, $r) {
        return $carry || $r['summary']['level'] > 0;
    }, false),
    'regions' => $regions,
], JSON_UNESCAPED_UNICODE);

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $response);

echo applyManualWeatherWarning($response);
