<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_feuer.jpg',
    'title' => t('nav.feuer'),
    'intro' => [
        t('einsatzgebiet.feuer.p1'),
        t('einsatzgebiet.feuer.p2'),
    ],
    'galleryFolder' => 'feuer',
    'galleryImages' => ['feuer_01.jpg', 'feuer_08.jpg', 'feuer_02.jpg', 'feuer_03.jpg', 'feuer_09.jpg', 'feuer_05.jpg'],
    'subcategory' => 'brand',
    'subcategoryLabel' => t('berichte.subcategory.brand'),
]);
