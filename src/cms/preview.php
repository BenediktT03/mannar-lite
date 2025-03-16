 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get content parameters
$contentType = isset($_GET['type']) ? $_GET['type'] : 'post';
$contentId = isset($_GET['id']) ? $_GET['id'] : null;

// Set default titles
$pageTitle = 'Content Preview';
$contentTitle = 'Preview';

// Define a flag for preview mode
define('IS_PREVIEW', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Preview specific styles */
        .preview-bar {
            background-color: #1a1a2e;
            color: #fff;
            padding: 10px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .preview-bar-message {
            font-weight: 500;
        }
        
        .preview-bar-actions {
            display: flex;
            gap: 10px;
        }
        
        .preview-content {
            margin-top: 60px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
        }
        
        .preview-content img {
            max-width: 100%;
            height: auto;
        }
        
        .preview-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .preview-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .preview-excerpt {
            font-size: 1.2rem;
            font-style: italic;
            color: #555;
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 3px solid #0077b6;
        }
    </style>
</head>
<body>
    <!-- Preview Bar -->
    <div class="preview-bar">
        <div class="preview-bar-message">
            Preview Mode - This is how your content will look when published
        </div>
        <div class="preview-bar-actions">
            <button id="close-preview" class="btn">Close Preview</button>
            <button id="edit-content" class="btn btn-primary">Edit</button>
            <button id="publish-content" class="btn btn-success">Publish</button>
        </div>
    </div>

    <div class="preview-content">
        <h1 id="content-title" class="preview-title">Loading preview...</h1>
        <div id="content-meta" class="preview-meta"></div>
        <div id="content-excerpt" class="preview-excerpt"></div>
        <div id="content-body"></div>
    </div>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, doc, getDoc, updateDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Content variables
        const contentType = "<?php echo $contentType; ?>";
        const contentId = "<?php echo $contentId; ?>";
        let contentData = null;
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                // Check if user has permission to preview content
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && (userDoc.data().role === 'admin' || userDoc.data().role === 'editor' || userDoc.data().role === 'author')) {
                    // User has permission, load content
                    loadContent();
                } else {
                    // User does not have permission
                    document.getElementById('content-title').textContent = 'Access Denied';
                    document.getElementById('content-body').innerHTML = '<p>You do not have permission to preview content.</p>';
                }
            } else {
                // User is not signed in
                document.getElementById('content-title').textContent = 'Authentication Required';
                document.getElementById('content-body').innerHTML = '<p>Please <a href="login.php">login</a> to preview content.</p>';
            }
        });
        
        // Load content
        async function loadContent() {
            if (!contentId || !contentType) {
                document.getElementById('content-title').textContent = 'Preview Error';
                document.getElementById('content-body').innerHTML = '<p>Missing content ID or type parameters.</p>';
                return;
            }
            
            try {
                const contentRef = doc(db, contentType + 's', contentId);
                const contentSnapshot = await getDoc(contentRef);
                
                if (contentSnapshot.exists()) {
                    contentData = contentSnapshot.data();
                    
                    // Set document title
                    document.title = `${contentData.title} - Preview - PHP Firebase CMS`;
                    
                    // Display content
                    document.getElementById('content-title').textContent = contentData.title || 'Untitled';
                    
                    // Set meta information
                    let metaHTML = '';
                    
                    // Author & date
                    const createdDate = contentData.createdAt ? new Date(contentData.createdAt).toLocaleDateString() : 'Unknown date';
                    metaHTML += `By ${contentData.authorName || 'Unknown'} | ${createdDate}`;
                    
                    // Add category for posts
                    if (contentType === 'post' && contentData.category) {
                        metaHTML += ` | Category: ${contentData.category}`;
                    }
                    
                    // Add tags for posts
                    if (contentType === 'post' && contentData.tags && contentData.tags.length > 0) {
                        metaHTML += ` | Tags: ${contentData.tags.join(', ')}`;
                    }
                    
                    document.getElementById('content-meta').innerHTML = metaHTML;
                    
                    // Set excerpt
                    if (contentData.excerpt) {
                        document.getElementById('content-excerpt').textContent = contentData.excerpt;
                        document.getElementById('content-excerpt').style.display = 'block';
                    } else {
                        document.getElementById('content-excerpt').style.display = 'none';
                    }
                    
                    // Set content body
                    document.getElementById('content-body').innerHTML = contentData.content || '<p>No content available.</p>';
                    
                    // Add custom CSS if available
                    if (contentData.customCSS) {
                        const styleElement = document.createElement('style');
                        styleElement.textContent = contentData.customCSS;
                        document.head.appendChild(styleElement);
                    }
                } else {
                    document.getElementById('content-title').textContent = 'Content Not Found';
                    document.getElementById('content-body').innerHTML = '<p>The requested content does not exist.</p>';
                }
            } catch (error) {
                console.error("Error loading content:", error);
                document.getElementById('content-title').textContent = 'Error Loading Preview';
                document.getElementById('content-body').innerHTML = `<p>An error occurred: ${error.message}</p>`;
            }
        }
        
        // Setup preview bar actions
        document.getElementById('close-preview').addEventListener('click', () => {
            window.close();
        });
        
        document.getElementById('edit-content').addEventListener('click', () => {
            window.location.href = `editor.php?id=${contentId}&type=${contentType}`;
        });
        
        document.getElementById('publish-content').addEventListener('click', async () => {
            try {
                // Get current user
                const user = auth.currentUser;
                if (!user) {
                    alert('You must be logged in to publish content.');
                    return;
                }
                
                // Check if user has permission to publish
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (!userDoc.exists() || (userDoc.data().role !== 'admin' && userDoc.data().role !== 'editor')) {
                    alert('You do not have permission to publish content.');
                    return;
                }
                
                // Update content status to published
                const contentRef = doc(db, contentType + 's', contentId);
                await updateDoc(contentRef, {
                    status: 'published',
                    publishedAt: new Date().toISOString(),
                    publishedBy: user.uid
                });
                
                alert('Content published successfully!');
                
                // Redirect to admin page
                window.location.href = 'admin.php';
            } catch (error) {
                console.error("Error publishing content:", error);
                alert(`Error publishing content: ${error.message}`);
            }
        });
    </script>
</body>
</html>