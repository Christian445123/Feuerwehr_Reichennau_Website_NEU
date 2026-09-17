<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/einsatzgebiet-template.php';
$db = getDB();

renderEinsatzgebietPage($db, [
    'heroImage' => 'assets/images/einsatzgebiet_gefahrgut.jpg',
    'title' => 'Gefahrgut',
    'intro' => [
        'Die FF Reichenau ist eine sogenannte Stützpunktfeuerwehr mit der Spezialisierung auf Gefahrgut. Dies ist ein Überbegriff für alle Einsätze mit chemischen, biologischen oder atomaren Gefahrstoffen. Die Kernkompetenz dieses Spezialgebiets besteht darin, die Gefahrstoffe einzudämmen, damit diese keine Gefahr mehr für Leib und Leben bzw. Besitz der Bevölkerung darstellen.',
        'Gefahrgut begegnet uns nicht nur auf der Straße, in der Industrie oder in Laboren — es ist ein fixer Bestandteil des täglichen Lebens: In jedem Haushalt finden sich Gegenstände und Stoffe, die in einem Einsatzszenario zu einem Gefahrstoff werden können. Putzmittel, Gasflaschen für den Gasgrill oder Akkugeräte können potenzielle Gefahrquellen sein.',
        'Um solche Gefahrenquellen sicher unschädlich machen zu können, besitzen wir ein sogenanntes GGF (Gefahrgutfahrzeug). Dieses Fahrzeug ist speziell für den Gefahrgut-Einsatz konzipiert und besitzt eine entsprechende Ausstattung, um solche Einsätze abzuarbeiten.',
    ],
    'galleryFolder' => 'gefahrgut',
    'galleryImages' => ['gefahrgut_01.jpg', 'gefahrgut_02.jpg', 'gefahrgut_03.jpg', 'gefahrgut_05.jpg'],
    'subcategory' => 'abc',
    'subcategoryLabel' => 'ABC/Gefahrgut',
]);
