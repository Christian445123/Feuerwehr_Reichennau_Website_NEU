<?php
/**
 * Gemeinsame Abruf-/Filterlogik für die GeoSphere-Austria-Wetterwarnungen
 * (Innsbruck-Stadt/Innsbruck-Land) - genutzt sowohl von warnungen-tirol.php
 * (JSON für die kleine Liste im Footer) als auch von config/site-alert.php
 * (damit eine echte GeoSphere-Warnung denselben Vollbild-Hinweis auslöst wie
 * eine manuelle Warnung).
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
 */

/**
 * Liefert ['fetched_at' => ..., 'regions' => [['name'=>, 'summary'=>['level'=>, 'type'=>]], ...]]
 * für Innsbruck-Stadt und Innsbruck-Land, mit 10-Minuten-Datei-Cache.
 * Bei einem Fehler wird - falls vorhanden - die zuletzt bekannte (ggf.
 * ältere) Fassung aus dem Cache zurückgegeben, sonst null.
 */
function getGeoSphereInnsbruckWarnings(): ?array {
    $cacheDir = __DIR__ . '/../cache/warnings';
    $cacheFile = $cacheDir . '/tirol.json';
    $maxAge = 10 * 60; // 10 Minuten - Warnungen ändern sich nicht minütlich,
                        // aber auch nicht tagelang gleich.

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
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
        if (is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            return is_array($cached) ? $cached : null;
        }
        return null;
    }

    $wtypeLabels = [1 => 'Sturm', 2 => 'Regen', 3 => 'Schnee', 4 => 'Glatteis', 5 => 'Gewitter', 6 => 'Hitze', 7 => 'Kälte'];

    /**
     * Verdichtet eine Liste von Gemeinde-Features auf die schlimmste
     * aktuell aktive Warnung (höchste Stufe) - mehrere gleich hohe
     * Warnarten werden mit "/" zusammengefasst, z.B. "Sturm / Regen".
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

    $result = [
        'fetched_at' => date('c'),
        'regions' => [
            ['name' => 'Innsbruck Stadt', 'summary' => $summarize($innsbruckStadtFeatures)],
            ['name' => 'Innsbruck Land', 'summary' => $summarize($innsbruckLandFeatures)],
        ],
    ];

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Höchste aktuell aktive Warnstufe/-art über Innsbruck-Stadt und
 * Innsbruck-Land zusammen, oder null, wenn gerade keine Warnung aktiv ist
 * (oder die Daten nicht abrufbar sind).
 */
function getWorstGeoSphereInnsbruckWarning(): ?array {
    $data = getGeoSphereInnsbruckWarnings();
    if ($data === null || !isset($data['regions'])) {
        return null;
    }

    $worst = null;
    foreach ($data['regions'] as $region) {
        $level = (int) ($region['summary']['level'] ?? 0);
        if ($level <= 0) continue;
        if ($worst === null || $level > $worst['level']) {
            $worst = [
                'level' => $level,
                'type' => $region['summary']['type'] ?? '',
                'region' => $region['name'] ?? '',
            ];
        }
    }

    return $worst;
}
