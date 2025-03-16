 <?php
/**
 * Configuration Loader
 * This file loads the Firebase configuration constants for use in PHP files.
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access to this file is not allowed.');
}

// Define CMS access constant for firebase-config.php
define('CMS_ACCESS', true);

// Include Firebase configuration
require_once __DIR__ . '/firebase-config.php';

// Get Firebase configuration
$firebaseConfig = new FirebaseConfig();
$configData = json_decode($firebaseConfig->getJsConfig(), true);

// Define constants for use in PHP files
define('FIREBASE_API_KEY', $configData['apiKey']);
define('FIREBASE_AUTH_DOMAIN', $configData['authDomain']);
define('FIREBASE_DATABASE_URL', $configData['databaseURL']);
define('FIREBASE_PROJECT_ID', $configData['projectId']);
define('FIREBASE_STORAGE_BUCKET', $configData['storageBucket']);
define('FIREBASE_MESSAGING_SENDER_ID', $configData['messagingSenderId']);
define('FIREBASE_APP_ID', $configData['appId']);
define('FIREBASE_MEASUREMENT_ID', $configData['measurementId']);

// Define CMS constants
define('CMS_NAME', 'PHP Firebase CMS');
define('CMS_VERSION', '1.0.0');
define('CMS_AUTHOR', 'Your Name');
define('CMS_WEBSITE', 'https://yourdomain.com');

// Define paths
define('CMS_ROOT', __DIR__);
define('CMS_UPLOADS_DIR', __DIR__ . '/uploads');
define('CMS_TEMPLATES_DIR', __DIR__ . '/templates');

// Check if uploads directory exists, create if not
if (!is_dir(CMS_UPLOADS_DIR)) {
    mkdir(CMS_UPLOADS_DIR, 0755, true);
}

// Function to check authentication status (can be used in pages to protect admin routes)
function requireAuth() {
    // Implementation will vary based on your auth approach
    // This is a placeholder - you should implement session-based auth validation
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login
        header('Location: login.php');
        exit;
    }
    
    return true;
}

// Function to check admin role (for admin-only pages)
function requireAdmin() {
    // First check authentication
    requireAuth();
    
    // Then check role
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // Redirect to dashboard or show error
        header('Location: dashboard.php?error=insufficient_permissions');
        exit;
    }
    
    return true;
}

// Helper function to sanitize output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}