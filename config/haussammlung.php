<?php
/**
 * Hinweis-Popup zur Haussammlung (QR-Code), für einen im Admin
 * eingestellten Zeitraum - siehe Admin -> Haussammlung
 * (admin/haussammlung.php). Nutzt den generischen site_settings-Speicher,
 * keine eigene Tabelle nötig.
 *
 * Das Popup verlinkt auf eine eigene Informationsseite
 * (pages/haussammlung.php), die zwar wie jede Seite über die URL erreichbar
 * ist, aber bewusst nirgends in der Navigation verlinkt wird - nur das
 * Popup führt dorthin.
 */

require_once __DIR__ . '/settings.php';

function getHaussammlungSettings(): array {
    return [
        'enabled' => getSiteSetting('haussammlung_enabled', '0') === '1',
        'von' => getSiteSetting('haussammlung_von', ''),
        'bis' => getSiteSetting('haussammlung_bis', ''),
        'titel' => getSiteSetting('haussammlung_titel', 'Haussammlung 2026'),
        'kurztext' => getSiteSetting('haussammlung_kurztext', 'Scanne den QR-Code oder spende direkt - vielen Dank für deine Unterstützung!'),
        'infotext' => getSiteSetting('haussammlung_infotext', ''),
        'qr_image' => getSiteSetting('haussammlung_qr_image', ''),
    ];
}

function saveHaussammlungSettings(array $data): void {
    $map = [
        'enabled' => $data['enabled'] ? '1' : '0',
        'von' => $data['von'] ?? '',
        'bis' => $data['bis'] ?? '',
        'titel' => $data['titel'] ?? '',
        'kurztext' => $data['kurztext'] ?? '',
        'infotext' => $data['infotext'] ?? '',
    ];
    foreach ($map as $key => $value) {
        setSiteSetting('haussammlung_' . $key, $value);
    }
    if (isset($data['qr_image'])) {
        setSiteSetting('haussammlung_qr_image', $data['qr_image']);
    }
}

/**
 * Ob das Popup aktuell angezeigt werden soll: im Admin eingeschaltet und
 * heutiges Datum innerhalb des eingestellten Zeitraums (leeres Von/Bis
 * bedeutet "kein Start-/Enddatum", also offen in diese Richtung).
 */
function isHaussammlungActive(array $settings): bool {
    if (!$settings['enabled']) return false;
    $today = date('Y-m-d');
    if ($settings['von'] !== '' && $today < $settings['von']) return false;
    if ($settings['bis'] !== '' && $today > $settings['bis']) return false;
    return true;
}

/**
 * Ersetzt {von}/{bis}-Platzhalter im Kurztext durch die lesbar formatierten
 * Zeitraum-Daten, damit man den Zeitraum nicht doppelt pflegen muss.
 */
function formatHaussammlungText(string $text, array $settings): string {
    $von = $settings['von'] !== '' ? date('d.m.Y', strtotime($settings['von'])) : '';
    $bis = $settings['bis'] !== '' ? date('d.m.Y', strtotime($settings['bis'])) : '';
    return str_replace(['{von}', '{bis}'], [$von, $bis], $text);
}

/**
 * Formatiert den ausführlichen Infotext als HTML mit echten Absätzen und
 * Zwischenüberschriften, statt alles als einen einzigen nl2br-Textblock
 * ("Wurst") auszugeben. Leerzeilen trennen Absätze; eine einzelne kurze
 * Zeile ohne Satzzeichen am Ende (z.B. "Warum wir sammeln") wird dabei als
 * Zwischenüberschrift erkannt - eine einfache Heuristik, keine echte
 * Markdown-Auszeichnung, reicht aber für diesen Anwendungsfall.
 */
function renderHaussammlungInfotext(string $text): string {
    $blocks = preg_split('/\n\s*\n/', trim($text));
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $isHeading = strpos($block, "\n") === false
            && mb_strlen($block) <= 70
            && !preg_match('/[.,:]$/', $block);
        if ($isHeading) {
            $html .= '<h3>' . htmlspecialchars($block) . '</h3>';
        } else {
            $html .= '<p>' . nl2br(htmlspecialchars($block)) . '</p>';
        }
    }
    return $html;
}
