<?php
// Re-download all member photos with CORRECT imageNeckline crop parameters
// The previous download used wrong coordinates (0,0,400,400) resulting in brown blobs

$photos = [
    // Kommando
    '5a245787217f4.jpg' => '321,73,691,691',
    '5a2456c2d36b1.jpg' => '252,39,873,873',
    '5aa40713cf567.jpg' => '42,26,527,527',
    '5a2454654aad7.jpg' => '267,62,845,845',
    
    // Mannschaft (JPGs only, skip GIFs which are placeholders)
    '5a24554b441e4.jpg' => '283,91,821,821',
    '5a2456e70cb51.jpg' => '297,26,886,886',
    '5a24558cb1b1d.jpg' => '259,34,797,797',
    '5aa40832f1780.jpg' => '48,19,539,539',
    '5a2457423ded7.jpg' => '327,53,763,763',
    '5a2450cc2bda8.jpg' => '253,48,857,857',
    '5a2450ec47e3d.jpg' => '287,89,816,816',
    '5a245132dd934.jpg' => '296,44,854,854',
    '5a24515e130c8.jpg' => '340,22,735,735',
    '5a2457314e22c.jpg' => '311,5,785,785',
    '5a2457d491380.jpg' => '252,58,836,836',
    '6367ee38591b9.jpg' => '68,31,696,696',
    '5a2451b2c5b4a.jpg' => '327,20,891,891',
    '5a2451e7f2eb0.jpg' => '230,98,814,814',
    '5a245214b1ee9.jpg' => '289,43,869,869',
    '5aa40802a5b31.jpg' => '26,53,528,528',
    '5a2457139e566.jpg' => '244,59,817,817',
    '5aa407ccc7bdb.jpg' => '49,17,591,591',
    '5a2452d23597b.jpg' => '181,0,912,912',
    '5a2452f802444.jpg' => '285,67,824,824',
    '5a24535a51643.jpg' => '263,81,831,831',
    '5a2453809ada5.jpg' => '294,88,824,824',
    '5a2453cb9c675.jpg' => '243,13,898,898',
    '5a2453ffbe4a9.jpg' => '289,38,874,874',
    '5aa406940e0d0.jpg' => '27,16,550,550',
    '5a24542f73068.jpg' => '243,43,869,869',
    '5aa40750e252e.jpg' => '73,0,553,553',
    '5a24547fa4db0.jpg' => '228,25,871,871',
    '5aa4065de1015.jpg' => '50,2,606,606',
    '5aa4076f4bffa.jpg' => '30,32,498,498',
    '5a2454a7cc1c4.jpg' => '257,20,867,867',
    '5a2454c26aa03.jpg' => '210,0,873,873',
    '5a2454e2aa567.jpg' => '181,65,665,665',
    '5aa4054a36eed.jpg' => '39,19,617,617',
    '5a245517ddd82.jpg' => '302,31,774,774',
    '5a2457c558839.jpg' => '349,72,752,752',
    '5aa407938c156.jpg' => '54,24,611,611',
    '5a24527f1d133.jpg' => '313,52,860,860',
    
    // Ehrenmitglieder
    '5a64a34e4df71.jpg' => '252,39,873,873',  // Werner Federspiel - using default
    '59e8e9eaadcd4.jpg' => '252,39,873,873',  // Harald Fröhlich
    '5a6647771010b.jpg' => '252,39,873,873',  // Anton Larcher
    '59e8ebca90ec4.jpg' => '252,39,873,873',  // Rudi Krebs
    '5a663d7222931.jpg' => '252,39,873,873',  // Romuald Niescher
    
    // Jugend
    '61e5bf91ebca7.jpg' => '252,39,873,873',  // Anna Maria Bidner
    '6596598c66787.jpg' => '252,39,873,873',  // Milena Fröhlich
    '65965950a9e0a.jpg' => '252,39,873,873',  // David Lacob
    '659659bcbbd69.jpg' => '252,39,873,873',  // Sophia Liner
    '622785ed5375c.jpg' => '252,39,873,873',  // Johannes Rainer
];

$baseUrl = 'http://www.ffr.at/images/image.php';
$destDir = 'uploads/members/';
$success = 0;
$failed = 0;

foreach ($photos as $filename => $neckline) {
    $url = $baseUrl . '?imageSrc=' . $filename 
         . '&imageNeckline=' . $neckline 
         . '&imageNewWith=400&imageNewHeight=400';
    
    $dest = $destDir . $filename;
    
    echo "Downloading $filename ... ";
    
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    
    $data = @file_get_contents($url, false, $ctx);
    
    if ($data && strlen($data) > 5000) {
        file_put_contents($dest, $data);
        echo "OK (" . round(strlen($data)/1024) . " KB)\n";
        $success++;
    } else {
        echo "FAILED (size: " . ($data ? strlen($data) : 0) . ")\n";
        $failed++;
    }
    
    usleep(200000); // 200ms delay
}

echo "\nDone: $success OK, $failed failed\n";
