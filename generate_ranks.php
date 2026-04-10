<?php
/**
 * Generate all Tiroler Feuerwehr rank badge SVGs
 * Based on official Tiroler Dienstgradtafel
 */
$dir = __DIR__ . '/assets/images/ranks/';

function starPoly($cx, $cy, $R, $fill) {
    $r = $R * 0.577;
    $pts = [];
    for ($i = 0; $i < 12; $i++) {
        $ang = deg2rad(-90 + $i * 30);
        $rd = ($i % 2 === 0) ? $R : $r;
        $pts[] = round($cx + $rd * cos($ang), 1) . ',' . round($cy + $rd * sin($ang), 1);
    }
    return '<polygon points="' . implode(' ', $pts) . '" fill="' . $fill . '"/>';
}

function barRect($cx, $cy, $w, $h, $fill) {
    return '<rect x="' . round($cx - $w/2, 1) . '" y="' . round($cy - $h/2, 1) . '" width="' . $w . '" height="' . $h . '" fill="' . $fill . '" rx="3"/>';
}

function makeSVG($bgType, $elements) {
    $bgColors = ['red' => '#CC0000', 'blue' => '#1B1B6E', 'purple' => '#4B0082'];
    $bg = $bgColors[$bgType];
    
    $xml = '<' . '?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<svg xmlns="http://www.w3.org/2000/svg" width="368" height="368" viewBox="0 0 368 368">' . "\n";
    
    if ($bgType === 'blue') {
        $xml .= '<rect width="368" height="368" fill="#DAA520"/>' . "\n";
        $xml .= '<rect x="14" y="14" width="340" height="340" fill="' . $bg . '"/>' . "\n";
    } else {
        $xml .= '<rect width="368" height="368" fill="' . $bg . '"/>' . "\n";
    }
    
    $xml .= implode("\n", $elements) . "\n";
    $xml .= '</svg>';
    return $xml;
}

// ============================================================
// RANK DEFINITIONS - Tiroler Feuerwehr Dienstgrade
// ============================================================

$ranks = [];
$barW = 200;
$barH = 22;
$barGap = 14;

// --- Mannschaft: Red bg, white (silver) 6-pointed stars ---
$ranks['pfm'] = makeSVG('red', []); // Probefeuerwehrmann: plain red

$ranks['fm'] = makeSVG('red', [
    starPoly(184, 184, 52, '#FFFFFF')  // 1 white star centered
]);

$ranks['ofm'] = makeSVG('red', [
    starPoly(118, 184, 44, '#FFFFFF'), // 2 white stars side by side
    starPoly(250, 184, 44, '#FFFFFF')
]);

$ranks['hfm'] = makeSVG('red', [
    starPoly(184, 128, 40, '#FFFFFF'), // 3 white stars: 1 top, 2 bottom
    starPoly(118, 232, 40, '#FFFFFF'),
    starPoly(250, 232, 40, '#FFFFFF')
]);

// --- Chargen: Red bg, white (silver) horizontal bars ---
$ranks['lm'] = makeSVG('red', [
    barRect(184, 184, 220, $barH, '#FFFFFF')  // 1 bar
]);

$ranks['olm'] = makeSVG('red', [
    barRect(184, 166, 220, $barH, '#FFFFFF'),  // 2 bars
    barRect(184, 202, 220, $barH, '#FFFFFF')
]);

$ranks['hlm'] = makeSVG('red', [
    barRect(184, 148, 220, $barH, '#FFFFFF'),  // 3 bars
    barRect(184, 184, 220, $barH, '#FFFFFF'),
    barRect(184, 220, 220, $barH, '#FFFFFF')
]);

// --- Brandmeister: Red bg, gold 6-pointed stars ---
$ranks['bm'] = makeSVG('red', [
    starPoly(184, 184, 52, '#FFD700')  // 1 gold star
]);

$ranks['obm'] = makeSVG('red', [
    starPoly(118, 184, 44, '#FFD700'), // 2 gold stars
    starPoly(250, 184, 44, '#FFD700')
]);

$ranks['hbm'] = makeSVG('red', [
    starPoly(184, 128, 40, '#FFD700'), // 3 gold stars
    starPoly(118, 232, 40, '#FFD700'),
    starPoly(250, 232, 40, '#FFD700')
]);

