 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mannar CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mannar CMS</div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="login.html" id="loginBtn">Login</a></li>
                <li><a href="register.html" id="registerBtn">Register</a></li>
                <li><a href="admin.html" id="adminBtn" style="display: none;">Admin</a></li>
                <li><button id="logoutBtn" style="display: none;">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section id="content">
            <h1>Welcome to Mannar CMS</h1>
            <div id="posts-container">
                <!-- Posts will be loaded here dynamically -->
                <p>Loading content...</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Mannar CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import the functions you need from the SDKs
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-analytics.js";
        import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, getDocs } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
        
        // Your Firebase configuration
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
        const analytics = getAnalytics(app);
        const auth = getAuth();
        const db = getFirestore(app);

        // Check authentication state
        onAuthStateChanged(auth, (user) => {
            const adminBtn = document.getElementById('adminBtn');
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');
            const logoutBtn = document.getElementById('logoutBtn');
            
            if (user) {
                // User is signed in
                adminBtn.style.display = 'block';
                logoutBtn.style.display = 'block';
                loginBtn.style.display = 'none';
                registerBtn.style.display = 'none';
                
                // Load posts from Firestore
                loadPosts();
            } else {
                // User is signed out
                adminBtn.style.display = 'none';
                logoutBtn.style.display = 'none';
                loginBtn.style.display = 'block';
                registerBtn.style.display = 'block';
                
                // Still load posts for anonymous users
                loadPosts();
            }
        });

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', () => {
            auth.signOut().then(() => {
                // Sign-out successful
                window.location.href = 'index.html';
            }).catch((error) => {
                // An error happened
                console.error('Logout error:', error);
            });
        });

        // Function to load posts from Firestore
        async function loadPosts() {
            const postsContainer = document.getElementById('posts-container');
            postsContainer.innerHTML = '<p>Loading content...</p>';
            
            try {
                const querySnapshot = await getDocs(collection(db, "posts"));
                
                if (querySnapshot.empty) {
                    postsContainer.innerHTML = '<p>No posts available yet.</p>';
                    return;
                }
                
                postsContainer.innerHTML = '';
                
                querySnapshot.forEach((doc) => {
                    const post = doc.data();
                    const postElement = document.createElement('article');
                    postElement.className = 'post';
                    
                    postElement.innerHTML = `
                        <h2>${post.title}</h2>
                        <div class="post-meta">
                            <span>Posted on: ${new Date(post.createdAt).toLocaleDateString()}</span>
                        </div>
                        <div class="post-content">
                            ${post.content}
                        </div>
                    `;
                    
                    postsContainer.appendChild(postElement);
                });
            } catch (error) {
                console.error("Error loading posts: ", error);
                postsContainer.innerHTML = '<p>Error loading content. Please try again later.</p>';
            }
        }
    </script>
</body>
</html>