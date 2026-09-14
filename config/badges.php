<?php
/**
 * Verwendungs- und Funktionsabzeichen (Tiroler Landesfeuerwehrverband)
 * Auszug für eine lokale Feuerwehr - relevante Dienstposten/Funktionen/Beauftragte.
 * Quelle: Dienstgradtafel V01_24, Landesfeuerwehrverband Tirol.
 */

function getAllBadges(): array {
    return [
        'KDT'   => ['name' => 'Kommandant od. Stellvertreter', 'color' => '#d5001c'],
        'KAS'   => ['name' => 'Kassier',                        'color' => '#e6b800'],
        'SCH'   => ['name' => 'Schriftführer',                  'color' => '#3498db'],
        'ZUG'   => ['name' => 'Zugskommandant',                 'color' => '#8e44ad'],
        'GRP'   => ['name' => 'Gruppenkommandant',               'color' => '#8e44ad'],
        'OMA'   => ['name' => 'Obermaschinist',                  'color' => '#34495e'],
        'MA'    => ['name' => 'Maschinist',                      'color' => '#34495e'],
        'GW'    => ['name' => 'Gerätewart',                      'color' => '#7f8c8d'],
        'ATS'   => ['name' => 'Atemschutzträger',                'color' => '#e67e22'],
        'FUNK'  => ['name' => 'Funker',                          'color' => '#16a085'],
        'STS'   => ['name' => 'Strahlenschutz',                  'color' => '#f39c12'],
        'TA'    => ['name' => 'Taucher',                         'color' => '#2980b9'],
        'RS'    => ['name' => 'Rettungsschwimmer',               'color' => '#2980b9'],
        'SFÜ'   => ['name' => 'Schiffsführer',                   'color' => '#2980b9'],
        'FMD'   => ['name' => 'Feuerwehrmedizinischer Dienst',   'color' => '#c0392b'],
        'FKUH'  => ['name' => 'Feuerwehrkurat Helfer',           'color' => '#8e44ad'],
        'JB'    => ['name' => 'Jugendbetreuer',                  'color' => '#27ae60'],
        'BAUS'  => ['name' => 'Beauftragter Ausbildung',         'color' => '#95a5a6'],
        'BIT'   => ['name' => 'Beauftragter IT',                 'color' => '#95a5a6'],
        'BFUNK' => ['name' => 'Beauftragter Funk',               'color' => '#95a5a6'],
        'BÖA'   => ['name' => 'Beauftragter Öffentlichkeitsarbeit', 'color' => '#95a5a6'],
    ];
}

function getBadgeName(string $code): string {
    $all = getAllBadges();
    return $all[$code]['name'] ?? $code;
}

function getBadgeColor(string $code): string {
    $all = getAllBadges();
    return $all[$code]['color'] ?? '#7f8c8d';
}
