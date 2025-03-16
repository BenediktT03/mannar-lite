 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get post ID
$postId = isset($_GET['id']) ? $_GET['id'] : null;

// Initialize variables
$post = null;
$postTitle = 'Post Not Found';
$postDescription = '';
$postImage = '';

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
    <title id="page-title"><?php echo $postTitle; ?> - <?php echo $siteTitle; ?></title>
    <meta name="description" id="page-description" content="<?php echo $postDescription; ?>">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" id="og-title" content="<?php echo $postTitle; ?> - <?php echo $siteTitle; ?>">
    <meta property="og:description" id="og-description" content="<?php echo $postDescription; ?>">
    <meta property="og:type" content="article">
    <?php if (isset($settings['siteUrl']) && !empty($settings['siteUrl'])): ?>
    <meta property="og:url" content="<?php echo $settings['siteUrl']; ?>/post.php?id=<?php echo $postId; ?>">
    <?php endif; ?>
    <meta property="og:image" id="og-image" content="<?php echo $postImage; ?>">
    
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
        /* Post-specific styles */
        .post-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .post-header {
            margin-bottom: 2rem;
        }
        
        .post-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .post-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .post-featured-image {
            margin-bottom: 2rem;
        }
        
        .post-featured-image img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        
        .post-content {
            line-height: 1.8;
            font-size: 1.1rem;
        }
        
        .post-content p {
            margin-bottom: 1.5rem;
        }
        
        .post-content h2, .post-content h3, .post-content h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin: 1.5rem 0;
        }
        
        .post-content blockquote {
            border-left: 4px solid <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            padding-left: 1rem;
            font-style: italic;
            color: #555;
            margin: 1.5rem 0;
        }
        
        .post-tags {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .post-tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #e9ecef;
            border-radius: 2px;
            font-size: 0.85rem;
            color: #333;
            text-decoration: none;
        }
        
        .post-tag:hover {
            background-color: #dee2e6;
        }
        
        .post-navigation {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e0e0e0;
            padding-top: 1.5rem;
        }
        
        .post-navigation a {
            color: <?php echo isset($settings['primaryColor']) ? $settings['primaryColor'] : '#0077b6'; ?>;
            text-decoration: none;
            display: inline-block;
            padding: 0.5rem 0;
        }
        
        .post-navigation a:hover {
            text-decoration: underline;
        }
        
        .post-comments {
            margin-top: 3rem;
            border-top: 1px solid #e0e0e0;
            padding-top: 1.5rem;
        }
        
        .comment {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .comment:last-child {
            border-bottom: none;
        }
        
        .comment-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .comment-author {
            font-weight: 500;
        }
        
        .comment-form {
            margin-top: 2rem;
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
        <div id="post-container" class="post-container" style="display: none;">
            <article>
                <header class="post-header">
                    <h1 id="post-title" class="post-title">Loading post...</h1>
                    <div id="post-meta" class="post-meta"></div>
                </header>
                
                <div id="post-featured-image" class="post-featured-image"></div>
                
                <div id="post-content" class="post-content">
                    <p>Loading content...</p>
                </div>
                
                <div id="post-tags" class="post-tags"></div>
            </article>
            
            <div id="post-navigation" class="post-navigation">
                <div id="prev-post"></div>
                <div id="next-post"></div>
            </div>
            
            <div id="post-comments" class="post-comments">
                <h2>Comments</h2>
                <div id="comments-container">
                    <p>Loading comments...</p>
                </div>
                
                <div id="comment-form-container">
                    <h3>Leave a Comment</h3>
                    <form id="comment-form" class="comment-form">
                        <div class="form-group">
                            <label for="comment-name">Name</label>
                            <input type="text" id="comment-name" required>
                        </div>
                        <div class="form-group">
                            <label for="comment-email">Email</label>
                            <input type="email" id="comment-email" required>
                        </div>
                        <div class="form-group">
                            <label for="comment-text">Comment</label>
                            <textarea id="comment-text" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn">Submit Comment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div id="error-container" class="error-container" style="display: none;">
            <div class="error-icon">⚠️</div>
            <h1>Post Not Found</h1>
            <p>Sorry, the post you are looking for does not exist or has been removed.</p>
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
        import { getFirestore, collection, doc, getDoc, getDocs, addDoc, query, where, orderBy, limit } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Get post ID
        const postId = <?php echo $postId ? "'" . addslashes($postId) . "'" : 'null'; ?>;
        
        // Global variables
        let post = null;
        let comments = [];
        
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
                
                // Pre-fill comment form if user is logged in
                document.getElementById('comment-name').value = user.displayName || '';
                document.getElementById('comment-email').value = user.email || '';
            }
            
            // Load post
            if (postId) {
                loadPost();
            } else {
                showError();
            }
        });
        
        // Load post
        async function loadPost() {
            try {
                const postRef = doc(db, "posts", postId);
                const postSnapshot = await getDoc(postRef);
                
                if (postSnapshot.exists()) {
                    post = postSnapshot.data();
                    
                    // Check if post is published
                    if (post.status !== 'published') {
                        const user = auth.currentUser;
                        if (!user || (user.uid !== post.authorId && !await isAdminOrEditor(user.uid))) {
                            showError();
                            return;
                        }
                    }
                    
                    // Display post
                    displayPost();
                    
                    // Load comments
                    loadComments();
                    
                    // Load related posts
                    loadRelatedPosts();
                } else {
                    showError();
                }
            } catch (error) {
                console.error("Error loading post:", error);
                showError();
            }
        }
        
        // Display post
        function displayPost() {
            // Show post container
            document.getElementById('post-container').style.display = 'block';
            
            // Set post title
            const postTitle = document.getElementById('post-title');
            postTitle.textContent = post.title;
            
            // Set page title and meta tags
            document.getElementById('page-title').textContent = `${post.title} - <?php echo $siteTitle; ?>`;
            document.getElementById('page-description').content = post.excerpt || '';
            document.getElementById('og-title').content = `${post.title} - <?php echo $siteTitle; ?>`;
            document.getElementById('og-description').content = post.excerpt || '';
            
            // Set featured image if available
            if (post.featuredImage) {
                document.getElementById('post-featured-image').innerHTML = `
                    <img src="${post.featuredImage}" alt="${post.title}">
                `;
                document.getElementById('og-image').content = post.featuredImage;
            } else {
                document.getElementById('post-featured-image').style.display = 'none';
            }
            
            // Set post meta
            const publishedDate = post.publishedAt ? new Date(post.publishedAt) : new Date();
            const formattedDate = publishedDate.toLocaleDateString();
            
            let metaHTML = `
                <span>By ${post.authorName || 'Unknown'}</span> • 
                <span>${formattedDate}</span>
            `;
            
            if (post.category) {
                metaHTML += ` • <span>Category: <a href="index.php?category=${post.category}">${post.category}</a></span>`;
            }
            
            document.getElementById('post-meta').innerHTML = metaHTML;
            
            // Set post content
            document.getElementById('post-content').innerHTML = post.content || '<p>No content available.</p>';
            
            // Set post tags
            if (post.tags && post.tags.length > 0) {
                let tagsHTML = '';
                
                post.tags.forEach(tag => {
                    tagsHTML += `<a href="index.php?tag=${tag}" class="post-tag">${tag}</a>`;
                });
                
                document.getElementById('post-tags').innerHTML = tagsHTML;
            } else {
                document.getElementById('post-tags').style.display = 'none';
            }
            
            // Apply custom CSS if available
            if (post.customCSS) {
                const styleElement = document.createElement('style');
                styleElement.textContent = post.customCSS;
                document.getElementById('custom-css-container').appendChild(styleElement);
            }
            
            // Set up comment form submission
            document.getElementById('comment-form').addEventListener('submit', submitComment);
        }
        
        // Load comments
        async function loadComments() {
            try {
                const commentsQuery = query(
                    collection(db, "comments"),
                    where("postId", "==", postId),
                    where("approved", "==", true),
                    orderBy("createdAt", "desc")
                );
                const commentsSnapshot = await getDocs(commentsQuery);
                
                const commentsContainer = document.getElementById('comments-container');
                
                if (commentsSnapshot.empty) {
                    commentsContainer.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
                    return;
                }
                
                comments = [];
                commentsSnapshot.forEach((doc) => {
                    comments.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                displayComments();
            } catch (error) {
                console.error("Error loading comments:", error);
                document.getElementById('comments-container').innerHTML = '<p>Error loading comments. Please try again later.</p>';
            }
        }
        
        // Display comments
        function displayComments() {
            const commentsContainer = document.getElementById('comments-container');
            
            if (comments.length === 0) {
                commentsContainer.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
                return;
            }
            
            let commentsHTML = '';
            
            comments.forEach(comment => {
                const commentDate = new Date(comment.createdAt).toLocaleDateString();
                
                commentsHTML += `
                    <div class="comment">
                        <div class="comment-meta">
                            <span class="comment-author">${comment.name}</span>
                            <span class="comment-date">${commentDate}</span>
                        </div>
                        <div class="comment-content">
                            <p>${comment.text}</p>
                        </div>
                    </div>
                `;
            });
            
            commentsContainer.innerHTML = commentsHTML;
        }
        
        // Submit comment
        async function submitComment(e) {
            e.preventDefault();
            
            const nameInput = document.getElementById('comment-name');
            const emailInput = document.getElementById('comment-email');
            const textInput = document.getElementById('comment-text');
            
            const name = nameInput.value.trim();
            const email = emailInput.value.trim();
            const text = textInput.value.trim();
            
            if (!name || !email || !text) {
                alert('Please fill in all fields.');
                return;
            }
            
            try {
                // Get settings to check comment status
                const settingsDoc = await getDoc(doc(db, "system", "settings"));
                const settings = settingsDoc.exists() ? settingsDoc.data() : {};
                
                // Check if comments are enabled
                if (settings.commentStatus === 'disabled') {
                    alert('Comments are currently disabled.');
                    return;
                }
                
                // Determine if comment should be automatically approved
                const approved = settings.commentStatus !== 'moderated';
                
                // Create comment
                const commentData = {
                    postId,
                    name,
                    email,
                    text,
                    approved,
                    createdAt: new Date().toISOString()
                };
                
                // Add user ID if user is logged in
                const user = auth.currentUser;
                if (user) {
                    commentData.userId = user.uid;
                }
                
                // Save to Firestore
                await addDoc(collection(db, "comments"), commentData);
                
                // Clear form
                nameInput.value = user ? user.displayName || '' : '';
                emailInput.value = user ? user.email || '' : '';
                textInput.value = '';
                
                // Show success message
                if (approved) {
                    alert('Comment submitted successfully!');
                    
                    // Reload comments
                    loadComments();
                } else {
                    alert('Comment submitted successfully! It will be visible after approval by an administrator.');
                }
            } catch (error) {
                console.error("Error submitting comment:", error);
                alert('Error submitting comment. Please try again later.');
            }
        }
        
        // Load related posts
        async function loadRelatedPosts() {
            try {
                // Determine query constraints based on post data
                let constraints = [
                    where("status", "==", "published"),
                    where("id", "!=", postId)
                ];
                
                // Add category constraint if available
                if (post.category) {
                    constraints.push(where("category", "==", post.category));
                }
                
                // Create query
                let relatedPostsQuery = query(
                    collection(db, "posts"),
                    ...constraints,
                    limit(2)
                );
                
                // Get related posts
                const relatedPostsSnapshot = await getDocs(relatedPostsQuery);
                
                // If not enough related posts by category, try related by tag
                if (relatedPostsSnapshot.size < 2 && post.tags && post.tags.length > 0) {
                    // Try to find posts with at least one matching tag
                    const tagQueries = post.tags.map(tag => {
                        return query(
                            collection(db, "posts"),
                            where("status", "==", "published"),
                            where("id", "!=", postId),
                            where("tags", "array-contains", tag),
                            limit(2)
                        );
                    });
                    
                    // Execute all tag queries
                    const tagQueryResults = await Promise.all(tagQueries.map(q => getDocs(q)));
                    
                    // Combine results, removing duplicates
                    const tagRelatedPosts = [];
                    const seenIds = new Set();
                    
                    tagQueryResults.forEach(snapshot => {
                        snapshot.forEach(doc => {
                            if (!seenIds.has(doc.id)) {
                                seenIds.add(doc.id);
                                tagRelatedPosts.push({
                                    id: doc.id,
                                    ...doc.data()
                                });
                            }
                        });
                    });
                    
                    // Add tag-related posts to category-related posts
                    relatedPostsSnapshot.forEach(doc => {
                        seenIds.add(doc.id);
                    });
                    
                    // Use tag-related posts if needed
                    if (tagRelatedPosts.length > 0) {
                        // Add to navigation
                        updatePostNavigation(tagRelatedPosts);
                        return;
                    }
                }
                
                // If still not enough, get recent posts
                if (relatedPostsSnapshot.size < 2) {
                    const recentPostsQuery = query(
                        collection(db, "posts"),
                        where("status", "==", "published"),
                        where("id", "!=", postId),
                        orderBy("publishedAt", "desc"),
                        limit(2)
                    );
                    
                    const recentPostsSnapshot = await getDocs(recentPostsQuery);
                    
                    // Combine results
                    const relatedPosts = [];
                    const seenIds = new Set();
                    
                    // Add category/tag related posts first
                    relatedPostsSnapshot.forEach(doc => {
                        seenIds.add(doc.id);
                        relatedPosts.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Add recent posts if needed
                    recentPostsSnapshot.forEach(doc => {
                        if (!seenIds.has(doc.id) && relatedPosts.length < 2) {
                            relatedPosts.push({
                                id: doc.id,
                                ...doc.data()
                            });
                        }
                    });
                    
                    // Update navigation
                    updatePostNavigation(relatedPosts);
                } else {
                    // Convert snapshot to array
                    const relatedPosts = [];
                    relatedPostsSnapshot.forEach(doc => {
                        relatedPosts.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Update navigation
                    updatePostNavigation(relatedPosts);
                }
            } catch (error) {
                console.error("Error loading related posts:", error);
            }
        }
        
        // Update post navigation
        function updatePostNavigation(relatedPosts) {
            const prevPostEl = document.getElementById('prev-post');
            const nextPostEl = document.getElementById('next-post');
            
            if (relatedPosts.length > 0) {
                prevPostEl.innerHTML = `<a href="post.php?id=${relatedPosts[0].id}">« ${relatedPosts[0].title}</a>`;
            } else {
                prevPostEl.innerHTML = '';
            }
            
            if (relatedPosts.length > 1) {
                nextPostEl.innerHTML = `<a href="post.php?id=${relatedPosts[1].id}">${relatedPosts[1].title} »</a>`;
            } else {
                nextPostEl.innerHTML = '';
            }
        }
        
        // Show error
        function showError() {
            document.getElementById('post-container').style.display = 'none';
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