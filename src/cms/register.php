<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="active">Register</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Create an Account</h1>
            <form id="registerForm">
                <div class="form-group">
                    <label for="displayName">Full Name</label>
                    <input type="text" id="displayName" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" required minlength="8">
                    <small>Password must be at least 8 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" required>
                </div>
                <div class="form-group">
                    <button type="submit">Register</button>
                </div>
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p id="errorMessage" style="color: red; display: none;"></p>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> PHP Firebase CMS</p>
    </footer>

    <!-- Firebase SDK -->
    <script type="module">
        // Import Firebase functions
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, createUserWithEmailAndPassword, updateProfile } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, doc, setDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
            
            // Validate password strength
            if (password.length < 8) {
                errorMessage.textContent = 'Password must be at least 8 characters long!';
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
                    role: 'user', // Default role
                    avatar: null,
                    bio: '',
                    lastLogin: new Date().toISOString()
                });
                
                // Display success message and redirect
                alert('Registration successful! You can now log in.');
                window.location.href = 'login.php';
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