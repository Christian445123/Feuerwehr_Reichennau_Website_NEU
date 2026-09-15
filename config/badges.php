<?php
/**
 * Verwendungs- und Funktionsabzeichen
 * Quelle: "Verwendung/Funktionsabzeichen Richtlinie", Stand 26.03.2012
 * (Bezirksfeuerwehrverband, Tiroler Landesfeuerwehrverband).
 *
 * Drei Kategorien lt. Richtlinie:
 * - Verwendungsabzeichen Feuerwehr: Doppelring, Gold/Rot gestickt
 * - Sachbearbeiter Feuerwehr: Doppelring, Silber gestickt
 * - Funktionsabzeichen Feuerwehr: Einzelring, farblich je nach Funktion
 *
 * 'JB' ist ein Altcode (vor dieser Richtlinien-Umstellung vergeben) und
 * bleibt aus Kompatibilitätsgründen erhalten, damit bereits zugewiesene
 * Mitglieder-Datensätze nicht verwaisen.
 */

function getAllBadges(): array {
    return [
        // --- Verwendungsabzeichen Feuerwehr ---
        'KDT'   => ['name' => 'Kommandant u. Stellvertreter',          'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'kdt.png'],
        'KAS'   => ['name' => 'Kassier',                                'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'kas.png'],
        'SCH'   => ['name' => 'Schriftführer',                          'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'sch.png'],
        'ZUG'   => ['name' => 'Zugskommandant',                         'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'zug.png'],
        'GRP'   => ['name' => 'Gruppenkommandant',                      'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'grp.png'],
        'OMA'   => ['name' => 'Obermaschinist',                         'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'oma.png'],
        'GW'    => ['name' => 'Gerätewart',                             'category' => 'Verwendungsabzeichen', 'color' => '#b8860b', 'image' => 'gw.png'],

        // --- Sachbearbeiter Feuerwehr (Beauftragte) ---
        'BATS'  => ['name' => 'Beauftragter Atemschutz',                'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bats.png'],
        'BEDV'  => ['name' => 'Beauftragter EDV',                       'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bedv.png'],
        'BFUNK' => ['name' => 'Beauftragter Funk',                      'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bfunk.png'],
        'BGS'   => ['name' => 'Beauftragter Gefährliche Stoffe',        'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bgs.png'],
        'BÖA'   => ['name' => 'Beauftragter Öffentlichkeitsarbeit',     'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'boea.png'],
        'BSTS'  => ['name' => 'Beauftragter Strahlenschutz',            'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bsts.png'],
        'BFJ'   => ['name' => 'Beauftragter Feuerwehrjugend',           'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'bfj.png'],
        'BAUS'  => ['name' => 'Beauftragter Ausbildung',                'category' => 'Sachbearbeiter', 'color' => '#7f8c8d', 'image' => 'baus.png'],

        // --- Funktionsabzeichen Feuerwehr ---
        'ATS'   => ['name' => 'Atemschutzgeräteträger',                          'category' => 'Funktionsabzeichen', 'color' => '#e67e22', 'image' => 'ats.png'],
        'FLUG'  => ['name' => 'Flugdienst',                                      'category' => 'Funktionsabzeichen', 'color' => '#2980b9', 'image' => 'flug.png'],
        'FUNK'  => ['name' => 'Funker',                                         'category' => 'Funktionsabzeichen', 'color' => '#16a085', 'image' => 'funk.png'],
        'FMD'   => ['name' => 'Feuerwehrmedizinischer Dienst / Feuerwehrarzt',  'category' => 'Funktionsabzeichen', 'color' => '#c0392b', 'image' => 'fmd.png'],
        'FKUR'  => ['name' => 'Feuerwehrkurat',                                 'category' => 'Funktionsabzeichen', 'color' => '#8e44ad', 'image' => 'fkur.png'],
        'MA'    => ['name' => 'Maschinist',                                     'category' => 'Funktionsabzeichen', 'color' => '#34495e', 'image' => 'ma.png'],
        'MAKF'  => ['name' => 'Maschinist und Kraftfahrer',                     'category' => 'Funktionsabzeichen', 'color' => '#34495e', 'image' => 'makf.png'],
        'SFÜ'   => ['name' => 'Schiffsführer',                                  'category' => 'Funktionsabzeichen', 'color' => '#2980b9', 'image' => 'sfue.png'],
        'SPR'   => ['name' => 'Sprengbefugter',                                 'category' => 'Funktionsabzeichen', 'color' => '#7f0000', 'image' => 'spr.png'],

        // --- Altcode (Kompatibilität, siehe Hinweis oben) ---
        'JB'    => ['name' => 'Jugendbetreuer',                          'category' => 'Sonstige', 'color' => '#27ae60', 'image' => 'jb.png'],
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

function getBadgeImage(string $code): string {
    $all = getAllBadges();
    $file = $all[$code]['image'] ?? null;
    return $file ? 'assets/images/badges/' . $file : '';
}

/** Badges gruppiert nach Kategorie, für die Auswahl im Admin-Formular. */
function getBadgesGrouped(): array {
    $grouped = [];
    foreach (getAllBadges() as $code => $b) {
        $grouped[$b['category']][$code] = $b;
    }
    return $grouped;
}
