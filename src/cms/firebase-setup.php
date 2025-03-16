 <?php
/**
 * Firebase Setup Script
 * Run this script to set up Firebase integration for the CMS
 */

// Ensure this script is run from command line
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.";
    exit(1);
}

// Define constants
define('CMS_ROOT', __DIR__);
define('CMS_ACCESS', true);

// Check if Composer is installed
exec('composer --version', $composerOutput, $composerReturnVar);
if ($composerReturnVar !== 0) {
    echo "Composer is not installed. Please install Composer first: https://getcomposer.org/download/\n";
    exit(1);
}

// Create composer.json if it doesn't exist
if (!file_exists(CMS_ROOT . '/composer.json')) {
    $composerJson = [
        "require" => [
            "kreait/firebase-php" => "^5.0",
            "google/cloud-firestore" => "^1.19",
            "google/cloud-storage" => "^1.23"
        ],
        "autoload" => [
            "psr-4" => [
                "CMS\\" => "src/"
            ]
        ]
    ];
    
    file_put_contents(
        CMS_ROOT . '/composer.json', 
        json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    
    echo "Created composer.json with Firebase dependencies\n";
}

// Install dependencies
echo "Installing Firebase dependencies...\n";
exec('composer install', $output, $returnVar);

if ($returnVar !== 0) {
    echo "Failed to install dependencies. Please check your internet connection and try again.\n";
    exit(1);
}

echo "Dependencies installed successfully.\n\n";

// Get Firebase configuration details from user
echo "Please enter your Firebase project configuration:\n";

echo "Project ID: ";
$projectId = trim(fgets(STDIN));

echo "API Key: ";
$apiKey = trim(fgets(STDIN));

echo "Auth Domain ({$projectId}.firebaseapp.com): ";
$authDomain = trim(fgets(STDIN));
if (empty($authDomain)) {
    $authDomain = "{$projectId}.firebaseapp.com";
}

echo "Database URL (https://{$projectId}.firebaseio.com): ";
$databaseURL = trim(fgets(STDIN));
if (empty($databaseURL)) {
    $databaseURL = "https://{$projectId}.firebaseio.com";
}

echo "Storage Bucket ({$projectId}.appspot.com): ";
$storageBucket = trim(fgets(STDIN));
if (empty($storageBucket)) {
    $storageBucket = "{$projectId}.appspot.com";
}

echo "Messaging Sender ID: ";
$messagingSenderId = trim(fgets(STDIN));

echo "App ID: ";
$appId = trim(fgets(STDIN));

echo "Measurement ID (G-XXXXXXXXXX): ";
$measurementId = trim(fgets(STDIN));

// Update firebase-config.php with provided values
$configFile = file_get_contents(CMS_ROOT . '/firebase-config.php');

$replacements = [
    'YOUR_API_KEY' => $apiKey,
    'YOUR_PROJECT_ID' => $projectId,
    'YOUR_MESSAGING_SENDER_ID' => $messagingSenderId,
    'YOUR_APP_ID' => $appId,
    'YOUR_MEASUREMENT_ID' => $measurementId
];

foreach ($replacements as $placeholder => $value) {
    $configFile = str_replace($placeholder, $value, $configFile);
}

file_put_contents(CMS_ROOT . '/firebase-config.php', $configFile);
echo "Firebase configuration updated successfully.\n\n";

// Create service account file
echo "Please download your service account JSON file from:\n";
echo "https://console.firebase.google.com/project/{$projectId}/settings/serviceaccounts/adminsdk\n";
echo "Save the file as 'service-account.json' in your project root directory.\n\n";
echo "Press Enter when you've completed this step...";
fgets(STDIN);

if (!file_exists(CMS_ROOT . '/service-account.json')) {
    echo "Warning: service-account.json not found. Please make sure to place it in the project root.\n";
} else {
    echo "Service account file found.\n";
}

// Create index.php if it doesn't exist
if (!file_exists(CMS_ROOT . '/index.php')) {
    $indexContent = <<<'EOD'
<?php
// Define constant to prevent direct access to included files
define('CMS_ACCESS', true);

// Include Firebase configuration
require_once __DIR__ . '/firebase-config.php';

// Initialize Firebase
$firebase = FirebaseConfig::getInstance();

// Basic page to test Firebase connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Firebase CMS</title>
</head>
<body>
    <h1>PHP Firebase CMS</h1>
    <p>Firebase connection test:</p>
    <?php
    try {
        // Test Firestore connection
        $testDoc = $firebase->getFirestore()->collection('system')->document('test');
        $testDoc->set(['timestamp' => time(), 'status' => 'connected']);
        echo '<p style="color: green;">✅ Firebase connection successful!</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">❌ Firebase connection failed: ' . $e->getMessage() . '</p>';
    }
    ?>
</body>
</html>
EOD;

    file_put_contents(CMS_ROOT . '/index.php', $indexContent);
    echo "Created index.php with Firebase connection test\n";
}

// Create necessary folders
$directories = [
    'src',
    'templates',
    'assets/css',
    'assets/js',
    'assets/images',
    'uploads'
];

foreach ($directories as $dir) {
    if (!is_dir(CMS_ROOT . '/' . $dir)) {
        mkdir(CMS_ROOT . '/' . $dir, 0755, true);
        echo "Created directory: {$dir}\n";
    }
}

echo "\n\n";
echo "====================================\n";
echo "Firebase setup completed successfully!\n";
echo "====================================\n\n";
echo "Next steps:\n";
echo "1. Make sure your service-account.json file is in the project root\n";
echo "2. Run your application to test the Firebase connection\n";
echo "3. Build your CMS features using the FirebaseConfig class\n\n";
echo "Happy coding!\n";
?>