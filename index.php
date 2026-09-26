<?php
/**
 * Freiwillige Feuerwehr Reichenau - Hauptrouter
 */

require_once __DIR__ . '/config/gate.php';
requireSiteAccess();

// Einfaches Routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Erlaubte Seiten (Whitelist)
$allowed_pages = [
    'home',
    'ueber-uns',
    'ausruestung',
    'jugend',
    'berichte',
    'termine',
    'kontakt',
    'datenschutz',
    'alarmierungen',
    'sicherheitstipps',
    'feuer',
    'technik',
    'gefahrgut',
    'mitmachen',
];

// Alte Sammelseite "Ausrüstung" wurde in Fuhrpark und Gerätehaus aufgeteilt
if ($page === 'ausruestung') {
    header('Location: index.php?page=fuhrpark', true, 301);
    exit;
}

// Sicherheitscheck: Nur erlaubte Seiten laden
if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}

$page_file = __DIR__ . '/pages/' . $page . '.php';

if (!file_exists($page_file)) {
    $page = 'home';
    $page_file = __DIR__ . '/pages/home.php';
}

// Seitentitel
$page_titles = [
    'home'             => 'Startseite',
    'ueber-uns'        => 'Über Uns',
    'ausruestung'      => 'Ausrüstung',
    'jugend'           => 'Jugend',
    'berichte'         => 'Aktuelles',
    'termine'          => 'Termine',
    'kontakt'          => 'Kontakt & Impressum',
    'datenschutz'      => 'Datenschutzerklärung',
    'alarmierungen'    => 'Alarmierungen',
    'sicherheitstipps' => 'Sicherheitstipps',
    'feuer'            => 'Einsatzgebiet Feuer',
    'technik'          => 'Einsatzgebiet Technik',
    'gefahrgut'        => 'Einsatzgebiet Gefahrgut',
    'mitmachen'        => 'Mitmachen',
];

$current_title = $page_titles[$page] ?? 'Startseite';
$current_page = $page;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
require_once $page_file;
require_once __DIR__ . '/includes/footer.php';
