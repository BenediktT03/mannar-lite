<?php
// Namespace muss die erste Anweisung sein
namespace Mannar\CMS;

// Danach kannst du define und andere Anweisungen verwenden
define('CMS_ACCESS', true);

// Sicherheitscheck gegen direkten Zugriff
if (!defined('CMS_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Firebase-Konfiguration laden
require_once __DIR__ . '/firebase-config.php';

// Firebase-Instanz abrufen
try {
    $firebaseConfig = FirebaseConfig::getInstance();
    
    // Prüfen, ob alle Dienste korrekt initialisiert wurden
    $serviceStatus = $firebaseConfig->checkServices();
    $serviceErrors = $firebaseConfig->getServiceErrors();
    
    if (!empty($serviceErrors)) {
        // Fehler bei Firebase-Diensten protokollieren
    }
    
    // Konfiguration für Client bereitstellen
    $jsConfig = $firebaseConfig->getJsConfig();
    
    // Debug-Info für Entwicklung
    $isDebug = true;
    
} catch (\Exception $e) {
    // Fehlerbehandlung
    error_log('Firebase Configuration Error: ' . $e->getMessage());
    $isFirebaseAvailable = false;
}

// Weitere Konfigurationseinstellungen
$cmsConfig = [
    'site_title' => 'Mannar CMS',
    'version' => '1.0.0',
    'debug' => $isDebug ?? false,
    'firebase_available' => empty($serviceErrors) ?? false
];