<?php
/**
 * Allgemeiner Warnhinweis, der (falls aktiv) als Vollbild-Overlay über die
 * gesamte Website/den gesamten Bildschirm erscheint (siehe
 * includes/header.php), bestätigt werden muss und danach als kleine
 * Markierung links liegen bleibt - zusätzlich prominent im Hero der
 * Startseite (siehe pages/home.php). Die Kategorie (Erdbeben, Hochwasser,
 * Unwetter, Verkehr, Chemieunfall, ...) bestimmt Icon und die angezeigten
 * Verhaltenstipps; Kategorien sind frei erweiter- und bearbeitbar über
 * Admin -> Warnhinweis (Tabelle site_alert_categories, siehe
 * config/migrations.php für die Vorbefüllung).
 *
 * Drei Quellen, in dieser Reihenfolge (die erste zutreffende gewinnt):
 * 1. Testmodus: fester Beispielinhalt, zum schnellen Prüfen der Optik
 * 2. Manuell: frei gewählte Kategorie + Text/Ort/Bereich/Farbe
 * 3. GeoSphere Austria: eine echte aktive Wetterwarnung für
 *    Innsbruck-Stadt/Innsbruck-Land/Tirol löst automatisch denselben
 *    Vollbild-Hinweis aus wie eine manuelle Warnung (siehe
 *    config/geosphere-warnings.php), Kategorie "unwetter"
 *
 * Mehrsprachigkeit: Kategorie-Label/Tipps (label_en/tips_en) und die frei
 * eingegebenen Felder einer manuellen Warnung (site_alert_location_en/
 * site_alert_area_en/site_alert_text_en) werden beim Speichern im Admin-
 * Bereich automatisch per DeepL mitübersetzt (siehe config/translate.php,
 * admin/warnhinweis.php) - exakt dasselbe Prinzip wie bei Berichten/
 * Mitgliederprofilen. Ohne DeepL-Key bleiben die *_en-Werte leer und die
 * Anzeige fällt automatisch auf die deutsche Fassung zurück.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/geosphere-warnings.php';
require_once __DIR__ . '/translate.php';

/**
 * Alle Warnhinweis-Kategorien, sortiert, als [category_key => [...]] - immer
 * mit deutscher UND (falls vorhanden) englischer Fassung (label/label_en,
 * tips/tips_en). Für die öffentliche Anzeige bitte localizeSiteAlert()
 * bzw. die 'label'/'tips'-Felder von getActiveSiteAlert() verwenden, die
 * bereits die passende Sprache gewählt haben.
 */
function getSiteAlertTypes(): array {
    $db = getDB();
    $rows = $db->query("SELECT * FROM site_alert_categories ORDER BY sort_order ASC, label ASC")->fetchAll();
    $types = [];
    foreach ($rows as $row) {
        $types[$row['category_key']] = [
            'label' => $row['label'],
            'label_en' => $row['label_en'] ?? '',
            'icon' => $row['icon'],
            'tips' => $row['tips'] ?? '',
            'tips_en' => $row['tips_en'] ?? '',
        ];
    }
    return $types;
}

function getSiteAlertCategory(string $key): ?array {
    $types = getSiteAlertTypes();
    return $types[$key] ?? null;
}

/**
 * Kategorie-Label/Tipps in der aktuellen Besuchersprache, mit Rückfall auf
 * die deutsche Fassung, falls (noch) keine Übersetzung vorliegt.
 */
function localizeSiteAlertCategory(array $category): array {
    return [
        'label' => isEnglish() && $category['label_en'] !== '' ? $category['label_en'] : $category['label'],
        'icon' => $category['icon'],
        'tips' => isEnglish() && $category['tips_en'] !== '' ? $category['tips_en'] : $category['tips'],
    ];
}

