 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mannar CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mannar CMS</div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="admin.html">Admin</a></li>
                <li><a href="dashboard.html" class="active">Dashboard</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>User Dashboard</h1>
            <div id="auth-warning" style="display: none; background-color: #ffdddd; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                <p>You must be logged in to access this page. Redirecting to login...</p>
            </div>
            
            <div id="dashboard" style="display: none;">
                <div class="user-info">
                    <h2>Welcome, <span id="user-name">User</span>!</h2>
                    <p>Email: <span id="user-email"></span></p>
                </div>
                
                <div class="user-stats">
                    <h3>Your Activity</h3>
                    <p>You have created <span id="post-count">0</span> posts.</p>
                </div>
                
                <div class="recent-posts">
                    <h3>Your Recent Posts</h3>
                    <div id="user-posts">
                        <p>Loading your posts...</p>
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
        import { getFirestore, collection, query, where, orderBy, limit, getDocs } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
            const dashboard = document.getElementById('dashboard');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // User is signed in
                dashboard.style.display = 'block';
                authWarning.style.display = 'none';
                
                // Display user info
                document.getElementById('user-name').textContent = user.displayName || 'User';
                document.getElementById('user-email').textContent = user.email;
                
                // Load user posts
                await loadUserPosts(user);
            } else {
                // User is not signed in
                dashboard.style.display = 'none';
                authWarning.style.display = 'block';
                
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
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
        
        // Load user's posts
        async function loadUserPosts(user) {
            try {
                const userPostsQuery = query(
                    collection(db, "posts"),
                    where("authorId", "==", user.uid),
                    orderBy("createdAt", "desc"),
                    limit(5)
                );
                
                const querySnapshot = await getDocs(userPostsQuery);
                
                // Update post count
                document.getElementById('post-count').textContent = querySnapshot.size;
                
                const userPostsElement = document.getElementById('user-posts');
                
                if (querySnapshot.empty) {
                    userPostsElement.innerHTML = '<p>You haven\'t created any posts yet.</p>';
                    return;
                }
                
                let postsHTML = '';
                
                querySnapshot.forEach((doc) => {
                    const post = doc.data();
                    const date = new Date(post.createdAt).toLocaleDateString();
                    
                    postsHTML += `
                        <div class="post-card">
                            <h4>${post.title}</h4>
                            <p class="post-date">Created on: ${date}</p>
                            <div class="post-actions">
                                <a href="admin.html#posts" class="post-link">Manage in Admin</a>
                            </div>
                        </div>
                    `;
                });
                
                userPostsElement.innerHTML = postsHTML;
            } catch (error) {
                console.error("Error loading user posts:", error);
                document.getElementById('user-posts').innerHTML = '<p>Error loading posts. Please try again.</p>';
            }
        }
    </script>
</body>
</html>