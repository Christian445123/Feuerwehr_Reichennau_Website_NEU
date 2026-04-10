<?php
/**
 * Datenbank mit Mitgliederdaten befüllen (Seed)
 * Einmalig ausführen: php config/seed.php
 * Oder im Browser: /config/seed.php
 */

require_once __DIR__ . '/database.php';

$db = getDB();

// Prüfen ob bereits Mitglieder vorhanden
$count = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
if ($count > 0) {
    echo "Datenbank enthält bereits $count Mitglieder. Mitglieder-Seed übersprungen.\n";
} else {

// ── Kommando ── [Vorname, Nachname, Dienstgrad, Funktion, Gruppe, Sortierung]
$kommando = [
    ['Helmut', 'Plank', 'HBI', 'Kommandant', 'Kommando', 1],
    ['David', 'Danner', 'OBI', 'Kdt.-Stv.', 'Kommando', 2],
    ['Martin', 'Rainalter', 'HV', 'Kassier', 'Kommando', 3],
    ['Nina', 'Rippl', 'HV', 'Schriftführerin', 'Kommando', 4],
];

// ── Ausschuss ──
$ausschuss = [
    ['Helmut', 'Plank', 'HBI', 'Kommandant', 'Ausschuss', 1],
    ['David', 'Danner', 'OBI', 'Kdt.-Stv.', 'Ausschuss', 2],
    ['Martin', 'Rainalter', 'HV', 'Kassier', 'Ausschuss', 3],
    ['Nina', 'Rippl', 'HV', 'Schriftführerin', 'Ausschuss', 4],
    ['Matthias', 'Stauder', 'BM', 'Zugskommandant', 'Ausschuss', 5],
    ['Harald', 'Glenda', 'OLM', 'Gruppenkommandant', 'Ausschuss', 6],
    ['Martin', 'Tiefnig', 'LM', 'Gerätewart', 'Ausschuss', 7],
    ['Michael', 'Pelzl', 'LM', 'Atemschutzwart', 'Ausschuss', 8],
    ['Johannes', 'Bauernfeind', 'HFM', 'Funkwart', 'Ausschuss', 9],
    ['Angela', 'Pelzl', 'HFM', 'Jugendbetreuerin', 'Ausschuss', 10],
    ['Dominik', 'Gasser', 'OFM', 'Zeugwart', 'Ausschuss', 11],
];

// ── Mannschaft ── [Vorname, Nachname, Dienstgrad]
$mannschaft = [
    ['Marcel', 'Achs', 'FM'],
    ['Johannes', 'Bauernfeind', 'HFM'],
    ['Marc', 'Bergmann', 'FM'],
    ['Andreas', 'Binder', 'OFM'],
    ['Linhard', 'Boakye', 'FM'],
    ['Irmgard', 'Brandlhuber', 'FM'],
    ['David', 'Danner', 'OBI'],
    ['Bianca', 'Ernst', 'FM'],
    ['Kevin', 'Friedl', 'PFM'],
    ['David', 'Fuchs', 'OFM'],
    ['Dominik', 'Gasser', 'OFM'],
    ['Selina', 'Gasser', 'FM'],
    ['Harald', 'Glenda', 'OLM'],
    ['Matthias', 'Glenda', 'LM'],
    ['Thomas', 'Glenda', 'OFM'],
    ['Julia', 'Gruber', 'FM'],
    ['Leo', 'Gschnaller', 'PFM'],
    ['Josef', 'Haller', 'HFM'],
    ['Mario', 'Hauptstock', 'OFM'],
    ['Fabian', 'Heinecke', 'FM'],
    ['Patrick', 'Heinrich', 'OFM'],
    ['Michel', 'Hilweg', 'FM'],
    ['Martin', 'Rainalter', 'HV'],
    ['Nina', 'Rippl', 'HV'],
    ['Michael', 'Pelzl', 'LM'],
    ['Angela', 'Pelzl', 'HFM'],
    ['Helmut', 'Plank', 'HBI'],
    ['Matthias', 'Stauder', 'BM'],
    ['Martin', 'Tiefnig', 'LM'],
];

// ── Ehrenmitglieder ──
$ehrenmitglieder = [
    ['Werner', 'Federspiel', 'EBI', 'Ehrenmitglied', 'Ehrenmitglieder', 1],
    ['Harald', 'Fröhlich', 'EHBI', 'Ehrenkommandant', 'Ehrenmitglieder', 2],
    ['Anton', 'Larcher', 'ELM', 'Ehrenmitglied', 'Ehrenmitglieder', 3],
    ['Armin', 'Praxmarer', 'EHBI', 'Ehrenkommandant', 'Ehrenmitglieder', 4],
    ['Romuald', 'Niescher', 'ELM', 'Ehrenmitglied', 'Ehrenmitglieder', 5],
    ['Rudi', 'Krebs', 'ELM', 'Ehrenmitglied', 'Ehrenmitglieder', 6],
];

$stmt = $db->prepare("INSERT INTO members (firstname, lastname, `rank`, `function`, group_name, sort_order, active) VALUES (?,?,?,?,?,?,1)");

$inserted = 0;

// Kommando einfügen
foreach ($kommando as $m) {
    $stmt->execute([$m[0], $m[1], $m[2], $m[3], $m[4], $m[5]]);
    $inserted++;
}

// Ausschuss einfügen
foreach ($ausschuss as $m) {
    $stmt->execute([$m[0], $m[1], $m[2], $m[3], $m[4], $m[5]]);
    $inserted++;
}

// Mannschaft einfügen
$i = 1;
foreach ($mannschaft as $m) {
    $stmt->execute([$m[0], $m[1], $m[2], '', 'Mannschaft', $i++]);
    $inserted++;
}

// Ehrenmitglieder einfügen
foreach ($ehrenmitglieder as $m) {
    $stmt->execute([$m[0], $m[1], $m[2], $m[3], $m[4], $m[5]]);
    $inserted++;
}

echo "Fertig! $inserted Mitglieder eingefügt.\n";
echo "Dienstgrade und Funktionen können im Admin-Bereich nachgetragen werden.\n";
} // end else (members)