/**
 * Legt eine neue Kategorie an oder aktualisiert eine bestehende (anhand
 * $key). $key wird dabei nie verändert - nur Label/Icon/Tipps. Übersetzt
 * Label/Tipps automatisch per DeepL ins Englische, falls konfiguriert
 * (siehe config/translate.php) - bei fehlendem Key oder Fehler bleiben
 * label_en/tips_en einfach leer (Anzeige fällt dann auf Deutsch zurück).
 */
function saveSiteAlertCategory(string $key, string $label, string $icon, string $tips, int $sortOrder = 0): void {
    $db = getDB();

    $labelEn = '';
    $tipsEn = '';
    if (function_exists('translateBatchToEnglish')) {
        $translated = translateBatchToEnglish(['label' => $label, 'tips' => $tips]);
        if ($translated !== null) {
            $labelEn = $translated['label'] ?? '';
            $tipsEn = $translated['tips'] ?? '';
        }
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM site_alert_categories WHERE category_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() > 0) {
        $db->prepare("UPDATE site_alert_categories SET label = ?, label_en = ?, icon = ?, tips = ?, tips_en = ?, sort_order = ? WHERE category_key = ?")
            ->execute([$label, $labelEn, $icon, $tips, $tipsEn, $sortOrder, $key]);
    } else {
        $db->prepare("INSERT INTO site_alert_categories (category_key, label, label_en, icon, tips, tips_en, sort_order) VALUES (?,?,?,?,?,?,?)")
            ->execute([$key, $label, $labelEn, $icon, $tips, $tipsEn, $sortOrder]);
    }
}

/**
 * Löscht eine Kategorie. Ist sie gerade als manueller Warnhinweis aktiv
 * ausgewählt, wird der Hinweis automatisch deaktiviert, damit keine
 * verwaiste Auswahl stehen bleibt.
 */
function deleteSiteAlertCategory(string $key): void {
    $db = getDB();
    $db->prepare("DELETE FROM site_alert_categories WHERE category_key = ?")->execute([$key]);
    if (getSiteSetting('site_alert_type', '') === $key) {
        setSiteSetting('site_alert_enabled', '0');
    }
}

/**
 * Speichert eine manuelle Warnung inkl. automatischer DeepL-Übersetzung von
 * Ort/Bereich/Hinweistext (site_alert_*_en in site_settings).
 */
function saveManualSiteAlert(bool $enabled, string $type, string $color, string $location, string $area, string $text): void {
    $locationEn = '';
    $areaEn = '';
    $textEn = '';
    if (function_exists('translateBatchToEnglish')) {
        $translated = translateBatchToEnglish(['location' => $location, 'area' => $area, 'text' => $text]);
        if ($translated !== null) {
            $locationEn = $translated['location'] ?? '';
            $areaEn = $translated['area'] ?? '';
            $textEn = $translated['text'] ?? '';
        }
    }

    setSiteSetting('site_alert_enabled', $enabled ? '1' : '0');
    setSiteSetting('site_alert_type', $type);
    setSiteSetting('site_alert_color', $color);
    setSiteSetting('site_alert_location', $location);
    setSiteSetting('site_alert_location_en', $locationEn);
    setSiteSetting('site_alert_area', $area);
    setSiteSetting('site_alert_area_en', $areaEn);
    setSiteSetting('site_alert_text', $text);
    setSiteSetting('site_alert_text_en', $textEn);
}

/**
 * Liefert den aktuell anzuzeigenden Seiten-Warnhinweis, oder null, wenn
 * keiner aktiv ist. Enthält immer 'label', 'tips' sowie 'location'/'area'/
 * 'text' bereits in der aktuellen Besuchersprache (Englisch mit Rückfall
 * auf Deutsch, falls keine Übersetzung vorliegt).
 */
