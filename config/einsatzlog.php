<?php
/**
 * Einsatzübersicht - eine schlichte, tabellarische Liste jeder Alarmierung
 * (Einsatz oder Übung), nach dem Vorbild von feuerwehr-zirl.at/
 * einsatzuebersicht/: Alarmzeit, Einsatztext, Straße, Ort, Kurzbericht,
 * dazu drei Zähler (Alarmierungen, Übungen, geleistete Stunden).
 *
 * Bewusst getrennt von den bebilderten "Berichten" (reports) - ein
 * Einsatzlog-Eintrag kann optional auf einen ausführlichen Bericht
 * verlinken (report_id), muss das aber nicht.
 */

require_once __DIR__ . '/database.php';

function getEinsatzlogTypes(): array {
    return ['einsatz' => 'Einsatz', 'uebung' => 'Übung'];
}

/**
 * Jahre, für die mindestens ein Eintrag existiert (neuestes zuerst) - für
 * das Jahres-Auswahlfeld auf der öffentlichen Seite.
 */
function getEinsatzlogYears(PDO $db): array {
    return $db->query("SELECT DISTINCT YEAR(alarmzeit) AS jahr FROM einsatzlog ORDER BY jahr DESC")->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Lifetime-Zähler über alle Jahre hinweg (wie bei feuerwehr-zirl.at, wo die
 * drei Zähler oben unabhängig vom Jahresfilter der Tabelle sind).
 */
function getEinsatzlogStats(PDO $db): array {
    $alarmierungen = (int) $db->query("SELECT COUNT(*) FROM einsatzlog WHERE typ = 'einsatz'")->fetchColumn();
    $uebungen = (int) $db->query("SELECT COUNT(*) FROM einsatzlog WHERE typ = 'uebung'")->fetchColumn();
    $minuten = (int) $db->query("SELECT COALESCE(SUM(dauer_minuten), 0) FROM einsatzlog")->fetchColumn();
    return [
        'alarmierungen' => $alarmierungen,
        'uebungen' => $uebungen,
        'stunden' => (int) round($minuten / 60),
    ];
}

/**
 * Einträge eines Jahres (oder aller Jahre bei null), neueste zuerst, dazu
 * eine fortlaufende Nummer über den gesamten Datenbestand (nicht nur das
 * gefilterte Jahr) - genau wie die "Nr."-Spalte auf feuerwehr-zirl.at.
 */
function getEinsatzlogEntries(PDO $db, ?int $year = null): array {
    if ($year !== null) {
        $stmt = $db->prepare("SELECT * FROM einsatzlog WHERE YEAR(alarmzeit) = ? ORDER BY alarmzeit DESC, id DESC");
        $stmt->execute([$year]);
    } else {
        $stmt = $db->query("SELECT * FROM einsatzlog ORDER BY alarmzeit DESC, id DESC");
    }
    $rows = $stmt->fetchAll();

    // Nummer = Position in der chronologischen Gesamtliste (ältester = Nr. 1),
    // unabhängig vom Jahresfilter.
    $allIds = $db->query("SELECT id FROM einsatzlog ORDER BY alarmzeit ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $numberById = array_flip($allIds);
    foreach ($rows as &$row) {
        $row['nr'] = ($numberById[$row['id']] ?? 0) + 1;
    }
    unset($row);

    return $rows;
}
