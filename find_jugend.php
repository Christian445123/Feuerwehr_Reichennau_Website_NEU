<?php
// Find correct neckline for Jugend members by checking their detail pages
$jugendFiles = [
    '61e5bf91ebca7.jpg',  // Anna Maria Bidner
    '6596598c66787.jpg',  // Milena Fröhlich
    '65965950a9e0a.jpg',  // David Lacob
    '659659bcbbd69.jpg',  // Sophia Liner
    '622785ed5375c.jpg',  // Johannes Rainer
];

// Try fetching detail pages for each member
$detailPages = [
    'http://www.ffr.at/Anna_Maria_Bidner',
    'http://www.ffr.at/Milena_Froehlich',
    'http://www.ffr.at/David_Lacob',
    'http://www.ffr.at/Sophia_Liner',
    'http://www.ffr.at/Johannes_Rainer',
];

$ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);

foreach ($detailPages as $url) {
    echo "=== $url ===\n";
    $html = @file_get_contents($url, false, $ctx);
    if (!$html) {
        echo "  FAILED\n";
        continue;
    }
    if (preg_match_all('/image\.php\?imageSrc=([a-f0-9]+\.jpg)&imageNeckline=([0-9,]*)/', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            echo "  {$m[1]} => '{$m[2]}'\n";
        }
    }
}

// Also try looking on the Jugend_Mannschaft page
echo "\n=== Jugend Detail Links ===\n";
$jugendHtml = @file_get_contents('http://www.ffr.at/Jugend', false, $ctx);
if ($jugendHtml) {
    if (preg_match_all('/goToLink[^>]*link="([^"]+)"/', $jugendHtml, $matches)) {
        foreach ($matches[1] as $link) {
            echo "  LINK: $link\n";
        }
    }
    // Also check for direct image references
    if (preg_match_all('/image\.php\?imageSrc=([a-f0-9]+\.jpg)&imageNeckline=([0-9,]*)/', $jugendHtml, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            echo "  {$m[1]} => '{$m[2]}'\n";
        }
    }
}
