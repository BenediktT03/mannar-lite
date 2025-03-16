 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mannar CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mannar CMS</div>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="register.html" class="active">Register</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Create an Account</h1>
            <form id="registerForm">
                <div class="form-group">
                    <label for="displayName">Name</label>
                    <input type="text" id="displayName" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" required minlength="6">
                    <small>Password must be at least 6 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" required>
                </div>
                <div class="form-group">
                    <button type="submit">Register</button>
                </div>
                <p>Already have an account? <a href="login.html">Login here</a></p>
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
        import { getAuth, createUserWithEmailAndPassword, updateProfile } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, doc, setDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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

        // Handle registration form submission
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const displayName = document.getElementById('displayName').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorMessage = document.getElementById('errorMessage');
            
            // Validate password match
            if (password !== confirmPassword) {
                errorMessage.textContent = 'Passwords do not match!';
                errorMessage.style.display = 'block';
                return;
            }
            
            try {
                // Create user with Firebase Authentication
                const userCredential = await createUserWithEmailAndPassword(auth, email, password);
                const user = userCredential.user;
                
                // Update profile with display name
                await updateProfile(user, { displayName });
                
                // Save user data to Firestore
                await setDoc(doc(db, "users", user.uid), {
                    displayName,
                    email,
                    createdAt: new Date().toISOString(),
                    role: 'user'
                });
                
                // Redirect to admin page after successful registration
                window.location.href = 'admin.html';
            } catch (error) {
                // Display error message
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
                console.error('Registration error:', error);
            }
        });
    </script>
</body>
</html>