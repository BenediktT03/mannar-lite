<?php
/**
 * WYSIWYG Editor Controller
 * 
 * Controller für den TinyMCE-Editor mit Medien-Upload und Inhaltsverwaltung.
 * Ermöglicht das Erstellen und Bearbeiten von Inhalten im CMS.
 */

namespace App\Controllers;

require_once __DIR__ . '/../Config/FirebaseConfig.php';

use App\Config\FirebaseConfig;

class EditorController
{
    private $firebase;
    private $firestore;
    private $storage;
    private $auth;
    private $postId;
    private $postType;
    private $postData;

    /**
     * Konstruktor - Initialisiert die Firebase-Verbindung
     */
    public function __construct()
    {
        $this->firebase = FirebaseConfig::getInstance();
        $this->firestore = $this->firebase->getFirestore();
        $this->storage = $this->firebase->getStorage();
        $this->auth = $this->firebase->getAuth();
        
        // Post-ID und Typ aus den Anfrageparametern ermitteln
        $this->postId = $_GET['id'] ?? null;
        $this->postType = $_GET['type'] ?? 'post';
        
        // Vorhandenen Post laden, falls eine ID angegeben wurde
        if ($this->postId) {
            $this->loadPost();
        }
    }

    /**
     * Hauptansicht des Editors anzeigen
     * 
     * @return void
     */
    public function index()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('content_edit')) {
            $_SESSION['error'] = 'Sie haben keine Berechtigung, Inhalte zu bearbeiten.';
            header('Location: index.php');
            exit;
        }

        // Kategorien laden
        $categories = $this->getCategories();
        
        // Tags laden
        $tags = $this->getTags();
        
        // TinyMCE-Konfiguration
        $editorConfig = $this->getEditorConfig();
        
        // An die View übergeben
        include __DIR__ . '/../Views/editor/index.php';
    }

    /**
     * Inhalt speichern
     * 
     * @return void
     */
    public function save()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('content_edit')) {
            $this->jsonResponse(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        // POST-Daten prüfen
        if (!isset($_POST['title']) || !isset($_POST['content'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Fehlende Pflichtfelder']);
            exit;
        }
        
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        $excerpt = $_POST['excerpt'] ?? '';
        $categoryIds = $_POST['categories'] ?? [];
        $tagIds = $_POST['tags'] ?? [];
        $status = $_POST['status'] ?? 'draft';
        $slug = $_POST['slug'] ?? $this->generateSlug($title);
        $featuredImage = $_POST['featured_image'] ?? '';
        $seoTitle = $_POST['seo_title'] ?? $title;
        $seoDescription = $_POST['seo_description'] ?? '';
        $seoKeywords = $_POST['seo_keywords'] ?? '';
        
        // Daten für Firestore vorbereiten
        $postData = [
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
            'status' => $status,
            'slug' => $slug,
            'featured_image' => $featuredImage,
            'seo' => [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'keywords' => $seoKeywords
            ],
            'author_id' => $_SESSION['user_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Neuen Post erstellen oder vorhandenen aktualisieren
        try {
            if ($this->postId) {
                // Vorherige Version speichern
                $this->saveVersion();
                
                // Post aktualisieren
                $postRef = $this->firestore->collection($this->postType . 's')->document($this->postId);
                $postRef->set($postData, ['merge' => true]);
                
                $result = [
                    'success' => true,
                    'message' => ucfirst($this->postType) . ' erfolgreich aktualisiert',
                    'id' => $this->postId
                ];
            } else {
                // Erstellungsdatum für neuen Post
                $postData['created_at'] = date('Y-m-d H:i:s');
                
                // Neuen Post erstellen
                $postRef = $this->firestore->collection($this->postType . 's')->newDocument();
                $postRef->set($postData);
                
                $result = [
                    'success' => true,
                    'message' => ucfirst($this->postType) . ' erfolgreich erstellt',
                    'id' => $postRef->id()
                ];
            }
            
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Fehler beim Speichern: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Vorschau generieren
     * 
     * @return void
     */
    public function preview()
    {
        // Berechtigungsprüfung
        if (!isset($_SESSION['user_id']) || !$this->checkPermission('content_view')) {
            $this->jsonResponse(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        // POST-Daten holen
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $featuredImage = $_POST['featured_image'] ?? '';
        
        // Vorschau-ID generieren
        $previewId = md5(uniqid());
        
        // Vorschaudaten in Firestore speichern
        $previewData = [
            'title' => $title,
            'content' => $content,
            'featured_image' => $featuredImage,
            'user_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600) // 1 Stunde gültig
        ];
        
        try {
            $previewRef = $this->firestore->collection('previews')->document($previewId);
            $previewRef->set($previewData);
            
            $previewUrl = 'preview.php?id=' . $previewId;
            
            $this->jsonResponse([
                'success' => true,
                'preview_id' => $previewId,
                'preview_url' => $previewUrl
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Fehler beim Erstellen der Vorschau: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Medien-Upload für den Editor
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
            $this->jsonResponse(['success' => false, 'message' => 'Keine Datei hochgeladen']);
            exit;
        }
        
        $file = $_FILES['file'];
        
        // Dateityp prüfen
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $this->jsonResponse(['success' => false, 'message' => 'Dateityp nicht erlaubt']);
            exit;
        }
        
        // Eindeutigen Dateinamen generieren
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = 'editor/' . date('Y/m/d') . '/' . $filename;
        
        try {
            // Storage-Bucket holen
            $bucket = $this->storage->getBucket($this->firebase->getDefaultBucket());
            
            // Datei hochladen
            $object = $bucket->upload(
                fopen($file['tmp_name'], 'r'),
                [
                    'name' => $destination,
                    'metadata' => [
                        'contentType' => $mimeType,
                        'uploadedBy' => $_SESSION['user_id'],
                        'uploadedAt' => date('Y-m-d H:i:s')
                    ]
                ]
            );
            
            // Öffentliche URL erhalten
            $url = $object->signedUrl(new \DateTime('+1000 years'));
            
            // Erfolg zurückmelden
            $this->jsonResponse([
                'success' => true,
                'file' => [
                    'url' => $url,
                    'title' => $file['name'],
                    'path' => $destination
                ]
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Fehler beim Hochladen: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Vorhandenen Post laden
     * 
     * @return void
     */
    private function loadPost()
    {
        try {
            $postRef = $this->firestore->collection($this->postType . 's')->document($this->postId);
            $snapshot = $postRef->snapshot();
            
            if ($snapshot->exists()) {
                $this->postData = $snapshot->data();
            } else {
                $this->postData = null;
                $this->postId = null;
            }
        } catch (\Exception $e) {
            $this->postData = null;
        }
    }

    /**
     * Kategorien abrufen
     * 
     * @return array Liste der Kategorien
     */
    private function getCategories()
    {
        try {
            $categories = [];
            $categoryRef = $this->firestore->collection('categories');
            $documents = $categoryRef->documents();
            
            foreach ($documents as $document) {
                $categories[] = [
                    'id' => $document->id(),
                    'name' => $document->data()['name'] ?? '',
                    'parent_id' => $document->data()['parent_id'] ?? null
                ];
            }
            
            // Nach Hierarchie sortieren
            $categories = $this->buildCategoryTree($categories);
            
            return $categories;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Kategoriebaum aufbauen
     * 
     * @param array $categories Liste aller Kategorien
     * @param string|null $parentId Elternkategorie-ID
     * @param int $level Hierarchieebene
     * @return array Hierarchischer Kategoriebaum
     */
    private function buildCategoryTree($categories, $parentId = null, $level = 0)
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] === $parentId) {
                $category['level'] = $level;
                $category['children'] = $this->buildCategoryTree($categories, $category['id'], $level + 1);
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    /**
     * Tags abrufen
     * 
     * @return array Liste der Tags
     */
    private function getTags()
    {
        try {
            $tags = [];
            $tagRef = $this->firestore->collection('tags');
            $documents = $tagRef->documents();
            
            foreach ($documents as $document) {
                $tags[] = [
                    'id' => $document->id(),
                    'name' => $document->data()['name'] ?? ''
                ];
            }
            
            return $tags;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * TinyMCE-Konfiguration erstellen
     * 
     * @return array Editor-Konfiguration
     */
    private function getEditorConfig()
    {
        return [
            'selector' => '#content',
            'height' => 500,
            'menubar' => true,
            'plugins' => [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            'toolbar' => 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | removeformat | help',
            'images_upload_url' => 'editor.php?action=upload',
            'automatic_uploads' => true,
            'images_reuse_filename' => true,
            'relative_urls' => false,
            'remove_script_host' => false,
            'convert_urls' => false
        ];
    }

    /**
     * Slug aus Titel generieren
     * 
     * @param string $title Titel
     * @return string URL-freundlicher Slug
     */
    private function generateSlug($title)
    {
        // Umlaute und Sonderzeichen ersetzen
        $replacements = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
            ' ' => '-', '&' => '-and-'
        ];
        
        $slug = str_replace(array_keys($replacements), array_values($replacements), $title);
        
        // Nur alphanumerische Zeichen und Bindestriche erlauben
        $slug = preg_replace('/[^a-zA-Z0-9-]/', '', $slug);
        
        // Mehrfache Bindestriche durch einen ersetzen
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Führende und abschließende Bindestriche entfernen
        $slug = trim($slug, '-');
        
        // Kleinbuchstaben
        $slug = strtolower($slug);
        
        return $slug;
    }

    /**
     * Vorherige Version speichern
     * 
     * @return void
     */
    private function saveVersion()
    {
        if (!$this->postData) {
            return;
        }
        
        try {
            $versionData = $this->postData;
            $versionData['post_id'] = $this->postId;
            $versionData['post_type'] = $this->postType;
            $versionData['version_date'] = date('Y-m-d H:i:s');
            $versionData['saved_by'] = $_SESSION['user_id'];
            
            $versionRef = $this->firestore->collection('versions')->newDocument();
            $versionRef->set($versionData);
        } catch (\Exception $e) {
            // Versionierung fehlgeschlagen, aber Fehler ignorieren
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
            'editor' => ['content_view', 'content_edit', 'content_delete', 'media_view', 'media_upload'],
            'author' => ['content_view', 'content_edit', 'media_view', 'media_upload'],
            'contributor' => ['content_view', 'content_edit_own', 'media_view']
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
$editorController = new EditorController();

// Routing basierend auf der Aktion
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'save':
        $editorController->save();
        break;
    case 'preview':
        $editorController->preview();
        break;
    case 'upload':
        $editorController->upload();
        break;
    default:
        $editorController->index();
        break;
}