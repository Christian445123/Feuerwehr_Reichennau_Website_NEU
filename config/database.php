<?php
/**
 * Datenbank-Konfiguration
 *
 * Unterstützt SQLite (lokal) und MariaDB/MySQL.
 * Zum Umschalten einfach DB_DRIVER auf 'mysql' setzen
 * und die MySQL-Zugangsdaten eintragen.
 */

require_once __DIR__ . '/env.php';

// ── Treiber-Auswahl: 'sqlite' oder 'mysql' (aus .env, Fallback: sqlite) ──
define('DB_DRIVER', env('DB_DRIVER', 'sqlite'));

// ── SQLite-Konfiguration ──
define('DB_SQLITE_PATH', __DIR__ . '/../data/ffr.db');

// ── MariaDB/MySQL-Konfiguration (aus .env) ──
define('DB_MYSQL_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_MYSQL_PORT', env('DB_PORT', '3306'));
define('DB_MYSQL_NAME', env('DB_NAME', 'ffr'));
define('DB_MYSQL_USER', env('DB_USER', 'ffr_user'));
define('DB_MYSQL_PASS', env('DB_PASSWORD', ''));
define('DB_MYSQL_CHARSET', 'utf8mb4');

// ── Upload-Konfiguration ──
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

// ── Singleton DB-Verbindung ──
function getDB(): PDO {
    static $db = null;
    if ($db !== null) {
        return $db;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_MYSQL_HOST, DB_MYSQL_PORT, DB_MYSQL_NAME, DB_MYSQL_CHARSET
        );
        $db = new PDO($dsn, DB_MYSQL_USER, DB_MYSQL_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } else {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $db = new PDO('sqlite:' . DB_SQLITE_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');
    }

    return $db;
}

/**
 * Datenbank-Tabellen erstellen
 */
function initDatabase(): void {
    $db = getDB();

    if (DB_DRIVER === 'mysql') {
        // ── MariaDB / MySQL ──
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(200) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(100) NOT NULL,
            lastname VARCHAR(100) NOT NULL,
            `rank` VARCHAR(50) DEFAULT '',
            `function` VARCHAR(100) DEFAULT '',
            functions TEXT DEFAULT NULL,
            group_name VARCHAR(50) DEFAULT 'Mannschaft',
            extra_groups VARCHAR(255) DEFAULT '',
            photo VARCHAR(255) DEFAULT '',
            sort_order INT DEFAULT 0,
            active TINYINT DEFAULT 1,
            entry_date VARCHAR(20) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            email VARCHAR(150) DEFAULT '',
            bio TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'einsatz',
            subcategory VARCHAR(50) DEFAULT NULL,
            content TEXT DEFAULT NULL,
            date DATE NOT NULL,
            author VARCHAR(100) DEFAULT '',
            published TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS report_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            caption VARCHAR(255) DEFAULT '',
            sort_order INT DEFAULT 0,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS ranks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            abbr VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(150) NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'Mannschaft',
            sort_order INT DEFAULT 0,
            badge VARCHAR(255) DEFAULT '',
            active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    } else {
        // ── SQLite ──
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            firstname TEXT NOT NULL,
            lastname TEXT NOT NULL,
            rank TEXT DEFAULT '',
            function TEXT DEFAULT '',
            group_name TEXT DEFAULT 'Mannschaft',
            extra_groups TEXT DEFAULT '',
            photo TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            entry_date TEXT DEFAULT '',
            phone TEXT DEFAULT '',
            email TEXT DEFAULT '',
            bio TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'einsatz',
            content TEXT DEFAULT '',
            date TEXT NOT NULL,
            author TEXT DEFAULT '',
            published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS report_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report_id INTEGER NOT NULL,
            filename TEXT NOT NULL,
            caption TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS ranks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            abbr TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'Mannschaft',
            sort_order INTEGER DEFAULT 0,
            badge TEXT DEFAULT '',
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            value TEXT
        )");
    }

    // Standard-Admin erstellen falls nicht vorhanden
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin2024', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrator']);
    }

    // Standard-Ränge einfügen falls leer
    seedDefaultRanks($db);

    // Standard-Zugangspasswort für die Seitensperre setzen, falls noch keines existiert
    $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
    $stmt->execute(['site_password']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('reichenau2026', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, value) VALUES ('site_password', ?)");
        $stmt->execute([$hash]);
    }
}

