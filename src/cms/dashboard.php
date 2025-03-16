<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="admin.php">Admin</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>User Dashboard</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="dashboard" style="display: none;">
                <div class="user-info">
                    <h2>Welcome, <span id="user-name">User</span>!</h2>
                    <p>Email: <span id="user-email"></span></p>
                    <p>Account created: <span id="user-created"></span></p>
                </div>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Your Posts</h3>
                        <p id="post-count">0</p>
                    </div>
                    <div class="stat-card">
                        <h3>Your Pages</h3>
                        <p id="page-count">0</p>
                    </div>
                    <div class="stat-card">
                        <h3>Media Items</h3>
                        <p id="media-count">0</p>
                    </div>
                </div>
                
                <h2>Your Recent Content</h2>
                <div id="user-content">
                    <p>Loading your content...</p>
                </div>
                
                <h2>User Profile</h2>
                <form id="profile-form">
                    <div class="form-group">
                        <label for="profile-name">Display Name</label>
                        <input type="text" id="profile-name" required>
                    </div>
                    <div class="form-group">
                        <label for="profile-bio">Bio</label>
                        <textarea id="profile-bio" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="profile-avatar">Profile Picture</label>
                        <input type="file" id="profile-avatar" accept="image/*">
                        <div id="avatar-preview"></div>
                    </div>
                    <div class="form-group">
                        <button type="submit">Update Profile</button>
                    </div>
                    <p id="profile-message" style="display: none;"></p>
                </form>
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
        import { getFirestore, collection, query, where, orderBy, limit, getDocs, doc, getDoc, updateDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
        import { getStorage, ref, uploadBytes, getDownloadURL } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-storage.js";

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
            const dashboard = document.getElementById('dashboard');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // User is signed in
                dashboard.style.display = 'block';
                authWarning.style.display = 'none';
                
                // Display user info
                document.getElementById('user-name').textContent = user.displayName || 'User';
                document.getElementById('user-email').textContent = user.email;
                
                // Get user data from Firestore
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists()) {
                    const userData = userDoc.data();
                    
                    // Set profile form values
                    document.getElementById('profile-name').value = userData.displayName || '';
                    document.getElementById('profile-bio').value = userData.bio || '';
                    
                    // Display avatar if exists
                    if (userData.avatar) {
                        const avatarPreview = document.getElementById('avatar-preview');
                        avatarPreview.innerHTML = `<img src="${userData.avatar}" alt="Profile" style="max-width: 100px; max-height: 100px; border-radius: 50%;">`;
                    }
                    
                    // Show user creation date
                    if (userData.createdAt) {
                        const createdDate = new Date(userData.createdAt);
                        document.getElementById('user-created').textContent = createdDate.toLocaleDateString();
                    }
                }
                
                // Load user content
                await loadUserContent(user.uid);
            } else {
                // User is not signed in
                dashboard.style.display = 'none';
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
        
        // Profile update
        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const user = auth.currentUser;
            if (!user) return;
            
            const displayName = document.getElementById('profile-name').value;
            const bio = document.getElementById('profile-bio').value;
            const avatarFile = document.getElementById('profile-avatar').files[0];
            const profileMessage = document.getElementById('profile-message');
            
            try {
                // Update user data in Firestore
                const userRef = doc(db, "users", user.uid);
                const updateData = {
                    displayName,
                    bio,
                    updatedAt: new Date().toISOString()
                };
                
                // Upload avatar if provided
                if (avatarFile) {
                    const storageRef = ref(storage, `avatars/${user.uid}/${Date.now()}_${avatarFile.name}`);
                    await uploadBytes(storageRef, avatarFile);
                    const avatarURL = await getDownloadURL(storageRef);
                    updateData.avatar = avatarURL;
                    
                    // Display avatar preview
                    const avatarPreview = document.getElementById('avatar-preview');
                    avatarPreview.innerHTML = `<img src="${avatarURL}" alt="Profile" style="max-width: 100px; max-height: 100px; border-radius: 50%;">`;
                }
                
                await updateDoc(userRef, updateData);
                
                // Update display name in Firebase Auth
                await user.updateProfile({ displayName });
                
                // Show success message
                profileMessage.textContent = 'Profile updated successfully!';
                profileMessage.style.display = 'block';
                profileMessage.style.color = 'green';
                
                // Update displayed name
                document.getElementById('user-name').textContent = displayName;
                
            } catch (error) {
                console.error('Profile update error:', error);
                profileMessage.textContent = `Error updating profile: ${error.message}`;
                profileMessage.style.display = 'block';
                profileMessage.style.color = 'red';
            }
        });
        
        // Load user content (posts, pages, media)
        async function loadUserContent(userId) {
            try {
                // Count posts
                const postsQuery = query(
                    collection(db, "posts"),
                    where("authorId", "==", userId)
                );
                const postsSnapshot = await getDocs(postsQuery);
                document.getElementById('post-count').textContent = postsSnapshot.size;
                
                // Count pages
                const pagesQuery = query(
                    collection(db, "pages"),
                    where("authorId", "==", userId)
                );
                const pagesSnapshot = await getDocs(pagesQuery);
                document.getElementById('page-count').textContent = pagesSnapshot.size;
                
                // Count media items
                const mediaQuery = query(
                    collection(db, "media"),
                    where("uploadedBy", "==", userId)
                );
                const mediaSnapshot = await getDocs(mediaQuery);
                document.getElementById('media-count').textContent = mediaSnapshot.size;
                
                // Load recent content
                const recentContentQuery = query(
                    collection(db, "posts"),
                    where("authorId", "==", userId),
                    orderBy("createdAt", "desc"),
                    limit(5)
                );
                const recentContentSnapshot = await getDocs(recentContentQuery);
                
                const userContentEl = document.getElementById('user-content');
                
                if (recentContentSnapshot.empty) {
                    userContentEl.innerHTML = '<p>You haven\'t created any content yet.</p>';
                    return;
                }
                
                let contentHTML = '<table>';
                contentHTML += '<tr><th>Title</th><th>Type</th><th>Created</th><th>Status</th><th>Actions</th></tr>';
                
                recentContentSnapshot.forEach((doc) => {
                    const content = doc.data();
                    const date = new Date(content.createdAt).toLocaleDateString();
                    
                    contentHTML += `
                        <tr>
                            <td>${content.title}</td>
                            <td>Post</td>
                            <td>${date}</td>
                            <td>${content.status || 'Draft'}</td>
                            <td>
                                <a href="editor.php?id=${doc.id}&type=post" class="btn btn-primary">Edit</a>
                            </td>
                        </tr>
                    `;
                });
                
                contentHTML += '</table>';
                userContentEl.innerHTML = contentHTML;
                
            } catch (error) {
                console.error("Error loading user content:", error);
                document.getElementById('user-content').innerHTML = '<p>Error loading content. Please try again.</p>';
            }
        }
    </script>
</body>
</html>