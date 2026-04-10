<?php
/**
 * Datenbank-Konfiguration
 *
 * Unterstützt SQLite (lokal) und MariaDB/MySQL.
 * Zum Umschalten einfach DB_DRIVER auf 'mysql' setzen
 * und die MySQL-Zugangsdaten eintragen.
 */

// ── Treiber-Auswahl: 'sqlite' oder 'mysql' ──
define('DB_DRIVER', 'sqlite');

// ── SQLite-Konfiguration ──
define('DB_SQLITE_PATH', __DIR__ . '/../data/ffr.db');

// ── MariaDB/MySQL-Konfiguration (für spätere Verwendung) ──
define('DB_MYSQL_HOST', '127.0.0.1');
define('DB_MYSQL_PORT', '3306');
define('DB_MYSQL_NAME', 'ffr');
define('DB_MYSQL_USER', 'ffr_user');
define('DB_MYSQL_PASS', '');
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
            group_name VARCHAR(50) DEFAULT 'Mannschaft',
            photo VARCHAR(255) DEFAULT '',
            sort_order INT DEFAULT 0,
            active TINYINT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'einsatz',
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
            photo TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
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
    }

    // Standard-Admin erstellen falls nicht vorhanden
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin2024', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrator']);
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
