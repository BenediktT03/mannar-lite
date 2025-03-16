 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get content type (post or page)
$contentType = isset($_GET['type']) ? $_GET['type'] : 'post';
$contentId = isset($_GET['id']) ? $_GET['id'] : null;
$isEdit = !empty($contentId);

// Set page title
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' ' . ucfirst($contentType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        /* Editor-specific styles */
        .editor-container {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        
        .editor-main {
            flex: 3;
        }
        
        .editor-sidebar {
            flex: 1;
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .editor-container {
                grid-template-columns: 1fr;
            }
        }
        
        .tox-tinymce {
            border-radius: 4px;
            border: 1px solid #e0e0e0 !important;
        }
        
        #preview-frame {
            width: 100%;
            height: 500px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        
        .tab-nav {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .tab-nav button {
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .tab-nav button.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
            position: relative;
            z-index: 1;
        }
        
        .tab-content {
            border: 1px solid #e0e0e0;
            border-radius: 0 4px 4px 4px;
            padding: 1rem;
            background-color: #fff;
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1><?php echo $pageTitle; ?></h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="editor-container" style="display: none;">
                <div class="editor-container">
                    <div class="editor-main">
                        <form id="content-form">
                            <div class="form-group">
                                <label for="content-title">Title</label>
                                <input type="text" id="content-title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-slug">Slug</label>
                                <input type="text" id="content-slug">
                                <small>Leave blank to auto-generate from title</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-excerpt">Excerpt</label>
                                <textarea id="content-excerpt" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-editor">Content</label>
                                <textarea id="content-editor"></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <div class="editor-sidebar">
                        <div class="form-group">
                            <label for="content-status">Status</label>
                            <select id="content-status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        
                        <?php if ($contentType === 'post'): ?>
                        <div class="form-group">
                            <label for="content-category">Category</label>
                            <select id="content-category">
                                <option value="">Select Category</option>
                                <!-- Categories will be loaded dynamically -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="content-tags">Tags</label>
                            <input type="text" id="content-tags">
                            <small>Separate tags with commas</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="content-featured-image">Featured Image</label>
                            <input type="file" id="content-featured-image" accept="image/*">
                            <div id="featured-image-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" id="save-draft-btn" class="btn">Save Draft</button>
                            <button type="button" id="publish-btn" class="btn btn-primary">Publish</button>
                            <?php if ($isEdit): ?>
                            <button type="button" id="preview-btn" class="btn">Preview</button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-nav">
                            <button class="tab-btn active" data-tab="seo">SEO</button>
                            <button class="tab-btn" data-tab="advanced">Advanced</button>
                        </div>
                        
                        <div class="tab-content">
                            <div id="seo-tab" class="tab-pane active">
                                <div class="form-group">
                                    <label for="meta-title">Meta Title</label>
                                    <input type="text" id="meta-title">
                                </div>
                                <div class="form-group">
                                    <label for="meta-description">Meta Description</label>
                                    <textarea id="meta-description" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div id="advanced-tab" class="tab-pane">
                                <div class="form-group">
                                    <label for="content-template">Template</label>
                                    <select id="content-template">
                                        <option value="default">Default</option>
                                        <option value="full-width">Full Width</option>
                                        <option value="sidebar">With Sidebar</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="content-custom-css">Custom CSS</label>
                                    <textarea id="content-custom-css" rows="5"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Modal -->
                <div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000;">
                    <div style="position: relative; width: 90%; height: 90%; margin: 2% auto; background-color: #fff; border-radius: 8px; overflow: hidden;">
                        <button id="close-preview" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                        <iframe id="preview-frame" style="width: 100%; height: 100%; border: none;"></iframe>
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
        import { getFirestore, collection, doc, getDoc, setDoc, updateDoc, addDoc, query, orderBy, getDocs } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
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
        
        // Content variables
        const contentType = "<?php echo $contentType; ?>";
        const contentId = "<?php echo $contentId; ?>";
        const isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;
        let contentData = null;
        let featuredImageURL = null;
        
        // Initialize TinyMCE editor
        tinymce.init({
            selector: '#content-editor',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500,
            menubar: false,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            },
            // Image upload handler
            images_upload_handler: function(blobInfo, progress) {
                return new Promise((resolve, reject) => {
                    const file = blobInfo.blob();
                    const fileName = blobInfo.filename();
                    
                    // Create storage reference
                    const storagePath = `content-images/${Date.now()}_${fileName}`;
                    const storageRef = ref(storage, storagePath);
                    
                    // Upload file
                    const uploadTask = uploadBytesResumable(storageRef, file);
                    
                    uploadTask.on('state_changed',
                        (snapshot) => {
                            // Track upload progress
                            const progressValue = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                            progress(progressValue);
                        },
                        (error) => {
                            reject(error.message);
                        },
                        async () => {
                            // Upload completed successfully
                            const downloadURL = await getDownloadURL(uploadTask.snapshot.ref);
                            resolve(downloadURL);
                        }
                    );
                });
            }
        });
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const editorContainer = document.getElementById('editor-container');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Check if user has permission to edit content
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && (userDoc.data().role === 'admin' || userDoc.data().role === 'editor')) {
                    // User has permission
                    editorContainer.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Initialize editor
                    initializeEditor(user);
                } else {
                    // User does not have permission
                    editorContainer.style.display = 'none';
                    authWarning.innerHTML = '<p>You do not have permission to edit content. Redirecting to dashboard...</p>';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                editorContainer.style.display = 'none';
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
        
        // Initialize editor
        async function initializeEditor(user) {
            // If editing existing content, load it
            if (isEdit && contentId) {
                try {
                    const contentRef = doc(db, contentType + 's', contentId);
                    const contentSnapshot = await getDoc(contentRef);
                    
                    if (contentSnapshot.exists()) {
                        contentData = contentSnapshot.data();
                        
                        // Fill form fields
                        document.getElementById('content-title').value = contentData.title || '';
                        document.getElementById('content-slug').value = contentData.slug || '';
                        document.getElementById('content-excerpt').value = contentData.excerpt || '';
                        
                        // Wait for TinyMCE to initialize
                        tinymce.get('content-editor').setContent(contentData.content || '');
                        
                        document.getElementById('content-status').value = contentData.status || 'draft';
                        
                        if (contentType === 'post') {
                            document.getElementById('content-category').value = contentData.category || '';
                            document.getElementById('content-tags').value = contentData.tags ? contentData.tags.join(', ') : '';
                        }
                        
                        // SEO data
                        document.getElementById('meta-title').value = contentData.metaTitle || '';
                        document.getElementById('meta-description').value = contentData.metaDescription || '';
                        
                        // Advanced options
                        document.getElementById('content-template').value = contentData.template || 'default';
                        document.getElementById('content-custom-css').value = contentData.customCSS || '';
                        
                        // Featured image
                        if (contentData.featuredImage) {
                            featuredImageURL = contentData.featuredImage;
                            document.getElementById('featured-image-preview').innerHTML = `<img src="${featuredImageURL}" alt="Featured image" style="max-width: 100%; max-height: 200px; margin-top: 10px;">`;
                        }
                    } else {
                        alert('Content not found!');
                        window.location.href = 'admin.php';
                    }
                } catch (error) {
                    console.error("Error loading content:", error);
                    alert('Error loading content. Please try again.');
                }
            }
            
            // If content type is post, load categories
            if (contentType === 'post') {
                try {
                    const categoriesQuery = query(collection(db, "categories"), orderBy("name"));
                    const categoriesSnapshot = await getDocs(categoriesQuery);
                    
                    const categorySelect = document.getElementById('content-category');
                    
                    categoriesSnapshot.forEach((doc) => {
                        const category = doc.data();
                        const option = document.createElement('option');
                        option.value = doc.id;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                } catch (error) {
                    console.error("Error loading categories:", error);
                }
            }
            
            // Set up tab navigation
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const tabId = button.getAttribute('data-tab');
                    
                    // Update active button
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    button.classList.add('active');
                    
                    // Show selected tab
                    document.querySelectorAll('.tab-pane').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
            
            // Featured image upload
            document.getElementById('content-featured-image').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                try {
                    // Create storage reference
                    const storagePath = `featured-images/${Date.now()}_${file.name}`;
                    const storageRef = ref(storage, storagePath);
                    
                    // Upload file
                    const uploadTask = uploadBytesResumable(storageRef, file);
                    
                    uploadTask.on('state_changed',
                        (snapshot) => {
                            // Track upload progress
                            const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                            console.log('Upload progress:', progress);
                        },
                        (error) => {
                            console.error("Upload error:", error);
                            alert(`Error uploading featured image: ${error.message}`);
                        },
                        async () => {
                            // Upload completed successfully
                            featuredImageURL = await getDownloadURL(uploadTask.snapshot.ref);
                            
                            // Display image preview
                            document.getElementById('featured-image-preview').innerHTML = `<img src="${featuredImageURL}" alt="Featured image" style="max-width: 100%; max-height: 200px; margin-top: 10px;">`;
                        }
                    );
                } catch (error) {
                    console.error("Error uploading featured image:", error);
                    alert(`Error uploading featured image: ${error.message}`);
                }
            });
            
            // Auto-generate slug from title
            document.getElementById('content-title').addEventListener('blur', () => {
                const titleInput = document.getElementById('content-title');
                const slugInput = document.getElementById('content-slug');
                
                if (titleInput.value && !slugInput.value) {
                    // Create slug from title
                    slugInput.value = createSlug(titleInput.value);
                }
            });
            
            // Save draft button
            document.getElementById('save-draft-btn').addEventListener('click', () => {
                saveContent('draft');
            });
            
            // Publish button
            document.getElementById('publish-btn').addEventListener('click', () => {
                saveContent('published');
            });
            
            // Preview button (only for edit mode)
            if (isEdit) {
                document.getElementById('preview-btn').addEventListener('click', () => {
                    showPreview();
                });
                
                // Close preview modal
                document.getElementById('close-preview').addEventListener('click', () => {
                    document.getElementById('preview-modal').style.display = 'none';
                });
            }
        }
        
        // Save content
        async function saveContent(status) {
            // Get current user
            const user = auth.currentUser;
            if (!user) return;
            
            // Get form values
            const title = document.getElementById('content-title').value;
            const slug = document.getElementById('content-slug').value || createSlug(title);
            const excerpt = document.getElementById('content-excerpt').value;
            const content = tinymce.get('content-editor').getContent();
            
            // Additional fields
            const metaTitle = document.getElementById('meta-title').value;
            const metaDescription = document.getElementById('meta-description').value;
            const template = document.getElementById('content-template').value;
            const customCSS = document.getElementById('content-custom-css').value;
            
            // Post-specific fields
            let category = '';
            let tags = [];
            
            if (contentType === 'post') {
                category = document.getElementById('content-category').value;
                const tagsInput = document.getElementById('content-tags').value;
                if (tagsInput) {
                    tags = tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag);
                }
            }
            
            // Validate required fields
            if (!title) {
                alert('Please enter a title.');
                return;
            }
            
            try {
                // Prepare content data
                const contentPayload = {
                    title,
                    slug,
                    excerpt,
                    content,
                    status,
                    metaTitle,
                    metaDescription,
                    template,
                    customCSS,
                    authorId: user.uid,
                    authorName: user.displayName || user.email,
                    updatedAt: new Date().toISOString()
                };
                
                // Add post-specific fields
                if (contentType === 'post') {
                    contentPayload.category = category;
                    contentPayload.tags = tags;
                }
                
                // Add featured image if uploaded
                if (featuredImageURL) {
                    contentPayload.featuredImage = featuredImageURL;
                }
                
                // Save to Firestore
                if (isEdit) {
                    // Update existing content
                    await updateDoc(doc(db, contentType + 's', contentId), contentPayload);
                    alert(`${contentType} updated successfully!`);
                } else {
                    // Create new content
                    contentPayload.createdAt = new Date().toISOString();
                    
                    const newDocRef = await addDoc(collection(db, contentType + 's'), contentPayload);
                    
                    alert(`${contentType} created successfully!`);
                    
                    // Redirect to edit mode
                    window.location.href = `editor.php?id=${newDocRef.id}&type=${contentType}`;
                }
            } catch (error) {
                console.error(`Error saving ${contentType}:`, error);
                alert(`Error saving ${contentType}. Please try again.`);
            }
        }
        
        // Show preview
        function showPreview() {
            // Create preview data
            const title = document.getElementById('content-title').value;
            const content = tinymce.get('content-editor').getContent();
            
            // Open preview modal
            document.getElementById('preview-modal').style.display = 'block';
            
            // Generate preview HTML
            const previewHTML = `
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>${title} - Preview</title>
                    <link rel="stylesheet" href="style.css">
                    <style>
                        body {
                            padding: 20px;
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        h1 {
                            margin-bottom: 20px;
                        }
                        .content {
                            line-height: 1.6;
                        }
                        ${document.getElementById('content-custom-css').value || ''}
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <div class="content">
                        ${content}
                    </div>
                </body>
                </html>
            `;
            
            // Set preview content
            const previewFrame = document.getElementById('preview-frame');
            previewFrame.contentWindow.document.open();
            previewFrame.contentWindow.document.write(previewHTML);
            previewFrame.contentWindow.document.close();
        }
        
        // Helper function to create slug from title
        function createSlug(text) {
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special chars
                .replace(/\s+/g, '-')     // Replace spaces with dashes
                .replace(/-+/g, '-')      // Replace multiple dashes with single dash
                .trim();                   // Trim whitespace
        }
    </script>
</body>
</html>