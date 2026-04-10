<?php
// Test: Download raw source images directly from ffr.at/images/ instead of using image.php crop
$testFiles = [
    '5a245787217f4.jpg',  // Helmut Plank
    '5a2456c2d36b1.jpg',  // David Danner
];

$ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Mozilla/5.0']]);

foreach ($testFiles as $file) {
    $url = "http://www.ffr.at/images/$file";
    echo "Trying $url ... ";
    $data = @file_get_contents($url, false, $ctx);
    if ($data && strlen($data) > 5000) {
        $tmpFile = "uploads/members/test_$file";
        file_put_contents($tmpFile, $data);
        $info = getimagesize($tmpFile);
        echo "OK: " . round(strlen($data)/1024) . " KB, {$info[0]}x{$info[1]}\n";
        unlink($tmpFile);
    } else {
        echo "FAILED (size: " . ($data ? strlen($data) : 0) . ")\n";
    }
}

// Also test with image.php and correct neckline
echo "\nWith image.php + correct neckline:\n";
$url = "http://www.ffr.at/images/image.php?imageSrc=5a245787217f4.jpg&imageNeckline=321,73,691,691&imageNewWith=400&imageNewHeight=400";
echo "Trying $url ... ";
$data = @file_get_contents($url, false, $ctx);
if ($data && strlen($data) > 5000) {
    $tmpFile = "uploads/members/test_crop.jpg";
    file_put_contents($tmpFile, $data);
    $info = getimagesize($tmpFile);
    echo "OK: " . round(strlen($data)/1024) . " KB, {$info[0]}x{$info[1]}\n";
    unlink($tmpFile);
} else {
    echo "FAILED (size: " . ($data ? strlen($data) : 0) . ")\n";
}
