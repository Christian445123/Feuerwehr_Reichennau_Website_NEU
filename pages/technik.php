<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_technik.jpg',
    'title' => t('nav.technik'),
    'intro' => [
        t('einsatzgebiet.technik.p1'),
        t('einsatzgebiet.technik.p2'),
        t('einsatzgebiet.technik.p3'),
    ],
    'galleryFolder' => 'technik',
    'galleryImages' => ['technik_grosspumpe_schulung.jpg', 'technik_img8604.jpg', 'technik_01.jpg', 'technik_02.jpg', 'technik_03.jpg', 'technik_04.jpg', 'technik_05.jpg', 'technik_06.jpg'],
    'subcategory' => 'technisch',
    'subcategoryLabel' => t('berichte.subcategory.technisch'),
]);
