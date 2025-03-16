<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Mannar CMS</title>
    <link rel="stylesheet" href="style.css">
    
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- Add basic styles for admin panel -->
    <style>
        .admin-container {
            display: flex;
            flex-wrap: wrap;
        }
        .admin-sidebar {
            flex: 1;
            min-width: 200px;
            background-color: #f0f0f0;
            padding: 1rem;
            border-radius: 5px;
            margin-right: 1rem;
        }
        .admin-content {
            flex: 3;
            min-width: 300px;
        }
        .admin-menu {
            list-style: none;
        }
        .admin-menu li {
            margin-bottom: 0.5rem;
        }
        .admin-menu a {
            display: block;
            padding: 0.5rem;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .admin-menu a:hover, .admin-menu a.active {
            background-color: #ddd;
        }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background-color: #f9f9f9;
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        .stat-card p {
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mannar CMS</div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="admin.html" class="active">Admin</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Admin Dashboard</h1>
            <div id="auth-warning" style="display: none; background-color: #ffdddd; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="admin-panel" style="display: none;">
                <div class="admin-container">
                    <div class="admin-sidebar">
                        <ul class="admin-menu">
                            <li><a href="#dashboard" class="active" data-page="dashboard">Dashboard</a></li>
                            <li><a href="#posts" data-page="posts">Manage Posts</a></li>
                            <li><a href="#new-post" data-page="new-post">Create New Post</a></li>
                            <li><a href="#media" data-page="media">Media Library</a></li>
                        </ul>
                    </div>
                    
                    <div class="admin-content">
                        <!-- Dashboard Page -->
                        <div id="dashboard-page" class="admin-page">
                            <h2>Dashboard</h2>
                            <div class="dashboard-stats">
                                <div class="stat-card">
                                    <h3>Total Posts</h3>
                                    <p id="total-posts">0</p>
                                </div>
                                <div class="stat-card">
                                    <h3>Your Posts</h3>
                                    <p id="user-posts">0</p>
                                </div>
                                <div class="stat-card">
                                    <h3>Media Files</h3>
                                    <p id="media-count">0</p>
                                </div>
                            </div>
                            <div>
                                <h3>Quick Actions</h3>
                                <p><a href="#new-post" data-page="new-post" class="admin-link">Create a new post</a></p>
                                <p><a href="#media" data-page="media" class="admin-link">Upload a new image</a></p>
                            </div>
                        </div>
                        
                        <!-- Posts Management Page -->
                        <div id="posts-page" class="admin-page" style="display: none;">
                            <h2>Manage Posts</h2>
                            <div id="posts-list">
                                <p>Loading posts...</p>
                            </div>
                        </div>
                        
                        <!-- New Post Page -->
                        <div id="new-post-page" class="admin-page" style="display: none;">
                            <h2>Create New Post</h2>
                            <form id="new-post-form">
                                <div class="form-group">
                                    <label for="post-title">Title</label>
                                    <input type="text" id="post-title" required>
                                </div>
                               <div class="form-group">
    <label for="post-content">Content</label>
    <textarea id="post-content" rows="15" required></textarea>
</div>
                                <div class="form-group">
                                    <button type="submit">Publish Post</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Media Library Page -->
                        <div id="media-page" class="admin-page" style="display: none;">
                            <h2>Media Library</h2>
                            <div class="form-group">
                                <label for="media-upload">Upload New Image</label>
                                <input type="file" id="media-upload" accept="image/*">
                            </div>
                            <div id="media-list">
                                <p>Media files will appear here...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Mannar CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, addDoc, getDocs, query, where, orderBy } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAQszUApKHZ3lPrpc7HOINpdOWW3SgvUBM",
            authDomain: "mannar-129a5.firebaseapp.com",
            projectId: "mannar-129a5",
            storageBucket: "mannar-129a5.firebasestorage.app",
            messagingSenderId: "687710492532",
            appId: "1:687710492532:web:c7b675da541271f8d83e21",
            measurementId: "G-NXBLYJ5CXL"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const adminPanel = document.getElementById('admin-panel');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // User is signed in
                adminPanel.style.display = 'block';
                authWarning.style.display = 'none';
                
                // Load dashboard data
                loadDashboardStats(user);
                
                // Setup admin panel navigation
                setupAdminNavigation();
            } else {
                // User is not signed in
                adminPanel.style.display = 'none';
                authWarning.style.display = 'block';
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            }
        });
        // Initialize TinyMCE
