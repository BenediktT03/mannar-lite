<?php
/**
 * Media Management Controller
 * 
 * Verwaltet die Medienübersicht, Uploads und Bildoptimierungen.
 * Bietet eine Benutzeroberfläche für die Medienverwaltung.
 */

namespace App\Controllers;

require_once __DIR__ . '/../Services/StorageHandler.php';
require_once __DIR__ . '/../Services/ImageOptimizer.php';

use App\Services\StorageHandler;
use App\Services\ImageOptimizer;

class MediaController
{
    private $storageHandler;
    private $imageOptimizer;
    private $allowedTypes;
    private $maxFileSize;

    /**
     * Konstruktor - Initialisiert die Medienkomponenten
     */
    public function __construct()
    {
        $this->storageHandler = new StorageHandler();
        $this->imageOptimizer = new ImageOptimizer();
        
        // Erlaubte Dateitypen definieren
        $this->allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'video/mp4', 'audio/mpeg'
        ];
        
        // Maximale Dateigröße in Bytes (20 MB)
        $this->maxFileSize = 20 * 1024 * 1024;
    }

    /**
     * Hauptansicht der Medienverwaltung
     * 
     * @return void
     */
    public function index()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('media_view')) {
            $_SESSION['error'] = 'Sie haben keine Berechtigung, auf die Medienverwaltung zuzugreifen.';
            header('Location: index.php');
            exit;
        }

        // Medien aus Firebase abrufen
        $mediaResult = $this->storageHandler->getAllMedia(100);
        $mediaItems = $mediaResult['success'] ? $mediaResult['media'] : [];
        
        // Nach Typ filtern, falls ein Filter gesetzt ist
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $filterType = $_GET['type'];
            $mediaResult = $this->storageHandler->filterMedia(['type' => $filterType]);
            $mediaItems = $mediaResult['success'] ? $mediaResult['media'] : [];
        }
        
        // An die View übergeben
        include __DIR__ . '/../Views/media/index.php';
    }

    /**
     * Datei-Upload-Handler
     * 
     * @return void
     */
    public function upload()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('media_upload')) {
            $this->jsonResponse(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }

        // Prüfen, ob eine Datei hochgeladen wurde
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $this->getUploadErrorMessage($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
            $this->jsonResponse(['success' => false, 'message' => $errorMessage]);
            exit;
        }

        $file = $_FILES['file'];
        
        // Dateigröße prüfen
        if ($file['size'] > $this->maxFileSize) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Die Datei ist zu groß. Maximale Größe: ' . ($this->maxFileSize / 1024 / 1024) . ' MB'
            ]);
            exit;
        }
        
        // MIME-Typ prüfen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Dieser Dateityp ist nicht erlaubt: ' . $mimeType
            ]);
            exit;
        }
        
        // Neuen Dateinamen erstellen
        $filename = $this->generateUniqueFilename($file['name']);
        $destination = 'media/' . date('Y/m/d') . '/' . $filename;
        
        // Bild optimieren, falls es sich um ein Bild handelt
        $optimizedPath = $file['tmp_name'];
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/gif') {
            $optimizedPath = $this->imageOptimizer->optimize($file['tmp_name'], $mimeType);
        }
        
        // Metadaten vorbereiten
        $metadata = [
            'originalName' => $file['name'],
            'optimized' => ($optimizedPath !== $file['tmp_name']),
            'userId' => $_SESSION['user_id'],
            'uploadedAt' => date('Y-m-d H:i:s')
        ];
        
        // In Firebase hochladen
        $result = $this->storageHandler->uploadFile($optimizedPath, $destination, $metadata);
        
        // Temporäre Dateien löschen
        if ($optimizedPath !== $file['tmp_name']) {
            @unlink($optimizedPath);
        }
        
        // Antwort senden
        $this->jsonResponse($result);
    }

    /**
     * Datei löschen
     * 
     * @return void
     */
    public function delete()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('media_delete')) {
            $this->jsonResponse(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        // Pfad prüfen
        if (!isset($_POST['path']) || empty($_POST['path'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Kein Dateipfad angegeben']);
            exit;
        }
        
        $path = $_POST['path'];
        $result = $this->storageHandler->deleteFile($path);
        
        $this->jsonResponse($result);
    }

    /**
     * Metadaten einer Datei abrufen
     * 
     * @return void
     */
    public function details()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('media_view')) {
            $this->jsonResponse(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        // Pfad prüfen
        if (!isset($_GET['path']) || empty($_GET['path'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Kein Dateipfad angegeben']);
            exit;
        }
        
        $path = $_GET['path'];
        $result = $this->storageHandler->getFileMetadata($path);
        
        $this->jsonResponse($result);
    }

    /**
     * Generiert einen eindeutigen Dateinamen
     * 
     * @param string $originalName Ursprünglicher Dateiname
     * @return string Eindeutiger Dateiname
     */
    private function generateUniqueFilename($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Umlaute und Sonderzeichen ersetzen
        $basename = $this->sanitizeFilename($basename);
        
        // Eindeutige ID hinzufügen
        $uniqueId = substr(md5(uniqid()), 0, 8);
        
        return $basename . '-' . $uniqueId . '.' . $extension;
    }

    /**
     * Bereinigt einen Dateinamen von Sonderzeichen
     * 
     * @param string $filename Dateiname
     * @return string Bereinigter Dateiname
     */
    private function sanitizeFilename($filename)
    {
        // Umlaute ersetzen
        $replace = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
            ' ' => '-', '&' => '-and-'
        ];
        
        $filename = str_replace(array_keys($replace), array_values($replace), $filename);
        
        // Alle nicht-alphanumerischen Zeichen durch - ersetzen
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '-', $filename);
        
        // Mehrfache Bindestriche durch einen ersetzen
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Führende und abschließende Bindestriche entfernen
        $filename = trim($filename, '-');
        
        return strtolower($filename);
    }

    /**
     * Fehlermeldung für Upload-Fehler generieren
     * 
     * @param int $errorCode Upload-Fehlercode
     * @return string Fehlermeldung
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Die Datei ist zu groß.';
            case UPLOAD_ERR_PARTIAL:
                return 'Die Datei wurde nur teilweise hochgeladen.';
            case UPLOAD_ERR_NO_FILE:
                return 'Es wurde keine Datei hochgeladen.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Temporärer Ordner fehlt.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Fehler beim Schreiben der Datei.';
            case UPLOAD_ERR_EXTENSION:
                return 'Eine PHP-Erweiterung hat den Upload gestoppt.';
            default:
                return 'Unbekannter Fehler beim Hochladen.';
        }
    }

    /**
     * Berechtigungen prüfen
     * 
     * @param string $permission Zu prüfende Berechtigung
     * @return bool Berechtigung vorhanden oder nicht
     */
    private function checkPermission($permission)
    {
        // Einfache Implementierung - sollte gegen Firestore-Rollen geprüft werden
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $role = $_SESSION['user_role'];
        
        // Administrator hat alle Rechte
        if ($role === 'admin') {
            return true;
        }
        
        // Berechtigungsmatrix
        $permissions = [
            'editor' => ['media_view', 'media_upload', 'media_delete'],
            'author' => ['media_view', 'media_upload'],
            'contributor' => ['media_view']
        ];
        
        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }

    /**
     * JSON-Antwort senden
     * 
     * @param array $data Zu sendende Daten
     * @return void
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Controller-Initialisierung und Routing
$mediaController = new MediaController();

// Routing basierend auf der Aktion
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'upload':
        $mediaController->upload();
        break;
    case 'delete':
        $mediaController->delete();
        break;
    case 'details':
        $mediaController->details();
        break;
    default:
        $mediaController->index();
        break;
}