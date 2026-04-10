<?php
// Try to get Jugend photos directly as raw images (without crop)
$jugend = [
    '61e5bf91ebca7.jpg',  // Anna Maria Bidner
    '6596598c66787.jpg',  // Milena Fröhlich  
    '65965950a9e0a.jpg',  // David Lacob
    '659659bcbbd69.jpg',  // Sophia Liner
    '622785ed5375c.jpg',  // Johannes Rainer
];

$ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0']]);

foreach ($jugend as $file) {
    // Try raw image URL
    $url = "http://www.ffr.at/images/$file";
    echo "Trying $url ... ";
    $headers = @get_headers($url, true);
    if ($headers && strpos($headers[0], '200')) {
        echo "EXISTS - downloading...\n";
        $data = file_get_contents($url, false, $ctx);
        if ($data && strlen($data) > 5000) {
            file_put_contents("uploads/members/$file", $data);
            $info = getimagesize("uploads/members/$file");
            echo "  Saved: " . strlen($data) . " bytes, {$info[0]}x{$info[1]}\n";
        }
    } else {
        echo "NOT FOUND\n";
        // Try via image.php without neckline
        $url2 = "http://www.ffr.at/images/image.php?imageSrc=$file&imageNewWith=400&imageNewHeight=400";
        echo "  Trying image.php without neckline... ";
        $data = @file_get_contents($url2, false, $ctx);
        if ($data && strlen($data) > 5000) {
            file_put_contents("uploads/members/$file", $data);
            $info = getimagesize("uploads/members/$file");
            echo "OK: " . strlen($data) . " bytes, {$info[0]}x{$info[1]}\n";
        } else {
            echo "FAILED\n";
        }
    }
}
