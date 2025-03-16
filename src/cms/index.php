<?php
// Include configuration
require_once 'config-loader.php';
// Am Anfang der index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get page parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$tag = isset($_GET['tag']) ? $_GET['tag'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Load settings
$settings = [];
if (function_exists('getDocument')) {
    $settingsData = getDocument('system', 'settings');
    if ($settingsData) {
        $settings = $settingsData;
    }
}

// Set default values
$siteTitle = isset($settings['siteTitle']) ? $settings['siteTitle'] : 'PHP Firebase CMS';
$siteDescription = isset($settings['siteDescription']) ? $settings['siteDescription'] : 'A modern CMS powered by PHP and Firebase';
$postsPerPage = isset($settings['postsPerPage']) ? (int)$settings['postsPerPage'] : 10;
$defaultLayout = isset($settings['defaultPostLayout']) ? $settings['defaultPostLayout'] : 'standard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteTitle; ?></title>
    <meta name="description" content="<?php echo $siteDescription; ?>">
    <?php if (isset($settings['metaKeywords']) && !empty($settings['metaKeywords'])): ?>
    <meta name="keywords" content="<?php echo $settings['metaKeywords']; ?>">
    <?php endif; ?>
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="<?php echo $siteTitle; ?>">
    <meta property="og:description" content="<?php echo $siteDescription; ?>">
    <meta property="og:type" content="website">
    <?php if (isset($settings['siteUrl']) && !empty($settings['siteUrl'])): ?>
    <meta property="og:url" content="<?php echo $settings['siteUrl']; ?>">
    <?php endif; ?>
    <?php if (isset($settings['socialImage']) && !empty($settings['socialImage'])): ?>
    <meta property="og:image" content="<?php echo $settings['socialImage']; ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <?php if (isset($settings['favicon']) && !empty($settings['favicon'])): ?>
    <link rel="icon" href="<?php echo $settings['favicon']; ?>">
    <?php endif; ?>
    
    <!-- Styles -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Google Analytics -->
    <?php if (isset($settings['googleAnalytics']) && !empty($settings['googleAnalytics'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $settings['googleAnalytics']; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo $settings['googleAnalytics']; ?>');
    </script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <?php if (isset($settings['customCSS']) && !empty($settings['customCSS'])): ?>
    <style>
        <?php echo $settings['customCSS']; ?>
    </style>
    <?php endif; ?>
    
    <style>
        /* Front-end specific styles */
        .site-header {
            background-color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            color: white;
            padding: 1rem 0;
        }
        
        .site-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .main-content {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin: 2rem auto;
        }
        
        .posts-container {
            flex: 3;
            min-width: 300px;
        }
        
        .sidebar {
            flex: 1;
            min-width: 250px;
        }
        
        .post {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .post:last-child {
            border-bottom: none;
        }
        
        .post-title {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .post-title a {
            color: #333;
            text-decoration: none;
        }
        
        .post-title a:hover {
            color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
        }
        
        .post-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .post-excerpt {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .post-thumbnail {
            margin-bottom: 1rem;
        }
        
        .post-thumbnail img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        
        .read-more {
            display: inline-block;
            color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            font-weight: 500;
            text-decoration: none;
        }
        
        .read-more:hover {
            text-decoration: underline;
        }
        
        .pagination {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .current {
            background-color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            color: white;
            border-color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
        }
        
        .widget {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .widget h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            font-size: 1.2rem;
        }
        
        .widget ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .widget ul li {
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .widget ul li:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .widget a {
            color: #333;
            text-decoration: none;
        }
        
        .widget a:hover {
            color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
        }
        
        .search-form {
            display: flex;
        }
        
        .search-form input[type="text"] {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 4px 0 0 4px;
        }
        
        .search-form button {
            padding: 0.5rem 1rem;
            background-color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #e9ecef;
            border-radius: 2px;
            font-size: 0.85rem;
            color: #333;
            text-decoration: none;
        }
        
        .tag:hover {
            background-color: #dee2e6;
        }
        
        .grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .grid-layout .post {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .grid-layout .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .grid-layout .post-content {
            padding: 1rem;
        }
        
        .grid-layout .post-thumbnail {
            margin-bottom: 0;
        }
        
        .grid-layout .post-thumbnail img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px 5px 0 0;
        }
        
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1><?php echo $siteTitle; ?></h1>
            <p class="site-description"><?php echo $siteDescription; ?></p>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor')): ?>
                            <li><a href="admin.php">Admin</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container main-content">
        <div class="posts-container">
            <div id="posts" class="<?php echo $defaultLayout === 'grid' ? 'grid-layout' : ''; ?>">
                <p>Loading posts...</p>
            </div>
            <div id="pagination" class="pagination" style="display: none;">
                <!-- Pagination will be populated here -->
            </div>
        </div>
        
        <div class="sidebar">
            <!-- Search Widget -->
            <div class="widget">
                <h3>Search</h3>
                <form class="search-form" action="index.php" method="get">
                    <input type="text" name="search" placeholder="Search..." value="<?php echo $search ? htmlspecialchars($search) : ''; ?>">
                    <button type="submit">Go</button>
                </form>
            </div>
            
            <!-- Categories Widget -->
            <div class="widget">
                <h3>Categories</h3>
                <div id="categories-widget">
                    <p>Loading categories...</p>
                </div>
            </div>
            
            <!-- Recent Posts Widget -->
            <div class="widget">
                <h3>Recent Posts</h3>
                <div id="recent-posts-widget">
                    <p>Loading recent posts...</p>
                </div>
            </div>
            
            <!-- Tags Widget -->
            <div class="widget">
                <h3>Tags</h3>
                <div id="tags-widget" class="tag-cloud">
                    <p>Loading tags...</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $siteTitle; ?>. All rights reserved.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p>Logged in as <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'; ?> | <a href="#" id="logoutBtn">Logout</a></p>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, query, where, orderBy, limit, startAfter, getDocs, doc, getDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Get page parameters
        const page = <?php echo $page; ?>;
        const category = <?php echo $category ? "'" . addslashes($category) . "'" : 'null'; ?>;
        const tag = <?php echo $tag ? "'" . addslashes($tag) . "'" : 'null'; ?>;
        const search = <?php echo $search ? "'" . addslashes($search) . "'" : 'null'; ?>;
        const postsPerPage = <?php echo $postsPerPage; ?>;
        const defaultLayout = "<?php echo $defaultLayout; ?>";
        
        // Global variables
        let lastDoc = null;
        let firstDoc = null;
        let totalPages = 1;
        let allTags = [];
        
        // Check authentication state
        onAuthStateChanged(auth, (user) => {
            if (user) {
                // Set up logout functionality
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        signOut(auth).then(() => {
                            // Sign-out successful
                            window.location.reload();
                        }).catch((error) => {
                            // An error happened
                            console.error('Logout error:', error);
                        });
                    });
                }
            }
            
            // Load posts and widgets
            loadPosts();
            loadCategories();
            loadRecentPosts();
            loadTags();
        });
        
        // Load posts
        async function loadPosts() {
            const postsContainer = document.getElementById('posts');
            postsContainer.innerHTML = '<p>Loading posts...</p>';
            
            try {
                // Create query constraints
                let constraints = [
                    where("status", "==", "published"),
                    orderBy("publishedAt", "desc")
                ];
                
                // Add category filter
                if (category) {
                    constraints.push(where("category", "==", category));
                }
                
                // Add tag filter
                if (tag) {
                    constraints.push(where("tags", "array-contains", tag));
                }
                
                // Create query
                let postsQuery = query(
                    collection(db, "posts"),
                    ...constraints,
                    limit(postsPerPage)
                );
                
                // Get posts
                const postsSnapshot = await getDocs(postsQuery);
                
                if (postsSnapshot.empty) {
                    postsContainer.innerHTML = '<div class="alert alert-info">No posts found.</div>';
                    return;
                }
                
                // Store first and last documents for pagination
                firstDoc = postsSnapshot.docs[0];
                lastDoc = postsSnapshot.docs[postsSnapshot.docs.length - 1];
                
                // Get total count for pagination
                const countQuery = query(
                    collection(db, "posts"),
                    where("status", "==", "published")
                );
                const countSnapshot = await getDocs(countQuery);
                totalPages = Math.ceil(countSnapshot.size / postsPerPage);
                
                // Process posts
                let postsHTML = '';
                
                postsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    
                    // Format date
                    const publishedDate = post.publishedAt ? new Date(post.publishedAt) : new Date();
                    const formattedDate = publishedDate.toLocaleDateString();
                    
                    // Generate post HTML based on layout
                    if (defaultLayout === 'grid') {
                        postsHTML += `
                            <article class="post">
                                ${post.featuredImage ? `
                                <div class="post-thumbnail">
                                    <a href="post.php?id=${doc.id}">
                                        <img src="${post.featuredImage}" alt="${post.title}">
                                    </a>
                                </div>
                                ` : ''}
                                <div class="post-content">
                                    <h2 class="post-title"><a href="post.php?id=${doc.id}">${post.title}</a></h2>
                                    <div class="post-meta">
                                        <span>By ${post.authorName || 'Unknown'}</span> • 
                                        <span>${formattedDate}</span>
                                    </div>
                                    <div class="post-excerpt">
                                        <p>${post.excerpt || truncateHTML(post.content, 120)}</p>
                                    </div>
                                    <a href="post.php?id=${doc.id}" class="read-more">Read More</a>
                                </div>
                            </article>
                        `;
                    } else {
                        postsHTML += `
                            <article class="post">
                                <h2 class="post-title"><a href="post.php?id=${doc.id}">${post.title}</a></h2>
                                <div class="post-meta">
                                    <span>By ${post.authorName || 'Unknown'}</span> • 
                                    <span>${formattedDate}</span>
                                    ${post.category ? ` • <span>Category: <a href="index.php?category=${post.category}">${post.category}</a></span>` : ''}
                                </div>
                                ${post.featuredImage ? `
                                <div class="post-thumbnail">
                                    <a href="post.php?id=${doc.id}">
                                        <img src="${post.featuredImage}" alt="${post.title}">
                                    </a>
                                </div>
                                ` : ''}
                                <div class="post-excerpt">
                                    <p>${post.excerpt || truncateHTML(post.content, 200)}</p>
                                </div>
                                <a href="post.php?id=${doc.id}" class="read-more">Read More</a>
                            </article>
                        `;
                    }
                });
                
                postsContainer.innerHTML = postsHTML;
                
                // Update pagination
                updatePagination();
            } catch (error) {
                console.error("Error loading posts:", error);
                postsContainer.innerHTML = '<div class="alert alert-danger">Error loading posts. Please try again later.</div>';
            }
        }
        
        // Load categories
        async function loadCategories() {
            const categoriesWidget = document.getElementById('categories-widget');
            
            try {
                const categoriesQuery = query(
                    collection(db, "categories"),
                    orderBy("name")
                );
                const categoriesSnapshot = await getDocs(categoriesQuery);
                
                if (categoriesSnapshot.empty) {
                    categoriesWidget.innerHTML = '<p>No categories found.</p>';
                    return;
                }
                
                let categoriesHTML = '<ul>';
                
                categoriesSnapshot.forEach((doc) => {
                    const category = doc.data();
                    categoriesHTML += `
                        <li><a href="index.php?category=${category.name}">${category.name}</a></li>
                    `;
                });
                
                categoriesHTML += '</ul>';
                categoriesWidget.innerHTML = categoriesHTML;
            } catch (error) {
                console.error("Error loading categories:", error);
                categoriesWidget.innerHTML = '<p>Error loading categories.</p>';
            }
        }
        
        // Load recent posts
        async function loadRecentPosts() {
            const recentPostsWidget = document.getElementById('recent-posts-widget');
            
            try {
                const recentPostsQuery = query(
                    collection(db, "posts"),
                    where("status", "==", "published"),
                    orderBy("publishedAt", "desc"),
                    limit(5)
                );
                const recentPostsSnapshot = await getDocs(recentPostsQuery);
                
                if (recentPostsSnapshot.empty) {
                    recentPostsWidget.innerHTML = '<p>No recent posts found.</p>';
                    return;
                }
                
                let recentPostsHTML = '<ul>';
                
                recentPostsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    recentPostsHTML += `
                        <li><a href="post.php?id=${doc.id}">${post.title}</a></li>
                    `;
                });
                
                recentPostsHTML += '</ul>';
                recentPostsWidget.innerHTML = recentPostsHTML;
            } catch (error) {
                console.error("Error loading recent posts:", error);
                recentPostsWidget.innerHTML = '<p>Error loading recent posts.</p>';
            }
        }
        
        // Load tags
        async function loadTags() {
            const tagsWidget = document.getElementById('tags-widget');
            
            try {
                const postsQuery = query(
                    collection(db, "posts"),
                    where("status", "==", "published")
                );
                const postsSnapshot = await getDocs(postsQuery);
                
                if (postsSnapshot.empty) {
                    tagsWidget.innerHTML = '<p>No tags found.</p>';
                    return;
                }
                
                // Extract all tags from posts
                const tagCounts = {};
                
                postsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    
                    if (post.tags && Array.isArray(post.tags)) {
                        post.tags.forEach(tag => {
                            if (tag && tag.trim()) {
                                tagCounts[tag] = (tagCounts[tag] || 0) + 1;
                            }
                        });
                    }
                });
                
                // Convert to array and sort by count
                allTags = Object.keys(tagCounts).map(tag => ({
                    name: tag,
                    count: tagCounts[tag]
                })).sort((a, b) => b.count - a.count);
                
                if (allTags.length === 0) {
                    tagsWidget.innerHTML = '<p>No tags found.</p>';
                    return;
                }
                
                let tagsHTML = '';
                
                allTags.forEach(tag => {
                    tagsHTML += `
                        <a href="index.php?tag=${tag.name}" class="tag">${tag.name} (${tag.count})</a>
                    `;
                });
                
                tagsWidget.innerHTML = tagsHTML;
            } catch (error) {
                console.error("Error loading tags:", error);
                tagsWidget.innerHTML = '<p>Error loading tags.</p>';
            }
        }
        
        // Update pagination
        function updatePagination() {
            const paginationContainer = document.getElementById('pagination');
            
            if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
            }
            
            let paginationHTML = '';
            
            // Previous button
            if (page > 1) {
                paginationHTML += `<a href="index.php?page=${page - 1}${category ? '&category=' + category : ''}${tag ? '&tag=' + tag : ''}${search ? '&search=' + search : ''}">Previous</a>`;
            } else {
                paginationHTML += `<span class="disabled">Previous</span>`;
            }
            
            // Page numbers
            const startPage = Math.max(1, page - 2);
            const endPage = Math.min(totalPages, page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === page) {
                    paginationHTML += `<span class="current">${i}</span>`;
                } else {
                    paginationHTML += `<a href="index.php?page=${i}${category ? '&category=' + category : ''}${tag ? '&tag=' + tag : ''}${search ? '&search=' + search : ''}">${i}</a>`;
                }
            }
            
            // Next button
            if (page < totalPages) {
                paginationHTML += `<a href="index.php?page=${page + 1}${category ? '&category=' + category : ''}${tag ? '&tag=' + tag : ''}${search ? '&search=' + search : ''}">Next</a>`;
            } else {
                paginationHTML += `<span class="disabled">Next</span>`;
            }
            
            paginationContainer.innerHTML = paginationHTML;
            paginationContainer.style.display = 'flex';
        }
        
        // Helper function to truncate HTML content
        function truncateHTML(html, maxLength) {
            // Create a temporary div
            const div = document.createElement('div');
            div.innerHTML = html;
            
            // Get text content
            let text = div.textContent || div.innerText || '';
            
            // Truncate text
            if (text.length > maxLength) {
                text = text.substring(0, maxLength) + '...';
            }
            
            return text;
        }
    </script>
</body>
</html>