 <?php
/**
 * Image Optimizer
 * 
 * Komprimiert und optimiert Bilder für eine bessere Performance.
 * Unterstützt WebP-Konvertierung und intelligente Größenanpassung.
 */

namespace App\Services;

class ImageOptimizer
{
    private $maxWidth;
    private $maxHeight;
    private $jpegQuality;
    private $pngCompression;
    private $webpQuality;
    private $convertToWebp;
    private $resizeImages;

    /**
     * Konstruktor - Initialisiert die Optimierungsparameter
     */
    public function __construct()
    {
        // Standardeinstellungen
        $this->maxWidth = 1920;
        $this->maxHeight = 1080;
        $this->jpegQuality = 85;
        $this->pngCompression = 9;
        $this->webpQuality = 80;
        $this->convertToWebp = true;
        $this->resizeImages = true;
        
        // Einstellungen aus Konfiguration laden
        $this->loadConfig();
    }

    /**
     * Konfiguration aus Firestore laden
     */
    private function loadConfig()
    {
        try {
            // Hier könnte eine Verbindung zu Firestore hergestellt werden,
            // um dynamische Einstellungen zu laden.
            // Für dieses Beispiel verwenden wir statische Werte.
        } catch (\Exception $e) {
            // Fehler beim Laden - Standardwerte beibehalten
        }
    }

    /**
     * Bild optimieren
     * 
     * @param string $filePath Pfad zum Eingabebild
     * @param string $mimeType MIME-Typ des Bildes
     * @return string Pfad zum optimierten Bild
     */
    public function optimize($filePath, $mimeType)
    {
        // Sicherstellen, dass wir ein Bild haben
        if (strpos($mimeType, 'image/') !== 0) {
            return $filePath;
        }
        
        // Prüfen, ob GD-Bibliothek verfügbar ist
        if (!extension_loaded('gd')) {
            return $filePath;
        }
        
        try {
            // Bild laden
            $image = $this->loadImage($filePath, $mimeType);
            
            if (!$image) {
                return $filePath;
            }
            
            // Originalgröße ermitteln
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            
            // Größenanpassung, falls erforderlich
            if ($this->resizeImages && ($originalWidth > $this->maxWidth || $originalHeight > $this->maxHeight)) {
                $image = $this->resizeImage($image, $originalWidth, $originalHeight);
            }
            
            // Temporäre Datei für das optimierte Bild erstellen
            $tempPath = tempnam(sys_get_temp_dir(), 'img_');
            
            // Als WebP speichern, falls aktiviert und unterstützt
            if ($this->convertToWebp && function_exists('imagewebp')) {
                $outputPath = $tempPath . '.webp';
                imagewebp($image, $outputPath, $this->webpQuality);
            } else {
                // Andernfalls im Originalformat speichern
                $outputPath = $tempPath . '.' . $this->getExtensionFromMimeType($mimeType);
                $this->saveImage($image, $outputPath, $mimeType);
            }
            
            // Ressourcen freigeben
            imagedestroy($image);
            
            // Temporäre Datei löschen
            @unlink($tempPath);
            
            return $outputPath;
        } catch (\Exception $e) {
            // Bei Fehlern das Originalbild zurückgeben
            return $filePath;
        }
    }

    /**
     * Bild laden basierend auf MIME-Typ
     * 
     * @param string $filePath Pfad zum Bild
     * @param string $mimeType MIME-Typ des Bildes
     * @return resource|false GD-Bildressource oder false bei Fehler
     */
    private function loadImage($filePath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($filePath);
                }
                break;
        }
        
        return false;
    }

    /**
     * Bild speichern basierend auf MIME-Typ
     * 
     * @param resource $image GD-Bildressource
     * @param string $outputPath Ausgabepfad
     * @param string $mimeType MIME-Typ des Bildes
     * @return bool Erfolg oder Misserfolg
     */
    private function saveImage($image, $outputPath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($image, $outputPath, $this->jpegQuality);
            case 'image/png':
                // PNG-Qualität: Kompression 0-9
                imagesavealpha($image, true);
                return imagepng($image, $outputPath, $this->pngCompression);
            case 'image/gif':
                return imagegif($image, $outputPath);
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    return imagewebp($image, $outputPath, $this->webpQuality);
                }
                break;
        }
        
        return false;
    }

    /**
     * Bild proportional verkleinern
     * 
     * @param resource $image GD-Bildressource
     * @param int $originalWidth Originalbreite
     * @param int $originalHeight Originalhöhe
     * @return resource Verkleinerte GD-Bildressource
     */
    private function resizeImage($image, $originalWidth, $originalHeight)
    {
        // Neues Größenverhältnis berechnen
        $ratio = min($this->maxWidth / $originalWidth, $this->maxHeight / $originalHeight);
        
        // Nur verkleinern, nicht vergrößern
        if ($ratio >= 1) {
            return $image;
        }
        
        // Neue Dimensionen berechnen
        $newWidth = round($originalWidth * $ratio);
        $newHeight = round($originalHeight * $ratio);
        
        // Neues Bild erstellen
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Transparenz erhalten (für PNG)
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        
        // Bild skalieren
        imagecopyresampled(
            $newImage, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight, $originalWidth, $originalHeight
        );
        
        // Originalbild freigeben
        imagedestroy($image);
        
        return $newImage;
    }

    /**
     * MIME-Typ in Dateierweiterung umwandeln
     * 
     * @param string $mimeType MIME-Typ
     * @return string Dateierweiterung
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $map[$mimeType] ?? 'jpg';
    }

    /**
     * Optimierungseinstellungen ändern
     * 
     * @param array $settings Neue Einstellungen
     * @return void
     */
    public function updateSettings(array $settings)
    {
        // Einstellungen aktualisieren, falls vorhanden
        if (isset($settings['maxWidth'])) {
            $this->maxWidth = (int)$settings['maxWidth'];
        }
        
        if (isset($settings['maxHeight'])) {
            $this->maxHeight = (int)$settings['maxHeight'];
        }
        
        if (isset($settings['jpegQuality'])) {
            $this->jpegQuality = min(100, max(0, (int)$settings['jpegQuality']));
        }
        
        if (isset($settings['pngCompression'])) {
            $this->pngCompression = min(9, max(0, (int)$settings['pngCompression']));
        }
        
        if (isset($settings['webpQuality'])) {
            $this->webpQuality = min(100, max(0, (int)$settings['webpQuality']));
        }
        
        if (isset($settings['convertToWebp'])) {
            $this->convertToWebp = (bool)$settings['convertToWebp'];
        }
        
        if (isset($settings['resizeImages'])) {
            $this->resizeImages = (bool)$settings['resizeImages'];
        }
    }

    /**
     * Aktuelle Einstellungen abrufen
     * 
     * @return array Aktuelle Einstellungen
     */
    public function getSettings()
    {
        return [
            'maxWidth' => $this->maxWidth,
            'maxHeight' => $this->maxHeight,
            'jpegQuality' => $this->jpegQuality,
            'pngCompression' => $this->pngCompression,
            'webpQuality' => $this->webpQuality,
            'convertToWebp' => $this->convertToWebp,
            'resizeImages' => $this->resizeImages
        ];
    }
}