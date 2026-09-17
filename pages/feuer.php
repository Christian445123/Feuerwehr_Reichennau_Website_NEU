<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_feuer.jpg',
    'title' => 'Feuer',
    'intro' => [
        'Es ist das namensgebende und wohl bekannteste Einsatzgebiet. Die Gefahr von Bränden effektiv und schnell unter Einsatz von spezieller Ausrüstung und Brandbekämpfungsmethoden zu löschen, zählt zu unseren Grundkompetenzen.',
        'Diese Kompetenz wird durch stätige Weiter- und Ausbildung immer weiter ausgebaut, da sich auch Brände durch neue Gegebenheiten verändern.',
    ],
    'galleryFolder' => 'feuer',
    'galleryImages' => ['feuer_01.jpg', 'feuer_08.jpg', 'feuer_02.jpg', 'feuer_03.jpg', 'feuer_09.jpg', 'feuer_05.jpg'],
    'subcategory' => 'brand',
    'subcategoryLabel' => 'Brand',
]);
