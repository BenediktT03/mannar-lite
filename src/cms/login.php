 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mannar CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mannar CMS</div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="login.html" class="active">Login</a></li>
                <li><a href="register.html">Register</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Login to Mannar CMS</h1>
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Login</button>
                </div>
                <p>Don't have an account? <a href="register.html">Register here</a></p>
                <p id="errorMessage" style="color: red; display: none;"></p>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Mannar CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

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

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            
            try {
                // Sign in with Firebase Authentication
                await signInWithEmailAndPassword(auth, email, password);
                
                // Redirect to admin page after successful login
                window.location.href = 'admin.html';
            } catch (error) {
                // Display error message
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
                console.error('Login error:', error);
            }
        });
    </script>
</body>
</html>