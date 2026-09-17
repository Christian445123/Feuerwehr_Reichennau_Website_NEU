<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_technik.jpg',
    'title' => 'Technik',
    'intro' => [
        'Technische Einsätze umfassen unter anderem Ölspuren, Überschwemmungen, Windwürfe, Autounfälle, Menschen- und Tierbergungen aus gefährlichen Situationen. Sie ergeben mittlerweile den Großteil des Einsatzaufkommens. Abgesehen von Großschadenslagen, werden diese Einsätze (meistens) still alarmiert. Das bedeutet, dass kein Alarm über die Sirenen im Schutzgebiet erfolgt.',
        'Um dieses vielfältige Einsatzgebiet erfolgreich bewältigen zu können, besitzen wir spezielle Ausrüstung wie etwa Kettensägen, Leitern, Sicherungsgerät und Wasserpumpen.',
        'Bei großen Überschwemmungen oder Wassermengen kommt unsere Großpumpe zum Einsatz, welche als Anhänger zum Einsatzort gebracht wird. Sie kann unabhängig von anderen taktischen Fahrzeugen eingesetzt werden und über Stunden bis Tage durchgehend mit entsprechendem Treibstoff-Nachschub operieren.',
    ],
    'galleryFolder' => 'technik',
    'galleryImages' => ['technik_grosspumpe_schulung.jpg', 'technik_img8604.jpg', 'technik_01.jpg', 'technik_02.jpg', 'technik_03.jpg', 'technik_04.jpg', 'technik_05.jpg', 'technik_06.jpg'],
    'subcategory' => 'technisch',
    'subcategoryLabel' => 'Technik',
]);