// ── Berichte seeden ──
$reportCount = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
if ($reportCount > 0) {
    echo "Es gibt bereits $reportCount Berichte. Berichte-Seed übersprungen.\n";
} else {
    $reports = [
        ['Wasserschaden KAT-Lager', 'einsatz', '2024-12-15', 'Einsatz wegen Wasserschaden im KAT-Lager.'],
        ['Brand Mehrfamilienhaus - Rauchentwicklung am Dach', 'einsatz', '2024-11-20', 'Brandeinsatz bei einem Mehrfamilienhaus. Rauchentwicklung wurde am Dach festgestellt.'],
        ['Brand Wald - Nachalarmierung', 'einsatz', '2024-10-05', 'Waldbrand mit Nachalarmierung mehrerer Einheiten.'],
        ['Brand MFH Wohnung, Hochhaus', 'einsatz', '2024-09-12', 'Wohnungsbrand in einem Hochhaus. Einsatz mit mehreren Atemschutztrupps.'],
        ['Gemeinschaftsübung mit der FF Mühlau', 'uebung', '2024-08-22', 'Gemeinsame Übung mit der Freiwilligen Feuerwehr Mühlau zur Verbesserung der Zusammenarbeit.'],
        ['THL Sicherungsarbeiten', 'einsatz', '2024-07-18', 'Technische Hilfeleistung - Sicherungsarbeiten nach Unwetter.'],
        ['41. Jahreshauptversammlung', 'sonstige', '2024-03-15', 'Die 41. Jahreshauptversammlung der Freiwilligen Feuerwehr Reichenau fand statt.'],
        ['THL Ölspur', 'einsatz', '2024-02-10', 'Technische Hilfeleistung wegen einer Ölspur auf der Fahrbahn.'],
        ['Brandsicherheitswache - Tuifltreffen', 'einsatz', '2024-01-20', 'Brandsicherheitswache beim traditionellen Tuifltreffen.'],
        ['Unterstützungseinsatz Kematen i. Tirol', 'einsatz', '2023-12-08', 'Unterstützungseinsatz in Kematen in Tirol.'],
        ['3 technische Einsätze - Ölspuren', 'einsatz', '2023-11-15', 'Drei technische Einsätze aufgrund von Ölspuren im Einsatzgebiet.'],
        ['Gefahrguteinsatz', 'einsatz', '2023-10-22', 'Einsatz der Gefahrguteinheit im Schutzgebiet.'],
        ['Brand in Tiefgarage', 'einsatz', '2023-09-30', 'Brandeinsatz in einer Tiefgarage. Einsatz unter schwerem Atemschutz.'],
        ['THL - Innuferreinigung', 'einsatz', '2023-06-10', 'Technische Hilfeleistung bei der Reinigung des Innufers.'],
        ['Zimmerbrand', 'einsatz', '2023-05-18', 'Zimmerbrand in einem Wohngebäude im Schutzgebiet.'],
    ];

    $stmtR = $db->prepare("INSERT INTO reports (title, category, date, content, author, published) VALUES (?,?,?,?,?,1)");
    $rInserted = 0;
    foreach ($reports as $r) {
        $stmtR->execute([$r[0], $r[1], $r[2], $r[3], 'FF Reichenau']);
        $rInserted++;
    }
    echo "$rInserted Berichte eingefügt.\n";
}