// --- Inspektoren: Red bg, gold stars above + 1 gold bar below ---
$ranks['bi'] = makeSVG('red', [
    starPoly(184, 150, 46, '#FFD700'),  // 1 star
    barRect(184, 300, $barW, $barH, '#FFD700')
]);

$ranks['obi'] = makeSVG('red', [
    starPoly(118, 155, 38, '#FFD700'),  // 2 stars
    starPoly(250, 155, 38, '#FFD700'),
    barRect(184, 305, $barW, $barH, '#FFD700')
]);

$ranks['hbi'] = makeSVG('red', [
    starPoly(184, 105, 34, '#FFD700'),  // 3 stars triangle
    starPoly(118, 200, 34, '#FFD700'),
    starPoly(250, 200, 34, '#FFD700'),
    barRect(184, 310, $barW, $barH, '#FFD700')
]);

// --- Höhere Offiziere: Red bg, gold stars above + 2 gold bars below ---
$ranks['abi'] = makeSVG('red', [
    starPoly(184, 130, 44, '#FFD700'),  // 1 star
    barRect(184, 280, $barW, $barH, '#FFD700'),
    barRect(184, 316, $barW, $barH, '#FFD700')
]);

$ranks['br'] = makeSVG('red', [
    starPoly(118, 135, 36, '#FFD700'),  // 2 stars
    starPoly(250, 135, 36, '#FFD700'),
    barRect(184, 280, $barW, $barH, '#FFD700'),
    barRect(184, 316, $barW, $barH, '#FFD700')
]);

$ranks['obr'] = makeSVG('red', [
    starPoly(184, 85, 32, '#FFD700'),   // 3 stars triangle
    starPoly(118, 175, 32, '#FFD700'),
    starPoly(250, 175, 32, '#FFD700'),
    barRect(184, 280, $barW, $barH, '#FFD700'),
    barRect(184, 316, $barW, $barH, '#FFD700')
]);

// --- Verwalter: Blue bg with gold border, gold stars ---
$ranks['v'] = makeSVG('blue', [
    starPoly(184, 184, 52, '#DAA520')  // 1 gold star
]);

$ranks['ov'] = makeSVG('blue', [
    starPoly(118, 184, 44, '#DAA520'), // 2 gold stars
    starPoly(250, 184, 44, '#DAA520')
]);

$ranks['hv'] = makeSVG('blue', [
    starPoly(184, 128, 40, '#DAA520'), // 3 gold stars
    starPoly(118, 232, 40, '#DAA520'),
    starPoly(250, 232, 40, '#DAA520')
]);

// --- Sonderdienste ---
$ranks['fkur'] = makeSVG('purple', [
    '<rect x="156" y="74" width="56" height="220" fill="#DAA520" rx="4"/>',
    '<rect x="90" y="142" width="188" height="56" fill="#DAA520" rx="4"/>'
]);

$ranks['farzt'] = makeSVG('red', [
    '<line x1="184" y1="68" x2="184" y2="305" stroke="#FFD700" stroke-width="14" stroke-linecap="round"/>',
    '<circle cx="184" cy="68" r="20" fill="none" stroke="#FFD700" stroke-width="10"/>',
    '<path d="M184,125 C230,148 140,185 188,215 C230,240 140,275 184,300" fill="none" stroke="#FFD700" stroke-width="11" stroke-linecap="round"/>'
]);

// --- Ehrenränge (same visual as base rank) ---
$ranks['ebi']  = $ranks['bi'];
$ranks['ehbi'] = $ranks['hbi'];
$ranks['ehv']  = $ranks['hv'];
$ranks['elm']  = $ranks['lm'];
$ranks['eobi'] = $ranks['obi'];
$ranks['eolm'] = $ranks['olm'];

// ============================================================
// GENERATE FILES
// ============================================================
$count = 0;
foreach ($ranks as $name => $svg) {
    file_put_contents($dir . $name . '.svg', $svg);
    echo "OK: $name.svg\n";
    $count++;
}
echo "\nDone! $count rank badges generated.\n";
