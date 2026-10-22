<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_gefahrgut.jpg',
    'title' => t('nav.gefahrgut'),
    'intro' => [
        t('einsatzgebiet.gefahrgut.p1'),
        t('einsatzgebiet.gefahrgut.p2'),
        t('einsatzgebiet.gefahrgut.p3'),
    ],
    'galleryFolder' => 'gefahrgut',
    'galleryImages' => ['gefahrgut_01.jpg', 'gefahrgut_02.jpg', 'gefahrgut_03.jpg', 'gefahrgut_05.jpg'],
    'subcategory' => 'abc',
    'subcategoryLabel' => t('einsatzgebiet.gefahrgut.subcategory_label'),
]);
