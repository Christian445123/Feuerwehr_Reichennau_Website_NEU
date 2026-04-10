<?php
/**
 * Download all Tiroler Feuerwehr rank badges from Kufstein website
 * and save as PNG files in assets/images/ranks/
 */
$dir = __DIR__ . '/assets/images/ranks/';

// First: remove ALL existing SVG files
foreach (glob($dir . '*.svg') as $old) {
    unlink($old);
    echo "Removed: " . basename($old) . "\n";
}
foreach (glob($dir . '*.png') as $old) {
    unlink($old);
    echo "Removed: " . basename($old) . "\n";
}
echo "\n";

// Rank badge URLs from Kufstein (official Tiroler Dienstgrade)
$ranks = [
    // Dienstgrade
    'jfm'   => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/JFM-e1545237830981.png',
    'pfm'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/PFM.png',
    'fm'    => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/FM.png',
    'ofm'   => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/OFM.png',
    'hfm'   => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/HFM.png',
    'lm'    => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/LM.png',
    'olm'   => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/OLM.png',
    'hlm'   => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/HLM.png',
    'bm'    => 'http://feuerwehr-kufstein.at/wp-content/uploads/2018/12/BM.png',
    'obm'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/OBM.png',
    'hbm'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/HBM.png',
    'bi'    => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Brandinspektor.png',
    'obi'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Oberbrandinspektor.png',
    'hbi'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Hauptbrandinspektor.png',
    'abi'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/ABI-e1544592474129.png',
    'br'    => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/BR-e1544592456650.png',
    'obr'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/OBR-e1544592427569.png',
    'lbd-stv' => 'https://feuerwehr-kufstein.at/wp-content/uploads/2023/06/Landesbranddirektor_Stellvertreter.png',
    'lbd'   => 'https://feuerwehr-kufstein.at/wp-content/uploads/2023/06/Landesbranddirektor.png',
    'fkur'  => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Feuerwehrkurat.png',
    'farzt' => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Feuerwehrartzt.png',
    'v'     => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Verwalter.png',
    'ov'    => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Oberverwalter.png',
    'hv'    => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Hauptverwalter.png',
];

// Funktionen (from functions table)
$functions = [
    'kdt'     => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Brandinspektor.png',     // same as BI typically
    'kdt-stv' => 'https://feuerwehr-kufstein.at/wp-content/uploads/2018/12/Oberbrandinspektor.png', // same as OBI typically
];
// We skip function badges - they use the same rank badges

$ctx = stream_context_create([
    'http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0'],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
]);

$ok = 0;
$fail = 0;
foreach ($ranks as $code => $url) {
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 100) {
        $path = $dir . $code . '.png';
        file_put_contents($path, $data);
        $size = strlen($data);
        echo "OK: $code.png ($size bytes)\n";
        $ok++;
    } else {
        echo "FAIL: $code ($url)\n";
        $fail++;
    }
}

// Create copies for Ehren-ranks (same badge as base rank)
$ehren = [
    'ebi'  => 'bi',
    'eobi' => 'obi',
    'ehbi' => 'hbi',
    'elm'  => 'lm',
    'eolm' => 'olm',
    'ehv'  => 'hv',
];

foreach ($ehren as $ehrenCode => $baseCode) {
    $src = $dir . $baseCode . '.png';
    $dst = $dir . $ehrenCode . '.png';
    if (file_exists($src)) {
        copy($src, $dst);
        echo "COPY: $ehrenCode.png (from $baseCode)\n";
        $ok++;
    }
}

echo "\nDone! $ok OK, $fail failed.\n";
