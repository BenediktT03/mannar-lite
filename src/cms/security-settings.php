 <?php
// security-settings.php: Security Settings and Firewall
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

// Default security settings if none exist
$defaultSettings = [
    'firewall' => [
        'enabled' => true,
        'block_suspicious_requests' => true,
        'block_tor_exit_nodes' => false,
        'allowed_countries' => [],
        'blocked_countries' => []
    ],
    'login_protection' => [
        'enabled' => true,
        'max_attempts' => 5,
        'lockout_time' => 30, // minutes
        'notify_admin' => true
    ],
    'rate_limiting' => [
        'enabled' => true,
        'page_views' => [
            'enabled' => true,
            'limit' => 60, // requests per minute
            'timeframe' => 60 // seconds
        ],
        'login_attempts' => [
            'enabled' => true,
            'limit' => 5, // attempts
            'timeframe' => 300 // seconds (5 minutes)
        ],
        'api_requests' => [
            'enabled' => true,
            'limit' => 60, // requests per minute
            'timeframe' => 60 // seconds
        ]
    ],
    'content_security' => [
        'xss_protection' => true,
        'content_type_options' => true,
        'frame_options' => 'SAMEORIGIN', // DENY, SAMEORIGIN, ALLOW-FROM
        'referrer_policy' => 'same-origin'
    ],
    'ip_management' => [
        'whitelist' => [],
        'blacklist' => []
    ],
    'attack_logs' => [
        'enabled' => true,
        'retention_days' => 30
    ]
];

// Get security settings from database
$securitySettings = getDocument('system', 'security_settings');
if (!$securitySettings) {
    $securitySettings = $defaultSettings;
} else {
    // Merge with defaults to ensure all fields exist
    $securitySettings = array_merge_recursive($defaultSettings, $securitySettings);
}

// Load security logs for display
function loadSecurityLogs($limit = 100, $offset = 0) {
    $logsQuery = query(
        collection(db, "security_logs"),
        orderBy("timestamp", "desc"),
        limit($limit),
        skip($offset)
    );
    
    $logs = [];
    $logsSnapshot = getDocs($logsQuery);
    
    foreach ($logsSnapshot as $doc) {
        $logs[] = [
            'id' => $doc->id(),
            ...$doc->data()
        ];
    }
    
    return $logs;
}

// Get attack statistics 
function getAttackStats() {
    // Last 24 hours
    $dayAgo = new DateTime();
    $dayAgo->modify('-1 day');
    
    $dayQuery = query(
        collection(db, "security_logs"),
        where("timestamp", ">=", $dayAgo->format('c')),
        orderBy("timestamp", "desc")
    );
    
    $daySnapshot = getDocs($dayQuery);
    $dayCount = $daySnapshot->size();
    
    // Last 7 days
    $weekAgo = new DateTime();
    $weekAgo->modify('-7 days');
    
    $weekQuery = query(
        collection(db, "security_logs"),
        where("timestamp", ">=", $weekAgo->format('c')),
        orderBy("timestamp", "desc")
    );
    
    $weekSnapshot = getDocs($weekQuery);
    $weekCount = $weekSnapshot->size();
    
    // Last 30 days
    $monthAgo = new DateTime();
    $monthAgo->modify('-30 days');
    
    $monthQuery = query(
        collection(db, "security_logs"),
        where("timestamp", ">=", $monthAgo->format('c')),
        orderBy("timestamp", "desc")
    );
    
    $monthSnapshot = getDocs($monthQuery);
    $monthCount = $monthSnapshot->size();
    
    // Get attack types
    $attackTypes = [];
    foreach ($monthSnapshot as $doc) {
        $type = $doc->data()['type'] ?? 'unknown';
        if (!isset($attackTypes[$type])) {
            $attackTypes[$type] = 0;
        }
        $attackTypes[$type]++;
    }
    
    // Get top IPs
    $ips = [];
    foreach ($monthSnapshot as $doc) {
        $ip = $doc->data()['ip'] ?? 'unknown';
        if (!isset($ips[$ip])) {
            $ips[$ip] = 0;
        }
        $ips[$ip]++;
    }
    
    // Sort and limit to top 10
    arsort($attackTypes);
    arsort($ips);
    $attackTypes = array_slice($attackTypes, 0, 10);
    $ips = array_slice($ips, 0, 10);
    
    return [
        'day' => $dayCount,
        'week' => $weekCount,
        'month' => $monthCount,
        'types' => $attackTypes,
        'ips' => $ips
    ];
}

// Get IP geolocation information
function getIpInfo($ip) {
    $url = "http://ip-api.com/json/{$ip}";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        return [
            'country' => 'Unknown',
            'countryCode' => '',
            'region' => '',
            'city' => '',
            'isp' => ''
        ];
    }
    
    $data = json_decode($response, true);
    
    if ($data['status'] === 'fail') {
        return [
            'country' => 'Unknown',
            'countryCode' => '',
            'region' => '',
            'city' => '',
            'isp' => ''
        ];
    }
    
    return [
        'country' => $data['country'] ?? 'Unknown',
        'countryCode' => $data['countryCode'] ?? '',
        'region' => $data['regionName'] ?? '',
        'city' => $data['city'] ?? '',
        'isp' => $data['isp'] ?? ''
    ];
}

// Check if IP is a Tor exit node
function isTorExitNode($ip) {
    $torExitNodes = @file_get_contents('https://check.torproject.org/exit-addresses');
    
    if ($torExitNodes === false) {
        return false;
    }
    
    return strpos($torExitNodes, $ip) !== false;
}

// Verify the .htaccess file exists and is correctly configured
function checkHtaccess() {
    $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
    
    if (!file_exists($htaccessPath)) {
        return [
            'exists' => false,
            'writable' => is_writable($_SERVER['DOCUMENT_ROOT']),
            'recommendations' => [
                'Create an .htaccess file for additional security'
            ]
        ];
    }
    
    $content = file_get_contents($htaccessPath);
    $issues = [];
    $recommendations = [];
    
    // Check for directory listing prevention
    if (strpos($content, 'Options -Indexes') === false) {
        $issues[] = 'Directory listing not disabled';
        $recommendations[] = 'Add "Options -Indexes" to prevent directory listing';
    }
    
    // Check for PHP version hiding
    if (strpos($content, 'ServerSignature Off') === false || 
        strpos($content, 'ServerTokens Prod') === false) {
        $issues[] = 'Server information leakage possible';
        $recommendations[] = 'Add "ServerSignature Off" and "ServerTokens Prod" to hide server information';
    }
    
    // Check for HTTPS redirect
    if (strpos($content, 'RewriteEngine On') === false || 
        strpos($content, 'HTTPS off') === false) {
        $issues[] = 'No HTTPS redirect found';
        $recommendations[] = 'Add HTTPS redirect for secure connections';
    }
    
    return [
        'exists' => true,
        'writable' => is_writable($htaccessPath),
        'issues' => $issues,
        'recommendations' => $recommendations
    ];
}

