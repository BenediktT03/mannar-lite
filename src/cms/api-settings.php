<?php
// api-settings.php: API Configuration
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Settings - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .api-key-item {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .api-key-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .api-key-name {
            font-weight: 500;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .api-key-value {
            font-family: monospace;
            padding: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .api-key-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .scopes-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }
        
        .scope-tag {
            background-color: #e9ecef;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.9rem;
        }
        
        .documentation {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .documentation h2:first-child {
            margin-top: 0;
        }
        
        .endpoint-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .endpoint-item:last-child {
            border-bottom: none;
        }
        
        .endpoint-path {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 5px 0;
            display: inline-block;
        }
        
        .http-method {
            font-weight: bold;
            margin-right: 10px;
        }
        
        .method-get {
            color: #0d6efd;
        }
        
        .method-post {
            color: #198754;
        }
        
        .method-put, .method-patch {
            color: #fd7e14;
        }
        
        .method-delete {
            color: #dc3545;
        }
        
        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .params-table th, .params-table td {
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
            text-align: left;
        }
        
        .params-table th {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        
        .response-example {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            margin-bottom: -1px;
        }
        
        .tab.active {
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: 500;
        }
        
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
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
                <li><a href="api-settings.php" class="active">API Settings</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>API Settings</h1>
            <div id="message-container" class="alert" style="display: none;"></div>
            
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" data-tab="keys">API Keys</div>
                    <div class="tab" data-tab="settings">Settings</div>
                    <div class="tab" data-tab="docs">Documentation</div>
                </div>
                
                <div class="tab-content">
                    <!-- API Keys Tab -->
                    <div id="keys-tab" class="tab-pane active">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>API Keys</h2>
                            <button id="create-key-btn" class="btn btn-primary">Create New API Key</button>
                        </div>
                        
                        <div id="api-keys-list">
                            <p>Loading API keys...</p>
                        </div>
                    </div>
                    
                    <!-- API Settings Tab -->
                    <div id="settings-tab" class="tab-pane">
                        <h2>API Settings</h2>
                        <form id="api-settings-form">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="api-enabled"> 
                                    Enable API
                                </label>
                                <p class="help-text">When disabled, all API requests will return 403 Forbidden.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="rate-limit">Rate Limit (requests per minute)</label>
                                <input type="number" id="rate-limit" min="0" max="10000" value="60">
                                <p class="help-text">Set to 0 for unlimited requests. Applies per API key.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="token-expiry">Access Token Expiry (days)</label>
                                <input type="number" id="token-expiry" min="1" max="365" value="30">
                                <p class="help-text">Number of days until an API token expires.</p>
                            </div>
                            
                            <h3>Allowed Endpoints</h3>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="posts-endpoint" checked> 
                                    /posts
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="pages-endpoint" checked> 
                                    /pages
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="categories-endpoint" checked> 
                                    /categories
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="tags-endpoint" checked> 
                                    /tags
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="media-endpoint" checked> 
                                    /media
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="users-endpoint"> 
                                    /users
                                </label>
                                <p class="help-text">Exposes user data. Enable with caution.</p>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="comments-endpoint" checked> 
                                    /comments
                                </label>
                            </div>
                            
                            <h3>CORS Settings</h3>
                            <div class="form-group">
                                <label for="allowed-origins">Allowed Origins</label>
                                <textarea id="allowed-origins" rows="3" placeholder="*"></textarea>
                                <p class="help-text">Comma-separated list of domains, or * for all domains.</p>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- API Documentation Tab -->
                    <div id="docs-tab" class="tab-pane">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>API Documentation</h2>
                            <button id="export-docs-btn" class="btn">Export Documentation</button>
                        </div>
                        
                        <div class="documentation">
                            <h2>Overview</h2>
                            <p>This API provides programmatic access to content from your CMS. All responses are in JSON format.</p>
                            
                            <h3>Authentication</h3>
                            <p>API requests require authentication using an API key. Include your key in the request header:</p>
                            <div class="response-example">X-API-Key: your_api_key_here</div>
                            
                            <h3>Rate Limiting</h3>
                            <p>The API is rate limited to <span id="docs-rate-limit">60</span> requests per minute by default. If you exceed this limit, you'll receive a 429 Too Many Requests response.</p>
                            
                            <h2>Endpoints</h2>
                            
                            <!-- Posts Endpoint -->
                            <div class="endpoint-item">
                                <h3>Posts</h3>
                                
                                <h4>List Posts</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/posts</div>
                                <p>Returns a paginated list of posts.</p>
                                
                                <h5>Parameters</h5>
                                <table class="params-table">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                    <tr>
                                        <td>page</td>
                                        <td>Integer</td>
                                        <td>No</td>
                                        <td>Page number (default: 1)</td>
                                    </tr>
                                    <tr>
                                        <td>per_page</td>
                                        <td>Integer</td>
                                        <td>No</td>
                                        <td>Items per page (default: 10, max: 100)</td>
                                    </tr>
                                    <tr>
                                        <td>category</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Filter by category slug</td>
                                    </tr>
                                    <tr>
                                        <td>tag</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Filter by tag</td>
                                    </tr>
                                    <tr>
                                        <td>search</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Search term</td>
                                    </tr>
                                </table>
                                
                                <h5>Response Example</h5>
                                <div class="response-example">{
  "data": [
    {
      "id": "abc123",
      "title": "Sample Post",
      "excerpt": "This is a sample post...",
      "slug": "sample-post",
      "content": "This is the full content of the post...",
      "author": "John Doe",
      "category": "Technology",
      "tags": ["sample", "test"],
      "featured_image": "https://example.com/image.jpg",
      "status": "published",
      "created_at": "2025-03-15T12:00:00Z",
      "updated_at": "2025-03-15T13:30:00Z"
    },
    // More posts...
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 45,
    "total_pages": 5
  }
}</div>
                                
                                <h4>Get Single Post</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/posts/:id</div>
                                <p>Returns a single post by ID.</p>
                                
                                <h5>Parameters</h5>
                                <table class="params-table">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                    <tr>
                                        <td>id</td>
                                        <td>String</td>
                                        <td>Yes</td>
                                        <td>Post ID</td>
                                    </tr>
                                </table>
                                
                                <h4>Create Post</h4>
                                <div class="endpoint-path"><span class="http-method method-post">POST</span> /api/posts</div>
                                <p>Creates a new post. Requires write access.</p>
                                
                                <h5>Request Body</h5>
                                <table class="params-table">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                    <tr>
                                        <td>title</td>
                                        <td>String</td>
                                        <td>Yes</td>
                                        <td>Post title</td>
                                    </tr>
                                    <tr>
                                        <td>content</td>
                                        <td>String</td>
                                        <td>Yes</td>
                                        <td>Post content (HTML)</td>
                                    </tr>
                                    <tr>
                                        <td>excerpt</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Post excerpt</td>
                                    </tr>
                                    <tr>
                                        <td>slug</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Post slug (generated from title if not provided)</td>
                                    </tr>
                                    <tr>
                                        <td>category</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Category ID</td>
                                    </tr>
                                    <tr>
                                        <td>tags</td>
                                        <td>Array</td>
                                        <td>No</td>
                                        <td>Array of tags</td>
                                    </tr>
                                    <tr>
                                        <td>featured_image</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Featured image URL</td>
                                    </tr>
                                    <tr>
                                        <td>status</td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Post status: "draft" or "published" (default: "draft")</td>
                                    </tr>
                                </table>
                                
                                <h4>Update Post</h4>
                                <div class="endpoint-path"><span class="http-method method-put">PUT</span> /api/posts/:id</div>
                                <p>Updates an existing post. Requires write access.</p>
                                
                                <h5>Parameters</h5>
                                <table class="params-table">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                    <tr>
                                        <td>id</td>
                                        <td>String</td>
                                        <td>Yes</td>
                                        <td>Post ID</td>
                                    </tr>
                                </table>
                                
                                <h4>Delete Post</h4>
                                <div class="endpoint-path"><span class="http-method method-delete">DELETE</span> /api/posts/:id</div>
                                <p>Deletes a post. Requires write access.</p>
                                
                                <h5>Parameters</h5>
                                <table class="params-table">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                    <tr>
                                        <td>id</td>
                                        <td>String</td>
                                        <td>Yes</td>
                                        <td>Post ID</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Categories Endpoint -->
                            <div class="endpoint-item">
                                <h3>Categories</h3>
                                
                                <h4>List Categories</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/categories</div>
                                <p>Returns a list of all categories.</p>
                                
                                <h4>Get Single Category</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/categories/:id</div>
                                <p>Returns a single category by ID.</p>
                                
                                <h4>Create Category</h4>
                                <div class="endpoint-path"><span class="http-method method-post">POST</span> /api/categories</div>
                                <p>Creates a new category. Requires write access.</p>
                                
                                <h4>Update Category</h4>
                                <div class="endpoint-path"><span class="http-method method-put">PUT</span> /api/categories/:id</div>
                                <p>Updates an existing category. Requires write access.</p>
                                
                                <h4>Delete Category</h4>
                                <div class="endpoint-path"><span class="http-method method-delete">DELETE</span> /api/categories/:id</div>
                                <p>Deletes a category. Requires write access.</p>
                            </div>
                            
                            <!-- Media Endpoint -->
                            <div class="endpoint-item">
                                <h3>Media</h3>
                                
                                <h4>List Media</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/media</div>
                                <p>Returns a paginated list of media items.</p>
                                
                                <h4>Get Single Media Item</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/media/:id</div>
                                <p>Returns a single media item by ID.</p>
                                
                                <h4>Upload Media</h4>
                                <div class="endpoint-path"><span class="http-method method-post">POST</span> /api/media</div>
                                <p>Uploads a new media file. Requires write access.</p>
                                <p>This endpoint accepts multipart/form-data with a 'file' field.</p>
                                
                                <h4>Delete Media</h4>
                                <div class="endpoint-path"><span class="http-method method-delete">DELETE</span> /api/media/:id</div>
                                <p>Deletes a media item. Requires write access.</p>
                            </div>
                            
                            <!-- Users Endpoint -->
                            <div class="endpoint-item">
                                <h3>Users</h3>
                                <p>Note: This endpoint is disabled by default for security reasons.</p>
                                
                                <h4>List Users</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/users</div>
                                <p>Returns a paginated list of users. Requires admin access.</p>
                                
                                <h4>Get Single User</h4>
                                <div class="endpoint-path"><span class="http-method method-get">GET</span> /api/users/:id</div>
                                <p>Returns a single user by ID. Users can access their own data, admins can access any user.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Create API Key Modal -->
            <div id="create-key-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Create New API Key</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="create-key-form">
                            <div class="form-group">
                                <label for="api-key-name">Name</label>
                                <input type="text" id="api-key-name" required placeholder="E.g., Production API, Mobile App">
                            </div>
                            
                            <div class="form-group">
                                <label>Permissions</label>
                                <div>
                                    <label>
                                        <input type="checkbox" id="permission-read" checked> 
                                        Read
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="permission-write"> 
                                        Write
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="permission-delete"> 
                                        Delete
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Scope</label>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-posts" checked> 
                                        Posts
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-pages" checked> 
                                        Pages
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-categories" checked> 
                                        Categories
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-tags" checked> 
                                        Tags
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-media" checked> 
                                        Media
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-comments" checked> 
                                        Comments
                                    </label>
                                </div>
                                <div>
                                    <label>
                                        <input type="checkbox" id="scope-users"> 
                                        Users
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button id="generate-key-btn" class="btn btn-primary">Generate Key</button>
                        <button class="btn modal-close-btn">Cancel</button>
                    </div>
                </div>
            </div>
            
            <!-- New API Key Modal -->
            <div id="new-key-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>API Key Generated</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Important:</strong> Copy your API key now. For security reasons, it will only be shown once.</p>
                        <div class="api-key-value" id="new-api-key"></div>
                        <button id="copy-key-btn" class="btn">Copy to Clipboard</button>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary modal-close-btn">Done</button>
                    </div>
                </div>
            </div>
            
            <!-- Delete API Key Modal -->
            <div id="delete-key-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Delete API Key</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this API key? This action cannot be undone and any applications using this key will lose access.</p>
                    </div>
                    <div class="modal-footer">
                        <button id="confirm-delete-key-btn" class="btn btn-danger">Delete Key</button>
                        <button class="btn modal-close-btn">Cancel</button>
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
        import { getFirestore, collection, doc, getDoc, setDoc, updateDoc, deleteDoc, getDocs } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        let apiKeys = [];
        let apiSettings = {
            enabled: true,
            rateLimit: 60,
            tokenExpiry: 30,
            endpoints: {
                posts: true,
                pages: true,
                categories: true,
                tags: true,
                media: true,
                users: false,
                comments: true
            },
            cors: {
                allowedOrigins: '*'
            }
        };
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                // Check if user is admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // Load API keys and settings
                    await Promise.all([
                        loadApiKeys(),
                        loadApiSettings()
                    ]);
                    
                    // Setup event listeners
                    setupEventListeners();
                } else {
                    // Redirect to dashboard
                    window.location.href = "dashboard.php";
                }
            } else {
                // Redirect to login page
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
        
        // Load API keys
        async function loadApiKeys() {
            try {
                const keysSnapshot = await getDocs(collection(db, "api_keys"));
                
                // Reset array
                apiKeys = [];
                
                keysSnapshot.forEach((doc) => {
                    apiKeys.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                renderApiKeys();
            } catch (error) {
                console.error("Error loading API keys:", error);
                showMessage("Error loading API keys. Please try again.", "error");
            }
        }
        
        // Load API settings
        async function loadApiSettings() {
            try {
                const settingsDoc = await getDoc(doc(db, "system", "api_settings"));
                
                if (settingsDoc.exists()) {
                    apiSettings = { ...apiSettings, ...settingsDoc.data() };
                }
                
                // Update form fields
                document.getElementById('api-enabled').checked = apiSettings.enabled;
                document.getElementById('rate-limit').value = apiSettings.rateLimit;
                document.getElementById('token-expiry').value = apiSettings.tokenExpiry;
                document.getElementById('posts-endpoint').checked = apiSettings.endpoints.posts;
                document.getElementById('pages-endpoint').checked = apiSettings.endpoints.pages;
                document.getElementById('categories-endpoint').checked = apiSettings.endpoints.categories;
                document.getElementById('tags-endpoint').checked = apiSettings.endpoints.tags;
                document.getElementById('media-endpoint').checked = apiSettings.endpoints.media;
                document.getElementById('users-endpoint').checked = apiSettings.endpoints.users;
                document.getElementById('comments-endpoint').checked = apiSettings.endpoints.comments;
                document.getElementById('allowed-origins').value = apiSettings.cors.allowedOrigins;
                
                // Update documentation
                document.getElementById('docs-rate-limit').textContent = apiSettings.rateLimit;
            } catch (error) {
                console.error("Error loading API settings:", error);
                showMessage("Error loading API settings. Please try again.", "error");
            }
        }
        
        // Render API keys
        function renderApiKeys() {
            const keysContainer = document.getElementById('api-keys-list');
            
            if (apiKeys.length === 0) {
                keysContainer.innerHTML = '<p>No API keys found. Create your first API key to get started.</p>';
                return;
            }
            
            let keysHTML = '';
            
            apiKeys.forEach(key => {
                // Format created date
                const createdDate = new Date(key.createdAt).toLocaleString();
                
                // Calculate expiry date
                const expiryDate = new Date(key.createdAt);
                expiryDate.setDate(expiryDate.getDate() + key.expiryDays);
                const formattedExpiryDate = expiryDate.toLocaleString();
                
                // Check if expired
                const isExpired = expiryDate < new Date();
                
                // Format permissions and scopes
                const permissions = [];
                if (key.permissions.read) permissions.push('Read');
                if (key.permissions.write) permissions.push('Write');
                if (key.permissions.delete) permissions.push('Delete');
                
                // Create scope tags
                let scopesHTML = '<div class="scopes-list">';
                for (const [scope, enabled] of Object.entries(key.scopes)) {
                    if (enabled) {
                        scopesHTML += `<span class="scope-tag">${scope}</span>`;
                    }
                }
                scopesHTML += '</div>';
                
                keysHTML += `
                    <div class="api-key-item" data-id="${key.id}">
                        <div class="api-key-header">
                            <h3 class="api-key-name">${key.name}</h3>
                            <div>
                                <button class="btn btn-danger delete-key-btn" data-id="${key.id}">Delete</button>
                            </div>
                        </div>
                        <p class="api-key-meta">
                            Created: ${createdDate} | 
                            Expires: ${formattedExpiryDate} 
                            ${isExpired ? '<span style="color: #dc3545;">(Expired)</span>' : ''}
                        </p>
                        <p>Permissions: ${permissions.join(', ')}</p>
                        <p>Scopes:</p>
                        ${scopesHTML}
                        <p>Last used: ${key.lastUsed ? new Date(key.lastUsed).toLocaleString() : 'Never'}</p>
                    </div>
                `;
            });
            
            keysContainer.innerHTML = keysHTML;
            
            // Add event listeners for delete buttons
            document.querySelectorAll('.delete-key-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const keyId = e.target.getAttribute('data-id');
                    openDeleteKeyModal(keyId);
                });
            });
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Tab navigation
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    // Update active tab
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Show selected tab content
                    const tabId = tab.getAttribute('data-tab');
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Create API key button
            document.getElementById('create-key-btn').addEventListener('click', () => {
                document.getElementById('create-key-modal').style.display = 'block';
            });
            
            // Generate API key button
            document.getElementById('generate-key-btn').addEventListener('click', generateApiKey);
            
            // Copy API key button
            document.getElementById('copy-key-btn').addEventListener('click', () => {
                const keyElement = document.getElementById('new-api-key');
                const range = document.createRange();
                range.selectNode(keyElement);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                
                document.getElementById('copy-key-btn').textContent = 'Copied!';
                setTimeout(() => {
                    document.getElementById('copy-key-btn').textContent = 'Copy to Clipboard';
                }, 2000);
            });
            
            // Confirm delete API key button
            document.getElementById('confirm-delete-key-btn').addEventListener('click', deleteApiKey);
            
            // API settings form
            document.getElementById('api-settings-form').addEventListener('submit', saveApiSettings);
            
            // Export docs button
            document.getElementById('export-docs-btn').addEventListener('click', exportDocumentation);
            
            // Modal close buttons
            document.querySelectorAll('.modal-close, .modal-close-btn').forEach(button => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.style.display = 'none';
                    });
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                document.querySelectorAll('.modal').forEach(modal => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        }
        
        // Generate API key
        async function generateApiKey() {
            const keyName = document.getElementById('api-key-name').value.trim();
            
            if (!keyName) {
                showMessage("Please enter a name for your API key.", "error");
                return;
            }
            
            // Get permissions
            const permissions = {
                read: document.getElementById('permission-read').checked,
                write: document.getElementById('permission-write').checked,
                delete: document.getElementById('permission-delete').checked
            };
            
            // Get scopes
            const scopes = {
                posts: document.getElementById('scope-posts').checked,
                pages: document.getElementById('scope-pages').checked,
                categories: document.getElementById('scope-categories').checked,
                tags: document.getElementById('scope-tags').checked,
                media: document.getElementById('scope-media').checked,
                comments: document.getElementById('scope-comments').checked,
                users: document.getElementById('scope-users').checked
            };
            
            try {
                // Generate a random API key
                const apiKey = generateRandomKey(32);
                
                // Generate key prefix (first 8 chars)
                const keyPrefix = apiKey.substring(0, 8);
                
                // Create API key document
                const keyData = {
                    name: keyName,
                    keyPrefix: keyPrefix,
                    keyHash: await hashApiKey(apiKey), // Store hashed version for security
                    permissions,
                    scopes,
                    expiryDays: apiSettings.tokenExpiry,
                    createdAt: new Date().toISOString(),
                    createdBy: auth.currentUser.uid,
                    lastUsed: null
                };
                
                // Save to Firestore
                await setDoc(doc(db, "api_keys", generateRandomKey(20)), keyData);
                
                // Close create modal
                document.getElementById('create-key-modal').style.display = 'none';
                
                // Show new key modal
                document.getElementById('new-api-key').textContent = apiKey;
                document.getElementById('new-key-modal').style.display = 'block';
                
                // Reset form
                document.getElementById('create-key-form').reset();
                
                // Reload API keys
                await loadApiKeys();
            } catch (error) {
                console.error("Error generating API key:", error);
                showMessage("Error generating API key. Please try again.", "error");
            }
        }
        
        // Delete API key
        async function deleteApiKey() {
            const keyId = document.getElementById('confirm-delete-key-btn').getAttribute('data-key-id');
            
            try {
                await deleteDoc(doc(db, "api_keys", keyId));
                
                // Close modal
                document.getElementById('delete-key-modal').style.display = 'none';
                
                // Reload API keys
                await loadApiKeys();
                
                showMessage("API key deleted successfully.", "success");
            } catch (error) {
                console.error("Error deleting API key:", error);
                showMessage("Error deleting API key. Please try again.", "error");
            }
        }
        
        // Save API settings
        async function saveApiSettings(e) {
            e.preventDefault();
            
            try {
                // Get form values
                const settings = {
                    enabled: document.getElementById('api-enabled').checked,
                    rateLimit: parseInt(document.getElementById('rate-limit').value),
                    tokenExpiry: parseInt(document.getElementById('token-expiry').value),
                    endpoints: {
                        posts: document.getElementById('posts-endpoint').checked,
                        pages: document.getElementById('pages-endpoint').checked,
                        categories: document.getElementById('categories-endpoint').checked,
                        tags: document.getElementById('tags-endpoint').checked,
                        media: document.getElementById('media-endpoint').checked,
                        users: document.getElementById('users-endpoint').checked,
                        comments: document.getElementById('comments-endpoint').checked
                    },
                    cors: {
                        allowedOrigins: document.getElementById('allowed-origins').value.trim() || '*'
                    },
                    updatedAt: new Date().toISOString(),
                    updatedBy: auth.currentUser.uid
                };
                
                // Save to Firestore
                await setDoc(doc(db, "system", "api_settings"), settings);
                
                // Update local settings
                apiSettings = settings;
                
                // Update documentation
                document.getElementById('docs-rate-limit').textContent = settings.rateLimit;
                
                showMessage("API settings saved successfully.", "success");
            } catch (error) {
                console.error("Error saving API settings:", error);
                showMessage("Error saving API settings. Please try again.", "error");
            }
        }
        
        // Export documentation
        function exportDocumentation() {
            // Get documentation HTML
            const documentation = document.querySelector('.documentation').innerHTML;
            
            // Create HTML document
            const html = `
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>API Documentation - PHP Firebase CMS</title>
                    <style>
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
                            line-height: 1.6;
                            color: #333;
                            max-width: 1000px;
                            margin: 0 auto;
                            padding: 20px;
                        }
                        
                        h1, h2, h3, h4, h5, h6 {
                            margin-top: 1.5em;
                            margin-bottom: 0.5em;
                        }
                        
                        h1 {
                            border-bottom: 1px solid #eee;
                            padding-bottom: 0.5em;
                        }
                        
                        .endpoint-path {
                            font-family: monospace;
                            background-color: #f8f9fa;
                            padding: 5px 10px;
                            border-radius: 4px;
                            margin: 5px 0;
                            display: inline-block;
                        }
                        
                        .http-method {
                            font-weight: bold;
                            margin-right: 10px;
                        }
                        
                        .method-get {
                            color: #0d6efd;
                        }
                        
                        .method-post {
                            color: #198754;
                        }
                        
                        .method-put, .method-patch {
                            color: #fd7e14;
                        }
                        
                        .method-delete {
                            color: #dc3545;
                        }
                        
                        .endpoint-item {
                            margin-bottom: 20px;
                            padding-bottom: 20px;
                            border-bottom: 1px solid #e0e0e0;
                        }
                        
                        .params-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 10px 0;
                        }
                        
                        .params-table th, .params-table td {
                            border: 1px solid #e0e0e0;
                            padding: 8px 12px;
                            text-align: left;
                        }
                        
                        .params-table th {
                            background-color: #f8f9fa;
                            font-weight: 500;
                        }
                        
                        .response-example {
                            background-color: #f8f9fa;
                            padding: 10px;
                            border-radius: 4px;
                            font-family: monospace;
                            white-space: pre-wrap;
                            overflow-x: auto;
                        }
                    </style>
                </head>
                <body>
                    <h1>PHP Firebase CMS API Documentation</h1>
                    ${documentation}
                </body>
                </html>
            `;
            
            // Create blob and download link
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'api-documentation.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        // Open delete API key modal
        function openDeleteKeyModal(keyId) {
            document.getElementById('confirm-delete-key-btn').setAttribute('data-key-id', keyId);
            document.getElementById('delete-key-modal').style.display = 'block';
        }
        
        // Helper function: Generate random API key
        function generateRandomKey(length) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            const values = new Uint32Array(length);
            window.crypto.getRandomValues(values);
            for (let i = 0; i < length; i++) {
                result += chars[values[i] % chars.length];
            }
            return result;
        }
        
        // Helper function: Hash API key
        async function hashApiKey(apiKey) {
            // In a real application, use a proper hashing algorithm like bcrypt
            // For simplicity, we'll use SHA-256 here
            const encoder = new TextEncoder();
            const data = encoder.encode(apiKey);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        }
        
        // Show message
        function showMessage(message, type) {
            const messageContainer = document.getElementById('message-container');
            messageContainer.textContent = message;
            messageContainer.className = `alert alert-${type}`;
            messageContainer.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageContainer.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html> 