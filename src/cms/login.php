<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php" class="active">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Login to CMS</h1>
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
                <p>Forgot password? <a href="#" id="resetPassword">Reset here</a></p>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p id="errorMessage" style="color: red; display: none;"></p>
            </form>
            
            <!-- Password Reset Form (hidden by default) -->
            <form id="resetForm" style="display: none;">
                <h2>Reset Password</h2>
                <div class="form-group">
                    <label for="resetEmail">Email</label>
                    <input type="email" id="resetEmail" required>
                </div>
                <div class="form-group">
                    <button type="submit">Send Reset Link</button>
                    <button type="button" id="cancelReset">Cancel</button>
                </div>
                <p id="resetMessage" style="display: none;"></p>
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
        import { getAuth, signInWithEmailAndPassword, sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

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

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            
            try {
                // Sign in with Firebase Authentication
                await signInWithEmailAndPassword(auth, email, password);
                
                // Redirect to admin dashboard after successful login
                window.location.href = 'admin.php';
            } catch (error) {
                // Display error message
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
                console.error('Login error:', error);
            }
        });

        // Show password reset form
        document.getElementById('resetPassword').addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('resetForm').style.display = 'block';
        });

        // Cancel password reset
        document.getElementById('cancelReset').addEventListener('click', () => {
            document.getElementById('resetForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('resetMessage').style.display = 'none';
        });

        // Handle password reset form submission
        document.getElementById('resetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('resetEmail').value;
            const resetMessage = document.getElementById('resetMessage');
            
            try {
                // Send password reset email
                await sendPasswordResetEmail(auth, email);
                
                // Display success message
                resetMessage.textContent = 'Password reset email sent. Please check your inbox.';
                resetMessage.style.color = 'green';
                resetMessage.style.display = 'block';
            } catch (error) {
                // Display error message
                resetMessage.textContent = error.message;
                resetMessage.style.color = 'red';
                resetMessage.style.display = 'block';
                console.error('Password reset error:', error);
            }
        });
    </script>
</body>
</html>