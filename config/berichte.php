<?php
/**
 * Kalenderjahr-Einteilung für die Einsatzberichte (Aktuelles Jahr / Vorjahr /
 * Archiv). Wird über Admin -> Einstellungen verwaltet und in site_settings
 * gespeichert. Ohne gespeicherten Wert wird automatisch das echte
 * Kalenderjahr verwendet, damit die Einteilung auch ohne Adminaktion immer
 * aktuell bleibt.
 */

require_once __DIR__ . '/database.php';

function getBerichteAktuellesJahr(): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'berichte_aktuelles_jahr'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return ($value !== false && $value !== '') ? (int) $value : (int) date('Y');
}

function getBerichteVorjahr(): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM site_settings WHERE setting_key = 'berichte_vorjahr'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return ($value !== false && $value !== '') ? (int) $value : getBerichteAktuellesJahr() - 1;
}

function setBerichteJahre(int $aktuellesJahr, int $vorjahr): void {
    $db = getDB();
    $values = [
        'berichte_aktuelles_jahr' => (string) $aktuellesJahr,
        'berichte_vorjahr' => (string) $vorjahr,
    ];
    foreach ($values as $key => $value) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() > 0) {
            $db->prepare("UPDATE site_settings SET value = ? WHERE setting_key = ?")->execute([$value, $key]);
        } else {
            $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES (?, ?)")->execute([$key, $value]);
        }
    }
}

/**
 * Beliebig viele externe Links pro Bericht (Instagram, Facebook, externe
 * Website, ...). Werden als JSON-Array von URLs in reports.links
 * gespeichert (siehe Migration 2026_09_29_add_report_links) und über
 * Admin -> Berichte mit einem "+"-Button gepflegt.
 */
function getReportLinks(array $report): array {
    $links = json_decode($report['links'] ?? '', true);
    return is_array($links) ? array_values(array_filter($links, fn($l) => is_string($l) && trim($l) !== '')) : [];
}

/**
 * Icon + Beschriftung werden automatisch aus der URL erkannt, damit beim
 * Bearbeiten kein zusätzliches Icon-Auswahlfeld gepflegt werden muss.
 */
function detectReportLinkIcon(string $url): array {
    if (stripos($url, 'instagram.com') !== false) {
        return ['icon' => 'fab fa-instagram', 'label' => 'Auf Instagram ansehen'];
    }
    if (stripos($url, 'facebook.com') !== false) {
        return ['icon' => 'fab fa-facebook', 'label' => 'Auf Facebook ansehen'];
    }
    if (stripos($url, 'youtube.com') !== false || stripos($url, 'youtu.be') !== false) {
        return ['icon' => 'fab fa-youtube', 'label' => 'Auf YouTube ansehen'];
    }
    return ['icon' => 'fas fa-external-link-alt', 'label' => 'Weitere Informationen'];
}

/**
 * Kategorien/Unterkategorien für Berichte, zentral an einer Stelle, damit
 * Admin und öffentliche Seiten dieselben Labels/Icons/Badges verwenden.
 *
 * Jeder Bericht kann zwei Kategorien zugeordnet werden (category/subcategory
 * und category2/subcategory2), z.B. "Übung" + "Einsatz/Brand", falls während
 * einer Übung tatsächlich ein Einsatz stattfand. Beide sind gleichwertig.
 */
function getReportCategories(): array {
    return [
        'einsatz' => 'Einsatz', 'uebung' => 'Übung', 'jugend' => 'Jugend',
        'veranstaltungen' => 'Veranstaltung', 'sonstige' => 'Sonstiges',
    ];
}

function getReportCategoryIcons(): array {
    return [
        'einsatz' => 'fa-fire', 'uebung' => 'fa-dumbbell',
        'jugend' => 'fa-child', 'veranstaltungen' => 'fa-calendar-alt', 'sonstige' => 'fa-newspaper',
    ];
}

function getReportCategoryBadges(): array {
    return [
        'einsatz' => 'badge-brand', 'uebung' => 'badge-uebung',
        'jugend' => 'badge-jugend', 'veranstaltungen' => 'badge-veranstaltungen', 'sonstige' => 'badge-sonstige',
    ];
}

function getReportSubcategoriesByCategory(): array {
    return [
        'einsatz' => ['brand', 'technisch', 'abc', 'unterstuetzung', 'sonstiges'],
        'uebung' => ['brand', 'technisch', 'abc', 'sonstiges'],
    ];
}

function getReportSubcategoryLabels(): array {
    return [
        'brand' => 'Brand', 'technisch' => 'Technisch', 'abc' => 'ABC',
        'unterstuetzung' => 'Unterstützung', 'sonstiges' => 'Sonstiges',
    ];
}

function getReportSubcategoryBadges(): array {
    return [
        'brand' => 'badge-brand', 'technisch' => 'badge-technisch', 'abc' => 'badge-abc',
        'unterstuetzung' => 'badge-unterstuetzung', 'sonstiges' => 'badge-sonstige',
    ];
}

/**
 * Gültige Kategorie/Unterkategorie-Kombination zurückgeben; ungültige
 * Unterkategorien werden auf leer zurückgesetzt. Für die zweite Kategorie
 * ist eine leere Kategorie ("keine zweite Kategorie") erlaubt.
 */
function sanitizeReportCategory(string $category, string $subcategory, bool $allowEmpty = false): array {
    $validCats = array_keys(getReportCategories());
    if ($allowEmpty && $category === '') {
        return ['', ''];
    }
    if (!in_array($category, $validCats, true)) {
        $category = $allowEmpty ? '' : 'einsatz';
    }
    if ($category === '') {
        return ['', ''];
    }
    $allowedSubcats = getReportSubcategoriesByCategory()[$category] ?? [];
    if (!in_array($subcategory, $allowedSubcats, true)) {
        $subcategory = '';
    }
    return [$category, $subcategory];
}

/**
 * Badge-HTML (Klasse + Beschriftung) für eine Kategorie/Unterkategorie-
 * Kombination - die Unterkategorie wird bevorzugt angezeigt, falls
 * vorhanden, sonst die Kategorie selbst.
 */
function getReportBadgeInfo(string $category, ?string $subcategory): ?array {
    if ($category === '') return null;
    $subLabels = getReportSubcategoryLabels();
    $subBadges = getReportSubcategoryBadges();
    if ($subcategory && isset($subLabels[$subcategory])) {
        return ['class' => $subBadges[$subcategory], 'label' => $subLabels[$subcategory]];
    }
    $catLabels = getReportCategories();
    $catBadges = getReportCategoryBadges();
    return ['class' => $catBadges[$category] ?? 'badge-sonstige', 'label' => $catLabels[$category] ?? ucfirst($category)];
}