function getActiveSiteAlert(): ?array {
    $types = getSiteAlertTypes();
    $en = isEnglish();

    if (getSiteSetting('site_alert_test_mode', '0') === '1') {
        $fallback = $types['sonstiges'] ?? ['icon' => 'fa-triangle-exclamation', 'label' => 'Sonstiges', 'label_en' => 'Other', 'tips' => '', 'tips_en' => ''];
        $localized = localizeSiteAlertCategory($fallback);
        return [
            'type' => 'sonstiges',
            'color' => '#e67e22',
            'icon' => $localized['icon'],
            'label' => $localized['label'],
            'location' => $en ? 'Test location' : 'Testort',
            'area' => $en ? 'Test area' : 'Testbereich',
            'text' => $en
                ? 'This is a test of the site-wide alert (test mode active in Admin -> Warnhinweis) - not a real warning.'
                : 'Dies ist ein Test des Seiten-Warnhinweises (Testmodus in Admin -> Warnhinweis aktiv) - kein echter Hinweis.',
            'tips' => $localized['tips'],
        ];
    }

    if (getSiteSetting('site_alert_enabled', '0') === '1') {
        $type = getSiteSetting('site_alert_type', 'sonstiges');
        $category = $types[$type] ?? null;
        $localized = $category !== null ? localizeSiteAlertCategory($category) : ['icon' => 'fa-triangle-exclamation', 'label' => 'Sonstiges', 'tips' => ''];

        $location = getSiteSetting('site_alert_location', '');
        $locationEn = getSiteSetting('site_alert_location_en', '');
        $area = getSiteSetting('site_alert_area', '');
        $areaEn = getSiteSetting('site_alert_area_en', '');
        $text = getSiteSetting('site_alert_text', '');
        $textEn = getSiteSetting('site_alert_text_en', '');

        return [
            'type' => $category !== null ? $type : 'sonstiges',
            'color' => getSiteSetting('site_alert_color', '#e74c3c'),
            'icon' => $localized['icon'],
            'label' => $localized['label'],
            'location' => $en && $locationEn !== '' ? $locationEn : $location,
            'area' => $en && $areaEn !== '' ? $areaEn : $area,
            'text' => $en && $textEn !== '' ? $textEn : $text,
            'tips' => $localized['tips'],
        ];
    }

    $geosphereAlert = getGeoSphereSiteAlert();
    if ($geosphereAlert !== null) {
        return $geosphereAlert;
    }

    return null;
}

/**
 * Baut - falls aktuell eine echte GeoSphere-Austria-Warnung für
 * Innsbruck-Stadt, Innsbruck-Land oder Tirol insgesamt aktiv ist - denselben
 * Hinweis, wie ihn auch eine manuelle Warnung erzeugen würde (gleiche
 * Anzeige/Overlay, Kategorie "unwetter" inkl. deren Tipps), in der
 * aktuellen Besuchersprache.
 */
function getGeoSphereSiteAlert(): ?array {
    $worst = getWorstGeoSphereInnsbruckWarning();
    if ($worst === null) {
        return null;
    }

    $en = isEnglish();
    $colorsByLevel = [1 => '#f1c40f', 2 => '#e67e22', 3 => '#e74c3c'];
    $unwetterCategory = getSiteAlertCategory('unwetter');
    $localized = $unwetterCategory !== null ? localizeSiteAlertCategory($unwetterCategory) : ['icon' => 'fa-cloud-bolt', 'label' => 'Unwetter', 'tips' => ''];
    $warningType = $en && $worst['type_en'] !== '' ? $worst['type_en'] : $worst['type'];

    return [
        'type' => 'unwetter',
        'color' => $colorsByLevel[$worst['level']] ?? '#e74c3c',
        'icon' => $localized['icon'],
        'label' => $localized['label'],
        'location' => $worst['region'],
        'area' => '',
        'text' => $en
            ? 'GeoSphere Austria currently warns of: ' . $warningType . '.'
            : 'GeoSphere Austria warnt aktuell vor: ' . $warningType . '.',
        'tips' => $localized['tips'],
    ];
}