tinymce.init({
    selector: '#post-content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    height: 400,
    menubar: false,
    // Setup callback to handle image uploads
    images_upload_handler: async function (blobInfo, success, failure) {
        try {
            const imageUrl = await uploadImage(blobInfo.blob());
            success(imageUrl);
        } catch (error) {
            failure('Image upload failed: ' + error.message);
        }
    }
});
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', () => {
            signOut(auth).then(() => {
                // Sign-out successful
                window.location.href = 'index.html';
            }).catch((error) => {
                // An error happened
                console.error('Logout error:', error);
            });
        });
        
        // Admin panel navigation
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
                    } else if (targetPage === 'media') {
                        loadMedia();
                    }
                });
            });
        }
        
        // Load dashboard statistics
        async function loadDashboardStats(user) {
            try {
                // Count total posts
                const postsSnapshot = await getDocs(collection(db, "posts"));
                document.getElementById('total-posts').textContent = postsSnapshot.size;
                
                // Count user's posts
                const userPostsQuery = query(
                    collection(db, "posts"), 
                    where("authorId", "==", user.uid)
                );
                const userPostsSnapshot = await getDocs(userPostsQuery);
                document.getElementById('user-posts').textContent = userPostsSnapshot.size;
                
                // For media count (placeholder for now)
                document.getElementById('media-count').textContent = "0";
            } catch (error) {
                console.error("Error loading dashboard stats:", error);
            }
        }
        
        // Load posts for management
        async function loadPosts() {
            const postsListElement = document.getElementById('posts-list');
            postsListElement.innerHTML = '<p>Loading posts...</p>';
            
            try {
                const postsQuery = query(
                    collection(db, "posts"),
                    orderBy("createdAt", "desc")
                );
                const querySnapshot = await getDocs(postsQuery);
                
                if (querySnapshot.empty) {
                    postsListElement.innerHTML = '<p>No posts found. Create your first post!</p>';
                    return;
                }
                
                let postsHTML = '<table style="width: 100%; border-collapse: collapse;">';
                postsHTML += '<tr><th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Title</th><th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Date</th><th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th></tr>';
                
                querySnapshot.forEach((doc) => {
                    const post = doc.data();
                    const date = new Date(post.createdAt).toLocaleDateString();
                    
                    postsHTML += `
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${post.title}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">${date}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <button class="edit-post" data-id="${doc.id}">Edit</button>
                                <button class="delete-post" data-id="${doc.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                });
                
                postsHTML += '</table>';
                postsListElement.innerHTML = postsHTML;
                
                // Add event listeners for edit and delete buttons
                document.querySelectorAll('.edit-post').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const postId = e.target.getAttribute('data-id');
                        // Implementation for edit will be added in a later step
                        console.log('Edit post:', postId);
                    });
                });
                
                document.querySelectorAll('.delete-post').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const postId = e.target.getAttribute('data-id');
                        // Implementation for delete will be added in a later step
                        console.log('Delete post:', postId);
                    });
                });
            } catch (error) {
                console.error("Error loading posts:", error);
                postsListElement.innerHTML = '<p>Error loading posts. Please try again.</p>';
            }
        }
        
        // Create new post
        document.getElementById('new-post-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const titleInput = document.getElementById('post-title');
            const contentInput = document.getElementById('post-content');
            
            const title = titleInput.value;
            const content = contentInput.value;
            
            try {
                const user = auth.currentUser;
                
                // Add post to Firestore
                await addDoc(collection(db, "posts"), {
                    title,
                    content,
                    authorId: user.uid,
                    authorName: user.displayName || 'Unknown',
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString()
                });
                
                // Clear form
                titleInput.value = '';
                contentInput.value = '';
                
                // Show success message
                alert('Post published successfully!');
                
                // Refresh dashboard stats
                loadDashboardStats(user);
            } catch (error) {
                console.error("Error adding post:", error);
                alert('Error publishing post. Please try again.');
            }
        });
        
        // Placeholder for media library functionality
        function loadMedia() {
            const mediaListElement = document.getElementById('media-list');
            mediaListElement.innerHTML = '<p>Media library functionality will be implemented in a later step.</p>';
        }
    </script>
</body>
</html>