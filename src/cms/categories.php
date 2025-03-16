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
    <title>Category Management - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PHP Firebase CMS</div>
            <ul>
                <li><a href="index.php">View Site</a></li>
                <li><a href="admin.php">Admin</a></li>
                <li><a href="categories.php" class="active">Categories</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Category Management</h1>
            <div id="auth-warning" class="alert alert-danger" style="display: none;">
                <p>You must be logged in as an administrator to access this page. Redirecting to login...</p>
            </div>
            
            <div id="categories-container" style="display: none;">
                <div id="category-message" class="alert" style="display: none;"></div>
                
                <div class="admin-container">
                    <div class="admin-sidebar">
                        <div class="form-group">
                            <h3>Add New Category</h3>
                            <form id="add-category-form">
                                <div class="form-group">
                                    <label for="category-name">Name</label>
                                    <input type="text" id="category-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="category-slug">Slug</label>
                                    <input type="text" id="category-slug">
                                    <small>Leave blank to auto-generate from name</small>
                                </div>
                                <div class="form-group">
                                    <label for="category-description">Description</label>
                                    <textarea id="category-description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="category-parent">Parent Category</label>
                                    <select id="category-parent">
                                        <option value="">None</option>
                                        <!-- Categories will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Add Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="admin-content">
                        <h2>Categories</h2>
                        <div id="categories-list">
                            <p>Loading categories...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Category Modal -->
                <div id="edit-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Edit Category</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-category-form">
                                <input type="hidden" id="edit-category-id">
                                <div class="form-group">
                                    <label for="edit-category-name">Name</label>
                                    <input type="text" id="edit-category-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit-category-slug">Slug</label>
                                    <input type="text" id="edit-category-slug">
                                </div>
                                <div class="form-group">
                                    <label for="edit-category-description">Description</label>
                                    <textarea id="edit-category-description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="edit-category-parent">Parent Category</label>
                                    <select id="edit-category-parent">
                                        <option value="">None</option>
                                        <!-- Categories will be loaded dynamically -->
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button id="update-category-btn" class="btn btn-primary">Update Category</button>
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
                            <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                            <div class="alert alert-warning">
                                <p><strong>Warning:</strong> All posts in this category will be set to uncategorized.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button id="confirm-delete-btn" class="btn btn-danger">Delete Category</button>
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
        import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { getFirestore, collection, doc, getDoc, setDoc, updateDoc, deleteDoc, getDocs, query, orderBy, where } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        let categories = [];
        let categoryToDelete = null;
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            const categoriesContainer = document.getElementById('categories-container');
            const authWarning = document.getElementById('auth-warning');
            
            if (user) {
                // Check if user is an admin
                const userDoc = await getDoc(doc(db, "users", user.uid));
                if (userDoc.exists() && userDoc.data().role === 'admin') {
                    // User is an admin
                    categoriesContainer.style.display = 'block';
                    authWarning.style.display = 'none';
                    
                    // Load categories
                    loadCategories();
                    
                    // Setup form and modals
                    setupFormSubmission();
                    setupModals();
                } else {
                    // User is not an admin
                    categoriesContainer.style.display = 'none';
                    authWarning.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                }
            } else {
                // User is not signed in
                categoriesContainer.style.display = 'none';
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
        
        // Load categories
        async function loadCategories() {
            try {
                const categoriesQuery = query(
                    collection(db, "categories"),
                    orderBy("name")
                );
                const categoriesSnapshot = await getDocs(categoriesQuery);
                
                // Reset categories array
                categories = [];
                
                categoriesSnapshot.forEach((doc) => {
                    categories.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                // Display categories
                displayCategories();
                
                // Populate parent category select boxes
                populateParentSelect();
            } catch (error) {
                console.error("Error loading categories:", error);
                showMessage('Error loading categories. Please try again.', 'error');
            }
        }
        
        // Display categories
        function displayCategories() {
            const categoriesList = document.getElementById('categories-list');
            
            if (categories.length === 0) {
                categoriesList.innerHTML = '<p>No categories found. Create your first category using the form.</p>';
                return;
            }
            
            // Build hierarchical structure
            const categoriesMap = {};
            const rootCategories = [];
            
            // First pass: create map of id -> category
            categories.forEach(category => {
                categoriesMap[category.id] = { ...category, children: [] };
            });
            
            // Second pass: build hierarchy
            categories.forEach(category => {
                if (category.parentId && categoriesMap[category.parentId]) {
                    categoriesMap[category.parentId].children.push(categoriesMap[category.id]);
                } else {
                    rootCategories.push(categoriesMap[category.id]);
                }
            });
            
            // Generate HTML
            let categoriesHTML = '<table>';
            categoriesHTML += '<tr><th>Name</th><th>Description</th><th>Slug</th><th>Posts</th><th>Actions</th></tr>';
            
            // Render root categories and their children
            rootCategories.forEach(category => {
                categoriesHTML += getCategoryRow(category);
                
                // Render children
                category.children.forEach(child => {
                    categoriesHTML += getCategoryRow(child, true);
                });
            });
            
            categoriesHTML += '</table>';
            categoriesList.innerHTML = categoriesHTML;
            
            // Add event listeners
            document.querySelectorAll('.edit-category').forEach(button => {
                button.addEventListener('click', (e) => {
                    const categoryId = e.target.getAttribute('data-id');
                    openEditModal(categoryId);
                });
            });
            
            document.querySelectorAll('.delete-category').forEach(button => {
                button.addEventListener('click', (e) => {
                    const categoryId = e.target.getAttribute('data-id');
                    openDeleteModal(categoryId);
                });
            });
        }
        
        // Generate category row HTML
        function getCategoryRow(category, isChild = false) {
            return `
                <tr>
                    <td>${isChild ? '— ' : ''}${category.name}</td>
                    <td>${category.description || ''}</td>
                    <td>${category.slug || ''}</td>
                    <td>${category.postCount || 0}</td>
                    <td>
                        <button class="btn btn-primary edit-category" data-id="${category.id}">Edit</button>
                        <button class="btn btn-danger delete-category" data-id="${category.id}">Delete</button>
                    </td>
                </tr>
            `;
        }
        
        // Populate parent category select boxes
        function populateParentSelect() {
            const parentSelects = [
                document.getElementById('category-parent'),
                document.getElementById('edit-category-parent')
            ];
            
            parentSelects.forEach(select => {
                // Clear existing options except the "None" option
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                // Add categories as options
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                });
            });
        }
        
        // Setup form submission
        function setupFormSubmission() {
            // Add category form
            document.getElementById('add-category-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const nameInput = document.getElementById('category-name');
                const slugInput = document.getElementById('category-slug');
                const descriptionInput = document.getElementById('category-description');
                const parentSelect = document.getElementById('category-parent');
                
                const name = nameInput.value.trim();
                const slug = slugInput.value.trim() || createSlug(name);
                const description = descriptionInput.value.trim();
                const parentId = parentSelect.value.trim();
                
                try {
                    // Check if slug is unique
                    if (!isSlugUnique(slug)) {
                        showMessage('Slug already exists. Please choose a different one.', 'error');
                        return;
                    }
                    
                    // Create new category
                    const newCategoryRef = doc(collection(db, "categories"));
                    await setDoc(newCategoryRef, {
                        name,
                        slug,
                        description,
                        parentId: parentId || null,
                        postCount: 0,
                        createdAt: new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    });
                    
                    // Clear form
                    nameInput.value = '';
                    slugInput.value = '';
                    descriptionInput.value = '';
                    parentSelect.value = '';
                    
                    showMessage('Category added successfully!', 'success');
                    
                    // Reload categories
                    loadCategories();
                } catch (error) {
                    console.error("Error adding category:", error);
                    showMessage('Error adding category. Please try again.', 'error');
                }
            });
            
            // Add slug generation on blur
            document.getElementById('category-name').addEventListener('blur', () => {
                const nameInput = document.getElementById('category-name');
                const slugInput = document.getElementById('category-slug');
                
                if (nameInput.value.trim() && !slugInput.value.trim()) {
                    slugInput.value = createSlug(nameInput.value.trim());
                }
            });
            
            document.getElementById('edit-category-name').addEventListener('blur', () => {
                const nameInput = document.getElementById('edit-category-name');
                const slugInput = document.getElementById('edit-category-slug');
                
                if (nameInput.value.trim() && !slugInput.value.trim()) {
                    slugInput.value = createSlug(nameInput.value.trim());
                }
            });
            
            // Update category form
            document.getElementById('update-category-btn').addEventListener('click', async () => {
                const categoryId = document.getElementById('edit-category-id').value;
                const name = document.getElementById('edit-category-name').value.trim();
                const slug = document.getElementById('edit-category-slug').value.trim() || createSlug(name);
                const description = document.getElementById('edit-category-description').value.trim();
                const parentId = document.getElementById('edit-category-parent').value.trim();
                
                try {
                    // Check if slug is unique (except for this category)
                    if (!isSlugUnique(slug, categoryId)) {
                        showMessage('Slug already exists. Please choose a different one.', 'error');
                        return;
                    }
                    
                    // Prevent setting parent to self or descendants
                    if (parentId === categoryId || isDescendant(categoryId, parentId)) {
                        showMessage('Cannot set a category as its own parent or child.', 'error');
                        return;
                    }
                    
                    // Update category
                    await updateDoc(doc(db, "categories", categoryId), {
                        name,
                        slug,
                        description,
                        parentId: parentId || null,
                        updatedAt: new Date().toISOString()
                    });
                    
                    showMessage('Category updated successfully!', 'success');
                    
                    // Close modal
                    document.getElementById('edit-modal').style.display = 'none';
                    
                    // Reload categories
                    loadCategories();
                } catch (error) {
                    console.error("Error updating category:", error);
                    showMessage('Error updating category. Please try again.', 'error');
                }
            });
            
            // Delete category
            document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
                if (!categoryToDelete) return;
                
                try {
                    // Get category and its children
                    const childrenQuery = query(
                        collection(db, "categories"),
                        where("parentId", "==", categoryToDelete)
                    );
                    const childrenSnapshot = await getDocs(childrenQuery);
                    
                    // Batch update to reset parent of children
                    for (const doc of childrenSnapshot.docs) {
                        await updateDoc(doc.ref, {
                            parentId: null,
                            updatedAt: new Date().toISOString()
                        });
                    }
                    
                    // Get posts with this category
                    const postsQuery = query(
                        collection(db, "posts"),
                        where("category", "==", getCategoryName(categoryToDelete))
                    );
                    const postsSnapshot = await getDocs(postsQuery);
                    
                    // Batch update to reset category of posts
                    for (const doc of postsSnapshot.docs) {
                        await updateDoc(doc.ref, {
                            category: '',
                            updatedAt: new Date().toISOString()
                        });
                    }
                    
                    // Delete category
                    await deleteDoc(doc(db, "categories", categoryToDelete));
                    
                    showMessage('Category deleted successfully!', 'success');
                    
                    // Close modal
                    document.getElementById('delete-modal').style.display = 'none';
                    
                    // Reload categories
                    loadCategories();
                } catch (error) {
                    console.error("Error deleting category:", error);
                    showMessage('Error deleting category. Please try again.', 'error');
                }
            });
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
        
        // Open edit modal
        function openEditModal(categoryId) {
            const category = categories.find(c => c.id === categoryId);
            if (!category) return;
            
            document.getElementById('edit-category-id').value = category.id;
            document.getElementById('edit-category-name').value = category.name || '';
            document.getElementById('edit-category-slug').value = category.slug || '';
            document.getElementById('edit-category-description').value = category.description || '';
            document.getElementById('edit-category-parent').value = category.parentId || '';
            
            // Disable selecting self as parent
            const parentSelect = document.getElementById('edit-category-parent');
            for (let i = 0; i < parentSelect.options.length; i++) {
                if (parentSelect.options[i].value === category.id) {
                    parentSelect.options[i].disabled = true;
                } else {
                    parentSelect.options[i].disabled = false;
                }
            }
            
            document.getElementById('edit-modal').style.display = 'block';
        }
        
        // Open delete modal
        function openDeleteModal(categoryId) {
            categoryToDelete = categoryId;
            document.getElementById('delete-modal').style.display = 'block';
        }
        
        // Show message
        function showMessage(message, type) {
            const messageElement = document.getElementById('category-message');
            messageElement.textContent = message;
            messageElement.className = 'alert';
            messageElement.classList.add(`alert-${type}`);
            messageElement.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
        
        // Create slug from text
        function createSlug(text) {
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special chars
                .replace(/\s+/g, '-')     // Replace spaces with dashes
                .replace(/-+/g, '-')      // Replace multiple dashes with single dash
                .trim();                   // Trim whitespace
        }
        
        // Check if slug is unique
        function isSlugUnique(slug, excludeId = null) {
            return !categories.some(category => 
                category.slug === slug && (!excludeId || category.id !== excludeId)
            );
        }
        
        // Check if category is a descendant of another category
        function isDescendant(categoryId, potentialParentId) {
            if (!potentialParentId) return false;
            if (categoryId === potentialParentId) return true;
            
            const potentialParent = categories.find(c => c.id === potentialParentId);
            if (!potentialParent || !potentialParent.parentId) return false;
            
            return isDescendant(categoryId, potentialParent.parentId);
        }
        
        // Get category name by ID
        function getCategoryName(categoryId) {
            const category = categories.find(c => c.id === categoryId);
            return category ? category.name : '';
        }
    </script>
</body>
</html>