/**
 * Standard-Dienstgrade einfügen (nur wenn Tabelle leer)
 */
function seedDefaultRanks(PDO $db): void {
    $count = $db->query("SELECT COUNT(*) FROM ranks")->fetchColumn();
    if ($count > 0) return;

    $defaults = [
        ['JFM',    'Jugendfeuerwehrmann',              'Jugend',           5],
        ['PFM',    'Probefeuerwehrmann',                'Mannschaft',      10],
        ['FM',     'Feuerwehrmann',                     'Mannschaft',      20],
        ['OFM',    'Oberfeuerwehrmann',                 'Mannschaft',      30],
        ['HFM',    'Hauptfeuerwehrmann',                'Mannschaft',      40],
        ['LM',     'Löschmeister',                      'Chargen',         50],
        ['OLM',    'Oberlöschmeister',                  'Chargen',         60],
        ['HLM',    'Hauptlöschmeister',                 'Chargen',         70],
        ['BM',     'Brandmeister',                      'Chargen',         80],
        ['OBM',    'Oberbrandmeister',                  'Chargen',         90],
        ['HBM',    'Hauptbrandmeister',                 'Chargen',        100],
        ['V',      'Verwalter',                         'Verwaltung',     110],
        ['OV',     'Oberverwalter',                     'Verwaltung',     120],
        ['HV',     'Hauptverwalter',                    'Verwaltung',     130],
        ['BI',     'Brandinspektor',                    'Offiziere',      140],
        ['OBI',    'Oberbrandinspektor',                'Offiziere',      150],
        ['HBI',    'Hauptbrandinspektor',               'Offiziere',      160],
        ['ABI',    'Abschnittsbrandinspektor',          'Höhere Offiziere', 170],
        ['BR',     'Brandrat',                          'Höhere Offiziere', 180],
        ['OBR',    'Oberbrandrat',                      'Höhere Offiziere', 190],
        ['LBD-STV','Landesbranddirektor-Stellvertreter','Landes',         195],
        ['LBD',    'Landesbranddirektor',               'Landes',         198],
        ['FARZT',  'Feuerwehrarzt',                     'Stab',           200],
        ['FKUR',   'Feuerwehrkurat',                    'Stab',           210],
        ['EBI',    'Ehrenbrandinspektor',               'Ehren',          300],
        ['EOBI',   'Ehrenoberbrandinspektor',           'Ehren',          310],
        ['EHBI',   'Ehrenhauptbrandinspektor',          'Ehren',          320],
        ['ELM',    'Ehrenlöschmeister',                 'Ehren',          330],
        ['EOLM',   'Ehrenoberlöschmeister',             'Ehren',          340],
        ['EHV',    'Ehrenhauptverwalter',               'Ehren',          350],
    ];

    $stmt = $db->prepare("INSERT INTO ranks (abbr, name, category, sort_order, badge) VALUES (?, ?, ?, ?, ?)");
    foreach ($defaults as $r) {
        $badge = 'assets/images/ranks/' . strtolower($r[0]) . '.png';
        $stmt->execute([$r[0], $r[1], $r[2], $r[3], $badge]);
    }
}

// ── Upload-Verzeichnisse erstellen ──
function ensureUploadDirs(): void {
    $dirs = [
        UPLOAD_PATH,
        UPLOAD_PATH . 'reports/',
        UPLOAD_PATH . 'members/',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Bild-Upload verarbeiten
 */
function handleImageUpload(array $file, string $subdir): ?string {
    ensureUploadDirs();

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
        return null;
    }

    // MIME-Type prüfen mit finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = UPLOAD_PATH . $subdir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $subdir . '/' . $filename;
    }

    return null;
}

// Datenbank beim ersten Laden initialisieren
initDatabase();
ensureUploadDirs();
