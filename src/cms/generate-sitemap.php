 <?php
/**
 * Sitemap Generator Script
 * This script generates an XML sitemap for your CMS
 */

// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
function isAdmin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    return $_SESSION['user_role'] === 'admin';
}

// Redirect if not admin
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

// Determine site URL from settings
$siteUrl = '';
$settings = getDocument('system', 'settings');
if ($settings && isset($settings['siteUrl'])) {
    $siteUrl = rtrim($settings['siteUrl'], '/');
} else {
    // Use current URL as fallback
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $siteUrl = $protocol . '://' . $host;
}

// Function to generate the sitemap
function generateSitemap($siteUrl) {
    global $db;
    
    // Start XML content
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    // Add homepage
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $siteUrl . '/</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
    $xml .= '    <priority>1.0</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
    
    // Get published posts
    $posts = queryDocuments('posts', [['status', '==', 'published']]);
    
    foreach ($posts as $postId => $post) {
        $lastmod = isset($post['updatedAt']) ? date('Y-m-d', strtotime($post['updatedAt'])) : date('Y-m-d');
        
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $siteUrl . '/post.php?id=' . $postId . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.8</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    // Get published pages
    $pages = queryDocuments('pages', [['status', '==', 'published']]);
    
    foreach ($pages as $pageId => $page) {
        $lastmod = isset($page['updatedAt']) ? date('Y-m-d', strtotime($page['updatedAt'])) : date('Y-m-d');
        
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $siteUrl . '/page.php?id=' . $pageId . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.6</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    // Get categories
    $categories = queryDocuments('categories', []);
    
    foreach ($categories as $categoryId => $category) {
        if (!isset($category['slug'])) continue;
        
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $siteUrl . '/index.php?category=' . urlencode($category['slug']) . '</loc>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.5</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    // Close XML
    $xml .= '</urlset>';
    
    return $xml;
}

// Generate sitemap
$sitemap = generateSitemap($siteUrl);

// Save sitemap to file
$result = file_put_contents('sitemap.xml', $sitemap);

// Determine if operation was successful
$success = ($result !== false);

// Determine message
$message = $success 
    ? 'Sitemap generated successfully! View it at: <a href="sitemap.xml" target="_blank">sitemap.xml</a>' 
    : 'Error generating sitemap. Make sure the web server has write permissions to the directory.';

// Set message type
$messageType = $success ? 'success' : 'error';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap Generator - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">View Site</a></li>
                <li><a href="admin.php">Admin</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Sitemap Generator</h1>
            
            <div class="alert alert-<?php echo $messageType; ?>">
                <p><?php echo $message; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Sitemap Details</h2>
                </div>
                <div class="card-body">
                    <p><strong>Site URL:</strong> <?php echo $siteUrl; ?></p>
                    <p><strong>Total URLs:</strong> <?php echo substr_count($sitemap, '<url>'); ?></p>
                    <p><strong>Sitemap Path:</strong> sitemap.xml</p>
                    <p><strong>Generated On:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
                <div class="card-footer">
                    <a href="generate-sitemap.php" class="btn btn-primary">Regenerate Sitemap</a>
                    <a href="admin.php" class="btn">Back to Admin</a>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Submit Your Sitemap</h2>
                </div>
                <div class="card-body">
                    <p>To ensure search engines discover your sitemap, you can:</p>
                    <ol>
                        <li>
                            <strong>Add to robots.txt:</strong><br>
                            Add the following line to your robots.txt file:<br>
                            <code>Sitemap: <?php echo $siteUrl; ?>/sitemap.xml</code>
                        </li>
                        <li>
                            <strong>Submit to search engines:</strong><br>
                            <ul>
                                <li><a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                                <li><a href="https://www.bing.com/webmasters/home" target="_blank">Bing Webmaster Tools</a></li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> PHP Firebase CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "<?php echo FIREBASE_API_KEY; ?>",
            authDomain: "<?php echo FIREBASE_AUTH_DOMAIN; ?>",
            projectId: "<?php echo FIREBASE_PROJECT_ID; ?>",
            storageBucket: "<?php echo FIREBASE_STORAGE_BUCKET; ?>",
            messagingSenderId: "<?php echo FIREBASE_MESSAGING_SENDER_ID; ?>",
            appId: "<?php echo FIREBASE_APP_ID; ?>",
            measurementId: "<?php echo FIREBASE_MEASUREMENT_ID; ?>"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', () => {
            signOut(auth).then(() => {
                // Sign-out successful
                window.location.href = 'index.php';
            }).catch((error) => {
                // An error happened
                console.error('Logout error:', error);
            });
        });
    </script>
</body>
</html>