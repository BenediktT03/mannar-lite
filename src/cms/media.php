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
    <title>Media Library - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Media library specific styles */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .media-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .media-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .media-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        
        .media-item-info {
            padding: 10px;
        }
        
        .media-item-info p {
            margin: 0;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-item-info .media-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .media-item-info .media-size {
            color: #666;
            font-size: 0.8rem;
        }
        
        .media-actions {
            display: flex;
            margin-top: 10px;
            gap: 5px;
        }
        
        .media-actions button {
            padding: 5px;
            font-size: 0.8rem;
            flex: 1;
        }
        
        .media-uploader {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .media-uploader h2 {
            margin-top: 0;
        }
        
        .upload-progress {
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
            display: none;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #0077b6;
            width: 0%;
            transition: width 0.3s;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .media-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .media-search {
            flex: 1;
            min-width: 200px;
        }
        
        .media-type-filter {
            min-width: 150px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            width: 80%;
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .media-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .media-preview {
            text-align: center;
        }
        
        .media-preview img {
            max-width: 100%;
            max-height: 300px;
            margin-bottom: 10px;
        }
        
        .media-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .media-info-table tr td {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .media-info-table tr td:first-child {
            font-weight: 500;
            width: 120px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            text-align: right;
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
            <h1>Media Library</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="media-container" style="display: none;">
                <div class="media-uploader">
                    <h2>Upload New Files</h2>
                    <form id="upload-form">
                        <div class="form-group">
                            <label for="file-input">Select Files</label>
                            <input type="file" id="file-input" multiple>
                            <small>Max file size: 10MB. Allowed types: images, documents, audio, video</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Upload Files</button>
                        </div>
                        <div class="upload-progress" id="upload-progress">
                            <div class="progress-bar" id="progress-bar"></div>
                        </div>
                        <div class="progress-text" id="progress-text"></div>
                    </form>
                </div>
                
                <div class="media-filters">
                    <div class="media-search form-group">
                        <input type="text" id="media-search" placeholder="Search media...">
                    </div>
                    <div class="media-type-filter form-group">
                        <select id="media-type-filter">
                            <option value="">All Media Types</option>
                            <option value="image">Images</option>
                            <option value="document">Documents</option>
                            <option value="audio">Audio</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                </div>
                
                <div id="media-grid" class="media-grid">
                    <p>Loading media...</p>
                </div>
                
                <!-- Pagination -->
                <div class="pagination" id="pagination" style="margin-top: 20px; text-align: center; display: none;">
                    <button id="prev-page" class="btn">Previous</button>
                    <span id="page-info">Page 1</span>
                    <button id="next-page" class="btn">Next</button>
                </div>
            </div>
            
            <!-- Media Details Modal -->
            <div id="media-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Media Details</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="media-details">
                            <div class="media-preview">
                                <img id="modal-image" src="" alt="">
                                <p id="modal-filename"></p>
                            </div>
                            <div class="media-info">
                                <table class="media-info-table">
                                    <tr>
                                        <td>File Type:</td>
                                        <td id="modal-type"></td>
                                    </tr>
                                    <tr>
                                        <td>Size:</td>
                                        <td id="modal-size"></td>
                                    </tr>
                                    <tr>
                                        <td>Dimensions:</td>
                                        <td id="modal-dimensions"></td>
                                    </tr>
                                    <tr>
                                        <td>Uploaded:</td>
                                        <td id="modal-uploaded"></td>
                                    </tr>
                                    <tr>
                                        <td>URL:</td>
                                        <td><input type="text" id="modal-url" readonly style="width: 100%"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="copy-url-btn" class="btn">Copy URL</button>
                        <button id="delete-media-btn" class="btn btn-danger">Delete</button>
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
        import { getFirestore, collection, addDoc, doc, getDoc, getDocs, deleteDoc, query, where, orderBy, limit, startAfter } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
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
        
        // Global variables
        let allMedia = [];
        let filteredMedia = [];
        let currentPage = 1;
        let itemsPerPage = 20;
        let lastDoc = null;
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const mediaContainer = document.getElementById('media-container');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Check if user has permission
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && (userDoc.data().role === 'admin' || userDoc.data().role === 'editor')) {
                    // User has permission
                    mediaContainer.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Initialize media library
                    initializeMediaLibrary();
                } else {
                    // User does not have permission
                    mediaContainer.style.display = 'none';
                    authWarning.innerHTML = '<p>You do not have permission to access the media library. Redirecting to dashboard...</p>';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                mediaContainer.style.display = 'none';
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
        
        // Initialize media library
        function initializeMediaLibrary() {
            // Load media
            loadMedia();
            
            // Setup file upload
            setupFileUpload();
            
            // Setup search and filtering
            setupFilters();
            
            // Setup modal
            setupMediaModal();
            
            // Setup pagination
            setupPagination();
        }
        
        // Load media
        async function loadMedia() {
            try {
                const mediaGrid = document.getElementById('media-grid');
                mediaGrid.innerHTML = '<p>Loading media...</p>';
                
                // Create query
                let mediaQuery = query(
                    collection(db, "media"),
                    orderBy("uploadedAt", "desc"),
                    limit(itemsPerPage)
                );
                
                const mediaSnapshot = await getDocs(mediaQuery);
                
                if (mediaSnapshot.empty) {
                    mediaGrid.innerHTML = '<p>No media files found. Upload some files to get started.</p>';
                    return;
                }
                
                // Store the last document for pagination
                lastDoc = mediaSnapshot.docs[mediaSnapshot.docs.length - 1];
                
                // Reset media arrays
                allMedia = [];
                filteredMedia = [];
                
                // Process media items
                mediaSnapshot.forEach((doc) => {
                    const mediaItem = {
                        id: doc.id,
                        ...doc.data()
                    };
                    allMedia.push(mediaItem);
                    filteredMedia.push(mediaItem);
                });
                
                // Display media
                displayMedia();
                
            } catch (error) {
                console.error("Error loading media:", error);
                document.getElementById('media-grid').innerHTML = '<p>Error loading media. Please try again.</p>';
            }
        }
        
        // Display media
        function displayMedia() {
            const mediaGrid = document.getElementById('media-grid');
            
            if (filteredMedia.length === 0) {
                mediaGrid.innerHTML = '<p>No media files match your search criteria.</p>';
                return;
            }
            
            let mediaHTML = '';
            
            filteredMedia.forEach((item) => {
                const isImage = item.type && item.type.startsWith('image/');
                
                mediaHTML += `
                    <div class="media-item" data-id="${item.id}">
                        ${isImage 
                            ? `<img src="${item.url}" alt="${item.name}" loading="lazy">`
                            : `<div style="height: 150px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">${getFileTypeIcon(item.type)}</div>`
                        }
                        <div class="media-item-info">
                            <p class="media-name">${item.name}</p>
                            <p class="media-size">${formatFileSize(item.size)}</p>
                            <div class="media-actions">
                                <button class="btn btn-primary view-media" data-id="${item.id}">View</button>
                                <button class="btn btn-danger delete-media" data-id="${item.id}" data-path="${item.path}">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            mediaGrid.innerHTML = mediaHTML;
            
            // Add event listeners
            document.querySelectorAll('.view-media').forEach(button => {
                button.addEventListener('click', (e) => {
                    const mediaId = e.target.getAttribute('data-id');
                    openMediaModal(mediaId);
                });
            });
            
            document.querySelectorAll('.delete-media').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const mediaId = e.target.getAttribute('data-id');
                    const mediaPath = e.target.getAttribute('data-path');
                    deleteMedia(mediaId, mediaPath);
                });
            });
            
            // Make the entire media item clickable
            document.querySelectorAll('.media-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    // Only trigger if the click wasn't on a button
                    if (!e.target.classList.contains('btn')) {
                        const mediaId = item.getAttribute('data-id');
                        openMediaModal(mediaId);
                    }
                });
            });
            
            // Update pagination
            updatePagination();
        }
        
        // Setup file upload
        function setupFileUpload() {
            const uploadForm = document.getElementById('upload-form');
            const fileInput = document.getElementById('file-input');
            const progressBar = document.getElementById('progress-bar');
            const progressContainer = document.getElementById('upload-progress');
            const progressText = document.getElementById('progress-text');
            
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const files = fileInput.files;
                
                if (files.length === 0) {
                    alert('Please select at least one file to upload.');
                    return;
                }
                
                progressContainer.style.display = 'block';
                progressText.textContent = 'Preparing to upload...';
                
                let uploadedCount = 0;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Validate file size (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        alert(`File ${file.name} is too large. Maximum file size is 10MB.`);
                        continue;
                    }
                    
                    try {
                        // Create storage reference
                        const timestamp = Date.now();
                        const storagePath = `media/${timestamp}_${file.name}`;
                        const storageRef = ref(storage, storagePath);
                        
                        // Upload file with progress tracking
                        const uploadTask = uploadBytesResumable(storageRef, file);
                        
                        uploadTask.on('state_changed',
                            (snapshot) => {
                                // Track upload progress
                                const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                                progressBar.style.width = `${progress}%`;
                                progressText.textContent = `Uploading ${file.name}: ${Math.round(progress)}%`;
                            },
                            (error) => {
                                console.error("Upload error:", error);
                                alert(`Error uploading ${file.name}: ${error.message}`);
                            },
                            async () => {
                                // Upload completed successfully
                                const downloadURL = await getDownloadURL(uploadTask.snapshot.ref);
                                
                                // Generate dimensions for images
                                let dimensions = null;
                                if (file.type.startsWith('image/')) {
                                    dimensions = await getImageDimensions(downloadURL);
                                }
                                
                                // Save media info to Firestore
                                await addDoc(collection(db, "media"), {
                                    name: file.name,
                                    type: file.type,
                                    size: file.size,
                                    url: downloadURL,
                                    path: storagePath,
                                    dimensions: dimensions,
                                    uploadedBy: auth.currentUser.uid,
                                    uploadedAt: new Date().toISOString()
                                });
                                
                                uploadedCount++;
                                
                                // If all files are uploaded
                                if (uploadedCount === files.length) {
                                    progressText.textContent = `Successfully uploaded ${uploadedCount} file(s).`;
                                    
                                    // Reset form and progress bar after 3 seconds
                                    setTimeout(() => {
                                        fileInput.value = '';
                                        progressBar.style.width = '0%';
                                        progressContainer.style.display = 'none';
                                        progressText.textContent = '';
                                        
                                        // Reload media
                                        loadMedia();
                                    }, 3000);
                                }
                            }
                        );
                    } catch (error) {
                        console.error("Error uploading file:", error);
                        alert(`Error uploading ${file.name}: ${error.message}`);
                    }
                }
            });
        }
        
        // Setup search and filtering
        function setupFilters() {
            const searchInput = document.getElementById('media-search');
            const typeFilter = document.getElementById('media-type-filter');
            
            searchInput.addEventListener('input', filterMedia);
            typeFilter.addEventListener('change', filterMedia);
            
            function filterMedia() {
                const searchTerm = searchInput.value.toLowerCase();
                const typeValue = typeFilter.value;
                
                filteredMedia = allMedia.filter(item => {
                    // Filter by search term
                    const matchesSearch = item.name.toLowerCase().includes(searchTerm);
                    
                    // Filter by type
                    let matchesType = true;
                    if (typeValue) {
                        if (typeValue === 'image') {
                            matchesType = item.type && item.type.startsWith('image/');
                        } else if (typeValue === 'document') {
                            matchesType = item.type && (
                                item.type.includes('pdf') || 
                                item.type.includes('word') || 
                                item.type.includes('excel') || 
                                item.type.includes('text') || 
                                item.type.includes('presentation')
                            );
                        } else if (typeValue === 'audio') {
                            matchesType = item.type && item.type.startsWith('audio/');
                        } else if (typeValue === 'video') {
                            matchesType = item.type && item.type.startsWith('video/');
                        }
                    }
                    
                    return matchesSearch && matchesType;
                });
                
                displayMedia();
            }
        }
        
        // Setup media modal
        function setupMediaModal() {
            const modal = document.getElementById('media-modal');
            const closeBtn = modal.querySelector('.modal-close');
            const copyUrlBtn = document.getElementById('copy-url-btn');
            const deleteBtn = document.getElementById('delete-media-btn');
            
            // Close modal when clicking the close button
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside the modal content
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Copy URL button
            copyUrlBtn.addEventListener('click', () => {
                const urlInput = document.getElementById('modal-url');
                urlInput.select();
                document.execCommand('copy');
                copyUrlBtn.textContent = 'Copied!';
                setTimeout(() => {
                    copyUrlBtn.textContent = 'Copy URL';
                }, 2000);
            });
            
            // Delete button
            deleteBtn.addEventListener('click', () => {
                const mediaId = deleteBtn.getAttribute('data-id');
                const mediaPath = deleteBtn.getAttribute('data-path');
                
                if (confirm('Are you sure you want to delete this media file? This action cannot be undone.')) {
                    deleteMedia(mediaId, mediaPath);
                    modal.style.display = 'none';
                }
            });
        }
        
        // Open media modal
        function openMediaModal(mediaId) {
            const mediaItem = allMedia.find(item => item.id === mediaId);
            
            if (!mediaItem) return;
            
            // Get modal elements
            const modal = document.getElementById('media-modal');
            const modalImage = document.getElementById('modal-image');
            const modalFilename = document.getElementById('modal-filename');
            const modalType = document.getElementById('modal-type');
            const modalSize = document.getElementById('modal-size');
            const modalDimensions = document.getElementById('modal-dimensions');
            const modalUploaded = document.getElementById('modal-uploaded');
            const modalUrl = document.getElementById('modal-url');
            const deleteBtn = document.getElementById('delete-media-btn');
            
            // Set modal content
            modalFilename.textContent = mediaItem.name;
            modalType.textContent = mediaItem.type || 'Unknown';
            modalSize.textContent = formatFileSize(mediaItem.size);
            
            // Set dimensions if available
            if (mediaItem.dimensions) {
                modalDimensions.textContent = `${mediaItem.dimensions.width} × ${mediaItem.dimensions.height}`;
            } else {
                modalDimensions.textContent = 'N/A';
            }
            
            // Set upload date
            if (mediaItem.uploadedAt) {
                const uploadDate = new Date(mediaItem.uploadedAt);
                modalUploaded.textContent = uploadDate.toLocaleString();
            } else {
                modalUploaded.textContent = 'Unknown';
            }
            
            // Set URL
            modalUrl.value = mediaItem.url;
            
            // Set delete button attributes
            deleteBtn.setAttribute('data-id', mediaItem.id);
            deleteBtn.setAttribute('data-path', mediaItem.path);
            
            // Set image or placeholder
            if (mediaItem.type && mediaItem.type.startsWith('image/')) {
                modalImage.src = mediaItem.url;
                modalImage.style.display = 'block';
            } else {
                modalImage.style.display = 'none';
            }
            
            // Show modal
            modal.style.display = 'block';
        }
        
        // Delete media
        async function deleteMedia(mediaId, mediaPath) {
            if (!confirm('Are you sure you want to delete this media file? This action cannot be undone.')) {
                return;
            }
            
            try {
                // Delete from storage
                const storageRef = ref(storage, mediaPath);
                await deleteObject(storageRef);
                
                // Delete from Firestore
                await deleteDoc(doc(db, "media", mediaId));
                
                // Update local data
                allMedia = allMedia.filter(item => item.id !== mediaId);
                filteredMedia = filteredMedia.filter(item => item.id !== mediaId);
                
                // Refresh display
                displayMedia();
                
                alert('Media file deleted successfully.');
            } catch (error) {
                console.error("Error deleting media:", error);
                alert(`Error deleting media: ${error.message}`);
            }
        }
        
        // Setup pagination
        function setupPagination() {
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadMediaPage();
                }
            });
            
            nextBtn.addEventListener('click', async () => {
                currentPage++;
                
                // Load next page from Firestore
                try {
                    if (lastDoc) {
                        const mediaQuery = query(
                            collection(db, "media"),
                            orderBy("uploadedAt", "desc"),
                            startAfter(lastDoc),
                            limit(itemsPerPage)
                        );
                        
                        const mediaSnapshot = await getDocs(mediaQuery);
                        
                        if (!mediaSnapshot.empty) {
                            // Store the last document for pagination
                            lastDoc = mediaSnapshot.docs[mediaSnapshot.docs.length - 1];
                            
                            // Add new items to arrays
                            mediaSnapshot.forEach((doc) => {
                                const mediaItem = {
                                    id: doc.id,
                                    ...doc.data()
                                };
                                allMedia.push(mediaItem);
                            });
                            
                            // Apply current filters
                            const searchTerm = document.getElementById('media-search').value.toLowerCase();
                            const typeValue = document.getElementById('media-type-filter').value;
                            
                            filteredMedia = allMedia.filter(item => {
                                // Filter by search term
                                const matchesSearch = item.name.toLowerCase().includes(searchTerm);
                                
                                // Filter by type
                                let matchesType = true;
                                if (typeValue) {
                                    if (typeValue === 'image') {
                                        matchesType = item.type && item.type.startsWith('image/');
                                    } else if (typeValue === 'document') {
                                        matchesType = item.type && (
                                            item.type.includes('pdf') || 
                                            item.type.includes('word') || 
                                            item.type.includes('excel') || 
                                            item.type.includes('text') || 
                                            item.type.includes('presentation')
                                        );
                                    } else if (typeValue === 'audio') {
                                        matchesType = item.type && item.type.startsWith('audio/');
                                    } else if (typeValue === 'video') {
                                        matchesType = item.type && item.type.startsWith('video/');
                                    }
                                }
                                
                                return matchesSearch && matchesType;
                            });
                            
                            displayMedia();
                        } else {
                            // No more items
                            currentPage--;
                            alert('No more media files to load.');
                        }
                    }
                } catch (error) {
                    console.error("Error loading more media:", error);
                    currentPage--;
                    alert('Error loading more media files.');
                }
            });
        }
        
        // Load media page
        function loadMediaPage() {
            // Update page info
            document.getElementById('page-info').textContent = `Page ${currentPage}`;
            
            // Enable/disable pagination buttons
            document.getElementById('prev-page').disabled = currentPage <= 1;
            
            // Display media for current page
            displayMedia();
        }
        
        // Update pagination
        function updatePagination() {
            const pagination = document.getElementById('pagination');
            const pageInfo = document.getElementById('page-info');
            
            // Show pagination if there are items
            pagination.style.display = filteredMedia.length > 0 ? 'block' : 'none';
            
            // Update page info
            pageInfo.textContent = `Page ${currentPage}`;
            
            // Enable/disable previous button
            document.getElementById('prev-page').disabled = currentPage <= 1;
        }
        
        // Helper function to get image dimensions
        function getImageDimensions(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    resolve({
                        width: img.width,
                        height: img.height
                    });
                };
                img.onerror = () => {
                    reject(new Error('Failed to load image'));
                };
                img.src = url;
            });
        }
        
        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Helper function to get file type icon
        function getFileTypeIcon(mimeType) {
            if (!mimeType) return '📄';
            
            if (mimeType.startsWith('image/')) {
                return '🖼️';
            } else if (mimeType.startsWith('video/')) {
                return '🎬';
            } else if (mimeType.startsWith('audio/')) {
                return '🎵';
            } else if (mimeType.includes('pdf')) {
                return '📑';
            } else if (mimeType.includes('word') || mimeType.includes('document')) {
                return '📝';
            } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
                return '📊';
            } else if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) {
                return '📽️';
            } else if (mimeType.includes('zip') || mimeType.includes('compressed')) {
                return '🗜️';
            } else {
                return '📄';
            }
        }
    </script>
</body>
</html>