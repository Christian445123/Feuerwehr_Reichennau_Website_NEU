<?php
// Scrape rank badge image URLs from Kufstein fire department
$url = 'https://feuerwehr-kufstein.at/?page_id=586';
$html = file_get_contents($url);
if (!$html) { echo "Failed to fetch page\n"; exit(1); }

// Find all img tags inside tables
preg_match_all('/<tr[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<\/tr>/si', $html, $matches);

echo "=== ALL TABLE ROW IMAGES ===\n";
foreach ($matches[0] as $i => $row) {
    // Extract text content (abbreviation + name)
    $text = strip_tags($row);
    $text = preg_replace('/\s+/', ' ', trim($text));
    echo $matches[1][$i] . " | " . $text . "\n";
}

echo "\n=== ALSO CHECK FOR data-src / lazy load ===\n";
preg_match_all('/<img[^>]+(data-src|data-lazy-src|data-original)=["\']([^"\']+)["\'][^>]*>/si', $html, $lazy);
foreach ($lazy[0] as $i => $tag) {
    echo $lazy[1][$i] . " = " . $lazy[2][$i] . "\n";
}

// Also check for the PDF link
preg_match('/Dienstgradtafel.*?href=["\']([^"\']+\.pdf)["\']/', $html, $pdfMatch);
if ($pdfMatch) {
    echo "\n=== DIENSTGRADTAFEL PDF ===\n";
    echo $pdfMatch[1] . "\n";
}
