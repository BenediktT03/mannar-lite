<?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin or editor
function isAdminOrEditor() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    return $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor';
}

// Redirect if not admin or editor
if (!isAdminOrEditor()) {
    header('Location: login.php');
    exit;
}

// Load Google Analytics settings
function loadAnalyticsSettings() {
    $settings = getDocument('system', 'analytics_settings');
    if (!$settings) {
        return [
            'enabled' => false,
            'tracking_id' => '',
            'view_id' => '',
            'service_account' => '',
            'reports' => [
                'overview' => true,
                'audience' => true,
                'acquisition' => true,
                'behavior' => true,
                'conversions' => false
            ],
            'custom_metrics' => [],
            'date_range' => '30'
        ];
    }
    
    return $settings;
}

// Load Analytics data and report settings
$analyticsSettings = loadAnalyticsSettings();
$enabled = $analyticsSettings['enabled'] ?? false;
$trackingId = $analyticsSettings['tracking_id'] ?? '';
$viewId = $analyticsSettings['view_id'] ?? '';
$serviceAccount = $analyticsSettings['service_account'] ?? '';
$enabledReports = $analyticsSettings['reports'] ?? [];
$customMetrics = $analyticsSettings['custom_metrics'] ?? [];
$dateRange = $analyticsSettings['date_range'] ?? '30';

// Format date ranges for display
$dateRanges = [
    '7' => 'Last 7 days',
    '30' => 'Last 30 days',
    '90' => 'Last 90 days',
    '180' => 'Last 6 months',
    '365' => 'Last year',
    'custom' => 'Custom range'
];

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Get form data
        $enabled = isset($_POST['analytics_enabled']);
        $trackingId = $_POST['tracking_id'] ?? '';
        $viewId = $_POST['view_id'] ?? '';
        $serviceAccount = $_POST['service_account'] ?? '';
        $dateRange = $_POST['date_range'] ?? '30';
        
        // Get report settings
        $enabledReports = [
            'overview' => isset($_POST['report_overview']),
            'audience' => isset($_POST['report_audience']),
            'acquisition' => isset($_POST['report_acquisition']),
            'behavior' => isset($_POST['report_behavior']),
            'conversions' => isset($_POST['report_conversions'])
        ];
        
        // Get custom metrics
        $customMetrics = [];
        if (isset($_POST['metric_name']) && is_array($_POST['metric_name'])) {
            for ($i = 0; $i < count($_POST['metric_name']); $i++) {
                if (!empty($_POST['metric_name'][$i]) && isset($_POST['metric_expression'][$i])) {
                    $customMetrics[] = [
                        'name' => $_POST['metric_name'][$i],
                        'expression' => $_POST['metric_expression'][$i],
                        'enabled' => isset($_POST['metric_enabled'][$i])
                    ];
                }
            }
        }
        
        // Update settings
        $updatedSettings = [
            'enabled' => $enabled,
            'tracking_id' => $trackingId,
            'view_id' => $viewId,
            'service_account' => $serviceAccount,
            'reports' => $enabledReports,
            'custom_metrics' => $customMetrics,
            'date_range' => $dateRange,
            'updated_at' => date('c'),
            'updated_by' => $_SESSION['user_id'] ?? 'unknown'
        ];
        
        // Save settings to Firestore
        if (saveDocument('system', 'analytics_settings', $updatedSettings)) {
            $message = 'Analytics settings saved successfully.';
            $messageType = 'success';
            
            // Update local variables
            $analyticsSettings = $updatedSettings;
        } else {
            $message = 'Failed to save settings. Please try again.';
            $messageType = 'error';
        }
    }
}

// Check if Google Analytics is properly configured
$isConfigured = $enabled && !empty($trackingId) && !empty($viewId);

