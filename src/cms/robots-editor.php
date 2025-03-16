 <?php
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

// Define paths
$robotsPath = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';

// Check if robots.txt exists
$robotsExists = file_exists($robotsPath);
$robotsContent = $robotsExists ? file_get_contents($robotsPath) : '';

// Default robots.txt content if not exists
$defaultRobotsContent = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /cms/\n\n# Sitemap\nSitemap: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/sitemap.xml";

// Load robots.txt settings from Firestore
function loadRobotsSettings() {
    $settings = getDocument('system', 'robots_settings');
    if (!$settings) {
        // Default settings
        return [
            'allow_all_bots' => true,
            'disallow_paths' => ['/admin/', '/cms/'],
            'allow_paths' => ['/'],
            'sitemap_enabled' => true,
            'sitemap_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/sitemap.xml",
            'custom_rules' => []
        ];
    }
    
    return $settings;
}

// Save robots.txt settings to Firestore
function saveRobotsSettings($settings) {
    return saveDocument('system', 'robots_settings', $settings);
}

// Generate robots.txt content from settings
function generateRobotsContent($settings) {
    $content = "";
    
    // Add default user agent rules
    if ($settings['allow_all_bots']) {
        $content .= "User-agent: *\n";
        
        // Add allow paths
        foreach ($settings['allow_paths'] as $path) {
            $content .= "Allow: {$path}\n";
        }
        
        // Add disallow paths
        foreach ($settings['disallow_paths'] as $path) {
            $content .= "Disallow: {$path}\n";
        }
        
        $content .= "\n";
    }
    
    // Add custom rules
    if (!empty($settings['custom_rules'])) {
        foreach ($settings['custom_rules'] as $rule) {
            $content .= "{$rule['directive']}: {$rule['value']}\n";
        }
        $content .= "\n";
    }
    
    // Add sitemap
    if ($settings['sitemap_enabled'] && !empty($settings['sitemap_url'])) {
        $content .= "# Sitemap\nSitemap: {$settings['sitemap_url']}\n";
    }
    
    return $content;
}

