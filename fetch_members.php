<?php
// Fetch raw HTML from ffr.at member detail pages to find image URLs
$pages = [
    'http://www.ffr.at/Kommando',
    'http://www.ffr.at/Mannschaft', 
    'http://www.ffr.at/Ausschuss',
    'http://www.ffr.at/Ehrenmitglieder',
    'http://www.ffr.at/Jugend'
];

foreach ($pages as $url) {
    echo "=== $url ===\n";
    $html = @file_get_contents($url);
    if (!$html) {
        echo "FAILED to fetch\n";
        continue;
    }
    
    // Look for image.php URLs
    if (preg_match_all('/image\.php\?[^"\')\s]+/', $html, $matches)) {
        foreach ($matches[0] as $m) {
            echo "  IMG: $m\n";
        }
    }
    
    // Look for background-image URLs  
    if (preg_match_all('/background-image:\s*url\([\'"]?([^)]+?)[\'"]?\)/', $html, $matches)) {
        foreach ($matches[1] as $m) {
            echo "  BG: $m\n";
        }
    }
    
    // Look for goToLink divs with member names
    if (preg_match_all('/class="goToLink"[^>]*link="([^"]+)"/', $html, $matches)) {
        foreach ($matches[1] as $m) {
            echo "  LINK: $m\n";
        }
    }
    echo "\n";
}