// API key is stored in .env or similar for local development
$googleMapsApiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <?php if (!empty($googleMapsApiKey)): ?>
    <!-- Google Maps for geographical data -->
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleMapsApiKey; ?>&libraries=visualization"></script>
    <?php endif; ?>
    <style>
        .analytics-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .analytics-container {
                grid-template-columns: 1fr;
            }
        }
        
        .analytics-sidebar {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
        }
        
        .analytics-content {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
        }
        
        .date-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .metrics-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .metric-change {
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .change-up {
            color: #198754;
        }
        
        .change-down {
            color: #dc3545;
        }
        
        .chart-container {
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            padding: 20px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-canvas {
            width: 100%;
            height: 300px;
        }
        
        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
            overflow-x: auto;
        }
        
        .tab-nav button {
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #495057;
            font-weight: 500;
            white-space: nowrap;
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
        
        .analytics-placeholder {
            padding: 50px 0;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
        
        .custom-metrics {
            margin-top: 20px;
        }
        
        .custom-metric-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .custom-metric-row input[type="text"] {
            flex: 1;
        }
        
        .add-metric-btn, .remove-metric-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            color: #666;
        }
        
        .remove-metric-btn {
            color: #dc3545;
        }
        
        .top-pages-table, .referrers-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .top-pages-table th, .top-pages-table td,
        .referrers-table th, .referrers-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .top-pages-table th, .referrers-table th {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-enabled {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-disabled {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .two-column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
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
                <li><a href="analytics-dashboard.php" class="active">Analytics</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Analytics Dashboard</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="analytics-container">
                <div class="analytics-sidebar">
                    <h2>Reports</h2>
                    <ul class="admin-menu">
                        <li><a href="#overview" class="report-link active" data-report="overview">Overview</a></li>
                        <li><a href="#audience" class="report-link" data-report="audience">Audience</a></li>
                        <li><a href="#acquisition" class="report-link" data-report="acquisition">Acquisition</a></li>
                        <li><a href="#behavior" class="report-link" data-report="behavior">Behavior</a></li>
                        <li><a href="#conversions" class="report-link" data-report="conversions">Conversions</a></li>
                    </ul>
                    
                    <h2>Settings</h2>
                    <ul class="admin-menu">
                        <li><a href="#settings" class="report-link" data-report="settings">Analytics Settings</a></li>
                    </ul>
                    
                    <div class="analytics-status">
                        <h3>Status</h3>
                        <?php if ($enabled): ?>
                            <div class="status-badge status-enabled">Analytics Enabled</div>
                        <?php else: ?>
                            <div class="status-badge status-disabled">Analytics Disabled</div>
                        <?php endif; ?>
                        
                        <?php if (!$isConfigured): ?>
                            <p class="text-warning" style="margin-top: 10px;">Analytics is not fully configured. Please update your settings.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="analytics-content">
                    <?php if (!$isConfigured): ?>
                        <div class="analytics-placeholder">
                            <h2>Google Analytics Not Configured</h2>
                            <p>Please configure your Google Analytics settings to view reports.</p>
                            <button class="btn btn-primary" id="goto-settings-btn">Configure Analytics</button>
                        </div>
                    <?php else: ?>
                        <div class="date-selector">
                            <div>
                                <label for="date-range">Date Range:</label>
                                <select id="date-range" class="form-select">
                                    <?php foreach ($dateRanges as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $value === $dateRange ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="custom-date-range" style="display: none;">
                                <label for="start-date">From:</label>
                                <input type="date" id="start-date" class="form-control">
                                <label for="end-date">To:</label>
                                <input type="date" id="end-date" class="form-control">
                            </div>
                            
                            <button id="refresh-data-btn" class="btn btn-primary">Refresh Data</button>
                        </div>
                        
                        <!-- Overview Report -->
                        <div id="overview-report" class="report-content active">
                            <div class="metrics-overview">
                                <div class="metric-card">
                                    <div class="metric-label">Users</div>
                                    <div class="metric-value" id="users-value">-</div>
                                    <div class="metric-change" id="users-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Sessions</div>
                                    <div class="metric-value" id="sessions-value">-</div>
                                    <div class="metric-change" id="sessions-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Pageviews</div>
                                    <div class="metric-value" id="pageviews-value">-</div>
                                    <div class="metric-change" id="pageviews-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Avg. Session Duration</div>
                                    <div class="metric-value" id="duration-value">-</div>
                                    <div class="metric-change" id="duration-change">-</div>
                                </div>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Audience Overview</h3>
                                    <div class="chart-actions">
                                        <select id="overview-metric-selector" class="form-select">
                                            <option value="users">Users</option>
                                            <option value="sessions">Sessions</option>
                                            <option value="pageviews">Pageviews</option>
                                        </select>
                                    </div>
                                </div>
                                <canvas id="overview-chart" class="chart-canvas"></canvas>
                            </div>
                            
                            <div class="two-column-grid">
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Top Pages</h3>
                                    </div>
                                    <table class="top-pages-table" id="top-pages-table">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th>Pageviews</th>
                                                <th>Avg. Time on Page</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center">Loading data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Traffic Sources</h3>
                                    </div>
                                    <canvas id="traffic-sources-chart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Audience Report -->
                        <div id="audience-report" class="report-content">
                            <div class="tab-container">
                                <div class="tab-nav">
                                    <button class="tab-button active" data-tab="demographics">Demographics</button>
                                    <button class="tab-button" data-tab="geo">Geography</button>
                                    <button class="tab-button" data-tab="technology">Technology</button>
                                    <button class="tab-button" data-tab="behavior">Behavior</button>
                                </div>
                                
                                <!-- Demographics Tab -->
                                <div id="demographics-tab" class="tab-content active">
                                    <div class="two-column-grid">
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Age Distribution</h3>
                                            </div>
                                            <canvas id="age-chart" class="chart-canvas"></canvas>
                                        </div>
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Gender Distribution</h3>
                                            </div>
                                            <canvas id="gender-chart" class="chart-canvas"></canvas>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <div class="chart-header">
                                            <h3 class="chart-title">Interests</h3>
                                        </div>
                                        <canvas id="interests-chart" class="chart-canvas"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Geography Tab -->
                                <div id="geo-tab" class="tab-content">
                                    <div class="chart-container">
                                        <div class="chart-header">
                                            <h3 class="chart-title">Users by Country</h3>
                                        </div>
                                        <?php if (!empty($googleMapsApiKey)): ?>
                                            <div id="world-map" class="map-container"></div>
                                        <?php else: ?>
                                            <canvas id="countries-chart" class="chart-canvas"></canvas>
                                        <?php endif; ?>
                                    </div>
                                    <div class="two-column-grid">
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Top Countries</h3>
                                            </div>
                                            <table class="top-pages-table" id="top-countries-table">
                                                <thead>
                                                    <tr>
                                                        <th>Country</th>
                                                        <th>Users</th>
                                                        <th>% of Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Loading data...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Top Cities</h3>
                                            </div>
                                            <table class="top-pages-table" id="top-cities-table">
                                                <thead>
                                                    <tr>
                                                        <th>City</th>
                                                        <th>Users</th>
                                                        <th>% of Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Loading data...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Technology Tab -->
                                <div id="technology-tab" class="tab-content">
                                    <div class="two-column-grid">
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Browsers</h3>
                                            </div>
                                            <canvas id="browsers-chart" class="chart-canvas"></canvas>
                                        </div>
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Operating Systems</h3>
                                            </div>
                                            <canvas id="os-chart" class="chart-canvas"></canvas>
                                        </div>
                                    </div>
                                    <div class="two-column-grid">
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Device Categories</h3>
                                            </div>
                                            <canvas id="devices-chart" class="chart-canvas"></canvas>
                                        </div>
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Screen Resolutions</h3>
                                            </div>
                                            <canvas id="resolutions-chart" class="chart-canvas"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Behavior Tab -->
                                <div id="behavior-tab" class="tab-content">
                                    <div class="two-column-grid">
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">New vs Returning Users</h3>
                                            </div>
                                            <canvas id="new-returning-chart" class="chart-canvas"></canvas>
                                        </div>
                                        <div class="chart-container">
                                            <div class="chart-header">
                                                <h3 class="chart-title">Frequency & Recency</h3>
                                            </div>
                                            <canvas id="frequency-chart" class="chart-canvas"></canvas>
                                        </div>
                                    </div>
                                    <div class="chart-container">
                                        <div class="chart-header">
                                            <h3 class="chart-title">Session Duration</h3>
                                        </div>
                                        <canvas id="duration-chart" class="chart-canvas"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acquisition Report -->
                        <div id="acquisition-report" class="report-content">
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Acquisition Channels</h3>
                                </div>
                                <canvas id="channels-chart" class="chart-canvas"></canvas>
                            </div>
                            <div class="two-column-grid">
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Top Referrers</h3>
                                    </div>
                                    <table class="referrers-table" id="top-referrers-table">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th>Users</th>
                                                <th>New Users</th>
                                                <th>Sessions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="4" class="text-center">Loading data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Social Networks</h3>
                                    </div>
                                    <canvas id="social-chart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Campaign Performance</h3>
                                </div>
                                <table class="referrers-table" id="campaigns-table">
                                    <thead>
                                        <tr>
                                            <th>Campaign</th>
                                            <th>Users</th>
                                            <th>Sessions</th>
                                            <th>Bounce Rate</th>
                                            <th>Conversion Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">Loading data...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Behavior Report -->
                        <div id="behavior-report" class="report-content">
                            <div class="metrics-overview">
                                <div class="metric-card">
                                    <div class="metric-label">Pageviews</div>
                                    <div class="metric-value" id="bh-pageviews-value">-</div>
                                    <div class="metric-change" id="bh-pageviews-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Pages / Session</div>
                                    <div class="metric-value" id="pages-per-session-value">-</div>
                                    <div class="metric-change" id="pages-per-session-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Avg. Time on Page</div>
                                    <div class="metric-value" id="avg-time-on-page-value">-</div>
                                    <div class="metric-change" id="avg-time-on-page-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Bounce Rate</div>
                                    <div class="metric-value" id="bounce-rate-value">-</div>
                                    <div class="metric-change" id="bounce-rate-change">-</div>
                                </div>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Pageviews Over Time</h3>
                                </div>
                                <canvas id="pageviews-chart" class="chart-canvas"></canvas>
                            </div>
                            
                            <div class="two-column-grid">
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Top Pages</h3>
                                    </div>
                                    <table class="top-pages-table" id="behavior-pages-table">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th>Pageviews</th>
                                                <th>Unique Pageviews</th>
                                                <th>Avg. Time on Page</th>
                                                <th>Bounce Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center">Loading data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Exit Pages</h3>
                                    </div>
                                    <table class="top-pages-table" id="exit-pages-table">
                                        <thead>
                                            <tr>
                                                <th>Page</th>
                                                <th>Exits</th>
                                                <th>Pageviews</th>
                                                <th>Exit Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="4" class="text-center">Loading data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Site Speed</h3>
                                </div>
                                <div class="two-column-grid">
                                    <canvas id="page-load-chart" class="chart-canvas"></canvas>
                                    <canvas id="server-response-chart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conversions Report -->
                        <div id="conversions-report" class="report-content">
                            <div class="metrics-overview">
                                <div class="metric-card">
                                    <div class="metric-label">Conversions</div>
                                    <div class="metric-value" id="conversions-value">-</div>
                                    <div class="metric-change" id="conversions-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Conversion Rate</div>
                                    <div class="metric-value" id="conversion-rate-value">-</div>
                                    <div class="metric-change" id="conversion-rate-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Goal Value</div>
                                    <div class="metric-value" id="goal-value-value">-</div>
                                    <div class="metric-change" id="goal-value-change">-</div>
                                </div>
                                <div class="metric-card">
                                    <div class="metric-label">Avg. Value</div>
                                    <div class="metric-value" id="avg-value-value">-</div>
                                    <div class="metric-change" id="avg-value-change">-</div>
                                </div>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 class="chart-title">Goal Completions</h3>
                                </div>
                                <canvas id="goals-chart" class="chart-canvas"></canvas>
                            </div>
                            
                            <div class="two-column-grid">
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Goal Conversion Rate by Channel</h3>
                                    </div>
                                    <canvas id="goal-channels-chart" class="chart-canvas"></canvas>
                                </div>
                                <div class="chart-container">
                                    <div class="chart-header">
                                        <h3 class="chart-title">Goal Conversion Rate by Device</h3>
                                    </div>
                                    <canvas id="goal-devices-chart" class="chart-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Settings -->
                        <div id="settings-report" class="report-content">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="analytics_enabled" <?php echo $enabled ? 'checked' : ''; ?>>
                                        Enable Google Analytics
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tracking-id">Google Analytics Tracking ID</label>
                                    <input type="text" id="tracking-id" name="tracking_id" value="<?php echo htmlspecialchars($trackingId); ?>" placeholder="UA-XXXXXXXXX-X or G-XXXXXXXXXX">
                                    <p class="help-text">Your Google Analytics tracking ID (e.g., UA-XXXXXXXXX-X or G-XXXXXXXXXX)</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="view-id">Google Analytics View ID</label>
                                    <input type="text" id="view-id" name="view_id" value="<?php echo htmlspecialchars($viewId); ?>" placeholder="XXXXXXXXX">
                                    <p class="help-text">Your Google Analytics View ID for reporting</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="service-account">Service Account JSON</label>
                                    <textarea id="service-account" name="service_account" rows="6" placeholder="{&#34;type&#34;: &#34;service_account&#34;, ...}"><?php echo htmlspecialchars($serviceAccount); ?></textarea>
                                    <p class="help-text">Google Service Account JSON for API access (optional, for enhanced reporting)</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date-range-setting">Default Date Range</label>
                                    <select id="date-range-setting" name="date_range">
                                        <?php foreach ($dateRanges as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo $value === $dateRange ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="help-text">Default date range for analytics reports</p>
                                </div>
                                
                                <h3>Enable/Disable Reports</h3>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="report_overview" <?php echo isset($enabledReports['overview']) && $enabledReports['overview'] ? 'checked' : ''; ?>>
                                        Overview
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="report_audience" <?php echo isset($enabledReports['audience']) && $enabledReports['audience'] ? 'checked' : ''; ?>>
                                        Audience
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="report_acquisition" <?php echo isset($enabledReports['acquisition']) && $enabledReports['acquisition'] ? 'checked' : ''; ?>>
                                        Acquisition
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="report_behavior" <?php echo isset($enabledReports['behavior']) && $enabledReports['behavior'] ? 'checked' : ''; ?>>
                                        Behavior
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="report_conversions" <?php echo isset($enabledReports['conversions']) && $enabledReports['conversions'] ? 'checked' : ''; ?>>
                                        Conversions
                                    </label>
                                </div>
                                
                                <h3>Custom Metrics</h3>
                                <p>Define custom metrics to track specific data points</p>
                                
                                <div id="custom-metrics-container">
                                    <?php if (empty($customMetrics)): ?>
                                        <div class="custom-metric-row">
                                            <input type="checkbox" name="metric_enabled[]" checked>
                                            <input type="text" name="metric_name[]" placeholder="Metric Name">
                                            <input type="text" name="metric_expression[]" placeholder="Expression (e.g., ga:sessions/ga:users)">
                                            <button type="button" class="remove-metric-btn" title="Remove metric">×</button>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($customMetrics as $metric): ?>
                                            <div class="custom-metric-row">
                                                <input type="checkbox" name="metric_enabled[]" <?php echo isset($metric['enabled']) && $metric['enabled'] ? 'checked' : ''; ?>>
                                                <input type="text" name="metric_name[]" value="<?php echo htmlspecialchars($metric['name']); ?>" placeholder="Metric Name">
                                                <input type="text" name="metric_expression[]" value="<?php echo htmlspecialchars($metric['expression']); ?>" placeholder="Expression (e.g., ga:sessions/ga:users)">
                                                <button type="button" class="remove-metric-btn" title="Remove metric">×</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" id="add-metric-btn" class="add-metric-btn">+ Add Custom Metric</button>
                                
                                <div class="form-group" style="margin-top: 20px;">
                                    <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
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
        import { getFirestore } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Initialize charts
        const charts = {};
        
        // Sample data (would be replaced with real data from Google Analytics API)
        const sampleData = {
            overviewChart: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: {
                    users: [1500, 1800, 2200, 1900, 2400, 2800, 3200],
                    sessions: [2300, 2600, 3100, 2800, 3500, 4000, 4600],
                    pageviews: [5600, 6200, 7500, 6800, 8200, 9500, 10800]
                }
            },
            topPages: [
                { page: '/home', pageviews: 3250, avgTime: '00:02:15' },
                { page: '/products', pageviews: 2180, avgTime: '00:01:48' },
                { page: '/blog/top-10-tips', pageviews: 1950, avgTime: '00:03:22' },
                { page: '/about-us', pageviews: 1250, avgTime: '00:01:32' },
                { page: '/contact', pageviews: 980, avgTime: '00:01:15' }
            ],
            trafficSources: {
                labels: ['Organic', 'Direct', 'Referral', 'Social', 'Email', 'Paid Search'],
                data: [42, 25, 15, 10, 5, 3]
            }
        };
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            const isConfigured = <?php echo $isConfigured ? 'true' : 'false'; ?>;
            
            // Setup navigation
            setupNavigation();
            
            // Setup settings page events
            setupSettingsEvents();
            
            // If analytics is configured, initialize reports
            if (isConfigured) {
                // Setup date range selector
                setupDateRange();
                
                // Load overview report (default view)
                loadOverviewReport();
                
                // Initialize all charts with sample data
                initCharts();
            } else {
                // Add click event to settings button
                const gotoSettingsBtn = document.getElementById('goto-settings-btn');
                if (gotoSettingsBtn) {
                    gotoSettingsBtn.addEventListener('click', () => {
                        document.querySelectorAll('.report-link').forEach(link => {
                            if (link.getAttribute('data-report') === 'settings') {
                                link.click();
                            }
                        });
                    });
                }
            }
        });
        
        // Setup navigation
        function setupNavigation() {
            // Report navigation
            document.querySelectorAll('.report-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active class
                    document.querySelectorAll('.report-link').forEach(l => {
                        l.classList.remove('active');
                    });
                    link.classList.add('active');
                    
                    // Hide all report content
                    document.querySelectorAll('.report-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show selected report
                    const reportId = link.getAttribute('data-report');
                    const reportElement = document.getElementById(reportId + '-report');
                    if (reportElement) {
                        reportElement.classList.add('active');
                    }
                    
                    // Load report data if needed
                    if (reportId === 'overview') {
                        loadOverviewReport();
                    } else if (reportId === 'audience') {
                        loadAudienceReport();
                    } else if (reportId === 'acquisition') {
                        loadAcquisitionReport();
                    } else if (reportId === 'behavior') {
                        loadBehaviorReport();
                    } else if (reportId === 'conversions') {
                        loadConversionsReport();
                    }
                });
            });
            
            // Tab navigation for audience report
            document.querySelectorAll('.tab-button').forEach(button => {
                button.addEventListener('click', () => {
                    // Update active class for buttons
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    button.classList.add('active');
                    
                    // Hide all tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show selected tab content
                    const tabId = button.getAttribute('data-tab');
                    const tabElement = document.getElementById(tabId + '-tab');
                    if (tabElement) {
                        tabElement.classList.add('active');
                    }
                });
            });
        }
        
        // Setup date range selector
        function setupDateRange() {
            const dateRangeSelect = document.getElementById('date-range');
            const customDateRange = document.getElementById('custom-date-range');
            const refreshDataBtn = document.getElementById('refresh-data-btn');
            
            // Show/hide custom date inputs based on selection
            dateRangeSelect.addEventListener('change', () => {
                if (dateRangeSelect.value === 'custom') {
                    customDateRange.style.display = 'block';
                } else {
                    customDateRange.style.display = 'none';
                }
            });
            
            // Initialize date inputs with sensible defaults
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            
            if (startDateInput && endDateInput) {
                startDateInput.valueAsDate = startDate;
                endDateInput.valueAsDate = endDate;
            }
            
            // Refresh data button
            refreshDataBtn.addEventListener('click', () => {
                const activeReport = document.querySelector('.report-content.active');
                if (activeReport) {
                    const reportId = activeReport.id.replace('-report', '');
                    
                    // Reload the active report
                    if (reportId === 'overview') {
                        loadOverviewReport();
                    } else if (reportId === 'audience') {
                        loadAudienceReport();
                    } else if (reportId === 'acquisition') {
                        loadAcquisitionReport();
                    } else if (reportId === 'behavior') {
                        loadBehaviorReport();
                    } else if (reportId === 'conversions') {
                        loadConversionsReport();
                    }
                }
            });
        }
        
        // Setup settings page events
        function setupSettingsEvents() {
            // Add custom metric
            const addMetricBtn = document.getElementById('add-metric-btn');
            if (addMetricBtn) {
                addMetricBtn.addEventListener('click', () => {
                    const container = document.getElementById('custom-metrics-container');
                    if (container) {
                        const metricRow = document.createElement('div');
                        metricRow.className = 'custom-metric-row';
                        metricRow.innerHTML = `
                            <input type="checkbox" name="metric_enabled[]" checked>
                            <input type="text" name="metric_name[]" placeholder="Metric Name">
                            <input type="text" name="metric_expression[]" placeholder="Expression (e.g., ga:sessions/ga:users)">
                            <button type="button" class="remove-metric-btn" title="Remove metric">×</button>
                        `;
                        container.appendChild(metricRow);
                        
                        // Add event listener to remove button
                        const removeBtn = metricRow.querySelector('.remove-metric-btn');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', () => {
                                metricRow.remove();
                            });
                        }
                    }
                });
            }
            
            // Remove custom metric
            document.querySelectorAll('.remove-metric-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const row = button.closest('.custom-metric-row');
                    if (row) {
                        row.remove();
                    }
                });
            });
        }
        
        // Load overview report
        function loadOverviewReport() {
            // In a real implementation, this would fetch data from Google Analytics API
            // For now, we'll use sample data
            
            // Update metrics
            updateMetric('users', 15432, 8.5);
            updateMetric('sessions', 22567, 12.3);
            updateMetric('pageviews', 68421, 15.7);
            updateMetric('duration', '2:35', -3.2);
            
            // Update overview chart
            updateOverviewChart('users');
            
            // Set up metric selector for overview chart
            const metricSelector = document.getElementById('overview-metric-selector');
            if (metricSelector) {
                metricSelector.addEventListener('change', () => {
                    updateOverviewChart(metricSelector.value);
                });
            }
            
            // Update top pages table
            updateTopPagesTable();
            
            // Update traffic sources chart
            updateTrafficSourcesChart();
        }
        
        // Load audience report
        function loadAudienceReport() {
            // In a real implementation, this would fetch data from Google Analytics API
            // For now, we'll initialize charts with sample data
            
            // Initialize demographics charts
            if (!charts.ageChart) {
                initializeAgeChart();
            }
            
            if (!charts.genderChart) {
                initializeGenderChart();
            }
            
            if (!charts.interestsChart) {
                initializeInterestsChart();
            }
            
            // Initialize geography charts
            if (!charts.countriesChart) {
                initializeCountriesChart();
            }
            
            // Initialize technology charts
            if (!charts.browsersChart) {
                initializeBrowsersChart();
            }
            
            if (!charts.osChart) {
                initializeOsChart();
            }
            
            if (!charts.devicesChart) {
                initializeDevicesChart();
            }
            
            if (!charts.resolutionsChart) {
                initializeResolutionsChart();
            }
            
            // Initialize behavior charts
            if (!charts.newReturningChart) {
                initializeNewReturningChart();
            }
            
            if (!charts.frequencyChart) {
                initializeFrequencyChart();
            }
            
            if (!charts.durationChart) {
                initializeDurationChart();
            }
            
            // Update tables
            updateTopCountriesTable();
            updateTopCitiesTable();
        }
        
        // Load acquisition report
        function loadAcquisitionReport() {
            // Initialize channels chart
            if (!charts.channelsChart) {
                initializeChannelsChart();
            }
            
            // Initialize social chart
            if (!charts.socialChart) {
                initializeSocialChart();
            }
            
            // Update tables
            updateTopReferrersTable();
            updateCampaignsTable();
        }
        
        // Load behavior report
        function loadBehaviorReport() {
            // Update metrics
            updateMetric('bh-pageviews', 68421, 15.7);
            updateMetric('pages-per-session', 3.2, 5.3);
            updateMetric('avg-time-on-page', '1:45', 2.1);
            updateMetric('bounce-rate', '32.5%', -4.8);
            
            // Initialize pageviews chart
            if (!charts.pageviewsChart) {
                initializePageviewsChart();
            }
            
            // Initialize page load charts
            if (!charts.pageLoadChart) {
                initializePageLoadChart();
            }
            
            if (!charts.serverResponseChart) {
                initializeServerResponseChart();
            }
            
            // Update tables
            updateBehaviorPagesTable();
            updateExitPagesTable();
        }
        
        // Load conversions report
        function loadConversionsReport() {
            // Update metrics
            updateMetric('conversions', 1250, 22.4);
            updateMetric('conversion-rate', '5.8%', 1.7);
            updateMetric('goal-value', '$15,625', 28.3);
            updateMetric('avg-value', '$12.50', 4.8);
            
            // Initialize goals chart
            if (!charts.goalsChart) {
                initializeGoalsChart();
            }
            
            // Initialize goal channels chart
            if (!charts.goalChannelsChart) {
                initializeGoalChannelsChart();
            }
            
            // Initialize goal devices chart
            if (!charts.goalDevicesChart) {
                initializeGoalDevicesChart();
            }
        }
        
        // Update metric display
        function updateMetric(id, value, change) {
            const valueElement = document.getElementById(`${id}-value`);
            const changeElement = document.getElementById(`${id}-change`);
            
            if (valueElement) {
                valueElement.textContent = value;
            }
            
            if (changeElement) {
                const sign = change >= 0 ? '+' : '';
                changeElement.textContent = `${sign}${change}%`;
                changeElement.className = `metric-change ${change >= 0 ? 'change-up' : 'change-down'}`;
            }
        }
        
        // Update overview chart
        function updateOverviewChart(metricType) {
            const chartCanvas = document.getElementById('overview-chart');
            if (!chartCanvas) return;
            
            // Destroy existing chart if it exists
            if (charts.overviewChart) {
                charts.overviewChart.destroy();
            }
            
            // Create new chart
            charts.overviewChart = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: sampleData.overviewChart.labels,
                    datasets: [{
                        label: metricType.charAt(0).toUpperCase() + metricType.slice(1),
                        data: sampleData.overviewChart.datasets[metricType],
                        borderColor: '#0077b6',
                        backgroundColor: 'rgba(0, 119, 182, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Update top pages table
        function updateTopPagesTable() {
            const tableBody = document.querySelector('#top-pages-table tbody');
            if (!tableBody) return;
            
            let html = '';
            
            sampleData.topPages.forEach(page => {
                html += `
                    <tr>
                        <td>${page.page}</td>
                        <td>${page.pageviews}</td>
                        <td>${page.avgTime}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        // Update traffic sources chart
        function updateTrafficSourcesChart() {
            const chartCanvas = document.getElementById('traffic-sources-chart');
            if (!chartCanvas) return;
            
            // Create chart
            charts.trafficSourcesChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: sampleData.trafficSources.labels,
                    datasets: [{
                        data: sampleData.trafficSources.data,
                        backgroundColor: [
                            '#0077b6',
                            '#00b4d8',
                            '#90e0ef',
                            '#ade8f4',
                            '#caf0f8',
                            '#e6f5f9'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
        
        // Initialize all charts with sample data
        function initCharts() {
            // The real implementation would load data from the Google Analytics API
            // and initialize charts with actual data
        }
        
        // Sample chart initializations
        function initializeAgeChart() {
            const chartCanvas = document.getElementById('age-chart');
            if (!chartCanvas) return;
            
            charts.ageChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
                    datasets: [{
                        label: 'Users by Age',
                        data: [1200, 2800, 2100, 1500, 800, 400],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeGenderChart() {
            const chartCanvas = document.getElementById('gender-chart');
            if (!chartCanvas) return;
            
            charts.genderChart = new Chart(chartCanvas, {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female', 'Unknown'],
                    datasets: [{
                        data: [48, 42, 10],
                        backgroundColor: ['#0077b6', '#00b4d8', '#caf0f8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function initializeInterestsChart() {
            const chartCanvas = document.getElementById('interests-chart');
            if (!chartCanvas) return;
            
            charts.interestsChart = new Chart(chartCanvas, {
                type: 'horizontalBar',
                data: {
                    labels: ['Technology', 'Travel', 'Food & Drink', 'Sports', 'Shopping', 'News', 'Entertainment'],
                    datasets: [{
                        label: 'Interest Affinity',
                        data: [85, 65, 55, 40, 70, 30, 60],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeCountriesChart() {
            const chartCanvas = document.getElementById('countries-chart');
            if (chartCanvas) {
                charts.countriesChart = new Chart(chartCanvas, {
                    type: 'bar',
                    data: {
                        labels: ['United States', 'United Kingdom', 'Canada', 'Germany', 'France', 'Australia', 'India', 'Other'],
                        datasets: [{
                            label: 'Users by Country',
                            data: [5200, 2100, 1800, 1200, 950, 800, 750, 2630],
                            backgroundColor: '#0077b6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
            
            // If Google Maps API is available, initialize map
            if (typeof google !== 'undefined' && google.maps && document.getElementById('world-map')) {
                // Map implementation would go here
                // This would show a heatmap of user locations
            }
        }
        
        // Update tables
        function updateTopCountriesTable() {
            const tableBody = document.querySelector('#top-countries-table tbody');
            if (!tableBody) return;
            
            const sampleCountries = [
                { country: 'United States', users: 5200, percentage: 33.7 },
                { country: 'United Kingdom', users: 2100, percentage: 13.6 },
                { country: 'Canada', users: 1800, percentage: 11.7 },
                { country: 'Germany', users: 1200, percentage: 7.8 },
                { country: 'France', users: 950, percentage: 6.2 }
            ];
            
            let html = '';
            
            sampleCountries.forEach(country => {
                html += `
                    <tr>
                        <td>${country.country}</td>
                        <td>${country.users}</td>
                        <td>${country.percentage}%</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function updateTopCitiesTable() {
            const tableBody = document.querySelector('#top-cities-table tbody');
            if (!tableBody) return;
            
            const sampleCities = [
                { city: 'New York', users: 1250, percentage: 8.1 },
                { city: 'London', users: 980, percentage: 6.4 },
                { city: 'Los Angeles', users: 850, percentage: 5.5 },
                { city: 'Toronto', users: 720, percentage: 4.7 },
                { city: 'Chicago', users: 680, percentage: 4.4 }
            ];
            
            let html = '';
            
            sampleCities.forEach(city => {
                html += `
                    <tr>
                        <td>${city.city}</td>
                        <td>${city.users}</td>
                        <td>${city.percentage}%</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function initializeBrowsersChart() {
            const chartCanvas = document.getElementById('browsers-chart');
            if (!chartCanvas) return;
            
            charts.browsersChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Chrome', 'Safari', 'Firefox', 'Edge', 'Other'],
                    datasets: [{
                        data: [58, 20, 12, 8, 2],
                        backgroundColor: ['#0077b6', '#00b4d8', '#90e0ef', '#ade8f4', '#caf0f8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function initializeOsChart() {
            const chartCanvas = document.getElementById('os-chart');
            if (!chartCanvas) return;
            
            charts.osChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Windows', 'macOS', 'iOS', 'Android', 'Linux', 'Other'],
                    datasets: [{
                        data: [35, 25, 18, 15, 5, 2],
                        backgroundColor: ['#0077b6', '#00b4d8', '#90e0ef', '#ade8f4', '#caf0f8', '#e6f5f9']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function initializeDevicesChart() {
            const chartCanvas = document.getElementById('devices-chart');
            if (!chartCanvas) return;
            
            charts.devicesChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: [45, 48, 7],
                        backgroundColor: ['#0077b6', '#00b4d8', '#90e0ef']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function initializeResolutionsChart() {
            const chartCanvas = document.getElementById('resolutions-chart');
            if (!chartCanvas) return;
            
            charts.resolutionsChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['1920x1080', '1366x768', '360x640', '414x896', '1536x864', 'Other'],
                    datasets: [{
                        label: 'Screen Resolutions',
                        data: [28, 15, 12, 10, 8, 27],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeNewReturningChart() {
            const chartCanvas = document.getElementById('new-returning-chart');
            if (!chartCanvas) return;
            
            charts.newReturningChart = new Chart(chartCanvas, {
                type: 'pie',
                data: {
                    labels: ['New Visitors', 'Returning Visitors'],
                    datasets: [{
                        data: [65, 35],
                        backgroundColor: ['#0077b6', '#00b4d8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function initializeFrequencyChart() {
            const chartCanvas = document.getElementById('frequency-chart');
            if (!chartCanvas) return;
            
            charts.frequencyChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['1', '2', '3', '4', '5-8', '9-14', '15-30', '31+'],
                    datasets: [{
                        label: 'Sessions per User',
                        data: [65, 15, 8, 5, 4, 2, 0.8, 0.2],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeDurationChart() {
            const chartCanvas = document.getElementById('duration-chart');
            if (!chartCanvas) return;
            
            charts.durationChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['0-10s', '11-30s', '31-60s', '1-3min', '3-10min', '10-30min', '30+min'],
                    datasets: [{
                        label: 'Session Duration',
                        data: [25, 18, 15, 20, 12, 8, 2],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeChannelsChart() {
            const chartCanvas = document.getElementById('channels-chart');
            if (!chartCanvas) return;
            
            charts.channelsChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Organic Search', 'Direct', 'Referral', 'Social', 'Email', 'Paid Search', 'Other'],
                    datasets: [{
                        label: 'Sessions',
                        data: [9500, 6800, 3400, 2300, 1200, 800, 200],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeSocialChart() {
            const chartCanvas = document.getElementById('social-chart');
            if (!chartCanvas) return;
            
            charts.socialChart = new Chart(chartCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Facebook', 'Twitter', 'LinkedIn', 'Instagram', 'Pinterest', 'Other'],
                    datasets: [{
                        data: [45, 25, 15, 10, 4, 1],
                        backgroundColor: ['#0077b6', '#00b4d8', '#90e0ef', '#ade8f4', '#caf0f8', '#e6f5f9']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function updateTopReferrersTable() {
            const tableBody = document.querySelector('#top-referrers-table tbody');
            if (!tableBody) return;
            
            const sampleReferrers = [
                { source: 'google.com', users: 8500, newUsers: 5525, sessions: 9500 },
                { source: 'facebook.com', users: 1800, newUsers: 1260, sessions: 2100 },
                { source: 'twitter.com', users: 950, newUsers: 665, sessions: 1100 },
                { source: 'linkedin.com', users: 650, newUsers: 390, sessions: 720 },
                { source: 'example.org', users: 450, newUsers: 270, sessions: 480 }
            ];
            
            let html = '';
            
            sampleReferrers.forEach(referrer => {
                html += `
                    <tr>
                        <td>${referrer.source}</td>
                        <td>${referrer.users}</td>
                        <td>${referrer.newUsers}</td>
                        <td>${referrer.sessions}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function updateCampaignsTable() {
            const tableBody = document.querySelector('#campaigns-table tbody');
            if (!tableBody) return;
            
            const sampleCampaigns = [
                { campaign: 'Summer Sale 2023', users: 2500, sessions: 3200, bounceRate: '28.5%', conversionRate: '4.8%' },
                { campaign: 'Email Newsletter', users: 1800, sessions: 2100, bounceRate: '32.1%', conversionRate: '3.2%' },
                { campaign: 'Product Launch', users: 1500, sessions: 1850, bounceRate: '25.3%', conversionRate: '5.5%' },
                { campaign: 'Holiday Promotion', users: 1200, sessions: 1450, bounceRate: '29.7%', conversionRate: '4.1%' },
                { campaign: 'Blog Outreach', users: 850, sessions: 980, bounceRate: '35.2%', conversionRate: '2.8%' }
            ];
            
            let html = '';
            
            sampleCampaigns.forEach(campaign => {
                html += `
                    <tr>
                        <td>${campaign.campaign}</td>
                        <td>${campaign.users}</td>
                        <td>${campaign.sessions}</td>
                        <td>${campaign.bounceRate}</td>
                        <td>${campaign.conversionRate}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function initializePageviewsChart() {
            const chartCanvas = document.getElementById('pageviews-chart');
            if (!chartCanvas) return;
            
            charts.pageviewsChart = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Pageviews',
                        data: [5600, 6200, 7500, 6800, 8200, 9500, 10800],
                        borderColor: '#0077b6',
                        backgroundColor: 'rgba(0, 119, 182, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        function updateBehaviorPagesTable() {
            const tableBody = document.querySelector('#behavior-pages-table tbody');
            if (!tableBody) return;
            
            const samplePages = [
                { page: '/home', pageviews: 12500, uniquePageviews: 9800, avgTime: '00:01:45', bounceRate: '35.2%' },
                { page: '/products', pageviews: 8700, uniquePageviews: 6500, avgTime: '00:02:15', bounceRate: '28.7%' },
                { page: '/blog', pageviews: 6200, uniquePageviews: 4800, avgTime: '00:03:10', bounceRate: '25.3%' },
                { page: '/about-us', pageviews: 3500, uniquePageviews: 3200, avgTime: '00:01:30', bounceRate: '42.1%' },
                { page: '/contact', pageviews: 2800, uniquePageviews: 2600, avgTime: '00:01:05', bounceRate: '38.5%' }
            ];
            
            let html = '';
            
            samplePages.forEach(page => {
                html += `
                    <tr>
                        <td>${page.page}</td>
                        <td>${page.pageviews}</td>
                        <td>${page.uniquePageviews}</td>
                        <td>${page.avgTime}</td>
                        <td>${page.bounceRate}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function updateExitPagesTable() {
            const tableBody = document.querySelector('#exit-pages-table tbody');
            if (!tableBody) return;
            
            const samplePages = [
                { page: '/thank-you', exits: 1850, pageviews: 2100, exitRate: '88.1%' },
                { page: '/contact', exits: 1250, pageviews: 2800, exitRate: '44.6%' },
                { page: '/products/checkout', exits: 980, pageviews: 1500, exitRate: '65.3%' },
                { page: '/blog/popular-post', exits: 750, pageviews: 3200, exitRate: '23.4%' },
                { page: '/about-us', exits: 650, pageviews: 3500, exitRate: '18.6%' }
            ];
            
            let html = '';
            
            samplePages.forEach(page => {
                html += `
                    <tr>
                        <td>${page.page}</td>
                        <td>${page.exits}</td>
                        <td>${page.pageviews}</td>
                        <td>${page.exitRate}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        function initializePageLoadChart() {
            const chartCanvas = document.getElementById('page-load-chart');
            if (!chartCanvas) return;
            
            charts.pageLoadChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['0-1s', '1-3s', '3-7s', '7-10s', '10+s'],
                    datasets: [{
                        label: 'Page Load Time',
                        data: [15, 45, 25, 10, 5],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Page Load Time'
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeServerResponseChart() {
            const chartCanvas = document.getElementById('server-response-chart');
            if (!chartCanvas) return;
            
            charts.serverResponseChart = new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    datasets: [{
                        label: 'Server Response Time (ms)',
                        data: [250, 235, 245, 210, 200, 215, 195],
                        borderColor: '#0077b6',
                        backgroundColor: 'rgba(0, 119, 182, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Server Response Time'
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeGoalsChart() {
            const chartCanvas = document.getElementById('goals-chart');
            if (!chartCanvas) return;
            
            charts.goalsChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Goal 1: Sign Up', 'Goal 2: Purchase', 'Goal 3: Contact Form', 'Goal 4: Newsletter', 'Goal 5: Download'],
                    datasets: [{
                        label: 'Completions',
                        data: [580, 350, 220, 625, 180],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeGoalChannelsChart() {
            const chartCanvas = document.getElementById('goal-channels-chart');
            if (!chartCanvas) return;
            
            charts.goalChannelsChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Organic Search', 'Direct', 'Referral', 'Social', 'Email', 'Paid Search'],
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: [4.8, 5.2, 3.7, 2.9, 7.5, 6.3],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function initializeGoalDevicesChart() {
            const chartCanvas = document.getElementById('goal-devices-chart');
            if (!chartCanvas) return;
            
            charts.goalDevicesChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: [6.8, 3.5, 4.2],
                        backgroundColor: '#0077b6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>
</html> 