// Save robots.txt file
function saveRobotsFile($content) {
    global $robotsPath;
    
    // Check if directory exists, create if not
    $dir = dirname($robotsPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Save file
    $result = file_put_contents($robotsPath, $content);
    
    return $result !== false;
}

// Check Firestore for robots.txt settings
$settings = loadRobotsSettings();

// Get saved settings or use defaults
$allowAllBots = $settings['allow_all_bots'] ?? true;
$disallowPaths = $settings['disallow_paths'] ?? ['/admin/', '/cms/'];
$allowPaths = $settings['allow_paths'] ?? ['/'];
$sitemapEnabled = $settings['sitemap_enabled'] ?? true;
$sitemapUrl = $settings['sitemap_url'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/sitemap.xml");
$customRules = $settings['custom_rules'] ?? [];

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Update settings
        $allowAllBots = isset($_POST['allow_all_bots']);
        
        // Get disallow paths
        $disallowPaths = [];
        if (isset($_POST['disallow_paths']) && !empty($_POST['disallow_paths'])) {
            $disallowPaths = array_map('trim', explode("\n", $_POST['disallow_paths']));
            $disallowPaths = array_filter($disallowPaths);
        }
        
        // Get allow paths
        $allowPaths = [];
        if (isset($_POST['allow_paths']) && !empty($_POST['allow_paths'])) {
            $allowPaths = array_map('trim', explode("\n", $_POST['allow_paths']));
            $allowPaths = array_filter($allowPaths);
        }
        
        // Get sitemap settings
        $sitemapEnabled = isset($_POST['sitemap_enabled']);
        $sitemapUrl = $_POST['sitemap_url'] ?? '';
        
        // Get custom rules
        $customRules = [];
        if (isset($_POST['custom_directive']) && is_array($_POST['custom_directive'])) {
            for ($i = 0; $i < count($_POST['custom_directive']); $i++) {
                if (!empty($_POST['custom_directive'][$i]) && isset($_POST['custom_value'][$i])) {
                    $customRules[] = [
                        'directive' => $_POST['custom_directive'][$i],
                        'value' => $_POST['custom_value'][$i]
                    ];
                }
            }
        }
        
        // Update settings
        $settings = [
            'allow_all_bots' => $allowAllBots,
            'disallow_paths' => $disallowPaths,
            'allow_paths' => $allowPaths,
            'sitemap_enabled' => $sitemapEnabled,
            'sitemap_url' => $sitemapUrl,
            'custom_rules' => $customRules,
            'updated_at' => date('c'),
            'updated_by' => $_SESSION['user_id'] ?? 'unknown'
        ];
        
        // Save settings to Firestore
        if (saveRobotsSettings($settings)) {
            // Generate robots.txt content
            $robotsContent = generateRobotsContent($settings);
            
            // Save robots.txt file
            if (saveRobotsFile($robotsContent)) {
                $message = 'Robots.txt settings saved and file updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to write robots.txt file. Please check file permissions.';
                $messageType = 'error';
            }
        } else {
            $message = 'Failed to save settings. Please try again.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['save_manual'])) {
        // Save manual content
        $robotsContent = $_POST['robots_content'] ?? '';
        
        // Save robots.txt file
        if (saveRobotsFile($robotsContent)) {
            $message = 'Robots.txt file updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to write robots.txt file. Please check file permissions.';
            $messageType = 'error';
        }
    }
}

// Check if sitemap exists
$sitemapExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml');

// Get sitemap URL
$sitemapUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/sitemap.xml";

// Get sitemap generation URL
$generateSitemapUrl = 'generate-sitemap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robots.txt Editor - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .robots-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .robots-container {
                grid-template-columns: 1fr;
            }
        }
        
        .robots-sidebar {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
        }
        
        .robots-content {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
        }
        
        .robots-status {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        
        .status-good {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-bad {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .tab-nav button {
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #495057;
            font-weight: 500;
        }
        
        .tab-nav button.active {
            border-bottom-color: #0077b6;
            color: #0077b6;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .robots-editor {
            width: 100%;
            min-height: 400px;
            font-family: monospace;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .rules-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .rules-table th, .rules-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .rules-table th {
            background-color: #f8f9fa;
        }
        
        .custom-rules {
            margin-top: 1.5rem;
        }
        
        .custom-rule-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .custom-rule-row input {
            flex: 1;
        }
        
        .add-rule-btn, .remove-rule-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            color: #666;
        }
        
        .remove-rule-btn {
            color: #dc3545;
        }
        
        .textarea-paths {
            width: 100%;
            height: 120px;
            font-family: monospace;
        }
    </style>
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
            <h1>Robots.txt Editor</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="robots-container">
                <div class="robots-sidebar">
                    <h2>About Robots.txt</h2>
                    <p>The robots.txt file tells search engine crawlers which pages or files they can or cannot request from your site.</p>
                    
                    <h3>Quick Tips</h3>
                    <ul>
                        <li>Use <strong>Allow</strong> to explicitly allow access to a page</li>
                        <li>Use <strong>Disallow</strong> to block access to a page</li>
                        <li>Use <strong>User-agent</strong> to target specific crawlers</li>
                        <li>Include a <strong>Sitemap</strong> to help crawlers find your content</li>
                    </ul>
                    
                    <h3>File Status</h3>
                    <p>
                        <?php if ($robotsExists): ?>
                            <span class="text-success">✓ robots.txt exists</span>
                        <?php else: ?>
                            <span class="text-warning">⚠ robots.txt does not exist yet</span>
                        <?php endif; ?>
                    </p>
                    
                    <p>
                        <?php if ($sitemapExists): ?>
                            <span class="text-success">✓ sitemap.xml exists</span>
                        <?php else: ?>
                            <span class="text-warning">⚠ sitemap.xml does not exist</span><br>
                            <a href="<?php echo $generateSitemapUrl; ?>" class="btn btn-sm">Generate Sitemap</a>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="robots-content">
                    <div class="robots-status <?php echo $robotsExists ? 'status-good' : 'status-warning'; ?>">
                        <h3>Robots.txt Status</h3>
                        <?php if ($robotsExists): ?>
                            <p>Your robots.txt file is active and can be viewed at <a href="/robots.txt" target="_blank">/robots.txt</a>.</p>
                        <?php else: ?>
                            <p>No robots.txt file detected. Create one using the editor below.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-container">
                        <div class="tab-nav">
                            <button class="tab-button active" data-tab="visual">Visual Editor</button>
                            <button class="tab-button" data-tab="manual">Manual Editor</button>
                            <button class="tab-button" data-tab="test">Test URLs</button>
                        </div>
                        
                        <!-- Visual Editor Tab -->
                        <div id="visual-tab" class="tab-content active">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="allow_all_bots" <?php echo $allowAllBots ? 'checked' : ''; ?>>
                                        Allow all bots (User-agent: *)
                                    </label>
                                    <p class="help-text">This applies the rules to all search engines</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="allow-paths">Allow Paths (One per line)</label>
                                    <textarea id="allow-paths" name="allow_paths" class="textarea-paths"><?php echo implode("\n", $allowPaths); ?></textarea>
                                    <p class="help-text">Specify paths that crawlers are allowed to access</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="disallow-paths">Disallow Paths (One per line)</label>
                                    <textarea id="disallow-paths" name="disallow_paths" class="textarea-paths"><?php echo implode("\n", $disallowPaths); ?></textarea>
                                    <p class="help-text">Specify paths that crawlers should not access</p>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="sitemap_enabled" <?php echo $sitemapEnabled ? 'checked' : ''; ?>>
                                        Include sitemap
                                    </label>
                                    <p class="help-text">Add your sitemap location to robots.txt</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sitemap-url">Sitemap URL</label>
                                    <input type="text" id="sitemap-url" name="sitemap_url" value="<?php echo htmlspecialchars($sitemapUrl); ?>">
                                </div>
                                
                                <div class="custom-rules">
                                    <h3>Custom Rules</h3>
                                    <p>Add custom directives for specific search engine bots</p>
                                    
                                    <div id="custom-rules-container">
                                        <?php if (empty($customRules)): ?>
                                            <div class="custom-rule-row">
                                                <input type="text" name="custom_directive[]" placeholder="Directive (e.g., User-agent)">
                                                <input type="text" name="custom_value[]" placeholder="Value (e.g., Googlebot)">
                                                <button type="button" class="remove-rule-btn" title="Remove rule">×</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($customRules as $rule): ?>
                                                <div class="custom-rule-row">
                                                    <input type="text" name="custom_directive[]" value="<?php echo htmlspecialchars($rule['directive']); ?>" placeholder="Directive (e.g., User-agent)">
                                                    <input type="text" name="custom_value[]" value="<?php echo htmlspecialchars($rule['value']); ?>" placeholder="Value (e.g., Googlebot)">
                                                    <button type="button" class="remove-rule-btn" title="Remove rule">×</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" id="add-rule-btn" class="add-rule-btn">+ Add Rule</button>
                                </div>
                                
                                <div class="form-group" style="margin-top: 20px;">
                                    <button type="submit" name="save_settings" class="btn btn-primary">Save and Update robots.txt</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Manual Editor Tab -->
                        <div id="manual-tab" class="tab-content">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="robots-content">Edit robots.txt content directly</label>
                                    <textarea id="robots-content" name="robots_content" class="robots-editor"><?php echo htmlspecialchars($robotsContent ?: $defaultRobotsContent); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="save_manual" class="btn btn-primary">Save Changes</button>
                                    <button type="button" id="reset-robots-btn" class="btn">Reset to Default</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Test Tab -->
                        <div id="test-tab" class="tab-content">
                            <h3>Test URL Against robots.txt</h3>
                            <p>Check if a specific URL is allowed or disallowed by your robots.txt rules.</p>
                            
                            <div class="form-group">
                                <label for="test-url">Enter URL to test</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="test-url" placeholder="e.g., /admin/dashboard">
                                    <button type="button" id="test-url-btn" class="btn btn-primary">Test</button>
                                </div>
                            </div>
                            
                            <div id="test-result" style="margin-top: 20px; display: none;">
                                <h4>Test Result</h4>
                                <div id="test-result-content"></div>
                            </div>
                        </div>
                    </div>
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
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

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
        
        // Default robots.txt content
        const defaultRobotsContent = `<?php echo str_replace(["\r\n", "\r", "\n"], "\\n", $defaultRobotsContent); ?>`;
        
        // Check authentication state
        onAuthStateChanged(auth, (user) => {
            if (!user) {
                // Redirect to login page if not authenticated
                window.location.href = "login.php";
            }
        });
        
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', () => {
            signOut(auth).then(() => {
                window.location.href = "login.php";
            }).catch((error) => {
                console.error("Logout error:", error);
            });
        });
        
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked tab
                button.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show corresponding tab content
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Add custom rule
        document.getElementById('add-rule-btn').addEventListener('click', () => {
            const container = document.getElementById('custom-rules-container');
            const ruleRow = document.createElement('div');
            ruleRow.className = 'custom-rule-row';
            ruleRow.innerHTML = `
                <input type="text" name="custom_directive[]" placeholder="Directive (e.g., User-agent)">
                <input type="text" name="custom_value[]" placeholder="Value (e.g., Googlebot)">
                <button type="button" class="remove-rule-btn" title="Remove rule">×</button>
            `;
            container.appendChild(ruleRow);
            
            // Add event listener to remove button
            const removeBtn = ruleRow.querySelector('.remove-rule-btn');
            removeBtn.addEventListener('click', () => {
                ruleRow.remove();
            });
        });
        
        // Remove custom rule
        document.querySelectorAll('.remove-rule-btn').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.custom-rule-row').remove();
            });
        });
        
        // Reset robots.txt content
        document.getElementById('reset-robots-btn').addEventListener('click', () => {
            if (confirm('Are you sure you want to reset robots.txt to default content?')) {
                document.getElementById('robots-content').value = defaultRobotsContent;
            }
        });
        
        // Test URL against robots.txt
        document.getElementById('test-url-btn').addEventListener('click', () => {
            const url = document.getElementById('test-url').value.trim();
            const resultContainer = document.getElementById('test-result');
            const resultContent = document.getElementById('test-result-content');
            
            if (!url) {
                resultContainer.style.display = 'block';
                resultContent.innerHTML = '<div class="alert alert-warning">Please enter a URL to test.</div>';
                return;
            }
            
            // Get robots.txt content
            let robotsContent;
            if (document.getElementById('manual-tab').classList.contains('active')) {
                robotsContent = document.getElementById('robots-content').value;
            } else {
                // Use the current settings to generate robots.txt content
                const allowAllBots = document.querySelector('input[name="allow_all_bots"]').checked;
                const allowPaths = document.getElementById('allow-paths').value.split('\n').filter(path => path.trim());
                const disallowPaths = document.getElementById('disallow-paths').value.split('\n').filter(path => path.trim());
                const sitemapEnabled = document.querySelector('input[name="sitemap_enabled"]').checked;
                const sitemapUrl = document.getElementById('sitemap-url').value;
                
                // Generate content
                robotsContent = 'User-agent: *\n';
                allowPaths.forEach(path => robotsContent += `Allow: ${path.trim()}\n`);
                disallowPaths.forEach(path => robotsContent += `Disallow: ${path.trim()}\n`);
                
                if (sitemapEnabled && sitemapUrl) {
                    robotsContent += `\n# Sitemap\nSitemap: ${sitemapUrl}\n`;
                }
                
                // Add custom rules
                const directives = document.querySelectorAll('input[name="custom_directive[]"]');
                const values = document.querySelectorAll('input[name="custom_value[]"]');
                
                for (let i = 0; i < directives.length; i++) {
                    if (directives[i].value && values[i].value) {
                        robotsContent += `${directives[i].value}: ${values[i].value}\n`;
                    }
                }
            }
            
            // Parse robots.txt content
            const lines = robotsContent.split('\n');
            const rules = [];
            
            let currentUserAgent = '*';
            
            for (const line of lines) {
                const trimmedLine = line.trim();
                
                // Skip comments and empty lines
                if (trimmedLine.startsWith('#') || trimmedLine === '') {
                    continue;
                }
                
                // Parse directive and value
                const colonIndex = trimmedLine.indexOf(':');
                if (colonIndex === -1) {
                    continue;
                }
                
                const directive = trimmedLine.substring(0, colonIndex).trim().toLowerCase();
                const value = trimmedLine.substring(colonIndex + 1).trim();
                
                if (directive === 'user-agent') {
                    currentUserAgent = value;
                } else if (directive === 'allow' || directive === 'disallow') {
                    rules.push({
                        userAgent: currentUserAgent,
                        directive: directive,
                        path: value
                    });
                }
            }
            
            // Test URL against rules
            const testUrl = url.startsWith('/') ? url : '/' + url;
            let isAllowed = true;
            let matchingRule = null;
            
            // Rules are processed in order, with later rules overriding earlier ones
            for (const rule of rules) {
                if (rule.userAgent === '*' || rule.userAgent === 'googlebot') {
                    if (testUrl.startsWith(rule.path) || (rule.path === '/' && testUrl === '/')) {
                        if (rule.directive === 'allow') {
                            isAllowed = true;
                            matchingRule = rule;
                        } else if (rule.directive === 'disallow') {
                            isAllowed = false;
                            matchingRule = rule;
                        }
                    }
                }
            }
            
            // Display result
            resultContainer.style.display = 'block';
            
            if (matchingRule) {
                if (isAllowed) {
                    resultContent.innerHTML = `
                        <div class="alert alert-success">
                            <p><strong>URL is allowed</strong> by robots.txt rules.</p>
                            <p>Matching rule: ${matchingRule.directive}: ${matchingRule.path}</p>
                        </div>
                    `;
                } else {
                    resultContent.innerHTML = `
                        <div class="alert alert-danger">
                            <p><strong>URL is disallowed</strong> by robots.txt rules.</p>
                            <p>Matching rule: ${matchingRule.directive}: ${matchingRule.path}</p>
                        </div>
                    `;
                }
            } else {
                resultContent.innerHTML = `
                    <div class="alert alert-success">
                        <p><strong>URL is allowed</strong> by robots.txt rules.</p>
                        <p>No specific rule matches this URL, so it is allowed by default.</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>