// Generate .htaccess content
function generateHtaccess($options) {
    $content = "# Generated by PHP Firebase CMS Security Settings\n\n";
    
    // Basic security options
    $content .= "# Disable directory listing\n";
    $content .= "Options -Indexes\n\n";
    
    $content .= "# Hide server information\n";
    $content .= "ServerSignature Off\n";
    $content .= "ServerTokens Prod\n\n";
    
    // HTTPS redirect
    if ($options['force_https']) {
        $content .= "# Force HTTPS\n";
        $content .= "<IfModule mod_rewrite.c>\n";
        $content .= "    RewriteEngine On\n";
        $content .= "    RewriteCond %{HTTPS} off\n";
        $content .= "    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n";
        $content .= "</IfModule>\n\n";
    }
    
    // Security headers
    $content .= "# Security headers\n";
    $content .= "<IfModule mod_headers.c>\n";
    
    if ($options['xss_protection']) {
        $content .= "    # XSS Protection\n";
        $content .= "    Header set X-XSS-Protection \"1; mode=block\"\n";
    }
    
    if ($options['content_type_options']) {
        $content .= "    # Prevent MIME sniffing\n";
        $content .= "    Header set X-Content-Type-Options \"nosniff\"\n";
    }
    
    if ($options['frame_options']) {
        $content .= "    # Frame options\n";
        $content .= "    Header set X-Frame-Options \"" . $options['frame_options'] . "\"\n";
    }
    
    if ($options['referrer_policy']) {
        $content .= "    # Referrer policy\n";
        $content .= "    Header set Referrer-Policy \"" . $options['referrer_policy'] . "\"\n";
    }
    
    $content .= "</IfModule>\n\n";
    
    // Block suspicious requests
    if ($options['block_suspicious']) {
        $content .= "# Block suspicious requests\n";
        $content .= "<IfModule mod_rewrite.c>\n";
        $content .= "    RewriteEngine On\n";
        $content .= "    # Block SQL injections\n";
        $content .= "    RewriteCond %{QUERY_STRING} ([a-z0-9]{2000,}) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (javascript:)(.*)(;) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (base64_encode)(.*)(\() [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (GLOBALS|REQUEST)(=|\\[|%) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (<|%3C)(.*)script(.*)(>|%3) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (\\\\|\.\.\.|\.\./|~|`|<|>|\|) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (boot\.ini|etc/passwd|self/environ) [NC,OR]\n";
        $content .= "    RewriteCond %{QUERY_STRING} (thumbs?\.db|\.htaccess|\.bash_history) [NC]\n";
        $content .= "    RewriteRule .* - [F]\n";
        $content .= "</IfModule>\n\n";
    }
    
    // Block access to sensitive files
    $content .= "# Block access to sensitive files\n";
    $content .= "<FilesMatch \"(^\.ht|composer\.(json|lock)|package(-lock)?\.json|firebase.*\.json|\.env|\.git)\">\n";
    $content .= "    Order allow,deny\n";
    $content .= "    Deny from all\n";
    $content .= "</FilesMatch>\n\n";
    
    // IP blocking
    if (!empty($options['ip_blacklist'])) {
        $content .= "# IP blocking\n";
        $content .= "Order Allow,Deny\n";
        $content .= "Allow from all\n";
        
        foreach ($options['ip_blacklist'] as $ip) {
            $content .= "Deny from " . $ip . "\n";
        }
        
        $content .= "\n";
    }
    
    // Country blocking
    if (!empty($options['country_blacklist']) && extension_loaded('maxminddb')) {
        $content .= "# Country blocking\n";
        $content .= "<IfModule mod_geoip.c>\n";
        $content .= "    GeoIPEnable On\n";
        
        foreach ($options['country_blacklist'] as $country) {
            $content .= "    Deny from env=GEOIP_COUNTRY_CODE=" . $country . "\n";
        }
        
        $content .= "</IfModule>\n\n";
    }
    
    return $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .security-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 600;
            color: #0077b6;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .chart-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .firewall-settings, .rate-limit-settings, .security-headers, .ip-management {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .settings-title {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .ip-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        .ip-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .ip-item:last-child {
            border-bottom: none;
        }
        
        .security-log {
            margin-top: 30px;
        }
        
        .security-log-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .security-log-table th, .security-log-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .security-log-table th {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        
        .log-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .log-type-attack {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .log-type-blocked {
            background-color: #e2e3e5;
            color: #41464b;
        }
        
        .log-type-suspicious {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .htaccess-check {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .htaccess-issues {
            margin-top: 15px;
        }
        
        .htaccess-issue {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 10px;
        }
        
        .htaccess-actions {
            margin-top: 20px;
        }
        
        .htaccess-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
            white-space: pre-wrap;
        }
        
        .country-selection {
            display: flex;
            gap: 20px;
        }
        
        .country-list {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
        }
        
        .country-list h4 {
            margin-top: 0;
        }
        
        .country-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .country-flag {
            margin-right: 8px;
            width: 20px;
            height: 15px;
        }
        
        .security-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .security-tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            margin-bottom: -1px;
        }
        
        .security-tab.active {
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 500;
        }
        
        .security-tab:hover:not(.active) {
            background-color: #f8f9fa;
        }
        
        .security-tab-content {
            display: none;
        }
        
        .security-tab-content.active {
            display: block;
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
                <li><a href="security-settings.php" class="active">Security</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Security Settings</h1>
            <div id="message" class="alert" style="display: none;"></div>
            
            <div class="security-tabs">
                <div class="security-tab active" data-tab="dashboard">Dashboard</div>
                <div class="security-tab" data-tab="firewall">Firewall</div>
                <div class="security-tab" data-tab="login">Login Protection</div>
                <div class="security-tab" data-tab="headers">Security Headers</div>
                <div class="security-tab" data-tab="ip">IP Management</div>
                <div class="security-tab" data-tab="logs">Security Logs</div>
                <div class="security-tab" data-tab="htaccess">.htaccess</div>
            </div>
            
            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="security-tab-content active">
                <div class="security-stats">
                    <div class="stat-card">
                        <div class="stat-label">Attacks Blocked Today</div>
                        <div class="stat-number" id="attacks-today">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Attacks This Week</div>
                        <div class="stat-number" id="attacks-week">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Attacks This Month</div>
                        <div class="stat-number" id="attacks-month">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Security Status</div>
                        <div class="stat-number" id="security-status">-</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-card">
                        <h3 class="chart-title">Attack Types</h3>
                        <canvas id="attack-types-chart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3 class="chart-title">Top Attacking IPs</h3>
                        <canvas id="attacking-ips-chart"></canvas>
                    </div>
                </div>
                
                <div class="security-check">
                    <h3>Security Recommendations</h3>
                    <div id="security-recommendations"></div>
                </div>
            </div>
            
            <!-- Firewall Tab -->
            <div id="firewall-tab" class="security-tab-content">
                <div class="firewall-settings">
                    <div class="settings-header">
                        <h3 class="settings-title">Firewall Settings</h3>
                    </div>
                    
                    <form id="firewall-form">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="firewall-enabled" <?php echo $securitySettings['firewall']['enabled'] ? 'checked' : ''; ?>>
                                Enable Firewall
                            </label>
                            <p class="help-text">Activates the firewall protection system</p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="block-suspicious" <?php echo $securitySettings['firewall']['block_suspicious_requests'] ? 'checked' : ''; ?>>
                                Block Suspicious Requests
                            </label>
                            <p class="help-text">Blocks requests that contain SQL injection, XSS, or other attack patterns</p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="block-tor" <?php echo $securitySettings['firewall']['block_tor_exit_nodes'] ? 'checked' : ''; ?>>
                                Block Tor Exit Nodes
                            </label>
                            <p class="help-text">Blocks access from Tor exit nodes</p>
                        </div>
                        
                        <h4>Country Blocking</h4>
                        <div class="country-selection">
                            <div class="country-list">
                                <h4>Allowed Countries</h4>
                                <p class="help-text">If any countries are selected, only these countries will be allowed</p>
                                <div id="allowed-countries">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="country-list">
                                <h4>Blocked Countries</h4>
                                <p class="help-text">These countries will be blocked</p>
                                <div id="blocked-countries">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">Save Firewall Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Login Protection Tab -->
            <div id="login-tab" class="security-tab-content">
                <div class="rate-limit-settings">
                    <div class="settings-header">
                        <h3 class="settings-title">Login Protection</h3>
                    </div>
                    
                    <form id="login-protection-form">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="login-protection-enabled" <?php echo $securitySettings['login_protection']['enabled'] ? 'checked' : ''; ?>>
                                Enable Login Protection
                            </label>
                            <p class="help-text">Limits the number of failed login attempts</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="max-attempts">Maximum Failed Attempts</label>
                            <input type="number" id="max-attempts" min="1" max="20" value="<?php echo $securitySettings['login_protection']['max_attempts']; ?>">
                            <p class="help-text">Number of failed attempts before lockout</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="lockout-time">Lockout Time (minutes)</label>
                            <input type="number" id="lockout-time" min="5" max="1440" value="<?php echo $securitySettings['login_protection']['lockout_time']; ?>">
                            <p class="help-text">Time in minutes an IP is locked out after too many failed attempts</p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="notify-admin" <?php echo $securitySettings['login_protection']['notify_admin'] ? 'checked' : ''; ?>>
                                Notify Admin
                            </label>
                            <p class="help-text">Send email notification to admin when an account is locked out</p>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Login Protection Settings</button>
                        </div>
                    </form>
                </div>
                
                <div class="rate-limit-settings" style="margin-top: 30px;">
                    <div class="settings-header">
                        <h3 class="settings-title">Rate Limiting</h3>
                    </div>
                    
                    <form id="rate-limit-form">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="rate-limit-enabled" <?php echo $securitySettings['rate_limiting']['enabled'] ? 'checked' : ''; ?>>
                                Enable Rate Limiting
                            </label>
                            <p class="help-text">Limits the number of requests per time period</p>
                        </div>
                        
                        <h4>Page View Rate Limiting</h4>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="page-rate-limit-enabled" <?php echo $securitySettings['rate_limiting']['page_views']['enabled'] ? 'checked' : ''; ?>>
                                Enable Page View Rate Limiting
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="page-rate-limit">Page View Limit</label>
                            <input type="number" id="page-rate-limit" min="10" max="1000" value="<?php echo $securitySettings['rate_limiting']['page_views']['limit']; ?>">
                            <p class="help-text">Maximum page views per minute per IP</p>
                        </div>
                        
                        <h4>Login Attempt Rate Limiting</h4>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="login-rate-limit-enabled" <?php echo $securitySettings['rate_limiting']['login_attempts']['enabled'] ? 'checked' : ''; ?>>
                                Enable Login Attempt Rate Limiting
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="login-rate-limit">Login Attempt Limit</label>
                            <input type="number" id="login-rate-limit" min="1" max="20" value="<?php echo $securitySettings['rate_limiting']['login_attempts']['limit']; ?>">
                            <p class="help-text">Maximum login attempts per 5 minutes per IP</p>
                        </div>
                        
                        <h4>API Request Rate Limiting</h4>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="api-rate-limit-enabled" <?php echo $securitySettings['rate_limiting']['api_requests']['enabled'] ? 'checked' : ''; ?>>
                                Enable API Request Rate Limiting
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="api-rate-limit">API Request Limit</label>
                            <input type="number" id="api-rate-limit" min="10" max="1000" value="<?php echo $securitySettings['rate_limiting']['api_requests']['limit']; ?>">
                            <p class="help-text">Maximum API requests per minute per API key</p>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Rate Limiting Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Headers Tab -->
            <div id="headers-tab" class="security-tab-content">
                <div class="security-headers">
                    <div class="settings-header">
                        <h3 class="settings-title">Security Headers</h3>
                    </div>
                    
                    <form id="security-headers-form">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="xss-protection" <?php echo $securitySettings['content_security']['xss_protection'] ? 'checked' : ''; ?>>
                                X-XSS-Protection
                            </label>
                            <p class="help-text">Enables XSS protection in modern browsers</p>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="content-type-options" <?php echo $securitySettings['content_security']['content_type_options'] ? 'checked' : ''; ?>>
                                X-Content-Type-Options
                            </label>
                            <p class="help-text">Prevents MIME type sniffing</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="frame-options">X-Frame-Options</label>
                            <select id="frame-options">
                                <option value="DENY" <?php echo $securitySettings['content_security']['frame_options'] === 'DENY' ? 'selected' : ''; ?>>DENY - Prevent framing completely</option>
                                <option value="SAMEORIGIN" <?php echo $securitySettings['content_security']['frame_options'] === 'SAMEORIGIN' ? 'selected' : ''; ?>>SAMEORIGIN - Allow same site framing</option>
                                <option value="ALLOW-FROM" <?php echo $securitySettings['content_security']['frame_options'] === 'ALLOW-FROM' ? 'selected' : ''; ?>>ALLOW-FROM - Specify allowed sites</option>
                            </select>
                            <p class="help-text">Controls how your website can be framed</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="referrer-policy">Referrer-Policy</label>
                            <select id="referrer-policy">
                                <option value="no-referrer" <?php echo $securitySettings['content_security']['referrer_policy'] === 'no-referrer' ? 'selected' : ''; ?>>no-referrer</option>
                                <option value="no-referrer-when-downgrade" <?php echo $securitySettings['content_security']['referrer_policy'] === 'no-referrer-when-downgrade' ? 'selected' : ''; ?>>no-referrer-when-downgrade</option>
                                <option value="origin" <?php echo $securitySettings['content_security']['referrer_policy'] === 'origin' ? 'selected' : ''; ?>>origin</option>
                                <option value="origin-when-cross-origin" <?php echo $securitySettings['content_security']['referrer_policy'] === 'origin-when-cross-origin' ? 'selected' : ''; ?>>origin-when-cross-origin</option>
                                <option value="same-origin" <?php echo $securitySettings['content_security']['referrer_policy'] === 'same-origin' ? 'selected' : ''; ?>>same-origin</option>
                                <option value="strict-origin" <?php echo $securitySettings['content_security']['referrer_policy'] === 'strict-origin' ? 'selected' : ''; ?>>strict-origin</option>
                                <option value="strict-origin-when-cross-origin" <?php echo $securitySettings['content_security']['referrer_policy'] === 'strict-origin-when-cross-origin' ? 'selected' : ''; ?>>strict-origin-when-cross-origin</option>
                                <option value="unsafe-url" <?php echo $securitySettings['content_security']['referrer_policy'] === 'unsafe-url' ? 'selected' : ''; ?>>unsafe-url</option>
                            </select>
                            <p class="help-text">Controls what information is sent with outgoing links</p>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Security Headers</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- IP Management Tab -->
            <div id="ip-tab" class="security-tab-content">
                <div class="ip-management">
                    <div class="settings-header">
                        <h3 class="settings-title">IP Whitelist</h3>
                    </div>
                    
                    <p>These IPs will always be allowed, even if other rules would block them</p>
                    
                    <div class="ip-list" id="whitelist-container">
                        <?php foreach ($securitySettings['ip_management']['whitelist'] as $ip): ?>
                        <div class="ip-item">
                            <span><?php echo $ip; ?></span>
                            <button class="btn btn-danger remove-ip" data-ip="<?php echo $ip; ?>" data-list="whitelist">Remove</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form id="add-whitelist-form">
                        <div class="form-group" style="display: flex; gap: 10px;">
                            <input type="text" id="whitelist-ip" placeholder="IP address" style="flex: 1;" pattern="^(\d{1,3}\.){3}\d{1,3}$">
                            <button type="submit" class="btn btn-primary">Add to Whitelist</button>
                        </div>
                    </form>
                </div>
                
                <div class="ip-management" style="margin-top: 30px;">
                    <div class="settings-header">
                        <h3 class="settings-title">IP Blacklist</h3>
                    </div>
                    
                    <p>These IPs will always be blocked</p>
                    
                    <div class="ip-list" id="blacklist-container">
                        <?php foreach ($securitySettings['ip_management']['blacklist'] as $ip): ?>
                        <div class="ip-item">
                            <span><?php echo $ip; ?></span>
                            <button class="btn btn-danger remove-ip" data-ip="<?php echo $ip; ?>" data-list="blacklist">Remove</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form id="add-blacklist-form">
                        <div class="form-group" style="display: flex; gap: 10px;">
                            <input type="text" id="blacklist-ip" placeholder="IP address" style="flex: 1;" pattern="^(\d{1,3}\.){3}\d{1,3}$">
                            <button type="submit" class="btn btn-primary">Add to Blacklist</button>
                        </div>
                    </form>
                </div>
                
                <div class="ip-management" style="margin-top: 30px;">
                    <div class="settings-header">
                        <h3 class="settings-title">Currently Blocked IPs</h3>
                    </div>
                    
                    <p>These IPs are temporarily blocked due to suspicious activity</p>
                    
                    <div class="ip-list" id="blocked-ips-container">
                        <!-- Will be loaded via JavaScript -->
                    </div>
                    
                    <button id="clear-blocked-ips" class="btn btn-danger">Clear All Blocked IPs</button>
                </div>
            </div>
            
            <!-- Security Logs Tab -->
            <div id="logs-tab" class="security-tab-content">
                <div class="security-log">
                    <div class="settings-header">
                        <h3 class="settings-title">Security Logs</h3>
                        <div>
                            <button id="refresh-logs" class="btn">Refresh</button>
                            <button id="clear-logs" class="btn btn-danger">Clear Logs</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="log-retention">Log Retention (days)</label>
                        <input type="number" id="log-retention" min="1" max="365" value="<?php echo $securitySettings['attack_logs']['retention_days']; ?>">
                        <button id="save-retention" class="btn btn-primary">Save</button>
                    </div>
                    
                    <table class="security-log-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>IP</th>
                                <th>Type</th>
                                <th>URL</th>
                                <th>Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="security-logs">
                            <!-- Will be loaded via JavaScript -->
                        </tbody>
                    </table>
                    
                    <div id="logs-pagination" class="pagination" style="margin-top: 20px;">
                        <!-- Pagination will be added here -->
                    </div>
                </div>
            </div>
            
            <!-- .htaccess Tab -->
            <div id="htaccess-tab" class="security-tab-content">
                <div class="htaccess-check">
                    <div class="settings-header">
                        <h3 class="settings-title">.htaccess Security</h3>
                    </div>
                    
                    <p>The .htaccess file provides additional security at the server level</p>
                    
                    <div id="htaccess-status">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <div class="htaccess-issues" id="htaccess-issues">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <div class="htaccess-actions">
                        <button id="generate-htaccess" class="btn btn-primary">Generate Secure .htaccess</button>
                        <button id="view-htaccess" class="btn">View Current .htaccess</button>
                    </div>
                    
                    <div class="htaccess-content" id="htaccess-content" style="display: none;">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> PHP Firebase CMS</p>
    </footer>

    <!-- Include Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, doc, getDoc, setDoc, addDoc, updateDoc, deleteDoc, getDocs, query, where, orderBy, limit } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        const db = getFirestore(app);
        
        // Global variables
        let securitySettings = <?php echo json_encode($securitySettings); ?>;
        let securityLogs = [];
        let currentLogsPage = 1;
        let logsPerPage = 20;
        let totalLogs = 0;
        let charts = {};
        
        // Country list for selections
        const countries = [
            { code: 'AF', name: 'Afghanistan' },
            { code: 'AL', name: 'Albania' },
            // ... add more countries
            { code: 'US', name: 'United States' },
            { code: 'GB', name: 'United Kingdom' },
            { code: 'CA', name: 'Canada' },
            // ... and so on
        ];
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                // Check if user is admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // Initialize security dashboard
                    initSecurityDashboard();
                    
                    // Set up event listeners
                    setupEventListeners();
                    
                    // Setup tabs
                    setupTabs();
                } else {
                    // Redirect to dashboard - not an admin
                    window.location.href = "dashboard.php";
                }
            } else {
                // Redirect to login page
                window.location.href = "login.php";
            }
        });
        
        // Set up tab navigation
        function setupTabs() {
            const tabs = document.querySelectorAll('.security-tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    tab.classList.add('active');
                    
                    // Hide all tab content
                    document.querySelectorAll('.security-tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show clicked tab content
                    const tabId = tab.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                    
                    // Special handling for dashboard tab (refresh charts)
                    if (tabId === 'dashboard') {
                        loadSecurityStats();
                    }
                    
                    // Special handling for logs tab
                    if (tabId === 'logs') {
                        loadSecurityLogs();
                    }
                    
                    // Special handling for htaccess tab
                    if (tabId === 'htaccess') {
                        checkHtaccess();
                    }
                });
            });
        }
        
        // Initialize security dashboard
        async function initSecurityDashboard() {
            // Load security stats
            loadSecurityStats();
            
            // Populate country lists
            populateCountryLists();
            
            // Handle logout button
            document.getElementById('logoutBtn').addEventListener('click', () => {
                signOut(auth).then(() => {
                    window.location.href = "login.php";
                }).catch((error) => {
                    console.error("Logout error:", error);
                });
            });
        }
        
        // Load security stats for dashboard
        async function loadSecurityStats() {
            try {
                // Get attack statistics
                const stats = await getAttackStats();
                
                // Update dashboard numbers
                document.getElementById('attacks-today').textContent = stats.day;
                document.getElementById('attacks-week').textContent = stats.week;
                document.getElementById('attacks-month').textContent = stats.month;
                
                // Calculate security score
                const securityScore = calculateSecurityScore();
                document.getElementById('security-status').innerHTML = `
                    ${securityScore.score}<span style="font-size: 1rem; color: ${securityScore.color};">/100</span>
                `;
                
                // Create charts
                createAttackTypesChart(stats.types);
                createAttackingIPsChart(stats.ips);
                
                // Generate security recommendations
                generateSecurityRecommendations();
            } catch (error) {
                console.error("Error loading security stats:", error);
                showMessage("Error loading security statistics", "error");
            }
        }
        
        // Get attack statistics
        async function getAttackStats() {
            // Last 24 hours
            const dayAgo = new Date();
            dayAgo.setDate(dayAgo.getDate() - 1);
            
            const dayQuery = query(
                collection(db, "security_logs"),
                where("timestamp", ">=", dayAgo.toISOString()),
                orderBy("timestamp", "desc")
            );
            
            const daySnapshot = await getDocs(dayQuery);
            const dayCount = daySnapshot.size;
            
            // Last 7 days
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            
            const weekQuery = query(
                collection(db, "security_logs"),
                where("timestamp", ">=", weekAgo.toISOString()),
                orderBy("timestamp", "desc")
            );
            
            const weekSnapshot = await getDocs(weekQuery);
            const weekCount = weekSnapshot.size;
            
            // Last 30 days
            const monthAgo = new Date();
            monthAgo.setDate(monthAgo.getDate() - 30);
            
            const monthQuery = query(
                collection(db, "security_logs"),
                where("timestamp", ">=", monthAgo.toISOString()),
                orderBy("timestamp", "desc")
            );
            
            const monthSnapshot = await getDocs(monthQuery);
            const monthCount = monthSnapshot.size;
            
            // Get attack types
            const attackTypes = {};
            monthSnapshot.forEach((doc) => {
                const type = doc.data().type || 'unknown';
                attackTypes[type] = (attackTypes[type] || 0) + 1;
            });
            
            // Get top IPs
            const ips = {};
            monthSnapshot.forEach((doc) => {
                const ip = doc.data().ip || 'unknown';
                ips[ip] = (ips[ip] || 0) + 1;
            });
            
            // Sort and limit to top 10
            const sortedTypes = Object.entries(attackTypes)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10);
                
            const sortedIPs = Object.entries(ips)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10);
            
            return {
                day: dayCount,
                week: weekCount,
                month: monthCount,
                types: Object.fromEntries(sortedTypes),
                ips: Object.fromEntries(sortedIPs)
            };
        }
        
        // Create chart for attack types
        function createAttackTypesChart(data) {
            const ctx = document.getElementById('attack-types-chart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (charts.attackTypes) {
                charts.attackTypes.destroy();
            }
            
            // Create the chart
            charts.attackTypes = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40',
                            '#8AC249',
                            '#EA526F',
                            '#7D5BA6',
                            '#6A7FDB'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        }
        
        // Create chart for attacking IPs
        function createAttackingIPsChart(data) {
            const ctx = document.getElementById('attacking-ips-chart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (charts.attackingIPs) {
                charts.attackingIPs.destroy();
            }
            
            // Create the chart
            charts.attackingIPs = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: 'Attack Count',
                        data: Object.values(data),
                        backgroundColor: '#36A2EB'
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        // Generate security recommendations
        function generateSecurityRecommendations() {
            const recommendations = [];
            
            // Check firewall settings
            if (!securitySettings.firewall.enabled) {
                recommendations.push("Enable the firewall for improved security");
            }
            
            // Check login protection
            if (!securitySettings.login_protection.enabled) {
                recommendations.push("Enable login protection to prevent brute force attacks");
            }
            
            // Check rate limiting
            if (!securitySettings.rate_limiting.enabled) {
                recommendations.push("Enable rate limiting to prevent abuse");
            }
            
            // Check security headers
            if (!securitySettings.content_security.xss_protection) {
                recommendations.push("Enable XSS protection header");
            }
            
            if (!securitySettings.content_security.content_type_options) {
                recommendations.push("Enable X-Content-Type-Options header to prevent MIME type sniffing");
            }
            
            // Check .htaccess
            const htaccessCheck = <?php echo json_encode(checkHtaccess()); ?>;
            if (!htaccessCheck.exists) {
                recommendations.push("Create an .htaccess file for additional security");
            } else if (htaccessCheck.recommendations.length > 0) {
                recommendations.push("Optimize your .htaccess file: " + htaccessCheck.recommendations[0]);
            }
            
            // Display recommendations
            const recommendationsContainer = document.getElementById('security-recommendations');
            
            if (recommendations.length === 0) {
                recommendationsContainer.innerHTML = `
                    <div class="alert alert-success">
                        <p>Your security settings look good! No recommendations at this time.</p>
                    </div>
                `;
            } else {
                let html = '<ul class="recommendations-list">';
                recommendations.forEach(recommendation => {
                    html += `<li>${recommendation}</li>`;
                });
                html += '</ul>';
                
                recommendationsContainer.innerHTML = html;
            }
        }
        
        // Calculate security score based on settings
        function calculateSecurityScore() {
            let score = 0;
            
            // Firewall (25 points max)
            if (securitySettings.firewall.enabled) score += 15;
            if (securitySettings.firewall.block_suspicious_requests) score += 5;
            if (securitySettings.firewall.block_tor_exit_nodes) score += 5;
            
            // Login protection (20 points max)
            if (securitySettings.login_protection.enabled) score += 10;
            if (securitySettings.login_protection.max_attempts <= 5) score += 5;
            if (securitySettings.login_protection.notify_admin) score += 5;
            
            // Rate limiting (15 points max)
            if (securitySettings.rate_limiting.enabled) score += 5;
            if (securitySettings.rate_limiting.page_views.enabled) score += 3;
            if (securitySettings.rate_limiting.login_attempts.enabled) score += 4;
            if (securitySettings.rate_limiting.api_requests.enabled) score += 3;
            
            // Security headers (20 points max)
            if (securitySettings.content_security.xss_protection) score += 5;
            if (securitySettings.content_security.content_type_options) score += 5;
            if (securitySettings.content_security.frame_options) score += 5;
            if (securitySettings.content_security.referrer_policy) score += 5;
            
            // .htaccess (20 points max)
            const htaccessCheck = <?php echo json_encode(checkHtaccess()); ?>;
            if (htaccessCheck.exists) score += 10;
            if (htaccessCheck.issues && htaccessCheck.issues.length === 0) score += 10;
            
            // Determine color based on score
            let color = '#dc3545'; // Red
            if (score >= 80) {
                color = '#28a745'; // Green
            } else if (score >= 60) {
                color = '#ffc107'; // Yellow
            } else if (score >= 40) {
                color = '#fd7e14'; // Orange
            }
            
            return {
                score,
                color
            };
        }
        
        // Setup event listeners for forms and buttons
        function setupEventListeners() {
            // Firewall form
            document.getElementById('firewall-form').addEventListener('submit', saveFirewallSettings);
            
            // Login protection form
            document.getElementById('login-protection-form').addEventListener('submit', saveLoginProtectionSettings);
            
            // Rate limit form
            document.getElementById('rate-limit-form').addEventListener('submit', saveRateLimitSettings);
            
            // Security headers form
            document.getElementById('security-headers-form').addEventListener('submit', saveSecurityHeadersSettings);
            
            // IP whitelist form
            document.getElementById('add-whitelist-form').addEventListener('submit', addToWhitelist);
            
            // IP blacklist form
            document.getElementById('add-blacklist-form').addEventListener('submit', addToBlacklist);
            
            // Remove IP buttons (will be added when IPs are loaded)
            
            // Clear blocked IPs button
            document.getElementById('clear-blocked-ips').addEventListener('click', clearBlockedIPs);
            
            // Security logs buttons
            document.getElementById('refresh-logs').addEventListener('click', () => loadSecurityLogs());
            document.getElementById('clear-logs').addEventListener('click', clearSecurityLogs);
            document.getElementById('save-retention').addEventListener('click', saveLogRetention);
            
            // .htaccess buttons
            document.getElementById('generate-htaccess').addEventListener('click', generateHtaccess);
            document.getElementById('view-htaccess').addEventListener('click', viewHtaccess);
        }
        
        // Load security logs
        async function loadSecurityLogs(page = 1) {
            try {
                // Update current page
                currentLogsPage = page;
                
                // Calculate offset
                const offset = (page - 1) * logsPerPage;
                
                // Get total count
                const countQuery = query(collection(db, "security_logs"));
                const countSnapshot = await getDocs(countQuery);
                totalLogs = countSnapshot.size;
                
                // Get logs for current page
                const logsQuery = query(
                    collection(db, "security_logs"),
                    orderBy("timestamp", "desc"),
                    limit(logsPerPage)
                );
                
                const logsSnapshot = await getDocs(logsQuery);
                
                // Reset logs array
                securityLogs = [];
                
                logsSnapshot.forEach((doc) => {
                    securityLogs.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                // Render logs
                renderSecurityLogs();
                
                // Update pagination
                updateLogsPagination();
            } catch (error) {
                console.error("Error loading security logs:", error);
                showMessage("Error loading security logs", "error");
            }
        }
        
        // Render security logs
        function renderSecurityLogs() {
            const logsContainer = document.getElementById('security-logs');
            
            if (securityLogs.length === 0) {
                logsContainer.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center;">No security logs found</td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            
            securityLogs.forEach(log => {
                // Format timestamp
                const date = new Date(log.timestamp);
                const formattedDate = date.toLocaleString();
                
                // Determine type class
                let typeClass = 'log-type-';
                if (log.type === 'attack') {
                    typeClass += 'attack';
                } else if (log.type === 'blocked') {
                    typeClass += 'blocked';
                } else {
                    typeClass += 'suspicious';
                }
                
                html += `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${log.ip}</td>
                        <td><span class="log-type ${typeClass}">${log.type}</span></td>
                        <td>${log.url || '-'}</td>
                        <td>${log.details || '-'}</td>
                        <td>
                            <button class="btn btn-danger btn-sm delete-log" data-id="${log.id}">Delete</button>
                            ${log.type !== 'blocked' ? `<button class="btn btn-sm block-ip" data-ip="${log.ip}">Block IP</button>` : ''}
                        </td>
                    </tr>
                `;
            });
            
            logsContainer.innerHTML = html;
            
            // Add event listeners for delete log buttons
            document.querySelectorAll('.delete-log').forEach(button => {
                button.addEventListener('click', (e) => {
                    const logId = e.target.getAttribute('data-id');
                    deleteSecurityLog(logId);
                });
            });
            
            // Add event listeners for block IP buttons
            document.querySelectorAll('.block-ip').forEach(button => {
                button.addEventListener('click', (e) => {
                    const ip = e.target.getAttribute('data-ip');
                    addToBlacklistDirect(ip);
                });
            });
        }
        
        // Update logs pagination
        function updateLogsPagination() {
            const paginationContainer = document.getElementById('logs-pagination');
            
            // Calculate total pages
            const totalPages = Math.ceil(totalLogs / logsPerPage);
            
            if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
            }
            
            paginationContainer.style.display = 'flex';
            
            let html = '';
            
            // Previous button
            if (currentLogsPage > 1) {
                html += `<a href="#" class="logs-page-link" data-page="${currentLogsPage - 1}">Previous</a>`;
            } else {
                html += `<span class="logs-page-link disabled">Previous</span>`;
            }
            
            // Page numbers
            const startPage = Math.max(1, currentLogsPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentLogsPage) {
                    html += `<span class="logs-page-link current">${i}</span>`;
                } else {
                    html += `<a href="#" class="logs-page-link" data-page="${i}">${i}</a>`;
                }
            }
            
            // Next button
            if (currentLogsPage < totalPages) {
                html += `<a href="#" class="logs-page-link" data-page="${currentLogsPage + 1}">Next</a>`;
            } else {
                html += `<span class="logs-page-link disabled">Next</span>`;
            }
            
            paginationContainer.innerHTML = html;
            
            // Add event listeners for pagination links
            document.querySelectorAll('.logs-page-link').forEach(link => {
                if (!link.classList.contains('disabled') && !link.classList.contains('current')) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(e.target.getAttribute('data-page'));
                        loadSecurityLogs(page);
                    });
                }
            });
        }
        
        // Save firewall settings
        async function saveFirewallSettings(e) {
            e.preventDefault();
            
            try {
                // Get form values
                const firewallEnabled = document.getElementById('firewall-enabled').checked;
                const blockSuspicious = document.getElementById('block-suspicious').checked;
                const blockTor = document.getElementById('block-tor').checked;
                
                // Get selected countries
                const allowedCountries = [...document.querySelectorAll('#allowed-countries input:checked')].map(input => input.value);
                const blockedCountries = [...document.querySelectorAll('#blocked-countries input:checked')].map(input => input.value);
                
                // Update settings
                securitySettings.firewall.enabled = firewallEnabled;
                securitySettings.firewall.block_suspicious_requests = blockSuspicious;
                securitySettings.firewall.block_tor_exit_nodes = blockTor;
                securitySettings.firewall.allowed_countries = allowedCountries;
                securitySettings.firewall.blocked_countries = blockedCountries;
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    firewall: securitySettings.firewall,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                showMessage("Firewall settings saved successfully", "success");
            } catch (error) {
                console.error("Error saving firewall settings:", error);
                showMessage("Error saving firewall settings", "error");
            }
        }
        
        // Save login protection settings
        async function saveLoginProtectionSettings(e) {
            e.preventDefault();
            
            try {
                // Get form values
                const loginProtectionEnabled = document.getElementById('login-protection-enabled').checked;
                const maxAttempts = parseInt(document.getElementById('max-attempts').value);
                const lockoutTime = parseInt(document.getElementById('lockout-time').value);
                const notifyAdmin = document.getElementById('notify-admin').checked;
                
                // Update settings
                securitySettings.login_protection.enabled = loginProtectionEnabled;
                securitySettings.login_protection.max_attempts = maxAttempts;
                securitySettings.login_protection.lockout_time = lockoutTime;
                securitySettings.login_protection.notify_admin = notifyAdmin;
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    login_protection: securitySettings.login_protection,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                showMessage("Login protection settings saved successfully", "success");
            } catch (error) {
                console.error("Error saving login protection settings:", error);
                showMessage("Error saving login protection settings", "error");
            }
        }
        
        // Save rate limit settings
        async function saveRateLimitSettings(e) {
            e.preventDefault();
            
            try {
                // Get form values
                const rateLimitEnabled = document.getElementById('rate-limit-enabled').checked;
                const pageRateLimitEnabled = document.getElementById('page-rate-limit-enabled').checked;
                const pageRateLimit = parseInt(document.getElementById('page-rate-limit').value);
                const loginRateLimitEnabled = document.getElementById('login-rate-limit-enabled').checked;
                const loginRateLimit = parseInt(document.getElementById('login-rate-limit').value);
                const apiRateLimitEnabled = document.getElementById('api-rate-limit-enabled').checked;
                const apiRateLimit = parseInt(document.getElementById('api-rate-limit').value);
                
                // Update settings
                securitySettings.rate_limiting.enabled = rateLimitEnabled;
                securitySettings.rate_limiting.page_views.enabled = pageRateLimitEnabled;
                securitySettings.rate_limiting.page_views.limit = pageRateLimit;
                securitySettings.rate_limiting.login_attempts.enabled = loginRateLimitEnabled;
                securitySettings.rate_limiting.login_attempts.limit = loginRateLimit;
                securitySettings.rate_limiting.api_requests.enabled = apiRateLimitEnabled;
                securitySettings.rate_limiting.api_requests.limit = apiRateLimit;
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    rate_limiting: securitySettings.rate_limiting,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                showMessage("Rate limiting settings saved successfully", "success");
            } catch (error) {
                console.error("Error saving rate limiting settings:", error);
                showMessage("Error saving rate limiting settings", "error");
            }
        }
        
        // Save security headers settings
        async function saveSecurityHeadersSettings(e) {
            e.preventDefault();
            
            try {
                // Get form values
                const xssProtection = document.getElementById('xss-protection').checked;
                const contentTypeOptions = document.getElementById('content-type-options').checked;
                const frameOptions = document.getElementById('frame-options').value;
                const referrerPolicy = document.getElementById('referrer-policy').value;
                
                // Update settings
                securitySettings.content_security.xss_protection = xssProtection;
                securitySettings.content_security.content_type_options = contentTypeOptions;
                securitySettings.content_security.frame_options = frameOptions;
                securitySettings.content_security.referrer_policy = referrerPolicy;
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    content_security: securitySettings.content_security,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                showMessage("Security headers settings saved successfully", "success");
            } catch (error) {
                console.error("Error saving security headers settings:", error);
                showMessage("Error saving security headers settings", "error");
            }
        }
        
        // Add IP to whitelist
        async function addToWhitelist(e) {
            e.preventDefault();
            
            const ip = document.getElementById('whitelist-ip').value.trim();
            
            if (!ip) {
                showMessage("Please enter an IP address", "error");
                return;
            }
            
            // Validate IP format
            if (!isValidIP(ip)) {
                showMessage("Please enter a valid IP address", "error");
                return;
            }
            
            try {
                // Check if IP is already in whitelist
                if (securitySettings.ip_management.whitelist.includes(ip)) {
                    showMessage("IP is already in whitelist", "error");
                    return;
                }
                
                // Add to whitelist
                securitySettings.ip_management.whitelist.push(ip);
                
                // Remove from blacklist if present
                securitySettings.ip_management.blacklist = securitySettings.ip_management.blacklist.filter(i => i !== ip);
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    ip_management: securitySettings.ip_management,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                // Update UI
                document.getElementById('whitelist-container').innerHTML += `
                    <div class="ip-item">
                        <span>${ip}</span>
                        <button class="btn btn-danger remove-ip" data-ip="${ip}" data-list="whitelist">Remove</button>
                    </div>
                `;
                
                // Add event listener for remove button
                document.querySelectorAll('.remove-ip[data-ip="' + ip + '"][data-list="whitelist"]').forEach(button => {
                    button.addEventListener('click', removeIP);
                });
                
                // Clear input
                document.getElementById('whitelist-ip').value = '';
                
                showMessage("IP added to whitelist successfully", "success");
            } catch (error) {
                console.error("Error adding IP to whitelist:", error);
                showMessage("Error adding IP to whitelist", "error");
            }
        }
        
        // Add IP to blacklist
        async function addToBlacklist(e) {
            e.preventDefault();
            
            const ip = document.getElementById('blacklist-ip').value.trim();
            
            if (!ip) {
                showMessage("Please enter an IP address", "error");
                return;
            }
            
            // Validate IP format
            if (!isValidIP(ip)) {
                showMessage("Please enter a valid IP address", "error");
                return;
            }
            
            try {
                // Check if IP is already in blacklist
                if (securitySettings.ip_management.blacklist.includes(ip)) {
                    showMessage("IP is already in blacklist", "error");
                    return;
                }
                
                // Add to blacklist
                securitySettings.ip_management.blacklist.push(ip);
                
                // Remove from whitelist if present
                securitySettings.ip_management.whitelist = securitySettings.ip_management.whitelist.filter(i => i !== ip);
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    ip_management: securitySettings.ip_management,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                // Update UI
                document.getElementById('blacklist-container').innerHTML += `
                    <div class="ip-item">
                        <span>${ip}</span>
                        <button class="btn btn-danger remove-ip" data-ip="${ip}" data-list="blacklist">Remove</button>
                    </div>
                `;
                
                // Add event listener for remove button
                document.querySelectorAll('.remove-ip[data-ip="' + ip + '"][data-list="blacklist"]').forEach(button => {
                    button.addEventListener('click', removeIP);
                });
                
                // Clear input
                document.getElementById('blacklist-ip').value = '';
                
                showMessage("IP added to blacklist successfully", "success");
            } catch (error) {
                console.error("Error adding IP to blacklist:", error);
                showMessage("Error adding IP to blacklist", "error");
            }
        }
        
        // Add IP to blacklist directly (from logs)
        async function addToBlacklistDirect(ip) {
            if (!ip) return;
            
            try {
                // Check if IP is already in blacklist
                if (securitySettings.ip_management.blacklist.includes(ip)) {
                    showMessage("IP is already in blacklist", "error");
                    return;
                }
                
                // Add to blacklist
                securitySettings.ip_management.blacklist.push(ip);
                
                // Remove from whitelist if present
                securitySettings.ip_management.whitelist = securitySettings.ip_management.whitelist.filter(i => i !== ip);
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    ip_management: securitySettings.ip_management,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                // Update UI
                document.getElementById('blacklist-container').innerHTML += `
                    <div class="ip-item">
                        <span>${ip}</span>
                        <button class="btn btn-danger remove-ip" data-ip="${ip}" data-list="blacklist">Remove</button>
                    </div>
                `;
                
                // Add event listener for remove button
                document.querySelectorAll('.remove-ip[data-ip="' + ip + '"][data-list="blacklist"]').forEach(button => {
                    button.addEventListener('click', removeIP);
                });
                
                showMessage("IP added to blacklist successfully", "success");
                
                // Reload security logs
                loadSecurityLogs(currentLogsPage);
            } catch (error) {
                console.error("Error adding IP to blacklist:", error);
                showMessage("Error adding IP to blacklist", "error");
            }
        }
        
        // Remove IP from whitelist or blacklist
        async function removeIP(e) {
            const ip = e.target.getAttribute('data-ip');
            const list = e.target.getAttribute('data-list');
            
            try {
                if (list === 'whitelist') {
                    // Remove from whitelist
                    securitySettings.ip_management.whitelist = securitySettings.ip_management.whitelist.filter(i => i !== ip);
                } else if (list === 'blacklist') {
                    // Remove from blacklist
                    securitySettings.ip_management.blacklist = securitySettings.ip_management.blacklist.filter(i => i !== ip);
                }
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    ip_management: securitySettings.ip_management,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                // Remove from UI
                e.target.parentElement.remove();
                
                showMessage(`IP removed from ${list} successfully`, "success");
            } catch (error) {
                console.error("Error removing IP:", error);
                showMessage("Error removing IP", "error");
            }
        }
        
        // Clear all blocked IPs
        async function clearBlockedIPs() {
            if (!confirm("Are you sure you want to clear all temporarily blocked IPs?")) {
                return;
            }
            
            try {
                // Get blocked IPs collection
                const blockedIPsQuery = query(collection(db, "blocked_ips"));
                const blockedIPsSnapshot = await getDocs(blockedIPsQuery);
                
                // Delete all documents
                for (const doc of blockedIPsSnapshot.docs) {
                    await deleteDoc(doc.ref);
                }
                
                showMessage("All temporarily blocked IPs cleared successfully", "success");
                
                // Update UI
                document.getElementById('blocked-ips-container').innerHTML = '<p>No temporarily blocked IPs</p>';
            } catch (error) {
                console.error("Error clearing blocked IPs:", error);
                showMessage("Error clearing blocked IPs", "error");
            }
        }
        
        // Delete security log
        async function deleteSecurityLog(logId) {
            if (!confirm("Are you sure you want to delete this log entry?")) {
                return;
            }
            
            try {
                await deleteDoc(doc(db, "security_logs", logId));
                
                showMessage("Log entry deleted successfully", "success");
                
                // Reload security logs
                loadSecurityLogs(currentLogsPage);
            } catch (error) {
                console.error("Error deleting log entry:", error);
                showMessage("Error deleting log entry", "error");
            }
        }
        
        // Clear all security logs
        async function clearSecurityLogs() {
            if (!confirm("Are you sure you want to clear all security logs? This action cannot be undone.")) {
                return;
            }
            
            try {
                // Get all logs
                const logsQuery = query(collection(db, "security_logs"));
                const logsSnapshot = await getDocs(logsQuery);
                
                // Delete all documents
                for (const doc of logsSnapshot.docs) {
                    await deleteDoc(doc.ref);
                }
                
                showMessage("All security logs cleared successfully", "success");
                
                // Reload security logs
                loadSecurityLogs();
            } catch (error) {
                console.error("Error clearing security logs:", error);
                showMessage("Error clearing security logs", "error");
            }
        }
        
        // Save log retention period
        async function saveLogRetention() {
            const retentionDays = parseInt(document.getElementById('log-retention').value);
            
            if (isNaN(retentionDays) || retentionDays < 1 || retentionDays > 365) {
                showMessage("Please enter a valid retention period (1-365 days)", "error");
                return;
            }
            
            try {
                // Update settings
                securitySettings.attack_logs.retention_days = retentionDays;
                
                // Save to Firestore
                await updateDoc(doc(db, "system", "security_settings"), {
                    attack_logs: securitySettings.attack_logs,
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                });
                
                showMessage("Log retention period saved successfully", "success");
            } catch (error) {
                console.error("Error saving log retention period:", error);
                showMessage("Error saving log retention period", "error");
            }
        }
        
        // Check .htaccess file
        async function checkHtaccess() {
            try {
                // Call PHP function through AJAX
                const response = await fetch('check-htaccess.php');
                const data = await response.json();
                
                const statusContainer = document.getElementById('htaccess-status');
                const issuesContainer = document.getElementById('htaccess-issues');
                
                if (data.exists) {
                    statusContainer.innerHTML = `
                        <div class="alert alert-success">
                            <p><strong>✓</strong> .htaccess file exists</p>
                        </div>
                    `;
                } else {
                    statusContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <p><strong>⚠</strong> .htaccess file does not exist</p>
                        </div>
                    `;
                }
                
                if (data.issues && data.issues.length > 0) {
                    let issuesHTML = '';
                    
                    data.issues.forEach(issue => {
                        issuesHTML += `
                            <div class="htaccess-issue">
                                <p>${issue}</p>
                            </div>
                        `;
                    });
                    
                    issuesContainer.innerHTML = issuesHTML;
                } else if (data.exists) {
                    issuesContainer.innerHTML = `
                        <div class="alert alert-success">
                            <p>No issues found in your .htaccess file</p>
                        </div>
                    `;
                } else {
                    issuesContainer.innerHTML = '';
                }
                
                return data;
            } catch (error) {
                console.error("Error checking .htaccess:", error);
                showMessage("Error checking .htaccess file", "error");
                return { exists: false, issues: ['Error checking .htaccess file'] };
            }
        }
        
        // Generate .htaccess file
        async function generateHtaccess() {
            try {
                // Get settings for .htaccess
                const options = {
                    force_https: true,
                    xss_protection: securitySettings.content_security.xss_protection,
                    content_type_options: securitySettings.content_security.content_type_options,
                    frame_options: securitySettings.content_security.frame_options,
                    referrer_policy: securitySettings.content_security.referrer_policy,
                    block_suspicious: securitySettings.firewall.block_suspicious_requests,
                    ip_blacklist: securitySettings.ip_management.blacklist,
                    country_blacklist: securitySettings.firewall.blocked_countries
                };
                
                // Call PHP function through AJAX
                const response = await fetch('generate-htaccess.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(options)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage("Secure .htaccess file generated successfully", "success");
                    
                    // Refresh .htaccess check
                    checkHtaccess();
                } else {
                    showMessage("Error generating .htaccess file: " + data.message, "error");
                }
            } catch (error) {
                console.error("Error generating .htaccess:", error);
                showMessage("Error generating .htaccess file", "error");
            }
        }
        
        // View current .htaccess file
        async function viewHtaccess() {
            try {
                // Call PHP function through AJAX
                const response = await fetch('view-htaccess.php');
                const data = await response.json();
                
                const contentContainer = document.getElementById('htaccess-content');
                
                if (data.exists) {
                    contentContainer.textContent = data.content;
                    contentContainer.style.display = 'block';
                } else {
                    contentContainer.textContent = '# .htaccess file does not exist';
                    contentContainer.style.display = 'block';
                }
            } catch (error) {
                console.error("Error viewing .htaccess:", error);
                showMessage("Error viewing .htaccess file", "error");
            }
        }
        
        // Populate country lists
        function populateCountryLists() {
            const allowedCountriesContainer = document.getElementById('allowed-countries');
            const blockedCountriesContainer = document.getElementById('blocked-countries');
            
            let allowedHTML = '';
            let blockedHTML = '';
            
            countries.forEach(country => {
                // Allowed countries
                allowedHTML += `
                    <div class="country-item">
                        <input type="checkbox" id="allow-${country.code}" value="${country.code}" 
                            ${securitySettings.firewall.allowed_countries.includes(country.code) ? 'checked' : ''}>
                        <label for="allow-${country.code}">
                            ${getCountryFlag(country.code)} ${country.name}
                        </label>
                    </div>
                `;
                
                // Blocked countries
                blockedHTML += `
                    <div class="country-item">
                        <input type="checkbox" id="block-${country.code}" value="${country.code}" 
                            ${securitySettings.firewall.blocked_countries.includes(country.code) ? 'checked' : ''}>
                        <label for="block-${country.code}">
                            ${getCountryFlag(country.code)} ${country.name}
                        </label>
                    </div>
                `;
            });
            
            allowedCountriesContainer.innerHTML = allowedHTML;
            blockedCountriesContainer.innerHTML = blockedHTML;
        }
        
        // Get country flag emoji
        function getCountryFlag(countryCode) {
            const codePoints = countryCode
                .toUpperCase()
                .split('')
                .map(char => 127397 + char.charCodeAt());
            return String.fromCodePoint(...codePoints);
        }
        
        // Validate IP address format
        function isValidIP(ip) {
            const regex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            if (!regex.test(ip)) return false;
            
            const parts = ip.split('.');
            for (let i = 0; i < 4; i++) {
                const part = parseInt(parts[i]);
                if (part < 0 || part > 255) return false;
            }
            
            return true;
        }
        
        // Show message
        function showMessage(message, type) {
            const messageElement = document.getElementById('message');
            messageElement.textContent = message;
            messageElement.className = `alert alert-${type}`;
            messageElement.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>