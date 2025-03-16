 <?php
/**
 * Firebase Storage Handler
 * 
 * Diese Klasse stellt eine Schnittstelle zum Firebase Storage dar und verwaltet
 * alle Dateioperationen für das CMS, einschließlich Uploads, Abrufen und Löschen
 * von Dateien. Außerdem werden Metadaten in Firestore gespeichert.
 */

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;
use Kreait\Firebase\Exception\FirebaseException;
use Google\Cloud\Storage\StorageClient;

class StorageHandler
{
    private $firebase;
    private $storage;
    private $firestore;
    private $bucket;
    private $defaultBucket;

    /**
     * Konstruktor - Initialisiert die Firebase-Verbindung
     */
    public function __construct()
    {
        // Firebase initialisieren
        $this->firebase = (new Factory)
            ->withServiceAccount(__DIR__ . '/../config/firebase-credentials.json');

        // Storage-Instanz erhalten
        $this->storage = $this->firebase->createStorage();
        $this->firestore = $this->firebase->createFirestore();
        
        // Standard-Bucket festlegen
        $this->defaultBucket = config('firebase.storage.default_bucket', 'default-bucket');
        $this->bucket = $this->storage->getBucket($this->defaultBucket);
    }

    /**
     * Datei in Firebase Storage hochladen
     * 
     * @param string $filePath Lokaler Dateipfad
     * @param string $destination Zielort im Storage
     * @param array $metadata Zusätzliche Metadaten
     * @return array Array mit Datei-URL und Metadaten
     */
    public function uploadFile($filePath, $destination, $metadata = [])
    {
        try {
            // Prüfen, ob die Datei existiert
            if (!file_exists($filePath)) {
                throw new \Exception("Datei existiert nicht: $filePath");
            }

            // MIME-Typ ermitteln
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $filePath);
            finfo_close($fileInfo);

            // Standardmetadaten definieren
            $defaultMetadata = [
                'contentType' => $mimeType,
                'cacheControl' => 'public, max-age=31536000',
                'uploadedBy' => $_SESSION['user_id'] ?? 'system',
                'uploadedAt' => date('Y-m-d H:i:s')
            ];

            // Metadaten kombinieren
            $finalMetadata = array_merge($defaultMetadata, $metadata);

            // Datei hochladen
            $object = $this->bucket->upload(
                fopen($filePath, 'r'),
                [
                    'name' => $destination,
                    'metadata' => $finalMetadata
                ]
            );

            // Öffentliche URL erhalten
            $url = $this->bucket->object($destination)->signedUrl(new \DateTime('+1000 years'));

            // Metadaten in Firestore speichern
            $mediaCollection = $this->firestore->collection('media');
            $mediaDocument = $mediaCollection->document();
            
            $firestoreData = [
                'name' => basename($destination),
                'path' => $destination,
                'url' => $url,
                'size' => filesize($filePath),
                'type' => $mimeType,
                'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
                'metadata' => $finalMetadata,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $mediaDocument->set($firestoreData);

            return [
                'success' => true,
                'url' => $url,
                'path' => $destination,
                'id' => $mediaDocument->id(),
                'metadata' => $firestoreData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Datei aus Firebase Storage löschen
     * 
     * @param string $path Pfad der Datei im Storage
     * @return bool Erfolgsstatus
     */
    public function deleteFile($path)
    {
        try {
            // Datei im Storage löschen
            $this->bucket->object($path)->delete();
            
            // Metadaten in Firestore löschen
            $mediaCollection = $this->firestore->collection('media');
            $query = $mediaCollection->where('path', '=', $path);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                $document->reference()->delete();
            }
            
            return [
                'success' => true,
                'message' => "Datei $path erfolgreich gelöscht"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Dateimetadaten aus Firestore abrufen
     * 
     * @param string $path Pfad der Datei im Storage
     * @return array Metadaten der Datei
     */
    public function getFileMetadata($path)
    {
        try {
            $mediaCollection = $this->firestore->collection('media');
            $query = $mediaCollection->where('path', '=', $path);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                return [
                    'success' => true,
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Datei nicht gefunden',
                'code' => 404
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Alle Mediendateien abrufen
     * 
     * @param int $limit Maximale Anzahl der Ergebnisse
     * @param string $orderBy Sortierfeld
     * @param string $direction Sortierrichtung (asc/desc)
     * @return array Liste der Mediendateien
     */
    public function getAllMedia($limit = 100, $orderBy = 'created_at', $direction = 'desc')
    {
        try {
            $mediaCollection = $this->firestore->collection('media');
            $query = $mediaCollection->orderBy($orderBy, $direction)->limit($limit);
            $documents = $query->documents();
            
            $media = [];
            foreach ($documents as $document) {
                $media[] = [
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
            
            return [
                'success' => true,
                'count' => count($media),
                'media' => $media
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Mediendateien nach bestimmten Kriterien filtern
     * 
     * @param array $filters Filterkriterien (z.B. ['type' => 'image/jpeg'])
     * @param int $limit Maximale Anzahl der Ergebnisse
     * @return array Gefilterte Mediendateien
     */
    public function filterMedia($filters, $limit = 100)
    {
        try {
            $mediaCollection = $this->firestore->collection('media');
            $query = $mediaCollection;
            
            foreach ($filters as $field => $value) {
                $query = $query->where($field, '=', $value);
            }
            
            $query = $query->limit($limit);
            $documents = $query->documents();
            
            $media = [];
            foreach ($documents as $document) {
                $media[] = [
                    'id' => $document->id(),
                    'data' => $document->data()
                ];
            }
            
            return [
                'success' => true,
                'count' => count($media),
                'media' => $media
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Datei-URL generieren
     * 
     * @param string $path Pfad der Datei im Storage
     * @param int $expiresIn Ablaufzeit in Sekunden (0 für dauerhafte URL)
     * @return string URL der Datei
     */
    public function getFileUrl($path, $expiresIn = 0)
    {
        try {
            $object = $this->bucket->object($path);
            
            if (!$object->exists()) {
                throw new \Exception("Datei existiert nicht: $path");
            }
            
            if ($expiresIn > 0) {
                $expiration = new \DateTime();
                $expiration->add(new \DateInterval("PT{$expiresIn}S"));
                return $object->signedUrl($expiration);
            } else {
                // Permanente URL (1000 Jahre)
                return $object->signedUrl(new \DateTime('+1000 years'));
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}