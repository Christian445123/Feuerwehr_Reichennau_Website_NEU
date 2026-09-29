<?php
/**
 * Automatische Verkleinerung/Komprimierung hochgeladener Bilder.
 *
 * Handyfotos sind oft 3000-6000px breit und mehrere MB groß - für die
 * Website (max. angezeigte Breite deutlich kleiner) ist das reiner
 * verschwendeter Speicherplatz. Wird von handleImageUpload() (siehe
 * config/database.php) nach jedem erfolgreichen Upload aufgerufen und
 * ersetzt die Datei durch eine verkleinerte/komprimierte Version.
 *
 * Läuft nur, wenn die GD-Erweiterung verfügbar ist - ohne GD bleibt die
 * Originaldatei unangetastet (Upload funktioniert also so oder so weiter,
 * nur eben ohne automatische Verkleinerung).
 */

const IMAGE_MAX_DIMENSION = 1920;
const IMAGE_JPEG_QUALITY = 82;
const IMAGE_WEBP_QUALITY = 82;
const IMAGE_PNG_COMPRESSION = 6;

function resizeAndCompressImage(string $path, string $mime): void {
    // Animierte GIFs würden beim Neuschreiben ihre Animation verlieren -
    // deshalb unangetastet lassen.
    if (!extension_loaded('gd') || $mime === 'image/gif') {
        return;
    }

    try {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        if (!$image) {
            return;
        }

        if ($mime === 'image/jpeg') {
            $image = applyExifOrientation($image, $path);
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > IMAGE_MAX_DIMENSION || $height > IMAGE_MAX_DIMENSION) {
            $ratio = min(IMAGE_MAX_DIMENSION / $width, IMAGE_MAX_DIMENSION / $height);
            $newWidth = max(1, (int) round($width * $ratio));
            $newHeight = max(1, (int) round($height * $ratio));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, IMAGE_JPEG_QUALITY),
            'image/png' => imagepng($image, $path, IMAGE_PNG_COMPRESSION),
            'image/webp' => imagewebp($image, $path, IMAGE_WEBP_QUALITY),
            default => null,
        };
        imagedestroy($image);
    } catch (\Throwable $e) {
        // Verkleinerung ist ein Bonus, kein Muss - bei Fehlern bleibt einfach
        // die Originaldatei stehen, der Upload selbst ist zu diesem Zeitpunkt
        // schon erfolgreich abgeschlossen.
        error_log('Bildkomprimierung fehlgeschlagen (' . $path . '): ' . $e->getMessage());
    }
}

/**
 * Handyfotos im Hochformat landen ohne das hier oft gedreht, weil die
 * Kamera das Bild liegend speichert und die Ausrichtung nur als
 * EXIF-Metadatum mitschickt.
 */
function applyExifOrientation($image, string $path) {
    if (!function_exists('exif_read_data')) {
        return $image;
    }
    $exif = @exif_read_data($path);
    if (!$exif || empty($exif['Orientation'])) {
        return $image;
    }
    $angle = match ((int) $exif['Orientation']) {
        3 => 180,
        6 => -90,
        8 => 90,
        default => 0,
    };
    if ($angle === 0) {
        return $image;
    }
    $rotated = imagerotate($image, $angle, 0);
    if ($rotated === false) {
        return $image;
    }
    imagedestroy($image);
    return $rotated;
}
