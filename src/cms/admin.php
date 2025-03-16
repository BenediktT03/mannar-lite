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
    <title>Admin Dashboard - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">View Site</a></li>
                <li><a href="admin.php" class="active">Admin</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Admin Dashboard</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="admin-panel" style="display: none;">
                <div class="admin-container">
                    <div class="admin-sidebar">
                        <ul class="admin-menu">
                            <li><a href="#dashboard" class="active" data-page="dashboard">Dashboard</a></li>
                            <li><a href="#posts" data-page="posts">Posts</a></li>
                            <li><a href="#pages" data-page="pages">Pages</a></li>
                            <li><a href="#media" data-page="media">Media</a></li>
                            <li><a href="#users" data-page="users">Users</a></li>
                            <li><a href="#settings" data-page="settings">Settings</a></li>
                        </ul>
                    </div>
                    
                    <div class="admin-content">
                        <!-- Dashboard Page -->
                        <div id="dashboard-page" class="admin-page">
                            <h2>Dashboard</h2>
                            <div class="stats-container">
                                <div class="stat-card">
                                    <h3>Total Posts</h3>
                                    <p id="total-posts">0</p>
                                </div>
                                <div class="stat-card">
                                    <h3>Total Pages</h3>
                                    <p id="total-pages">0</p>
                                </div>
                                <div class="stat-card">
                                    <h3>Users</h3>
                                    <p id="total-users">0</p>
                                </div>
                                <div class="stat-card">
                                    <h3>Media Files</h3>
                                    <p id="total-media">0</p>
                                </div>
                            </div>
                            
                            <h3>Recent Content</h3>
                            <div id="recent-content">
                                <p>Loading recent content...</p>
                            </div>
                            
                            <h3>Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="editor.php?type=post" class="btn btn-primary">Create New Post</a>
                                <a href="editor.php?type=page" class="btn btn-primary">Create New Page</a>
                                <a href="#media" data-page="media" class="btn btn-primary admin-link">Upload Media</a>
                            </div>
                        </div>
                        
                        <!-- Posts Page -->
                        <div id="posts-page" class="admin-page" style="display: none;">
                            <h2>Posts</h2>
                            <div class="page-actions">
                                <a href="editor.php?type=post" class="btn btn-primary">Add New Post</a>
                            </div>
                            <div id="posts-list">
                                <p>Loading posts...</p>
                            </div>
                        </div>
                        
                        <!-- Pages Page -->
                        <div id="pages-page" class="admin-page" style="display: none;">
                            <h2>Pages</h2>
                            <div class="page-actions">
                                <a href="editor.php?type=page" class="btn btn-primary">Add New Page</a>
                            </div>
                            <div id="pages-list">
                                <p>Loading pages...</p>
                            </div>
                        </div>
                        
                        <!-- Media Page -->
                        <div id="media-page" class="admin-page" style="display: none;">
                            <h2>Media Library</h2>
                            <div class="media-upload">
                                <h3>Upload New Files</h3>
                                <form id="media-upload-form">
                                    <div class="form-group">
                                        <label for="media-file">Select Files</label>
                                        <input type="file" id="media-file" multiple>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Upload</button>
                                    </div>
                                    <div id="upload-progress" style="display: none;">
                                        <progress value="0" max="100"></progress>
                                        <span>0%</span>
                                    </div>
                                </form>
                            </div>
                            
                            <h3>Media Library</h3>
                            <div id="media-library">
                                <p>Loading media files...</p>
                            </div>
                        </div>
                        
                        <!-- Users Page -->
                        <div id="users-page" class="admin-page" style="display: none;">
                            <h2>Users</h2>
                            <div id="users-list">
                                <p>Loading users...</p>
                            </div>
                        </div>
                        
                        <!-- Settings Page -->
                        <div id="settings-page" class="admin-page" style="display: none;">
                            <h2>CMS Settings</h2>
                            <form id="settings-form">
                                <div class="form-group">
                                    <label for="site-title">Site Title</label>
                                    <input type="text" id="site-title" name="site-title">
                                </div>
                                <div class="form-group">
                                    <label for="site-description">Site Description</label>
                                    <textarea id="site-description" name="site-description" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="posts-per-page">Posts Per Page</label>
                                    <input type="number" id="posts-per-page" name="posts-per-page" min="1" max="50">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </div>
                                <div id="settings-message" style="display: none;"></div>
                            </form>
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
        import { getFirestore, collection, query, where, orderBy, limit, getDocs, doc, getDoc, updateDoc, deleteDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
        import { getStorage, ref, uploadBytesResumable, getDownloadURL, deleteObject } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js";

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
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const adminPanel = document.getElementById('admin-panel');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Check if user is an admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // User is an admin
                    adminPanel.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Load dashboard data
                    loadDashboardData();
                    
                    // Setup admin panel navigation
                    setupAdminNavigation();
                } else {
                    // User is not an admin
                    adminPanel.style.display = 'none';
                    authWarning.innerHTML = '<p>You do not have admin permissions. Redirecting to dashboard...</p>';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                adminPanel.style.display = 'none';
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
        
        // Setup admin panel navigation
        function setupAdminNavigation() {
            const menuLinks = document.querySelectorAll('.admin-menu a, .admin-link');
            const pages = document.querySelectorAll('.admin-page');
            
            menuLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active menu item
                    document.querySelectorAll('.admin-menu a').forEach(item => {
                        item.classList.remove('active');
                        if (item.getAttribute('data-page') === link.getAttribute('data-page')) {
                            item.classList.add('active');
                        }
                    });
                    
                    // Show the selected page
                    const targetPage = link.getAttribute('data-page');
                    pages.forEach(page => {
                        page.style.display = 'none';
                    });
                    document.getElementById(`${targetPage}-page`).style.display = 'block';
                    
                    // Load page-specific content
                    if (targetPage === 'posts') {
                        loadPosts();
                    } else if (targetPage === 'pages') {
                        loadPages();
                    } else if (targetPage === 'media') {
                        loadMedia();
                    } else if (targetPage === 'users') {
                        loadUsers();
                    } else if (targetPage === 'settings') {
                        loadSettings();
                    }
                });
            });
        }
        
        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Get statistics
                const postsSnapshot = await getDocs(collection(db, "posts"));
                document.getElementById('total-posts').textContent = postsSnapshot.size;
                
                const pagesSnapshot = await getDocs(collection(db, "pages"));
                document.getElementById('total-pages').textContent = pagesSnapshot.size;
                
                const usersSnapshot = await getDocs(collection(db, "users"));
                document.getElementById('total-users').textContent = usersSnapshot.size;
                
                const mediaSnapshot = await getDocs(collection(db, "media"));
                document.getElementById('total-media').textContent = mediaSnapshot.size;
                
                // Load recent content
                const recentContentQuery = query(
                    collection(db, "posts"),
                    orderBy("createdAt", "desc"),
                    limit(5)
                );
                const recentContentSnapshot = await getDocs(recentContentQuery);
                
                const recentContentEl = document.getElementById('recent-content');
                
                if (recentContentSnapshot.empty) {
                    recentContentEl.innerHTML = '<p>No content found.</p>';
                    return;
                }
                
                let contentHTML = '<table>';
                contentHTML += '<tr><th>Title</th><th>Author</th><th>Date</th><th>Status</th><th>Actions</th></tr>';
                
                recentContentSnapshot.forEach((doc) => {
                    const content = doc.data();
                    const date = new Date(content.createdAt).toLocaleDateString();
                    
                    contentHTML += `
                        <tr>
                            <td>${content.title}</td>
                            <td>${content.authorName || 'Unknown'}</td>
                            <td>${date}</td>
                            <td>${content.status || 'Draft'}</td>
                            <td>
                                <a href="editor.php?id=${doc.id}&type=post" class="btn btn-primary">Edit</a>
                            </td>
                        </tr>
                    `;
                });
                
                contentHTML += '</table>';
                recentContentEl.innerHTML = contentHTML;
                
            } catch (error) {
                console.error("Error loading dashboard data:", error);
            }
        }
        
        // Load posts
        async function loadPosts() {
            const postsListEl = document.getElementById('posts-list');
            postsListEl.innerHTML = '<p>Loading posts...</p>';
            
            try {
                const postsQuery = query(
                    collection(db, "posts"),
                    orderBy("createdAt", "desc")
                );
                const postsSnapshot = await getDocs(postsQuery);
                
                if (postsSnapshot.empty) {
                    postsListEl.innerHTML = '<p>No posts found.</p>';
                    return;
                }
                
                let postsHTML = '<table>';
                postsHTML += '<tr><th>Title</th><th>Author</th><th>Date</th><th>Status</th><th>Actions</th></tr>';
                
                postsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    const date = new Date(post.createdAt).toLocaleDateString();
                    
                    postsHTML += `
                        <tr>
                            <td>${post.title}</td>
                            <td>${post.authorName || 'Unknown'}</td>
                            <td>${date}</td>
                            <td>${post.status || 'Draft'}</td>
                            <td>
                                <a href="editor.php?id=${doc.id}&type=post" class="btn btn-primary">Edit</a>
                                <button class="btn btn-danger delete-post" data-id="${doc.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                });
                
                postsHTML += '</table>';
                postsListEl.innerHTML = postsHTML;
                
                // Add event listeners for delete buttons
                document.querySelectorAll('.delete-post').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        if (confirm('Are you sure you want to delete this post?')) {
                            const postId = e.target.getAttribute('data-id');
                            try {
                                await deleteDoc(doc(db, "posts", postId));
                                loadPosts(); // Reload posts list
                            } catch (error) {
                                console.error("Error deleting post:", error);
                                alert('Error deleting post. Please try again.');
                            }
                        }
                    });
                });
                
            } catch (error) {
                console.error("Error loading posts:", error);
                postsListEl.innerHTML = '<p>Error loading posts. Please try again.</p>';
            }
        }
        
        // Load pages
        async function loadPages() {
            const pagesListEl = document.getElementById('pages-list');
            pagesListEl.innerHTML = '<p>Loading pages...</p>';
            
            try {
                const pagesQuery = query(
                    collection(db, "pages"),
                    orderBy("createdAt", "desc")
                );
                const pagesSnapshot = await getDocs(pagesQuery);
                
                if (pagesSnapshot.empty) {
                    pagesListEl.innerHTML = '<p>No pages found.</p>';
                    return;
                }
                
                let pagesHTML = '<table>';
                pagesHTML += '<tr><th>Title</th><th>Slug</th><th>Date</th><th>Status</th><th>Actions</th></tr>';
                
                pagesSnapshot.forEach((doc) => {
                    const page = doc.data();
                    const date = new Date(page.createdAt).toLocaleDateString();
                    
                    pagesHTML += `
                        <tr>
                            <td>${page.title}</td>
                            <td>${page.slug || ''}</td>
                            <td>${date}</td>
                            <td>${page.status || 'Draft'}</td>
                            <td>
                                <a href="editor.php?id=${doc.id}&type=page" class="btn btn-primary">Edit</a>
                                <button class="btn btn-danger delete-page" data-id="${doc.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                });
                
                pagesHTML += '</table>';
                pagesListEl.innerHTML = pagesHTML;
                
                // Add event listeners for delete buttons
                document.querySelectorAll('.delete-page').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        if (confirm('Are you sure you want to delete this page?')) {
                            const pageId = e.target.getAttribute('data-id');
                            try {
                                await deleteDoc(doc(db, "pages", pageId));
                                loadPages(); // Reload pages list
                            } catch (error) {
                                console.error("Error deleting page:", error);
                                alert('Error deleting page. Please try again.');
                            }
                        }
                    });
                });
                
            } catch (error) {
                console.error("Error loading pages:", error);
                pagesListEl.innerHTML = '<p>Error loading pages. Please try again.</p>';
            }
        }
        
        // Load media
        async function loadMedia() {
            const mediaLibraryEl = document.getElementById('media-library');
            mediaLibraryEl.innerHTML = '<p>Loading media files...</p>';
            
            try {
                const mediaQuery = query(
                    collection(db, "media"),
                    orderBy("uploadedAt", "desc")
                );
                const mediaSnapshot = await getDocs(mediaQuery);
                
                if (mediaSnapshot.empty) {
                    mediaLibraryEl.innerHTML = '<p>No media files found.</p>';
                    return;
                }
                
                let mediaHTML = '<div class="media-grid">';
                
                mediaSnapshot.forEach((doc) => {
                    const media = doc.data();
                    
                    mediaHTML += `
                        <div class="media-item">
                            <img src="${media.url}" alt="${media.name}">
                            <div class="media-item-info">
                                <p class="media-name">${media.name}</p>
                                <p class="media-size">${formatFileSize(media.size)}</p>
                                <button class="btn btn-danger delete-media" data-id="${doc.id}" data-path="${media.path}">Delete</button>
                            </div>
                        </div>
                    `;
                });
                
                mediaHTML += '</div>';
                mediaLibraryEl.innerHTML = mediaHTML;
                
                // Add event listeners for delete buttons
                document.querySelectorAll('.delete-media').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        if (confirm('Are you sure you want to delete this media file?')) {
                            const mediaId = e.target.getAttribute('data-id');
                            const mediaPath = e.target.getAttribute('data-path');
                            try {
                                // Delete from storage
                                const storageRef = ref(storage, mediaPath);
                                await deleteObject(storageRef);
                                
                                // Delete from Firestore
                                await deleteDoc(doc(db, "media", mediaId));
                                
                                loadMedia(); // Reload media list
                            } catch (error) {
                                console.error("Error deleting media:", error);
                                alert('Error deleting media. Please try again.');
                            }
                        }
                    });
                });
                
            } catch (error) {
                console.error("Error loading media:", error);
                mediaLibraryEl.innerHTML = '<p>Error loading media files. Please try again.</p>';
            }
            
            // Setup media upload form
            document.getElementById('media-upload-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const fileInput = document.getElementById('media-file');
                const progressBar = document.getElementById('upload-progress').querySelector('progress');
                const progressText = document.getElementById('upload-progress').querySelector('span');
                
                if (fileInput.files.length === 0) {
                    alert('Please select at least one file to upload.');
                    return;
                }
                
                document.getElementById('upload-progress').style.display = 'block';
                
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    
                    try {
                        // Create storage reference
                        const storagePath = `media/${Date.now()}_${file.name}`;
                        const storageRef = ref(storage, storagePath);
                        
                        // Upload file with progress tracking
                        const uploadTask = uploadBytesResumable(storageRef, file);
                        
                        uploadTask.on('state_changed',
                            (snapshot) => {
                                // Track upload progress
                                const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                                progressBar.value = progress;
                                progressText.textContent = `${Math.round(progress)}%`;
                            },
                            (error) => {
                                console.error("Upload error:", error);
                                alert(`Error uploading ${file.name}`);
                            },
                            async () => {
                                // Upload completed successfully
                                const downloadURL = await getDownloadURL(uploadTask.snapshot.ref);
                                
                                // Save media info to Firestore
                                await addDoc(collection(db, "media"), {
                                    name: file.name,
                                    type: file.type,
                                    size: file.size,
                                    url: downloadURL,
                                    path: storagePath,
                                    uploadedBy: auth.currentUser.uid,
                                    uploadedAt: new Date().toISOString()
                                });
                                
                                // If this is the last file, reload media library
                                if (i === fileInput.files.length - 1) {
                                    fileInput.value = ''; // Clear input
                                    document.getElementById('upload-progress').style.display = 'none';
                                    loadMedia();
                                }
                            }
                        );
                    } catch (error) {
                        console.error("Error uploading file:", error);
                        alert(`Error uploading ${file.name}`);
                    }
                }
            });
        }
        
        // Load users
        async function loadUsers() {
            const usersListEl = document.getElementById('users-list');
            usersListEl.innerHTML = '<p>Loading users...</p>';
            
            try {
                const usersQuery = query(
                    collection(db, "users"),
                    orderBy("createdAt", "desc")
                );
                const usersSnapshot = await getDocs(usersQuery);
                
                if (usersSnapshot.empty) {
                    usersListEl.innerHTML = '<p>No users found.</p>';
                    return;
                }
                
                let usersHTML = '<table>';
                usersHTML += '<tr><th>Name</th><th>Email</th><th>Role</th><th>Join Date</th><th>Actions</th></tr>';
                
                usersSnapshot.forEach((doc) => {
                    const user = doc.data();
                    const date = new Date(user.createdAt).toLocaleDateString();
                    
                    usersHTML += `
                        <tr>
                            <td>${user.displayName || 'Unknown'}</td>
                            <td>${user.email}</td>
                            <td>
                                <select class="user-role" data-id="${doc.id}">
                                    <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                                    <option value="editor" ${user.role === 'editor' ? 'selected' : ''}>Editor</option>
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </td>
                            <td>${date}</td>
                            <td>
                                <button class="btn btn-danger delete-user" data-id="${doc.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                });
                
                usersHTML += '</table>';
                usersListEl.innerHTML = usersHTML;
                
                // Add event listeners for role selects
                document.querySelectorAll('.user-role').forEach(select => {
                    select.addEventListener('change', async (e) => {
                        const userId = e.target.getAttribute('data-id');
                        const newRole = e.target.value;
                        
                        try {
                            await updateDoc(doc(db, "users", userId), {
                                role: newRole,
                                updatedAt: new Date().toISOString()
                            });
                            
                            alert('User role updated successfully.');
                        } catch (error) {
                            console.error("Error updating user role:", error);
                            alert('Error updating user role. Please try again.');
                            loadUsers(); // Reload to reset the select
                        }
                    });
                });
                
                // Add event listeners for delete buttons
                document.querySelectorAll('.delete-user').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        if (confirm('Are you sure you want to delete this user?')) {
                            const userId = e.target.getAttribute('data-id');
                            try {
                                await deleteDoc(doc(db, "users", userId));
                                loadUsers(); // Reload users list
                            } catch (error) {
                                console.error("Error deleting user:", error);
                                alert('Error deleting user. Please try again.');
                            }
                        }
                    });
                });
                
            } catch (error) {
                console.error("Error loading users:", error);
                usersListEl.innerHTML = '<p>Error loading users. Please try again.</p>';
            }
        }
        
        // Load settings
        async function loadSettings() {
            try {
                const settingsDoc = await getDoc(doc(db, "system", "settings"));
                
                if (settingsDoc.exists()) {
                    const settings = settingsDoc.data();
                    
                    document.getElementById('site-title').value = settings.siteTitle || '';
                    document.getElementById('site-description').value = settings.siteDescription || '';
                    document.getElementById('posts-per-page').value = settings.postsPerPage || 10;
                }
                
                // Setup settings form
                document.getElementById('settings-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const siteTitle = document.getElementById('site-title').value;
                    const siteDescription = document.getElementById('site-description').value;
                    const postsPerPage = parseInt(document.getElementById('posts-per-page').value);
                    const settingsMessage = document.getElementById('settings-message');
                    
                    try {
                        await setDoc(doc(db, "system", "settings"), {
                            siteTitle,
                            siteDescription,
                            postsPerPage,
                            updatedAt: new Date().toISOString(),
                            updatedBy: auth.currentUser.uid
                        });
                        
                        settingsMessage.textContent = 'Settings saved successfully!';
                        settingsMessage.style.display = 'block';
                        settingsMessage.style.color = 'green';
                    } catch (error) {
                        console.error("Error saving settings:", error);
                        settingsMessage.textContent = 'Error saving settings. Please try again.';
                        settingsMessage.style.display = 'block';
                        settingsMessage.style.color = 'red';
                    }
                });
                
            } catch (error) {
                console.error("Error loading settings:", error);
            }
        }
        
        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>