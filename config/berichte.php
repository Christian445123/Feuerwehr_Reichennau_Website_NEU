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
 * Website, ...), jeweils mit optionaler eigener Beschreibung (z.B. "Hier
 * der TT-Bericht zu diesem Einsatz"). Werden als JSON-Array in reports.links
 * gespeichert (siehe Migration 2026_09_29_add_report_links) und über
 * Admin -> Berichte mit einem "+"-Button gepflegt.
 *
 * Älteres Format (einfaches Array von URL-Strings, vor der Beschreibungs-
 * Funktion) wird weiterhin gelesen und automatisch in das neue Format
 * ['url' => ..., 'label' => ''] übersetzt.
 */
function getReportLinks(array $report): array {
    $raw = json_decode($report['links'] ?? '', true);
    if (!is_array($raw)) return [];
    $links = [];
    foreach ($raw as $item) {
        if (is_string($item)) {
            $url = trim($item);
            $label = '';
        } elseif (is_array($item)) {
            $url = trim($item['url'] ?? '');
            $label = trim($item['label'] ?? '');
        } else {
            continue;
        }
        if ($url === '') continue;
        $links[] = ['url' => $url, 'label' => $label];
    }
    return $links;
}

/**
 * Ob die Links eines Berichts öffentlich angezeigt werden (Admin -> Berichte
 * -> Checkbox "Links nicht öffentlich anzeigen"). Die Links bleiben dabei
 * gespeichert, erscheinen nur nicht auf der Website - praktisch, um einen
 * Link vorerst nur intern zu notieren.
 */
function areReportLinksVisible(array $report): bool {
    return empty($report['links_hidden']);
}

/**
 * Anzeigename eines Links: die eigene Beschreibung, falls gepflegt, sonst
 * die automatisch anhand der URL erkannte Standard-Beschriftung.
 */
function getReportLinkLabel(array $link): string {
    $label = trim($link['label'] ?? '');
    return $label !== '' ? $label : detectReportLinkIcon($link['url'])['label'];
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

/**
 * Maximale Anzahl gleichzeitig wählbarer Einsatz-/Übungsarten (Mehrfach-
 * auswahl) - z.B. ein Einsatz, der von Brand in ABC überging, oder ein
 * Bezirksübungstag mit Brand-, Technisch- und ABC-Teil. Kategorien ohne
 * Eintrag hier haben keine Unterkategorien und damit keine Begrenzung nötig.
 */
function getReportSubcategoryLimit(string $category): int {
    return ['einsatz' => 2, 'uebung' => 3][$category] ?? 99;
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
 * Eine Einsatz-/Übungsart-Spalte (reports.subcategory bzw. subcategory2)
 * enthält seit der Mehrfachauswahl kommagetrennt mehrere Werte (z.B.
 * "brand,technisch,abc" bei einem Bezirksübungstag). Diese Funktion liest
 * sie unabhängig davon, ob der Aufrufer schon ein Array oder noch den
 * rohen String aus der Datenbank hat.
 */
function getReportSubcategoryList($value): array {
    $list = is_array($value) ? $value : explode(',', (string) $value);
    return array_values(array_filter(array_map('trim', $list), fn($v) => $v !== ''));
}

/**
 * Beschriftung einer (ggf. mehrfachen) Einsatz-/Übungsart als lesbarer Text,
 * z.B. "Brand + ABC" - für Stellen, an denen kein Badge, sondern reiner
 * Text gebraucht wird (z.B. die Alarmierungen-Zeitleiste).
 */
function getReportSubcategoryLabelText($value, string $fallback = 'Einsatz'): string {
    $labels = getReportSubcategoryLabels();
    $parts = array_map(fn($s) => $labels[$s] ?? $s, getReportSubcategoryList($value));
    return $parts ? implode(' + ', $parts) : $fallback;
}

/**
 * Gültige Kategorie/Unterkategorie-Kombination zurückgeben; ungültige oder
 * zur Kategorie nicht passende Unterkategorien werden verworfen, mehrere
 * gültige bleiben als kommagetrennte Liste erhalten (Mehrfachauswahl, z.B.
 * eine Übung mit Brand- UND Technisch- UND ABC-Teil). Für die zweite
 * Kategorie ist eine leere Kategorie ("keine zweite Kategorie") erlaubt.
 */
function sanitizeReportCategory(string $category, array $subcategories, bool $allowEmpty = false): array {
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
    $valid = array_values(array_unique(array_intersect(getReportSubcategoryList($subcategories), $allowedSubcats)));
    $valid = array_slice($valid, 0, getReportSubcategoryLimit($category));
    return [$category, implode(',', $valid)];
}

/**
 * Badges (Klasse + Beschriftung) für eine Kategorie/Unterkategorie-
 * Kombination - bei mehreren Unterkategorien (Mehrfachauswahl) kommt je
 * Unterkategorie ein eigenes Badge zurück, sonst ein einzelnes Kategorie-
 * Badge. Gibt ein leeres Array zurück, wenn keine Kategorie gesetzt ist.
 */
function getReportBadges(string $category, $subcategory): array {
    if ($category === '') return [];
    $subs = getReportSubcategoryList($subcategory ?? '');
    $subLabels = getReportSubcategoryLabels();
    $subBadges = getReportSubcategoryBadges();
    $badges = [];
    foreach ($subs as $s) {
        if (isset($subLabels[$s])) {
            $badges[] = ['class' => $subBadges[$s], 'label' => $subLabels[$s]];
        }
    }
    if (!empty($badges)) return $badges;

    $catLabels = getReportCategories();
    $catBadges = getReportCategoryBadges();
    return [['class' => $catBadges[$category] ?? 'badge-sonstige', 'label' => $catLabels[$category] ?? ucfirst($category)]];
}

/**
 * Badges gruppiert nach Kategorie, mit der Kategorie als vorangestellter
 * Beschriftung - z.B. bei einem Bezirksübungstag mit Einsatz-Anteil:
 * "Einsatz: Brand" neben "Übung: Technisch, ABC". Ein Bericht hat höchstens
 * zwei Gruppen (erste + optionale zweite Kategorie).
 */
function getReportBadgeGroups(array $report): array {
    $groups = [];
    $catLabels = getReportCategories();
    foreach ([['category', 'subcategory'], ['category2', 'subcategory2']] as [$catKey, $subKey]) {
        $category = $report[$catKey] ?? '';
        if ($category === '') continue;
        $subs = getReportSubcategoryList($report[$subKey] ?? '');
        // Ohne gewählte Unterkategorie reicht die Kategorie-Beschriftung
        // allein ("Jugend") - sonst gäbe es ein doppeltes "Jugend: Jugend".
        $groups[] = [
            'label' => $catLabels[$category] ?? ucfirst($category),
            'badges' => $subs ? getReportBadges($category, $report[$subKey] ?? '') : [],
        ];
    }
    return $groups;
}
