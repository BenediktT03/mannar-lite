 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get page ID
$pageId = isset($_GET['id']) ? $_GET['id'] : null;

// Initialize variables
$page = null;
$pageTitle = 'Page Not Found';
$pageDescription = '';
$pageImage = '';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title"><?php echo $pageTitle; ?> - <?php echo $siteTitle; ?></title>
    <meta name="description" id="page-description" content="<?php echo $pageDescription; ?>">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" id="og-title" content="<?php echo $pageTitle; ?> - <?php echo $siteTitle; ?>">
    <meta property="og:description" id="og-description" content="<?php echo $pageDescription; ?>">
    <meta property="og:type" content="website">
    <?php if (isset($settings['siteUrl']) && !empty($settings['siteUrl'])): ?>
    <meta property="og:url" content="<?php echo $settings['siteUrl']; ?>/page.php?id=<?php echo $pageId; ?>">
    <?php endif; ?>
    <meta property="og:image" id="og-image" content="<?php echo $pageImage; ?>">
    
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
        /* Page-specific styles */
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .page-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .page-featured-image {
            margin-bottom: 2rem;
        }
        
        .page-featured-image img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        
        .page-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }
        
        .page-content p {
            margin-bottom: 1.5rem;
        }
        
        .page-content h2, .page-content h3, .page-content h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin: 1.5rem 0;
        }
        
        .page-content blockquote {
            border-left: 4px solid <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            padding-left: 1rem;
            font-style: italic;
            color: #555;
            margin: 1.5rem 0;
        }
        
        /* Template-specific layouts */
        .template-full-width .page-container {
            max-width: 100%;
            padding: 0;
        }
        
        .template-sidebar {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .template-sidebar {
                grid-template-columns: 1fr;
            }
        }
        
        .error-container {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dc3545;
        }
        
        .back-to-home {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .back-to-home:hover {
            background-color: <?php echo isset($settings['secondaryColor']) ? $settings['secondaryColor'] : '#023e8a'; ?>;
        }
    </style>
    <div id="custom-css-container"></div>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1><?php echo $siteTitle; ?></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
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

    <main>
        <div id="page-container" class="page-container" style="display: none;">
            <article>
                <header class="page-header">
                    <h1 id="page-title" class="page-title">Loading page...</h1>
                    <div id="page-meta" class="page-meta"></div>
                </header>
                
                <div id="page-featured-image" class="page-featured-image"></div>
                
                <div id="page-content" class="page-content">
                    <p>Loading content...</p>
                </div>
            </article>
        </div>
        
        <div id="error-container" class="error-container" style="display: none;">
            <div class="error-icon">⚠️</div>
            <h1>Page Not Found</h1>
            <p>Sorry, the page you are looking for does not exist or has been removed.</p>
            <a href="index.php" class="back-to-home">Back to Home</a>
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
        import { getFirestore, collection, doc, getDoc, getDocs, query, where, orderBy } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Get page ID
        const pageId = <?php echo $pageId ? "'" . addslashes($pageId) . "'" : 'null'; ?>;
        
        // Global variables
        let page = null;
        
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
            
            // Load page
            if (pageId) {
                loadPage();
            } else {
                showError();
            }
        });
        
        // Load page
        async function loadPage() {
            try {
                const pageRef = doc(db, "pages", pageId);
                const pageSnapshot = await getDoc(pageRef);
                
                if (pageSnapshot.exists()) {
                    page = pageSnapshot.data();
                    
                    // Check if page is published
                    if (page.status !== 'published') {
                        const user = auth.currentUser;
                        if (!user || (user.uid !== page.authorId && !await isAdminOrEditor(user.uid))) {
                            showError();
                            return;
                        }
                    }
                    
                    // Apply template if specified
                    if (page.template) {
                        applyTemplate(page.template);
                    }
                    
                    // Display page
                    displayPage();
                } else {
                    showError();
                }
            } catch (error) {
                console.error("Error loading page:", error);
                showError();
            }
        }
        
        // Display page
        function displayPage() {
            // Show page container
            document.getElementById('page-container').style.display = 'block';
            
            // Set page title
            const pageTitle = document.getElementById('page-title');
            pageTitle.textContent = page.title;
            
            // Set document title and meta tags
            document.getElementById('page-title').textContent = `${page.title} - <?php echo $siteTitle; ?>`;
            document.getElementById('page-description').content = page.excerpt || '';
            document.getElementById('og-title').content = `${page.title} - <?php echo $siteTitle; ?>`;
            document.getElementById('og-description').content = page.excerpt || '';
            
            // Set featured image if available
            if (page.featuredImage) {
                document.getElementById('page-featured-image').innerHTML = `
                    <img src="${page.featuredImage}" alt="${page.title}">
                `;
                document.getElementById('og-image').content = page.featuredImage;
            } else {
                document.getElementById('page-featured-image').style.display = 'none';
            }
            
            // Set page meta
            const lastUpdated = page.updatedAt ? new Date(page.updatedAt) : new Date();
            const formattedDate = lastUpdated.toLocaleDateString();
            
            let metaHTML = `
                <span>Last updated: ${formattedDate}</span>
            `;
            
            document.getElementById('page-meta').innerHTML = metaHTML;
            
            // Set page content
            document.getElementById('page-content').innerHTML = page.content || '<p>No content available.</p>';
            
            // Apply custom CSS if available
            if (page.customCSS) {
                const styleElement = document.createElement('style');
                styleElement.textContent = page.customCSS;
                document.getElementById('custom-css-container').appendChild(styleElement);
            }
        }
        
        // Apply template-specific layout
        function applyTemplate(template) {
            const pageContainer = document.getElementById('page-container');
            
            // Remove any existing template classes
            pageContainer.classList.remove('template-full-width', 'template-sidebar');
            
            // Apply new template class
            if (template === 'full-width') {
                pageContainer.classList.add('template-full-width');
            } else if (template === 'sidebar') {
                pageContainer.classList.add('template-sidebar');
                
                // Create sidebar container if it doesn't exist
                if (!document.getElementById('page-sidebar')) {
                    const sidebar = document.createElement('div');
                    sidebar.id = 'page-sidebar';
                    sidebar.className = 'page-sidebar';
                    
                    // Load sidebar widgets
                    loadSidebarWidgets(sidebar);
                    
                    // Add sidebar after content
                    const content = document.querySelector('.page-content');
                    content.parentNode.parentNode.appendChild(sidebar);
                }
            }
        }
        
        // Load sidebar widgets
        async function loadSidebarWidgets(container) {
            try {
                // Get sidebar widgets
                const widgetsSnapshot = await getDoc(doc(db, "widgets", "sidebar-page"));
                
                if (widgetsSnapshot.exists() && widgetsSnapshot.data().widgets) {
                    const widgets = widgetsSnapshot.data().widgets;
                    
                    // Render each widget
                    widgets.forEach(widget => {
                        const widgetElement = document.createElement('div');
                        widgetElement.className = 'widget';
                        
                        // Widget title
                        if (widget.title && widget.settings.show_title !== false) {
                            const title = document.createElement('h3');
                            title.textContent = widget.title;
                            widgetElement.appendChild(title);
                        }
                        
                        // Widget content based on type
                        const content = document.createElement('div');
                        content.className = 'widget-content';
                        
                        switch(widget.type) {
                            case 'text':
                                content.innerHTML = widget.settings.content || '';
                                break;
                                
                            case 'recent_posts':
                                // Will be populated with AJAX
                                content.innerHTML = '<p>Loading recent posts...</p>';
                                loadRecentPosts(content, widget.settings);
                                break;
                                
                            case 'categories':
                                // Will be populated with AJAX
                                content.innerHTML = '<p>Loading categories...</p>';
                                loadCategories(content, widget.settings);
                                break;
                                
                            case 'tags':
                                // Will be populated with AJAX
                                content.innerHTML = '<p>Loading tags...</p>';
                                loadTags(content, widget.settings);
                                break;
                                
                            case 'search':
                                content.innerHTML = `
                                    <form action="index.php" method="get" class="search-form">
                                        <input type="text" name="search" placeholder="${widget.settings.placeholder || 'Search...'}">
                                        <button type="submit">${widget.settings.button_text || 'Search'}</button>
                                    </form>
                                `;
                                break;
                                
                            case 'image':
                                if (widget.settings.image_url) {
                                    const imgHTML = widget.settings.link_url 
                                        ? `<a href="${widget.settings.link_url}"><img src="${widget.settings.image_url}" alt="${widget.settings.alt_text || ''}"></a>`
                                        : `<img src="${widget.settings.image_url}" alt="${widget.settings.alt_text || ''}">`;
                                    content.innerHTML = imgHTML;
                                }
                                break;
                                
                            case 'html':
                                content.innerHTML = widget.settings.html_content || '';
                                break;
                                
                            case 'social':
                                const socialLinks = [];
                                if (widget.settings.facebook) socialLinks.push(`<a href="${widget.settings.facebook}" class="social-link" target="_blank">${widget.settings.show_labels ? 'Facebook' : '<i class="social-icon facebook"></i>'}</a>`);
                                if (widget.settings.twitter) socialLinks.push(`<a href="${widget.settings.twitter}" class="social-link" target="_blank">${widget.settings.show_labels ? 'Twitter' : '<i class="social-icon twitter"></i>'}</a>`);
                                if (widget.settings.instagram) socialLinks.push(`<a href="${widget.settings.instagram}" class="social-link" target="_blank">${widget.settings.show_labels ? 'Instagram' : '<i class="social-icon instagram"></i>'}</a>`);
                                if (widget.settings.linkedin) socialLinks.push(`<a href="${widget.settings.linkedin}" class="social-link" target="_blank">${widget.settings.show_labels ? 'LinkedIn' : '<i class="social-icon linkedin"></i>'}</a>`);
                                if (widget.settings.youtube) socialLinks.push(`<a href="${widget.settings.youtube}" class="social-link" target="_blank">${widget.settings.show_labels ? 'YouTube' : '<i class="social-icon youtube"></i>'}</a>`);
                                
                                content.innerHTML = `<div class="social-links">${socialLinks.join('')}</div>`;
                                break;
                                
                            default:
                                content.innerHTML = '<p>Widget type not supported</p>';
                        }
                        
                        widgetElement.appendChild(content);
                        container.appendChild(widgetElement);
                    });
                } else {
                    container.innerHTML = '<p>No widgets configured for this sidebar.</p>';
                }
            } catch (error) {
                console.error("Error loading sidebar widgets:", error);
                container.innerHTML = '<p>Error loading widgets. Please try again.</p>';
            }
        }
        
        // Load recent posts for widget
        async function loadRecentPosts(container, settings) {
            try {
                // Create query
                let recentPostsQuery = query(
                    collection(db, "posts"),
                    where("status", "==", "published"),
                    orderBy("publishedAt", "desc"),
                    limit(settings.count || 5)
                );
                
                // Add category filter if specified
                if (settings.category) {
                    recentPostsQuery = query(
                        collection(db, "posts"),
                        where("status", "==", "published"),
                        where("category", "==", settings.category),
                        orderBy("publishedAt", "desc"),
                        limit(settings.count || 5)
                    );
                }
                
                const recentPostsSnapshot = await getDocs(recentPostsQuery);
                
                if (recentPostsSnapshot.empty) {
                    container.innerHTML = '<p>No recent posts found.</p>';
                    return;
                }
                
                let postsHTML = '<ul class="widget-posts">';
                
                recentPostsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    const date = post.publishedAt ? new Date(post.publishedAt).toLocaleDateString() : '';
                    
                    postsHTML += `
                        <li class="widget-post">
                            <a href="post.php?id=${doc.id}" class="widget-post-title">${post.title}</a>
                            ${settings.show_date ? `<span class="widget-post-date">${date}</span>` : ''}
                            ${settings.show_excerpt && post.excerpt ? `<p class="widget-post-excerpt">${post.excerpt.substring(0, 100)}${post.excerpt.length > 100 ? '...' : ''}</p>` : ''}
                        </li>
                    `;
                });
                
                postsHTML += '</ul>';
                container.innerHTML = postsHTML;
            } catch (error) {
                console.error("Error loading recent posts:", error);
                container.innerHTML = '<p>Error loading recent posts.</p>';
            }
        }
        
        // Load categories for widget
        async function loadCategories(container, settings) {
            try {
                const categoriesQuery = query(
                    collection(db, "categories"),
                    orderBy("name")
                );
                
                const categoriesSnapshot = await getDocs(categoriesQuery);
                
                if (categoriesSnapshot.empty) {
                    container.innerHTML = '<p>No categories found.</p>';
                    return;
                }
                
                let categoriesHTML = '<ul class="widget-categories">';
                
                // Get post counts if needed
                let categoryCounts = {};
                if (settings.show_count) {
                    const postsSnapshot = await getDocs(collection(db, "posts"));
                    
                    postsSnapshot.forEach((doc) => {
                        const post = doc.data();
                        if (post.category) {
                            categoryCounts[post.category] = (categoryCounts[post.category] || 0) + 1;
                        }
                    });
                }
                
                categoriesSnapshot.forEach((doc) => {
                    const category = doc.data();
                    const count = categoryCounts[category.name] || 0;
                    
                    // Skip empty categories if needed
                    if (settings.hide_empty && count === 0) {
                        return;
                    }
                    
                    categoriesHTML += `
                        <li class="widget-category">
                            <a href="index.php?category=${category.name}" class="widget-category-link">${category.name}</a>
                            ${settings.show_count ? `<span class="widget-category-count">(${count})</span>` : ''}
                        </li>
                    `;
                });
                
                categoriesHTML += '</ul>';
                container.innerHTML = categoriesHTML;
            } catch (error) {
                console.error("Error loading categories:", error);
                container.innerHTML = '<p>Error loading categories.</p>';
            }
        }
        
        // Load tags for widget
        async function loadTags(container, settings) {
            try {
                const postsQuery = query(
                    collection(db, "posts"),
                    where("status", "==", "published")
                );
                
                const postsSnapshot = await getDocs(postsQuery);
                
                if (postsSnapshot.empty) {
                    container.innerHTML = '<p>No tags found.</p>';
                    return;
                }
                
                // Collect all tags
                const tagCounts = {};
                
                postsSnapshot.forEach((doc) => {
                    const post = doc.data();
                    
                    if (post.tags && Array.isArray(post.tags)) {
                        post.tags.forEach(tag => {
                            if (tag) {
                                tagCounts[tag] = (tagCounts[tag] || 0) + 1;
                            }
                        });
                    }
                });
                
                // Sort by count and limit
                const sortedTags = Object.keys(tagCounts)
                    .map(tag => ({ tag, count: tagCounts[tag] }))
                    .sort((a, b) => b.count - a.count)
                    .slice(0, settings.max_tags || 20);
                
                if (sortedTags.length === 0) {
                    container.innerHTML = '<p>No tags found.</p>';
                    return;
                }
                
                // Generate tag cloud
                let tagsHTML = '<div class="widget-tag-cloud">';
                
                sortedTags.forEach(({ tag, count }) => {
                    tagsHTML += `
                        <a href="index.php?tag=${encodeURIComponent(tag)}" class="widget-tag">
                            ${tag}
                            ${settings.show_count ? `<span class="widget-tag-count">(${count})</span>` : ''}
                        </a>
                    `;
                });
                
                tagsHTML += '</div>';
                container.innerHTML = tagsHTML;
            } catch (error) {
                console.error("Error loading tags:", error);
                container.innerHTML = '<p>Error loading tags.</p>';
            }
        }
        
        // Show error
        function showError() {
            document.getElementById('page-container').style.display = 'none';
            document.getElementById('error-container').style.display = 'block';
        }
        
        // Check if user is admin or editor
        async function isAdminOrEditor(userId) {
            try {
                const userDoc = await getDoc(doc(db, "users", userId));
                if (userDoc.exists()) {
                    const userData = userDoc.data();
                    return userData.role === 'admin' || userData.role === 'editor';
                }
                return false;
            } catch (error) {
                console.error("Error checking user role:", error);
                return false;
            }
        }
    </script>
</body>
</html>