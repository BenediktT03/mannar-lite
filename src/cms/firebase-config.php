<?php
// Namespace muss die erste Anweisung nach dem PHP-Tag sein
namespace Mannar\CMS;

// Prevent direct access to this file
if (!defined('CMS_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Require Firebase PHP SDK via Composer
require_once __DIR__ . '/../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Storage;
use Kreait\Firebase\Exception\FirebaseException;

class FirebaseConfig {
    private static $instance = null;
    private $firebase;
    private $firestore;
    private $storage;
    private $auth;
    private $defaultBucket;
    private $config;

    // Private constructor for singleton pattern
    private function __construct() {
        $this->loadConfig();
        $this->initFirebase();
    }

    // Get singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Load configuration from JSON file
    private function loadConfig() {
        $configPath = __DIR__ . '/../config/firebase-config.json';
        
        if (!file_exists($configPath)) {
            throw new \Exception('Firebase-Konfigurationsdatei nicht gefunden: ' . $configPath);
        }
        
        $configJson = file_get_contents($configPath);
        $this->config = json_decode($configJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Fehler beim Parsen der Firebase-Konfiguration: ' . json_last_error_msg());
        }
        
        // Standard-Bucket aus Konfiguration laden
        $this->defaultBucket = $this->config['storage']['default_bucket'] ?? 'default-bucket';
    }

    // Initialize Firebase
    private function initFirebase() {
        $credentialsPath = __DIR__ . '/../config/firebase-credentials.json';
        
        if (!file_exists($credentialsPath)) {
            throw new \Exception('Firebase-Credentials nicht gefunden: ' . $credentialsPath);
        }
        
        try {
            $this->firebase = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->withDatabaseUri($this->config['database_url'] ?? '');
                
            // Dienste instanziieren
            $this->auth = $this->firebase->createAuth();
            $this->firestore = $this->firebase->createFirestore();
            $this->storage = $this->firebase->createStorage();
        } catch (FirebaseException $e) {
            throw new \Exception('Firebase-Initialisierungsfehler: ' . $e->getMessage());
        }
    }

    // Get Firebase Authentication instance
    public function getAuth() {
        return $this->auth;
    }

    // Get Firestore database instance
    public function getFirestore() {
        return $this->firestore;
    }

    // Get Firebase Storage instance
    public function getStorage() {
        return $this->storage;
    }

    // Get default bucket for storage
    public function getDefaultBucket() {
        return $this->defaultBucket;
    }

    // Get configuration value
    public function getConfig($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    // Check if services are initialized
    public function checkServices() {
        return [
            'auth' => $this->auth !== null,
            'firestore' => $this->firestore !== null,
            'storage' => $this->storage !== null
        ];
    }

    // Get error messages for uninitialized services
    public function getServiceErrors() {
        $errors = [];
        $services = $this->checkServices();
        
        foreach ($services as $service => $initialized) {
            if (!$initialized) {
                $errors[$service] = "Firebase-$service wurde nicht initialisiert.";
            }
        }
        
        return $errors;
    }

    // Get Firebase configuration for JavaScript
    public function getJsConfig() {
        return json_encode($this->config);
    }
}

// Helper functions for common Firebase operations

// Create or update a document in Firestore
function saveDocument($collection, $documentId, $data) {
    try {
        $firestore = FirebaseConfig::getInstance()->getFirestore();
        $docRef = $firestore->collection($collection)->document($documentId);
        $docRef->set($data, ['merge' => true]);
        return true;
    } catch (Exception $e) {
        error_log('Error saving document: ' . $e->getMessage());
        return false;
    }
}

// Get a document from Firestore
function getDocument($collection, $documentId) {
    try {
        $firestore = FirebaseConfig::getInstance()->getFirestore();
        $docRef = $firestore->collection($collection)->document($documentId);
        $snapshot = $docRef->snapshot();
        
        if ($snapshot->exists()) {
            return $snapshot->data();
        } else {
            return null;
        }
    } catch (Exception $e) {
        error_log('Error getting document: ' . $e->getMessage());
        return null;
    }
}

// Delete a document from Firestore
function deleteDocument($collection, $documentId) {
    try {
        $firestore = FirebaseConfig::getInstance()->getFirestore();
        $firestore->collection($collection)->document($documentId)->delete();
        return true;
    } catch (Exception $e) {
        error_log('Error deleting document: ' . $e->getMessage());
        return false;
    }
}

// Query documents from Firestore with conditions
function queryDocuments($collection, $conditions = []) {
    try {
        $firestore = FirebaseConfig::getInstance()->getFirestore();
        $query = $firestore->collection($collection);
        
        foreach ($conditions as $condition) {
            if (count($condition) === 3) {
                list($field, $operator, $value) = $condition;
                $query = $query->where($field, $operator, $value);
            }
        }
        
        $documents = [];
        $snapshot = $query->documents();
        
        foreach ($snapshot as $document) {
            if ($document->exists()) {
                $documents[$document->id()] = $document->data();
            }
        }
        
        return $documents;
    } catch (Exception $e) {
        error_log('Error querying documents: ' . $e->getMessage());
        return [];
    }
}

// Upload a file to Firebase Storage
function uploadFile($bucketPath, $localFilePath, $contentType = null) {
    try {
        $storage = FirebaseConfig::getInstance()->getStorage();
        $bucket = $storage->getBucket();
        
        $object = $bucket->upload(
            file_get_contents($localFilePath),
            [
                'name' => $bucketPath,
                'contentType' => $contentType
            ]
        );
        
        // Make file publicly accessible
        $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);
        
        return $object->signedUrl(new \DateTime('2099-01-01'));
    } catch (Exception $e) {
        error_log('Error uploading file: ' . $e->getMessage());
        return null;
    }
}

// Delete a file from Firebase Storage
function deleteFile($bucketPath) {
    try {
        $storage = FirebaseConfig::getInstance()->getStorage();
        $bucket = $storage->getBucket();
        $object = $bucket->object($bucketPath);
        
        if ($object->exists()) {
            $object->delete();
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Error deleting file: ' . $e->getMessage());
        return false;
    }
}
?>