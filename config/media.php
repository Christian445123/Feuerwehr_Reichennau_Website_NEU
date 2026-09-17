<?php
/**
 * Austauschbare Fahrzeug- und Wache-Fotos (Admin -> Fahrzeug- & Wache-Fotos).
 *
 * Fahrzeuge haben je eine Foto-Galerie (erstes Foto = Hauptbild, Rest =
 * Vorschaubilder), feste Einzelbilder (z.B. die Wache-Fotos) sind über einen
 * festen Slot-Schlüssel mit unveränderlichem Label ansprechbar.
 */

require_once __DIR__ . '/database.php';

function getVehicleLabels(): array {
    return [
        'tf' => 'Transportfahrzeug – TF',
        'tlfh' => 'Tanklöschfahrzeug – TLFH',
        'klf_a' => 'Kleinlöschfahrzeug Allrad – KLF-A',
        'last1' => 'Transportfahrzeug – LAST1',
        'ggf' => 'Gefahrgutfahrzeug – GGF',
        'grosspumpe' => 'Großpumpenhänger',
        'anhaenger' => 'Anhänger leicht',
    ];
}

/**
 * Alle Fotos eines Fahrzeugs, sortiert (erstes = Hauptbild).
 */
function getVehiclePhotos(PDO $db, string $vehicleKey): array {
    $stmt = $db->prepare("SELECT * FROM vehicle_photos WHERE vehicle_key = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$vehicleKey]);
    return $stmt->fetchAll();
}

/**
 * Fotos aller Fahrzeuge auf einmal (für die Ausrüstungsseite), gruppiert
 * nach vehicle_key.
 */
function getAllVehiclePhotos(PDO $db): array {
    $rows = $db->query("SELECT * FROM vehicle_photos ORDER BY vehicle_key, sort_order ASC, id ASC")->fetchAll();
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['vehicle_key']][] = $row;
    }
    return $grouped;
}

function addVehiclePhoto(PDO $db, string $vehicleKey, string $photoPath): void {
    $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM vehicle_photos WHERE vehicle_key = ?");
    $maxSort->execute([$vehicleKey]);
    $nextSort = ((int) $maxSort->fetchColumn()) + 1;
    $stmt = $db->prepare("INSERT INTO vehicle_photos (vehicle_key, photo_path, sort_order) VALUES (?,?,?)");
    $stmt->execute([$vehicleKey, $photoPath, $nextSort]);
}

/**
 * Löscht ein Fahrzeugfoto (DB-Eintrag + Datei, falls sie im uploads/-Ordner
 * liegt - mitgelieferte Standardfotos unter assets/images/ werden nicht
 * gelöscht, nur der Datenbank-Eintrag).
 */
function deleteVehiclePhoto(PDO $db, int $photoId): void {
    $stmt = $db->prepare("SELECT photo_path FROM vehicle_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $path = $stmt->fetchColumn();
    if ($path && str_starts_with($path, 'uploads/')) {
        $fullPath = __DIR__ . '/../' . $path;
        if (file_exists($fullPath)) unlink($fullPath);
    }
    $db->prepare("DELETE FROM vehicle_photos WHERE id = ?")->execute([$photoId]);
}

/**
 * Aktuelles Foto eines festen Slots (z.B. 'wache_umkleide1').
 */
function getMediaSlot(PDO $db, string $slotKey): ?array {
    $stmt = $db->prepare("SELECT * FROM media_slots WHERE slot_key = ?");
    $stmt->execute([$slotKey]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getAllMediaSlots(PDO $db): array {
    return $db->query("SELECT * FROM media_slots ORDER BY id ASC")->fetchAll();
}

function setMediaSlotPhoto(PDO $db, string $slotKey, string $photoPath): void {
    $stmt = $db->prepare("SELECT photo_path FROM media_slots WHERE slot_key = ?");
    $stmt->execute([$slotKey]);
    $oldPath = $stmt->fetchColumn();
    if ($oldPath && str_starts_with($oldPath, 'uploads/')) {
        $fullPath = __DIR__ . '/../' . $oldPath;
        if (file_exists($fullPath)) unlink($fullPath);
    }
    $db->prepare("UPDATE media_slots SET photo_path = ? WHERE slot_key = ?")->execute([$photoPath, $slotKey]);
}
