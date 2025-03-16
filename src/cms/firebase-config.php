 <?php
/**
 * Firebase Configuration for PHP CMS
 * This file handles the connection between PHP and Firebase services
 */

// Prevent direct access to this file
if (!defined('CMS_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Require Firebase PHP SDK via Composer
require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class FirebaseConfig {
    private static $instance = null;
    private $firebase;
    private $firestore;
    private $storage;
    private $auth;

    // Firebase project configuration
    private $config = [
        'apiKey' => 'AIzaSyAQszUApKHZ3lPrpc7HOINpdOWW3SgvUBM',
        'authDomain' => 'mannar-129a5.firebaseapp.com',
        'databaseURL' => 'https://YOUR_PROJECT_ID.firebaseio.com',
        'projectId' => 'mannar-129a5',
        'storageBucket' => 'mannar-129a5.firebasestorage.app',
        'messagingSenderId' => '687710492532',
        'appId' => '1:687710492532:web:c7b675da541271f8d83e21',
        'measurementId' => 'G-NXBLYJ5CXL'
    ];

    // Private constructor for singleton pattern
    private function __construct() {
        try {
            // Path to your service account JSON file
            $serviceAccountPath = __DIR__ . '/service-account.json';
            
            // Create Firebase instance
            $factory = (new Factory)
                ->withServiceAccount($serviceAccountPath)
                ->withDatabaseUri('https://' . $this->config['projectId'] . '.firebaseio.com');
            
            $this->firebase = $factory->createFirebase();
            
            // Initialize services
            $this->firestore = $this->firebase->firestore();
            $this->storage = $this->firebase->storage();
            $this->auth = $this->firebase->auth();
            
        } catch (Exception $e) {
            die('Firebase initialization error: ' . $e->getMessage());
        }
    }

    // Get singleton instance
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new FirebaseConfig();
        }
        return self::$instance;
    }

    // Get Firestore database instance
    public function getFirestore() {
        return $this->firestore;
    }

    // Get Firebase Storage instance
    public function getStorage() {
        return $this->storage;
    }

    // Get Firebase Authentication instance
    public function getAuth() {
        return $this->auth;
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