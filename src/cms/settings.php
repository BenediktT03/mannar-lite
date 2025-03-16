 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
        }
        
        .settings-nav {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
        }
        
        .settings-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .settings-nav li {
            margin-bottom: 10px;
        }
        
        .settings-nav a {
            display: block;
            padding: 8px 10px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .settings-nav a:hover, .settings-nav a.active {
            background-color: #e9ecef;
            color: #0077b6;
        }
        
        .settings-content {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
        }
        
        .settings-page {
            display: none;
        }
        
        .settings-page.active {
            display: block;
        }
        
        .form-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .form-row label {
            width: 200px;
            margin-bottom: 0;
        }
        
        .form-row input, .form-row select, .form-row textarea {
            flex: 1;
        }
        
        .color-picker {
            width: 100px !important;
            padding: 5px;
            height: 40px;
        }
        
        .settings-message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        
        .settings-message.success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .settings-message.error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-row label {
                width: 100%;
                margin-bottom: 5px;
            }
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
                <li><a href="settings.php" class="active">Settings</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>CMS Settings</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in as an administrator to access this page. Redirecting to login...</p>
            </div>
            
            <div id="settings-container" style="display: none;">
                <div class="settings-message" id="settings-message"></div>
                
                <div class="settings-container">
                    <div class="settings-nav">
                        <ul>
                            <li><a href="#general" class="settings-link active" data-page="general">General</a></li>
                            <li><a href="#appearance" class="settings-link" data-page="appearance">Appearance</a></li>
                            <li><a href="#content" class="settings-link" data-page="content">Content</a></li>
                            <li><a href="#seo" class="settings-link" data-page="seo">SEO & Social</a></li>
                            <li><a href="#advanced" class="settings-link" data-page="advanced">Advanced</a></li>
                            <li><a href="#backup" class="settings-link" data-page="backup">Backup & Restore</a></li>
                        </ul>
                    </div>
                    
                    <div class="settings-content">
                        <!-- General Settings -->
                        <div id="general-page" class="settings-page active">
                            <h2>General Settings</h2>
                            <p>Basic settings for your CMS and website.</p>
                            
                            <form id="general-form">
                                <div class="form-row">
                                    <label for="site-title">Site Title</label>
                                    <input type="text" id="site-title" name="siteTitle" required>
                                </div>
                                
                                <div class="form-row">
                                    <label for="site-description">Site Description</label>
                                    <textarea id="site-description" name="siteDescription" rows="2"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <label for="site-url">Site URL</label>
                                    <input type="url" id="site-url" name="siteUrl">
                                </div>
                                
                                <div class="form-row">
                                    <label for="admin-email">Admin Email</label>
                                    <input type="email" id="admin-email" name="adminEmail">
                                </div>
                                
                                <div class="form-row">
                                    <label for="date-format">Date Format</label>
                                    <select id="date-format" name="dateFormat">
                                        <option value="F j, Y">January 1, 2025</option>
                                        <option value="Y-m-d">2025-01-01</option>
                                        <option value="m/d/Y">01/01/2025</option>
                                        <option value="d/m/Y">01/01/2025</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="time-format">Time Format</label>
                                    <select id="time-format" name="timeFormat">
                                        <option value="g:i a">1:30 pm</option>
                                        <option value="g:i A">1:30 PM</option>
                                        <option value="H:i">13:30</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save General Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Appearance Settings -->
                        <div id="appearance-page" class="settings-page">
                            <h2>Appearance Settings</h2>
                            <p>Customize the look and feel of your website.</p>
                            
                            <form id="appearance-form">
                                <div class="form-row">
                                    <label for="active-theme">Active Theme</label>
                                    <select id="active-theme" name="activeTheme">
                                        <option value="default">Default</option>
                                        <option value="dark">Dark</option>
                                        <option value="light">Light</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="primary-color">Primary Color</label>
                                    <input type="color" id="primary-color" name="primaryColor" class="color-picker" value="#0077b6">
                                </div>
                                
                                <div class="form-row">
                                    <label for="secondary-color">Secondary Color</label>
                                    <input type="color" id="secondary-color" name="secondaryColor" class="color-picker" value="#1a1a2e">
                                </div>
                                
                                <div class="form-row">
                                    <label for="font-family">Font Family</label>
                                    <select id="font-family" name="fontFamily">
                                        <option value="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif">System Default</option>
                                        <option value="'Roboto', sans-serif">Roboto</option>
                                        <option value="'Open Sans', sans-serif">Open Sans</option>
                                        <option value="'Lato', sans-serif">Lato</option>
                                        <option value="'Merriweather', serif">Merriweather</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="logo-upload">Site Logo</label>
                                    <input type="file" id="logo-upload" accept="image/*">
                                </div>
                                
                                <div id="logo-preview" style="margin-bottom: 15px;"></div>
                                
                                <div class="form-row">
                                    <label for="custom-css">Custom CSS</label>
                                    <textarea id="custom-css" name="customCSS" rows="6"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save Appearance Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Content Settings -->
                        <div id="content-page" class="settings-page">
                            <h2>Content Settings</h2>
                            <p>Configure how content is displayed on your website.</p>
                            
                            <form id="content-form">
                                <div class="form-row">
                                    <label for="posts-per-page">Posts Per Page</label>
                                    <input type="number" id="posts-per-page" name="postsPerPage" min="1" max="50" value="10">
                                </div>
                                
                                <div class="form-row">
                                    <label for="default-post-layout">Default Post Layout</label>
                                    <select id="default-post-layout" name="defaultPostLayout">
                                        <option value="standard">Standard</option>
                                        <option value="grid">Grid</option>
                                        <option value="list">List</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="comment-status">Comments</label>
                                    <select id="comment-status" name="commentStatus">
                                        <option value="enabled">Enabled</option>
                                        <option value="disabled">Disabled</option>
                                        <option value="moderated">Moderated</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="media-sizes">Image Sizes</label>
                                    <div style="flex: 1;">
                                        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                                            <input type="text" id="thumbnail-size" placeholder="Thumbnail (WxH)" style="width: 150px;" value="150x150">
                                            <input type="text" id="medium-size" placeholder="Medium (WxH)" style="width: 150px;" value="300x300">
                                            <input type="text" id="large-size" placeholder="Large (WxH)" style="width: 150px;" value="800x800">
                                        </div>
                                        <small>Enter dimensions in pixels (width x height)</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save Content Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- SEO & Social Settings -->
                        <div id="seo-page" class="settings-page">
                            <h2>SEO & Social Settings</h2>
                            <p>Optimize your website for search engines and social media.</p>
                            
                            <form id="seo-form">
                                <div class="form-row">
                                    <label for="meta-title">Default Meta Title</label>
                                    <input type="text" id="meta-title" name="metaTitle">
                                </div>
                                
                                <div class="form-row">
                                    <label for="meta-description">Default Meta Description</label>
                                    <textarea id="meta-description" name="metaDescription" rows="2"></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <label for="meta-keywords">Default Meta Keywords</label>
                                    <input type="text" id="meta-keywords" name="metaKeywords">
                                    <small>Separate keywords with commas</small>
                                </div>
                                
                                <div class="form-row">
                                    <label for="social-image">Default Social Image</label>
                                    <input type="file" id="social-image" accept="image/*">
                                </div>
                                
                                <div id="social-image-preview" style="margin-bottom: 15px;"></div>
                                
                                <div class="form-row">
                                    <label for="google-analytics">Google Analytics ID</label>
                                    <input type="text" id="google-analytics" name="googleAnalytics" placeholder="UA-XXXXXXXXX-X or G-XXXXXXXXXX">
                                </div>
                                
                                <div class="form-row">
                                    <label for="sitemap-status">XML Sitemap</label>
                                    <select id="sitemap-status" name="sitemapStatus">
                                        <option value="enabled">Enabled</option>
                                        <option value="disabled">Disabled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save SEO Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Advanced Settings -->
                        <div id="advanced-page" class="settings-page">
                            <h2>Advanced Settings</h2>
                            <p>Configure advanced features of your CMS.</p>
                            
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> These settings can affect the functionality of your website. Change with caution.</p>
                            </div>
                            
                            <form id="advanced-form">
                                <div class="form-row">
                                    <label for="cache-enabled">Cache</label>
                                    <select id="cache-enabled" name="cacheEnabled">
                                        <option value="enabled">Enabled</option>
                                        <option value="disabled">Disabled</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="cache-lifetime">Cache Lifetime</label>
                                    <input type="number" id="cache-lifetime" name="cacheLifetime" value="3600">
                                    <small>In seconds (3600 = 1 hour)</small>
                                </div>
                                
                                <div class="form-row">
                                    <label for="maintenance-mode">Maintenance Mode</label>
                                    <select id="maintenance-mode" name="maintenanceMode">
                                        <option value="disabled">Disabled</option>
                                        <option value="enabled">Enabled</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="api-enabled">REST API</label>
                                    <select id="api-enabled" name="apiEnabled">
                                        <option value="disabled">Disabled</option>
                                        <option value="enabled">Enabled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save Advanced Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Backup & Restore Settings -->
                        <div id="backup-page" class="settings-page">
                            <h2>Backup & Restore</h2>
                            <p>Create backups of your CMS data or restore from a previous backup.</p>
                            
                            <div class="alert alert-info">
                                <p><strong>Note:</strong> Backups include all your content, users, and settings, but not media files.</p>
                            </div>
                            
                            <h3>Create Backup</h3>
                            <p>Generate a backup file of your CMS data.</p>
                            <button id="create-backup-btn" class="btn btn-primary">Create Backup</button>
                            <div id="backup-progress" style="margin-top: 10px; display: none;">
                                <p>Creating backup... <span id="backup-progress-text">0%</span></p>
                                <div style="height: 20px; background-color: #f0f0f0; border-radius: 4px; overflow: hidden;">
                                    <div id="backup-progress-bar" style="height: 100%; width: 0%; background-color: #0077b6;"></div>
                                </div>
                            </div>
                            
                            <h3 style="margin-top: 30px;">Restore from Backup</h3>
                            <p>Restore your CMS data from a backup file.</p>
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> Restoring from a backup will overwrite your current data. This cannot be undone.</p>
                            </div>
                            <form id="restore-form">
                                <div class="form-group">
                                    <label for="backup-file">Select Backup File</label>
                                    <input type="file" id="backup-file" accept=".json">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-danger">Restore from Backup</button>
                                </div>
                            </form>
                            <div id="restore-progress" style="margin-top: 10px; display: none;">
                                <p>Restoring backup... <span id="restore-progress-text">0%</span></p>
                                <div style="height: 20px; background-color: #f0f0f0; border-radius: 4px; overflow: hidden;">
                                    <div id="restore-progress-bar" style="height: 100%; width: 0%; background-color: #0077b6;"></div>
                                </div>
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
        import { getFirestore, collection, doc, getDoc, setDoc, getDocs } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
        import { getStorage, ref, uploadBytesResumable, getDownloadURL } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js";

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
        const storage = getStorage(app);
        
        // Global variables
        let settings = {};
        let logoURL = null;
        let socialImageURL = null;
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const settingsContainer = document.getElementById('settings-container');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Check if user is an admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // User is an admin
                    settingsContainer.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Load settings
                    loadSettings();
                    
                    // Setup settings navigation
                    setupSettingsNavigation();
                } else {
                    // User is not an admin
                    settingsContainer.style.display = 'none';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                settingsContainer.style.display = 'none';
                authWarning.style.display = 'block';
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        });
        
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
        
        // Setup settings navigation
        function setupSettingsNavigation() {
            const settingsLinks = document.querySelectorAll('.settings-link');
            
            settingsLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active link
                    settingsLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    
                    // Show selected page
                    const pageId = link.getAttribute('data-page');
                    document.querySelectorAll('.settings-page').forEach(page => {
                        page.classList.remove('active');
                    });
                    document.getElementById(`${pageId}-page`).classList.add('active');
                });
            });
            
            // Setup form submissions
            setupFormSubmissions();
            
            // Setup file uploads
            setupFileUploads();
            
            // Setup backup functionality
            setupBackupFunctionality();
        }
        
        // Load settings
        async function loadSettings() {
            try {
                const settingsDoc = await getDoc(doc(db, "system", "settings"));
                
                if (settingsDoc.exists()) {
                    settings = settingsDoc.data();
                    
                    // Fill general settings form
                    document.getElementById('site-title').value = settings.siteTitle || '';
                    document.getElementById('site-description').value = settings.siteDescription || '';
                    document.getElementById('site-url').value = settings.siteUrl || '';
                    document.getElementById('admin-email').value = settings.adminEmail || '';
                    document.getElementById('date-format').value = settings.dateFormat || 'F j, Y';
                    document.getElementById('time-format').value = settings.timeFormat || 'g:i a';
                    
                    // Fill appearance settings form
                    document.getElementById('active-theme').value = settings.activeTheme || 'default';
                    document.getElementById('primary-color').value = settings.primaryColor || '#0077b6';
                    document.getElementById('secondary-color').value = settings.secondaryColor || '#1a1a2e';
                    document.getElementById('font-family').value = settings.fontFamily || 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif';
                    document.getElementById('custom-css').value = settings.customCSS || '';
                    
                    // Display logo if exists
                    if (settings.logo) {
                        logoURL = settings.logo;
                        document.getElementById('logo-preview').innerHTML = `<img src="${logoURL}" alt="Site Logo" style="max-height: 100px;">`;
                    }
                    
                    // Fill content settings form
                    document.getElementById('posts-per-page').value = settings.postsPerPage || 10;
                    document.getElementById('default-post-layout').value = settings.defaultPostLayout || 'standard';
                    document.getElementById('comment-status').value = settings.commentStatus || 'enabled';
                    
                    // Fill image size inputs
                    if (settings.imageSizes) {
                        document.getElementById('thumbnail-size').value = settings.imageSizes.thumbnail || '150x150';
                        document.getElementById('medium-size').value = settings.imageSizes.medium || '300x300';
                        document.getElementById('large-size').value = settings.imageSizes.large || '800x800';
                    }
                    
                    // Fill SEO settings form
                    document.getElementById('meta-title').value = settings.metaTitle || '';
                    document.getElementById('meta-description').value = settings.metaDescription || '';
                    document.getElementById('meta-keywords').value = settings.metaKeywords || '';
                    document.getElementById('google-analytics').value = settings.googleAnalytics || '';
                    document.getElementById('sitemap-status').value = settings.sitemapStatus || 'enabled';
                    
                    // Display social image if exists
                    if (settings.socialImage) {
                        socialImageURL = settings.socialImage;
                        document.getElementById('social-image-preview').innerHTML = `<img src="${socialImageURL}" alt="Social Image" style="max-height: 100px;">`;
                    }
                    
                    // Fill advanced settings form
                    document.getElementById('cache-enabled').value = settings.cacheEnabled || 'enabled';
                    document.getElementById('cache-lifetime').value = settings.cacheLifetime || 3600;
                    document.getElementById('maintenance-mode').value = settings.maintenanceMode || 'disabled';
                    document.getElementById('api-enabled').value = settings.apiEnabled || 'disabled';
                }
            } catch (error) {
                console.error("Error loading settings:", error);
                showMessage('Error loading settings. Please try again.', 'error');
            }
        }
        
        // Setup form submissions
        function setupFormSubmissions() {
            // General Settings Form
            document.getElementById('general-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    // Get form values
                    const formData = {
                        siteTitle: document.getElementById('site-title').value,
                        siteDescription: document.getElementById('site-description').value,
                        siteUrl: document.getElementById('site-url').value,
                        adminEmail: document.getElementById('admin-email').value,
                        dateFormat: document.getElementById('date-format').value,
                        timeFormat: document.getElementById('time-format').value,
                        updatedAt: new Date().toISOString(),
                        updatedBy: auth.currentUser.uid
                    };
                    
                    // Update settings
                    await updateSettings(formData);
                    
                    showMessage('General settings saved successfully!', 'success');
                } catch (error) {
                    console.error("Error saving general settings:", error);
                    showMessage('Error saving general settings. Please try again.', 'error');
                }
            });
            
            // Appearance Settings Form
            document.getElementById('appearance-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    // Get form values
                    const formData = {
                        activeTheme: document.getElementById('active-theme').value,
                        primaryColor: document.getElementById('primary-color').value,
                        secondaryColor: document.getElementById('secondary-color').value,
                        fontFamily: document.getElementById('font-family').value,
                        customCSS: document.getElementById('custom-css').value,
                        updatedAt: new Date().toISOString(),
                        updatedBy: auth.currentUser.uid
                    };
                    
                    // Add logo URL if uploaded
                    if (logoURL) {
                        formData.logo = logoURL;
                    }
                    
                    // Update settings
                    await updateSettings(formData);
                    
                    showMessage('Appearance settings saved successfully!', 'success');
                } catch (error) {
                    console.error("Error saving appearance settings:", error);
                    showMessage('Error saving appearance settings. Please try again.', 'error');
                }
            });
            
            // Content Settings Form
            document.getElementById('content-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    // Get form values
                    const formData = {
                        postsPerPage: parseInt(document.getElementById('posts-per-page').value),
                        defaultPostLayout: document.getElementById('default-post-layout').value,
                        commentStatus: document.getElementById('comment-status').value,
                        imageSizes: {
                            thumbnail: document.getElementById('thumbnail-size').value,
                            medium: document.getElementById('medium-size').value,
                            large: document.getElementById('large-size').value
                        },
                        updatedAt: new Date().toISOString(),
                        updatedBy: auth.currentUser.uid
                    };
                    
                    // Update settings
                    await updateSettings(formData);
                    
                    showMessage('Content settings saved successfully!', 'success');
                } catch (error) {
                    console.error("Error saving content settings:", error);
                    showMessage('Error saving content settings. Please try again.', 'error');
                }
            });
            
            // SEO Settings Form
            document.getElementById('seo-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    // Get form values
                    const formData = {
                        metaTitle: document.getElementById('meta-title').value,
                        metaDescription: document.getElementById('meta-description').value,
                        metaKeywords: document.getElementById('meta-keywords').value,
                        googleAnalytics: document.getElementById('google-analytics').value,
                        sitemapStatus: document.getElementById('sitemap-status').value,
                        updatedAt: new Date().toISOString(),
                        updatedBy: auth.currentUser.uid
                    };
                    
                    // Add social image URL if uploaded
                    if (socialImageURL) {
                        formData.socialImage = socialImageURL;
                    }
                    
                    // Update settings
                    await updateSettings(formData);
                    
                    showMessage('SEO settings saved successfully!', 'success');
                } catch (error) {
                    console.error("Error saving SEO settings:", error);
                    showMessage('Error saving SEO settings. Please try again.', 'error');
                }
            });
            
            // Advanced Settings Form
            document.getElementById('advanced-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    // Get form values
                    const formData = {
                        cacheEnabled: document.getElementById('cache-enabled').value,
                        cacheLifetime: parseInt(document.getElementById('cache-lifetime').value),
                        maintenanceMode: document.getElementById('maintenance-mode').value,
                        apiEnabled: document.getElementById('api-enabled').value,
                        updatedAt: new Date().toISOString(),
                        updatedBy: auth.currentUser.uid
                    };
                    
                    // Update settings
                    await updateSettings(formData);
                    
                    showMessage('Advanced settings saved successfully!', 'success');
                } catch (error) {
                    console.error("Error saving advanced settings:", error);
                    showMessage('Error saving advanced settings. Please try again.', 'error');
                }
            });
        }
        
        // Setup file uploads
        function setupFileUploads() {
            // Logo Upload
            document.getElementById('logo-upload').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                try {
                    // Create storage reference
                    const storageRef = ref(storage, `settings/logo_${Date.now()}_${file.name}`);
                    
                    // Upload file
                    const uploadTask = uploadBytesResumable(storageRef, file);
                    
                    uploadTask.on('state_changed',
                        (snapshot) => {
                            // Handle progress
                            const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                            console.log('Upload progress:', progress);
                        },
                        (error) => {
                            // Handle error
                            console.error("Upload error:", error);
                            showMessage(`Error uploading logo: ${error.message}`, 'error');
                        },
                        async () => {
                            // Handle success
                            logoURL = await getDownloadURL(uploadTask.snapshot.ref);
                            
                            // Display logo preview
                            document.getElementById('logo-preview').innerHTML = `<img src="${logoURL}" alt="Site Logo" style="max-height: 100px;">`;
                        }
                    );
                } catch (error) {
                    console.error("Error uploading logo:", error);
                    showMessage(`Error uploading logo: ${error.message}`, 'error');
                }
            });
            
            // Social Image Upload
            document.getElementById('social-image').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                try {
                    // Create storage reference
                    const storageRef = ref(storage, `settings/social_${Date.now()}_${file.name}`);
                    
                    // Upload file
                    const uploadTask = uploadBytesResumable(storageRef, file);
                    
                    uploadTask.on('state_changed',
                        (snapshot) => {
                            // Handle progress
                            const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                            console.log('Upload progress:', progress);
                        },
                        (error) => {
                            // Handle error
                            console.error("Upload error:", error);
                            showMessage(`Error uploading social image: ${error.message}`, 'error');
                        },
                        async () => {
                            // Handle success
                            socialImageURL = await getDownloadURL(uploadTask.snapshot.ref);
                            
                            // Display social image preview
                            document.getElementById('social-image-preview').innerHTML = `<img src="${socialImageURL}" alt="Social Image" style="max-height: 100px;">`;
                        }
                    );
                } catch (error) {
                    console.error("Error uploading social image:", error);
                    showMessage(`Error uploading social image: ${error.message}`, 'error');
                }
            });
        }
        
        // Setup backup functionality
        function setupBackupFunctionality() {
            // Create Backup Button
            document.getElementById('create-backup-btn').addEventListener('click', async () => {
                try {
                    // Show progress
                    document.getElementById('backup-progress').style.display = 'block';
                    document.getElementById('backup-progress-text').textContent = '0%';
                    document.getElementById('backup-progress-bar').style.width = '0%';
                    
                    // Collect all data from Firestore
                    const backup = {
                        posts: [],
                        pages: [],
                        users: [],
                        media: [],
                        categories: [],
                        settings: null,
                        version: '1.0',
                        timestamp: new Date().toISOString()
                    };
                    
                    // Update progress
                    updateBackupProgress(10, 'Backing up posts...');
                    
                    // Get posts
                    const postsSnapshot = await getDocs(collection(db, "posts"));
                    postsSnapshot.forEach((doc) => {
                        backup.posts.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update progress
                    updateBackupProgress(30, 'Backing up pages...');
                    
                    // Get pages
                    const pagesSnapshot = await getDocs(collection(db, "pages"));
                    pagesSnapshot.forEach((doc) => {
                        backup.pages.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update progress
                    updateBackupProgress(50, 'Backing up users...');
                    
                    // Get users
                    const usersSnapshot = await getDocs(collection(db, "users"));
                    usersSnapshot.forEach((doc) => {
                        backup.users.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update progress
                    updateBackupProgress(70, 'Backing up media...');
                    
                    // Get media
                    const mediaSnapshot = await getDocs(collection(db, "media"));
                    mediaSnapshot.forEach((doc) => {
                        backup.media.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update progress
                    updateBackupProgress(85, 'Backing up categories...');
                    
                    // Get categories
                    const categoriesSnapshot = await getDocs(collection(db, "categories"));
                    categoriesSnapshot.forEach((doc) => {
                        backup.categories.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update progress
                    updateBackupProgress(95, 'Backing up settings...');
                    
                    // Get settings
                    const settingsDoc = await getDoc(doc(db, "system", "settings"));
                    if (settingsDoc.exists()) {
                        backup.settings = settingsDoc.data();
                    }
                    
                    // Update progress
                    updateBackupProgress(100, 'Backup complete!');
                    
                    // Generate backup file
                    const backupJSON = JSON.stringify(backup);
                    const blob = new Blob([backupJSON], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    
                    // Create download link
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `cms_backup_${new Date().toISOString().slice(0, 10)}.json`;
                    a.click();
                    
                    setTimeout(() => {
                        URL.revokeObjectURL(url);
                        document.getElementById('backup-progress').style.display = 'none';
                    }, 3000);
                } catch (error) {
                    console.error("Error creating backup:", error);
                    showMessage(`Error creating backup: ${error.message}`, 'error');
                    document.getElementById('backup-progress').style.display = 'none';
                }
            });
            
            // Restore from Backup Form
            document.getElementById('restore-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const file = document.getElementById('backup-file').files[0];
                if (!file) {
                    showMessage('Please select a backup file.', 'error');
                    return;
                }
                
                if (!confirm('Are you sure you want to restore from this backup? This will overwrite your current data and cannot be undone.')) {
                    return;
                }
                
                try {
                    // Show progress
                    document.getElementById('restore-progress').style.display = 'block';
                    document.getElementById('restore-progress-text').textContent = '0%';
                    document.getElementById('restore-progress-bar').style.width = '0%';
                    
                    // Read the backup file
                    const reader = new FileReader();
                    
                    reader.onload = async (e) => {
                        try {
                            const backup = JSON.parse(e.target.result);
                            
                            // Validate backup format
                            if (!backup.version || !backup.timestamp) {
                                throw new Error('Invalid backup file format.');
                            }
                            
                            // Update progress
                            updateRestoreProgress(10, 'Restoring settings...');
                            
                            // Restore settings
                            if (backup.settings) {
                                await setDoc(doc(db, "system", "settings"), backup.settings);
                            }
                            
                            // Update progress
                            updateRestoreProgress(20, 'Restoring users...');
                            
                            // Restore users
                            for (const user of backup.users) {
                                const userId = user.id;
                                delete user.id;
                                await setDoc(doc(db, "users", userId), user);
                            }
                            
                            // Update progress
                            updateRestoreProgress(40, 'Restoring categories...');
                            
                            // Restore categories
                            for (const category of backup.categories) {
                                const categoryId = category.id;
                                delete category.id;
                                await setDoc(doc(db, "categories", categoryId), category);
                            }
                            
                            // Update progress
                            updateRestoreProgress(50, 'Restoring posts...');
                            
                            // Restore posts
                            for (const post of backup.posts) {
                                const postId = post.id;
                                delete post.id;
                                await setDoc(doc(db, "posts", postId), post);
                            }
                            
                            // Update progress
                            updateRestoreProgress(70, 'Restoring pages...');
                            
                            // Restore pages
                            for (const page of backup.pages) {
                                const pageId = page.id;
                                delete page.id;
                                await setDoc(doc(db, "pages", pageId), page);
                            }
                            
                            // Update progress
                            updateRestoreProgress(90, 'Restoring media...');
                            
                            // Restore media
                            for (const media of backup.media) {
                                const mediaId = media.id;
                                delete media.id;
                                await setDoc(doc(db, "media", mediaId), media);
                            }
                            
                            // Update progress
                            updateRestoreProgress(100, 'Restore complete!');
                            
                            showMessage('Backup restored successfully! Reloading page...', 'success');
                            
                            // Reload page after 3 seconds
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } catch (error) {
                            console.error("Error processing backup file:", error);
                            showMessage(`Error processing backup file: ${error.message}`, 'error');
                            document.getElementById('restore-progress').style.display = 'none';
                        }
                    };
                    
                    reader.onerror = () => {
                        showMessage('Error reading backup file.', 'error');
                        document.getElementById('restore-progress').style.display = 'none';
                    };
                    
                    reader.readAsText(file);
                } catch (error) {
                    console.error("Error restoring backup:", error);
                    showMessage(`Error restoring backup: ${error.message}`, 'error');
                    document.getElementById('restore-progress').style.display = 'none';
                }
            });
        }
        
        // Update settings
        async function updateSettings(newSettings) {
            try {
                // Get current settings
                let currentSettings = {};
                
                const settingsDoc = await getDoc(doc(db, "system", "settings"));
                if (settingsDoc.exists()) {
                    currentSettings = settingsDoc.data();
                }
                
                // Merge with new settings
                const mergedSettings = { ...currentSettings, ...newSettings };
                
                // Save to Firestore
                await setDoc(doc(db, "system", "settings"), mergedSettings);
                
                // Update local settings
                settings = mergedSettings;
                
                return true;
            } catch (error) {
                console.error("Error updating settings:", error);
                throw error;
            }
        }
        
        // Show message
        function showMessage(message, type) {
            const messageElement = document.getElementById('settings-message');
            messageElement.textContent = message;
            messageElement.className = 'settings-message';
            messageElement.classList.add(type);
            messageElement.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
        
        // Update backup progress
        function updateBackupProgress(percentage, message) {
            document.getElementById('backup-progress-text').textContent = message + ' ' + percentage + '%';
            document.getElementById('backup-progress-bar').style.width = percentage + '%';
        }
        
        // Update restore progress
        function updateRestoreProgress(percentage, message) {
            document.getElementById('restore-progress-text').textContent = message + ' ' + percentage + '%';
            document.getElementById('restore-progress-bar').style.width = percentage + '%';
        }
    </script>
</body>
</html>