<?php
/**
 * Instagram-Feed für die Startseite.
 *
 * Holt die letzten Beiträge über die Instagram API (Business-/Creator-Konto,
 * langlebiges Zugriffstoken aus Admin -> Einstellungen), speichert die Bilder
 * auf dem eigenen Server (uploads/instagram/) und legt die Beitragsliste als
 * JSON in site_settings ab. Besucher verbinden sich dadurch nie mit Meta.
 * Aktualisiert wird beim Seitenaufruf höchstens einmal pro Stunde; das Token
 * wird automatisch verlängert, sobald es älter als 30 Tage ist.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/hero.php'; // getHeroSetting / setHeroSetting

const INSTAGRAM_POST_COUNT = 6;
const INSTAGRAM_REFRESH_SECONDS = 3600;
const INSTAGRAM_TOKEN_RENEW_DAYS = 30;

function getInstagramToken(PDO $db): string {
    $stored = getHeroSetting($db, 'instagram_token', '');
    return $stored === '' ? '' : decryptSecret($stored);
}

function saveInstagramToken(PDO $db, string $token): void {
    setHeroSetting($db, 'instagram_token', $token === '' ? '' : encryptSecret($token));
    setHeroSetting($db, 'instagram_token_date', $token === '' ? '' : date('Y-m-d'));
    setHeroSetting($db, 'instagram_fetched_at', '0');
}

function instagramHttpGet(string $url): ?array {
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return null;
    $data = json_decode($body, true);
    return is_array($data) && !isset($data['error']) ? $data : null;
}

/** Zuletzt gespeicherte Beiträge: [['image' => ..., 'link' => ..., 'caption' => ...], ...] */
function getInstagramPosts(PDO $db): array {
    $list = json_decode(getHeroSetting($db, 'instagram_posts', '[]'), true);
    return is_array($list) ? $list : [];
}

/**
 * Lädt die Beiträge neu. Gibt eine Fehlermeldung zurück oder null bei Erfolg.
 */
function refreshInstagramFeed(PDO $db): ?string {
    $token = getInstagramToken($db);
    if ($token === '') return 'Kein Zugriffstoken hinterlegt.';

    // Token verlängern, wenn älter als 30 Tage
    $tokenDate = getHeroSetting($db, 'instagram_token_date', '');
    if ($tokenDate !== '' && strtotime($tokenDate) < strtotime('-' . INSTAGRAM_TOKEN_RENEW_DAYS . ' days')) {
        $renewed = instagramHttpGet('https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . urlencode($token));
        if ($renewed && !empty($renewed['access_token'])) {
            $token = $renewed['access_token'];
            setHeroSetting($db, 'instagram_token', encryptSecret($token));
            setHeroSetting($db, 'instagram_token_date', date('Y-m-d'));
        }
    }

    $fields = 'id,media_type,media_url,thumbnail_url,permalink,caption';
    $data = instagramHttpGet('https://graph.instagram.com/me/media?fields=' . $fields . '&limit=12&access_token=' . urlencode($token));
    setHeroSetting($db, 'instagram_fetched_at', (string) time());
    if (!$data || empty($data['data'])) {
        return 'Instagram hat keine Beiträge geliefert - Token prüfen.';
    }

    $dir = UPLOAD_PATH . 'instagram/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $posts = [];
    foreach ($data['data'] as $item) {
        if (count($posts) >= INSTAGRAM_POST_COUNT) break;
        $src = ($item['media_type'] ?? '') === 'VIDEO' ? ($item['thumbnail_url'] ?? '') : ($item['media_url'] ?? '');
        if ($src === '' || empty($item['permalink'])) continue;

        $file = 'ig_' . preg_replace('/[^0-9A-Za-z]/', '', $item['id']) . '.jpg';
        if (!file_exists($dir . $file)) {
            $img = @file_get_contents($src, false, stream_context_create(['http' => ['timeout' => 10]]));
            if ($img === false) continue;
            file_put_contents($dir . $file, $img);
        }
        $posts[] = [
            'image' => UPLOAD_URL . 'instagram/' . $file,
            'link' => $item['permalink'],
            'caption' => mb_substr((string) ($item['caption'] ?? ''), 0, 140),
        ];
    }
    if (!$posts) return 'Keine Bilder konnten geladen werden.';

    // Nicht mehr benötigte Bilder löschen
    $keep = array_map(fn($p) => basename($p['image']), $posts);
    foreach (glob($dir . 'ig_*.jpg') ?: [] as $f) {
        if (!in_array(basename($f), $keep, true)) @unlink($f);
    }

    setHeroSetting($db, 'instagram_posts', json_encode($posts));
    return null;
}

/**
 * Für die Startseite: liefert die Beiträge und aktualisiert sie bei Bedarf
 * (höchstens einmal pro Stunde).
 */
function getInstagramFeed(PDO $db): array {
    if (getHeroSetting($db, 'instagram_token', '') === '') return [];
    $fetchedAt = (int) getHeroSetting($db, 'instagram_fetched_at', '0');
    if (time() - $fetchedAt > INSTAGRAM_REFRESH_SECONDS) {
        refreshInstagramFeed($db);
    }
    return getInstagramPosts($db);
}
