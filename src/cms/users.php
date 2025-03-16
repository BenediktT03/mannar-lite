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
    <title>User Management - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .role-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-editor {
            background-color: #0d6efd;
            color: white;
        }
        
        .role-author {
            background-color: #198754;
            color: white;
        }
        
        .role-user {
            background-color: #6c757d;
            color: white;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .user-search {
            flex: 1;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-active {
            background-color: #198754;
        }
        
        .status-inactive {
            background-color: #dc3545;
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
                <li><a href="users.php" class="active">Users</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>User Management</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in as an administrator to access this page. Redirecting to login...</p>
            </div>
            
            <div id="users-container" style="display: none;">
                <div id="user-message" class="alert" style="display: none;"></div>
                
                <div class="user-filters">
                    <div class="user-search form-group">
                        <input type="text" id="user-search" placeholder="Search users...">
                    </div>
                    
                    <div class="form-group">
                        <select id="role-filter">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="author">Author</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select id="status-filter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div id="users-list">
                    <p>Loading users...</p>
                </div>
                
                <div id="pagination" class="pagination" style="display: none;">
                    <!-- Pagination will be populated here -->
                </div>
                
                <!-- Edit User Modal -->
                <div id="edit-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Edit User</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-user-form">
                                <input type="hidden" id="edit-user-id">
                                <div class="form-group">
                                    <label for="edit-display-name">Display Name</label>
                                    <input type="text" id="edit-display-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-email">Email</label>
                                    <input type="email" id="edit-email" required readonly>
                                    <small>Email cannot be changed</small>
                                </div>
                                <div class="form-group">
                                    <label for="edit-role">Role</label>
                                    <select id="edit-role">
                                        <option value="user">User</option>
                                        <option value="author">Author</option>
                                        <option value="editor">Editor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-status">Status</label>
                                    <select id="edit-status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="edit-bio">Bio</label>
                                    <textarea id="edit-bio" rows="3"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button id="update-user-btn" class="btn btn-primary">Update User</button>
                            <button class="btn modal-close-btn">Cancel</button>
                        </div>
                    </div>
                </div>
                
                <!-- Confirm Delete Modal -->
                <div id="delete-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Confirm Delete</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> All content created by this user will remain but will be attributed to "Unknown" author.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button id="confirm-delete-btn" class="btn btn-danger">Delete User</button>
                            <button class="btn modal-close-btn">Cancel</button>
                        </div>
                    </div>
                </div>
                
                <!-- Add User Modal -->
                <div id="add-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Add New User</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="add-user-form">
                                <div class="form-group">
                                    <label for="add-display-name">Display Name</label>
                                    <input type="text" id="add-display-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="add-email">Email</label>
                                    <input type="email" id="add-email" required>
                                </div>
                                <div class="form-group">
                                    <label for="add-password">Password</label>
                                    <input type="password" id="add-password" required minlength="8">
                                    <small>Password must be at least 8 characters</small>
                                </div>
                                <div class="form-group">
                                    <label for="add-role">Role</label>
                                    <select id="add-role">
                                        <option value="user">User</option>
                                        <option value="author">Author</option>
                                        <option value="editor">Editor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button id="add-user-btn" class="btn btn-primary">Add User</button>
                            <button class="btn modal-close-btn">Cancel</button>
                        </div>
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
        import { getAuth, onAuthStateChanged, signOut, createUserWithEmailAndPassword, updateProfile } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, doc, getDoc, setDoc, updateDoc, deleteDoc, getDocs, query, orderBy, where, limit, startAfter } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        
        // Global variables
        let currentUser = null;
        let users = [];
        let filteredUsers = [];
        let userToDelete = null;
        let currentPage = 1;
        let itemsPerPage = 10;
        let lastDoc = null;
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const usersContainer = document.getElementById('users-container');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Store current user
                currentUser = user;
                
                // Check if user is an admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // User is an admin
                    usersContainer.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Load users
                    loadUsers();
                    
                    // Setup filters
                    setupFilters();
                    
                    // Setup modals
                    setupModals();
                    
                    // Setup buttons
                    setupButtons();
                } else {
                    // User is not an admin
                    usersContainer.style.display = 'none';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                usersContainer.style.display = 'none';
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
        
        // Load users
        async function loadUsers() {
            try {
                const usersList = document.getElementById('users-list');
                usersList.innerHTML = '<p>Loading users...</p>';
                
                // Create query
                const usersQuery = query(
                    collection(db, "users"),
                    orderBy("createdAt", "desc"),
                    limit(itemsPerPage)
                );
                
                const usersSnapshot = await getDocs(usersQuery);
                
                // Reset users array
                users = [];
                
                usersSnapshot.forEach((doc) => {
                    users.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                // Store the last document for pagination
                if (usersSnapshot.docs.length > 0) {
                    lastDoc = usersSnapshot.docs[usersSnapshot.docs.length - 1];
                }
                
                // Apply current filters
                applyFilters();
                
            } catch (error) {
                console.error("Error loading users:", error);
                showMessage('Error loading users. Please try again.', 'error');
            }
        }
        
        // Display users
        function displayUsers() {
            const usersList = document.getElementById('users-list');
            
            if (filteredUsers.length === 0) {
                usersList.innerHTML = '<p>No users found matching your criteria.</p>';
                return;
            }
            
            let usersHTML = `
                <div class="add-new-btn" style="margin-bottom: 1rem;">
                    <button id="add-new-user-btn" class="btn btn-primary">Add New User</button>
                </div>
                <table>
                    <tr>
                        <th style="width: 60px;">Avatar</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
            `;
            
            filteredUsers.forEach(user => {
                const joinDate = user.createdAt ? new Date(user.createdAt).toLocaleDateString() : 'Unknown';
                const status = user.status || 'active';
                
                usersHTML += `
                    <tr>
                        <td>
                            ${user.avatar 
                                ? `<img src="${user.avatar}" alt="${user.displayName}" class="user-avatar">` 
                                : `<div class="user-avatar" style="background-color: #e9ecef; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666;">${getInitials(user.displayName)}</div>`
                            }
                        </td>
                        <td>${user.displayName || 'Unknown'}</td>
                        <td>${user.email}</td>
                        <td><span class="role-badge role-${user.role || 'user'}">${user.role || 'user'}</span></td>
                        <td><span class="status-indicator status-${status}"></span>${status}</td>
                        <td>${joinDate}</td>
                        <td>
                            <button class="btn btn-primary edit-user" data-id="${user.id}">Edit</button>
                            ${user.id !== currentUser.uid 
                                ? `<button class="btn btn-danger delete-user" data-id="${user.id}">Delete</button>` 
                                : `<button class="btn btn-danger" disabled title="You cannot delete your own account">Delete</button>`
                            }
                        </td>
                    </tr>
                `;
            });
            
            usersHTML += '</table>';
            usersList.innerHTML = usersHTML;
            
            // Add event listeners
            document.getElementById('add-new-user-btn').addEventListener('click', () => {
                document.getElementById('add-modal').style.display = 'block';
            });
            
            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', (e) => {
                    const userId = e.target.getAttribute('data-id');
                    openEditModal(userId);
                });
            });
            
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', (e) => {
                    const userId = e.target.getAttribute('data-id');
                    openDeleteModal(userId);
                });
            });
            
            // Update pagination
            updatePagination();
        }
        
        // Setup filters
        function setupFilters() {
            const searchInput = document.getElementById('user-search');
            const roleFilter = document.getElementById('role-filter');
            const statusFilter = document.getElementById('status-filter');
            
            searchInput.addEventListener('input', applyFilters);
            roleFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
        }
        
        // Apply filters
        function applyFilters() {
            const searchTerm = document.getElementById('user-search').value.toLowerCase();
            const roleFilter = document.getElementById('role-filter').value;
            const statusFilter = document.getElementById('status-filter').value;
            
            filteredUsers = users.filter(user => {
                // Search term filter
                const matchesSearch = 
                    (user.displayName && user.displayName.toLowerCase().includes(searchTerm)) || 
                    (user.email && user.email.toLowerCase().includes(searchTerm));
                
                // Role filter
                const matchesRole = !roleFilter || (user.role === roleFilter);
                
                // Status filter
                const matchesStatus = !statusFilter || (user.status === statusFilter);
                
                return matchesSearch && matchesRole && matchesStatus;
            });
            
            displayUsers();
        }
        
        // Setup modals
        function setupModals() {
            // Close modals when clicking close button or outside the modal
            const modals = document.querySelectorAll('.modal');
            const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
            
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    modals.forEach(modal => {
                        modal.style.display = 'none';
                    });
                });
            });
            
            window.addEventListener('click', (e) => {
                modals.forEach(modal => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        }
        
        // Setup buttons
        function setupButtons() {
            // Update user
            document.getElementById('update-user-btn').addEventListener('click', async () => {
                const userId = document.getElementById('edit-user-id').value;
                const displayName = document.getElementById('edit-display-name').value.trim();
                const role = document.getElementById('edit-role').value;
                const status = document.getElementById('edit-status').value;
                const bio = document.getElementById('edit-bio').value.trim();
                
                if (!displayName) {
                    showMessage('Display name is required.', 'error');
                    return;
                }
                
                try {
                    // Update user in Firestore
                    await updateDoc(doc(db, "users", userId), {
                        displayName,
                        role,
                        status,
                        bio,
                        updatedAt: new Date().toISOString()
                    });
                    
                    showMessage('User updated successfully!', 'success');
                    
                    // Close modal
                    document.getElementById('edit-modal').style.display = 'none';
                    
                    // Reload users
                    loadUsers();
                } catch (error) {
                    console.error("Error updating user:", error);
                    showMessage('Error updating user. Please try again.', 'error');
                }
            });
            
            // Delete user
            document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
                if (!userToDelete) return;
                
                try {
                    // Update posts to change author name to "Unknown"
                    const postsQuery = query(
                        collection(db, "posts"),
                        where("authorId", "==", userToDelete)
                    );
                    const postsSnapshot = await getDocs(postsQuery);
                    
                    const updatePromises = [];
                    postsSnapshot.forEach(doc => {
                        updatePromises.push(updateDoc(doc.ref, {
                            authorName: 'Unknown',
                            updatedAt: new Date().toISOString()
                        }));
                    });
                    
                    // Wait for all updates to complete
                    await Promise.all(updatePromises);
                    
                    // Delete user from Firestore
                    await deleteDoc(doc(db, "users", userToDelete));
                    
                    showMessage('User deleted successfully!', 'success');
                    
                    // Close modal
                    document.getElementById('delete-modal').style.display = 'none';
                    
                    // Reload users
                    loadUsers();
                } catch (error) {
                    console.error("Error deleting user:", error);
                    showMessage('Error deleting user. Please try again.', 'error');
                }
            });
            
            // Add new user
            document.getElementById('add-user-btn').addEventListener('click', async () => {
                const displayName = document.getElementById('add-display-name').value.trim();
                const email = document.getElementById('add-email').value.trim();
                const password = document.getElementById('add-password').value;
                const role = document.getElementById('add-role').value;
                
                if (!displayName || !email || !password) {
                    showMessage('All fields are required.', 'error');
                    return;
                }
                
                try {
                    // Create user with Firebase Authentication
                    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
                    const user = userCredential.user;
                    
                    // Update display name
                    await updateProfile(user, {
                        displayName: displayName
                    });
                    
                    // Save user data to Firestore
                    await setDoc(doc(db, "users", user.uid), {
                        displayName,
                        email,
                        role,
                        status: 'active',
                        createdAt: new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    });
                    
                    showMessage('User added successfully!', 'success');
                    
                    // Clear form
                    document.getElementById('add-display-name').value = '';
                    document.getElementById('add-email').value = '';
                    document.getElementById('add-password').value = '';
                    document.getElementById('add-role').value = 'user';
                    
                    // Close modal
                    document.getElementById('add-modal').style.display = 'none';
                    
                    // Reload users
                    loadUsers();
                } catch (error) {
                    console.error("Error adding user:", error);
                    showMessage(`Error adding user: ${error.message}`, 'error');
                }
            });
        }
        
        // Open edit modal
        function openEditModal(userId) {
            const user = users.find(u => u.id === userId);
            if (!user) return;
            
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-display-name').value = user.displayName || '';
            document.getElementById('edit-email').value = user.email || '';
            document.getElementById('edit-role').value = user.role || 'user';
            document.getElementById('edit-status').value = user.status || 'active';
            document.getElementById('edit-bio').value = user.bio || '';
            
            document.getElementById('edit-modal').style.display = 'block';
        }
        
        // Open delete modal
        function openDeleteModal(userId) {
            userToDelete = userId;
            document.getElementById('delete-modal').style.display = 'block';
        }
        
        // Update pagination
        function updatePagination() {
            const paginationElement = document.getElementById('pagination');
            
            // Check if pagination is needed
            if (users.length < itemsPerPage) {
                paginationElement.style.display = 'none';
                return;
            }
            
            // Show pagination
            paginationElement.style.display = 'flex';
            
            let paginationHTML = '';
            
            // Previous button
            if (currentPage > 1) {
                paginationHTML += `<a href="#" class="prev-page">« Previous</a>`;
            } else {
                paginationHTML += `<span class="disabled">« Previous</span>`;
            }
            
            // Page info
            paginationHTML += `<span>Page ${currentPage}</span>`;
            
            // Next button
            if (lastDoc) {
                paginationHTML += `<a href="#" class="next-page">Next »</a>`;
            } else {
                paginationHTML += `<span class="disabled">Next »</span>`;
            }
            
            paginationElement.innerHTML = paginationHTML;
            
            // Add event listeners
            const prevBtn = paginationElement.querySelector('.prev-page');
            const nextBtn = paginationElement.querySelector('.next-page');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadPreviousPage();
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadNextPage();
                });
            }
        }
        
        // Load previous page
        function loadPreviousPage() {
            if (currentPage <= 1) return;
            
            currentPage--;
            loadUsers();
        }
        
        // Load next page
        async function loadNextPage() {
            if (!lastDoc) return;
            
            try {
                // Create query
                const usersQuery = query(
                    collection(db, "users"),
                    orderBy("createdAt", "desc"),
                    startAfter(lastDoc),
                    limit(itemsPerPage)
                );
                
                const usersSnapshot = await getDocs(usersQuery);
                
                if (usersSnapshot.empty) {
                    lastDoc = null;
                } else {
                    // Reset users array
                    users = [];
                    
                    usersSnapshot.forEach((doc) => {
                        users.push({
                            id: doc.id,
                            ...doc.data()
                        });
                    });
                    
                    // Store the last document for pagination
                    lastDoc = usersSnapshot.docs[usersSnapshot.docs.length - 1];
                    
                    // Increment page number
                    currentPage++;
                    
                    // Apply current filters
                    applyFilters();
                }
            } catch (error) {
                console.error("Error loading next page:", error);
                showMessage('Error loading more users. Please try again.', 'error');
            }
        }
        
        // Show message
        function showMessage(message, type) {
            const messageElement = document.getElementById('user-message');
            messageElement.textContent = message;
            messageElement.className = 'alert';
            messageElement.classList.add(`alert-${type}`);
            messageElement.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
        
        // Get user initials
        function getInitials(name) {
            if (!name) return '?';
            
            const names = name.split(' ');
            if (names.length === 1) {
                return names[0].charAt(0).toUpperCase();
            }
            
            return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase();
        }
    </script>
</body>
</html>