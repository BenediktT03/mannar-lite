 <?php
// Include configuration
require_once 'config-loader.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin or editor
function isAdminOrEditor() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    return $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'editor';
}

// Redirect if not admin or editor
if (!isAdminOrEditor()) {
    header('Location: login.php');
    exit;
}

// Define available widget types
$widgetTypes = [
    'text' => 'Text Widget',
    'recent_posts' => 'Recent Posts',
    'categories' => 'Categories',
    'tags' => 'Tag Cloud',
    'search' => 'Search Box',
    'image' => 'Image Widget',
    'html' => 'Custom HTML',
    'social' => 'Social Media Links',
    'popular_posts' => 'Popular Posts',
    'newsletter' => 'Newsletter Signup'
];

// Define sidebar locations
$sidebarLocations = [
    'sidebar-main' => 'Main Sidebar',
    'sidebar-footer' => 'Footer',
    'sidebar-header' => 'Header',
    'sidebar-page' => 'Page Sidebar'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Management - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .widgets-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        
        .sidebar-selector {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
        }
        
        .sidebar-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-list li {
            margin-bottom: 10px;
        }
        
        .sidebar-list a {
            display: block;
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        
        .sidebar-list a:hover, .sidebar-list a.active {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .widgets-content {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
        }
        
        .widget-item {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .widget-title {
            font-weight: 500;
            margin: 0;
        }
        
        .widget-content {
            display: none;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        
        .widget-item.open .widget-content {
            display: block;
        }
        
        .widget-actions {
            position: absolute;
            right: 15px;
            top: 15px;
            display: flex;
            gap: 5px;
        }
        
        .available-widgets {
            margin-bottom: 20px;
        }
        
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .add-widget-item {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .add-widget-item:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .widget-type-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .widget-type-name {
            font-weight: 500;
        }
        
        .sortable-placeholder {
            border: 1px dashed #999;
            background-color: #f8f9fa;
            height: 50px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .widget-drag-handle {
            cursor: move;
            margin-right: 10px;
            color: #999;
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
                <li><a href="widgets.php" class="active">Widgets</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Widget Management</h1>
            <div id="message-container" class="alert" style="display: none;"></div>
            
            <div class="widgets-container">
                <div class="sidebar-selector">
                    <h2>Sidebar Locations</h2>
                    <p>Select a sidebar to manage its widgets.</p>
                    <ul class="sidebar-list">
                        <?php foreach ($sidebarLocations as $id => $name): ?>
                            <li>
                                <a href="#<?php echo $id; ?>" class="sidebar-link" data-sidebar="<?php echo $id; ?>">
                                    <?php echo $name; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div style="margin-top: 20px;">
                        <h3>Add New Sidebar</h3>
                        <form id="add-sidebar-form">
                            <div class="form-group">
                                <label for="sidebar-id">Sidebar ID</label>
                                <input type="text" id="sidebar-id" required placeholder="sidebar-custom">
                            </div>
                            <div class="form-group">
                                <label for="sidebar-name">Sidebar Name</label>
                                <input type="text" id="sidebar-name" required placeholder="Custom Sidebar">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Add Sidebar</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="widgets-content">
                    <div id="no-sidebar-selected" style="text-align: center; padding: 50px 0;">
                        <h2>Select a Sidebar</h2>
                        <p>Choose a sidebar from the left to manage its widgets.</p>
                    </div>
                    
                    <div id="sidebar-widgets" style="display: none;">
                        <div class="sidebar-header">
                            <h2>Widgets in <span id="current-sidebar-name">Main Sidebar</span></h2>
                            <p>Drag and drop to reorder widgets. Click on a widget to edit its settings.</p>
                        </div>
                        
                        <div class="available-widgets">
                            <h3>Add New Widget</h3>
                            <div class="widget-grid">
                                <?php foreach ($widgetTypes as $type => $name): ?>
                                    <div class="add-widget-item" data-type="<?php echo $type; ?>">
                                        <div class="widget-type-icon"><?php echo getWidgetIcon($type); ?></div>
                                        <div class="widget-type-name"><?php echo $name; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <h3>Current Widgets</h3>
                        <div id="widgets-container" class="widgets-list">
                            <p id="no-widgets-message">No widgets in this sidebar yet. Add some widgets from above.</p>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px; text-align: right;">
                            <button id="save-widgets-btn" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Widget Settings Modal -->
            <div id="widget-settings-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Widget Settings</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="widget-settings-form">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="update-widget-btn" class="btn btn-primary">Update Widget</button>
                        <button class="btn modal-close-btn">Cancel</button>
                    </div>
                </div>
            </div>
            
            <!-- Delete Sidebar Confirmation Modal -->
            <div id="delete-sidebar-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Delete Sidebar</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this sidebar? All widgets in this sidebar will be removed.</p>
                    </div>
                    <div class="modal-footer">
                        <button id="confirm-delete-sidebar-btn" class="btn btn-danger">Delete Sidebar</button>
                        <button class="btn modal-close-btn">Cancel</button>
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
        import { getFirestore, collection, doc, getDoc, setDoc, deleteDoc, addDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

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
        let currentSidebar = null;
        let widgets = [];
        let sidebarSettings = {};
        
        // Check authentication state
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                // Load sidebars
                await loadSidebars();
                
                // Set up event listeners
                setupEventListeners();
            } else {
                // Redirect to login page
                window.location.href = "login.php";
            }
        });
        
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', () => {
            signOut(auth).then(() => {
                window.location.href = "login.php";
            }).catch((error) => {
                console.error("Logout error:", error);
            });
        });
        
        // Load sidebars from Firestore
        async function loadSidebars() {
            try {
                const sidebarsDoc = await getDoc(doc(db, "system", "sidebars"));
                
                if (sidebarsDoc.exists()) {
                    sidebarSettings = sidebarsDoc.data().sidebars || {};
                    
                    // Add custom sidebars to the list
                    for (const [id, name] of Object.entries(sidebarSettings)) {
                        if (!document.querySelector(`.sidebar-link[data-sidebar="${id}"]`)) {
                            addSidebarToList(id, name);
                        }
                    }
                }
            } catch (error) {
                console.error("Error loading sidebars:", error);
                showMessage("Error loading sidebars. Please try again.", "error");
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Sidebar selection
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active state
                    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    
                    // Load widgets for selected sidebar
                    const sidebarId = link.getAttribute('data-sidebar');
                    const sidebarName = link.textContent.trim();
                    currentSidebar = sidebarId;
                    
                    document.getElementById('no-sidebar-selected').style.display = 'none';
                    document.getElementById('sidebar-widgets').style.display = 'block';
                    document.getElementById('current-sidebar-name').textContent = sidebarName;
                    
                    loadWidgets(sidebarId);
                });
            });
            
            // Add new sidebar form
            document.getElementById('add-sidebar-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const sidebarId = document.getElementById('sidebar-id').value.trim();
                const sidebarName = document.getElementById('sidebar-name').value.trim();
                
                if (!sidebarId || !sidebarName) {
                    showMessage("Please enter both sidebar ID and name.", "error");
                    return;
                }
                
                try {
                    // Add to sidebar settings
                    sidebarSettings[sidebarId] = sidebarName;
                    
                    // Save to Firestore
                    await setDoc(doc(db, "system", "sidebars"), {
                        sidebars: sidebarSettings,
                        updatedAt: new Date().toISOString()
                    });
                    
                    // Add to sidebar list
                    addSidebarToList(sidebarId, sidebarName);
                    
                    // Reset form
                    document.getElementById('add-sidebar-form').reset();
                    
                    showMessage("Sidebar added successfully!", "success");
                } catch (error) {
                    console.error("Error adding sidebar:", error);
                    showMessage("Error adding sidebar. Please try again.", "error");
                }
            });
            
            // Add widget items
            document.querySelectorAll('.add-widget-item').forEach(item => {
                item.addEventListener('click', () => {
                    if (!currentSidebar) {
                        showMessage("Please select a sidebar first.", "error");
                        return;
                    }
                    
                    const widgetType = item.getAttribute('data-type');
                    addWidget(widgetType);
                });
            });
            
            // Save widgets button
            document.getElementById('save-widgets-btn').addEventListener('click', saveWidgets);
            
            // Modal close buttons
            document.querySelectorAll('.modal-close, .modal-close-btn').forEach(button => {
                button.addEventListener('click', () => {
                    document.getElementById('widget-settings-modal').style.display = 'none';
                    document.getElementById('delete-sidebar-modal').style.display = 'none';
                });
            });
            
            // Update widget button
            document.getElementById('update-widget-btn').addEventListener('click', updateWidgetSettings);
            
            // Delete sidebar confirmation
            document.getElementById('confirm-delete-sidebar-btn').addEventListener('click', deleteSidebar);
        }
        
        // Load widgets for a sidebar
        async function loadWidgets(sidebarId) {
            try {
                const widgetsDoc = await getDoc(doc(db, "widgets", sidebarId));
                
                // Reset widgets array
                widgets = [];
                
                if (widgetsDoc.exists()) {
                    widgets = widgetsDoc.data().widgets || [];
                }
                
                renderWidgets();
            } catch (error) {
                console.error("Error loading widgets:", error);
                showMessage("Error loading widgets. Please try again.", "error");
            }
        }
        
        // Render widgets
        function renderWidgets() {
            const widgetsContainer = document.getElementById('widgets-container');
            const noWidgetsMessage = document.getElementById('no-widgets-message');
            
            if (widgets.length === 0) {
                noWidgetsMessage.style.display = 'block';
                widgetsContainer.innerHTML = noWidgetsMessage.outerHTML;
                return;
            }
            
            noWidgetsMessage.style.display = 'none';
            let widgetsHTML = '';
            
            widgets.forEach((widget, index) => {
                widgetsHTML += `
                    <div class="widget-item" data-index="${index}">
                        <div class="widget-header">
                            <span class="widget-drag-handle">&#9776;</span>
                            <h3 class="widget-title">${widget.title || getWidgetTypeName(widget.type)}</h3>
                            <div class="widget-actions">
                                <button class="btn edit-widget" data-index="${index}">Edit</button>
                                <button class="btn btn-danger delete-widget" data-index="${index}">Remove</button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <p><strong>Type:</strong> ${getWidgetTypeName(widget.type)}</p>
                            ${getWidgetPreview(widget)}
                        </div>
                    </div>
                `;
            });
            
            widgetsContainer.innerHTML = widgetsHTML;
            
            // Add click events for widget headers (expand/collapse)
            document.querySelectorAll('.widget-header').forEach(header => {
                header.addEventListener('click', (e) => {
                    // Ignore if clicking on actions
                    if (e.target.closest('.widget-actions')) return;
                    
                    const widget = header.closest('.widget-item');
                    widget.classList.toggle('open');
                });
            });
            
            // Add event listeners for edit buttons
            document.querySelectorAll('.edit-widget').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const index = parseInt(button.getAttribute('data-index'));
                    openWidgetSettings(index);
                });
            });
            
            // Add event listeners for delete buttons
            document.querySelectorAll('.delete-widget').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const index = parseInt(button.getAttribute('data-index'));
                    removeWidget(index);
                });
            });
            
            // Initialize drag and drop
            initSortable();
        }
        
        // Add a widget
        function addWidget(type) {
            // Create a new widget object
            const widget = {
                type: type,
                title: getWidgetTypeName(type),
                settings: getDefaultSettings(type)
            };
            
            // Add to widgets array
            widgets.push(widget);
            
            // Re-render widgets
            renderWidgets();
            
            // Show success message
            showMessage("Widget added! Don't forget to save your changes.", "success");
        }
        
        // Remove a widget
        function removeWidget(index) {
            if (confirm("Are you sure you want to remove this widget?")) {
                // Remove from widgets array
                widgets.splice(index, 1);
                
                // Re-render widgets
                renderWidgets();
                
                // Show success message
                showMessage("Widget removed! Don't forget to save your changes.", "success");
            }
        }
        
        // Open widget settings modal
        function openWidgetSettings(index) {
            const widget = widgets[index];
            const modal = document.getElementById('widget-settings-modal');
            const form = document.getElementById('widget-settings-form');
            
            // Set form HTML based on widget type
            form.innerHTML = getSettingsForm(widget);
            
            // Set widget index as data attribute
            document.getElementById('update-widget-btn').setAttribute('data-index', index);
            
            // Fill form with current settings
            for (const [key, value] of Object.entries(widget.settings)) {
                const input = document.getElementById(`widget-${key}`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = value;
                    } else {
                        input.value = value;
                    }
                }
            }
            
            // Show modal
            modal.style.display = 'block';
        }
        
        // Update widget settings
        function updateWidgetSettings() {
            const index = parseInt(document.getElementById('update-widget-btn').getAttribute('data-index'));
            const widget = widgets[index];
            
            // Update title
            const titleInput = document.getElementById('widget-title');
            if (titleInput) {
                widget.title = titleInput.value;
            }
            
            // Update settings based on form inputs
            const inputs = document.querySelectorAll('#widget-settings-form input, #widget-settings-form select, #widget-settings-form textarea');
            inputs.forEach(input => {
                if (input.id && input.id !== 'widget-title') {
                    const key = input.id.replace('widget-', '');
                    widget.settings[key] = input.type === 'checkbox' ? input.checked : input.value;
                }
            });
            
            // Close modal
            document.getElementById('widget-settings-modal').style.display = 'none';
            
            // Re-render widgets
            renderWidgets();
            
            // Show success message
            showMessage("Widget updated! Don't forget to save your changes.", "success");
        }
        
        // Save widgets to Firestore
        async function saveWidgets() {
            if (!currentSidebar) {
                showMessage("Please select a sidebar first.", "error");
                return;
            }
            
            try {
                await setDoc(doc(db, "widgets", currentSidebar), {
                    widgets: widgets,
                    updatedAt: new Date().toISOString()
                });
                
                showMessage("Widgets saved successfully!", "success");
            } catch (error) {
                console.error("Error saving widgets:", error);
                showMessage("Error saving widgets. Please try again.", "error");
            }
        }
        
        // Delete sidebar
        async function deleteSidebar() {
            if (!currentSidebar) {
                showMessage("Please select a sidebar first.", "error");
                return;
            }
            
            try {
                // Delete widgets document
                await deleteDoc(doc(db, "widgets", currentSidebar));
                
                // Remove from settings
                delete sidebarSettings[currentSidebar];
                
                // Save updated settings
                await setDoc(doc(db, "system", "sidebars"), {
                    sidebars: sidebarSettings,
                    updatedAt: new Date().toISOString()
                });
                
                // Remove from sidebar list
                const sidebarLink = document.querySelector(`.sidebar-link[data-sidebar="${currentSidebar}"]`);
                if (sidebarLink) {
                    sidebarLink.parentNode.remove();
                }
                
                // Reset current sidebar
                currentSidebar = null;
                document.getElementById('no-sidebar-selected').style.display = 'block';
                document.getElementById('sidebar-widgets').style.display = 'none';
                
                // Close modal
                document.getElementById('delete-sidebar-modal').style.display = 'none';
                
                showMessage("Sidebar deleted successfully!", "success");
            } catch (error) {
                console.error("Error deleting sidebar:", error);
                showMessage("Error deleting sidebar. Please try again.", "error");
            }
        }
        
        // Initialize sortable for drag and drop
        function initSortable() {
            // Using a simple implementation here
            // For a production app, consider a library like SortableJS
            const container = document.getElementById('widgets-container');
            let draggedItem = null;
            
            // Add event listeners to all widget items
            document.querySelectorAll('.widget-item').forEach(item => {
                const handle = item.querySelector('.widget-drag-handle');
                
                handle.addEventListener('mousedown', (e) => {
                    draggedItem = item;
                    const rect = item.getBoundingClientRect();
                    
                    // Create a clone for dragging
                    const clone = item.cloneNode(true);
                    clone.style.position = 'absolute';
                    clone.style.zIndex = 1000;
                    clone.style.width = rect.width + 'px';
                    clone.style.opacity = '0.8';
                    document.body.appendChild(clone);
                    
                    // Create placeholder
                    const placeholder = document.createElement('div');
                    placeholder.className = 'sortable-placeholder';
                    item.parentNode.insertBefore(placeholder, item);
                    
                    // Hide original
                    item.style.display = 'none';
                    
                    // Set up move and up handlers
                    document.addEventListener('mousemove', mouseMoveHandler);
                    document.addEventListener('mouseup', mouseUpHandler);
                    
                    // Move handler
                    function mouseMoveHandler(e) {
                        clone.style.top = (e.clientY - 20) + 'px';
                        clone.style.left = (e.clientX - 20) + 'px';
                        
                        // Find closest widget item
                        const items = Array.from(document.querySelectorAll('.widget-item'));
                        items.forEach(current => {
                            if (current !== draggedItem && current.style.display !== 'none') {
                                const rect = current.getBoundingClientRect();
                                const centerY = rect.top + rect.height / 2;
                                
                                if (e.clientY < centerY) {
                                    container.insertBefore(placeholder, current);
                                } else if (current.nextSibling && current.nextSibling !== placeholder) {
                                    container.insertBefore(placeholder, current.nextSibling);
                                } else {
                                    container.appendChild(placeholder);
                                }
                            }
                        });
                    }
                    
                    // Up handler
                    function mouseUpHandler() {
                        // Clean up
                        document.removeEventListener('mousemove', mouseMoveHandler);
                        document.removeEventListener('mouseup', mouseUpHandler);
                        document.body.removeChild(clone);
                        
                        // Show original at new position
                        container.insertBefore(draggedItem, placeholder);
                        container.removeChild(placeholder);
                        draggedItem.style.display = '';
                        
                        // Update widgets array
                        const newOrder = Array.from(container.querySelectorAll('.widget-item')).map(item => {
                            const index = parseInt(item.getAttribute('data-index'));
                            return widgets[index];
                        });
                        
                        widgets = newOrder;
                        renderWidgets();
                    }
                });
            });
        }
        
        // Show message
        function showMessage(message, type) {
            const messageContainer = document.getElementById('message-container');
            messageContainer.textContent = message;
            messageContainer.className = `alert alert-${type}`;
            messageContainer.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                messageContainer.style.display = 'none';
            }, 5000);
        }
        
        // Helper function: Add sidebar to list
        function addSidebarToList(id, name) {
            const sidebarList = document.querySelector('.sidebar-list');
            const listItem = document.createElement('li');
            listItem.innerHTML = `
                <a href="#${id}" class="sidebar-link" data-sidebar="${id}">
                    ${name}
                    <button class="delete-sidebar-btn" data-sidebar="${id}" style="float: right; background: none; border: none; color: #dc3545; cursor: pointer;">&times;</button>
                </a>
            `;
            sidebarList.appendChild(listItem);
            
            // Add click event for the new sidebar link
            const link = listItem.querySelector('.sidebar-link');
            link.addEventListener('click', (e) => {
                if (e.target.classList.contains('delete-sidebar-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    openDeleteSidebarModal(id);
                    return;
                }
                
                e.preventDefault();
                
                // Update active state
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Load widgets for selected sidebar
                currentSidebar = id;
                
                document.getElementById('no-sidebar-selected').style.display = 'none';
                document.getElementById('sidebar-widgets').style.display = 'block';
                document.getElementById('current-sidebar-name').textContent = name;
                
                loadWidgets(id);
            });
            
            // Add click event for the delete button
            listItem.querySelector('.delete-sidebar-btn').addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openDeleteSidebarModal(id);
            });
        }
        
        // Open delete sidebar modal
        function openDeleteSidebarModal(sidebarId) {
            document.getElementById('confirm-delete-sidebar-btn').setAttribute('data-sidebar', sidebarId);
            document.getElementById('delete-sidebar-modal').style.display = 'block';
            currentSidebar = sidebarId;
        }
        
        // Helper function: Get widget type name
        function getWidgetTypeName(type) {
            const types = {
                'text': 'Text Widget',
                'recent_posts': 'Recent Posts',
                'categories': 'Categories',
                'tags': 'Tag Cloud',
                'search': 'Search Box',
                'image': 'Image Widget',
                'html': 'Custom HTML',
                'social': 'Social Media Links',
                'popular_posts': 'Popular Posts',
                'newsletter': 'Newsletter Signup'
            };
            
            return types[type] || 'Widget';
        }
        
        // Helper function: Get widget icon
        function getWidgetIcon(type) {
            const icons = {
                'text': '📝',
                'recent_posts': '📰',
                'categories': '📑',
                'tags': '🏷️',
                'search': '🔍',
                'image': '🖼️',
                'html': '📄',
                'social': '📱',
                'popular_posts': '🔥',
                'newsletter': '📧'
            };
            
            return icons[type] || '📦';
        }
        
        // Helper function: Get default settings for a widget type
        function getDefaultSettings(type) {
            switch (type) {
                case 'text':
                    return {
                        content: 'Enter your text here...',
                        show_title: true
                    };
                case 'recent_posts':
                    return {
                        count: 5,
                        show_date: true,
                        show_excerpt: false,
                        category: ''
                    };
                case 'categories':
                    return {
                        show_count: true,
                        hide_empty: true
                    };
                case 'tags':
                    return {
                        max_tags: 20,
                        show_count: true
                    };
                case 'search':
                    return {
                        placeholder: 'Search...',
                        button_text: 'Search'
                    };
                case 'image':
                    return {
                        image_url: '',
                        alt_text: '',
                        link_url: ''
                    };
                case 'html':
                    return {
                        html_content: '<div>Custom HTML here...</div>'
                    };
                case 'social':
                    return {
                        facebook: '',
                        twitter: '',
                        instagram: '',
                        linkedin: '',
                        youtube: '',
                        show_labels: true
                    };
                case 'popular_posts':
                    return {
                        count: 5,
                        time_range: 'month',
                        show_image: true
                    };
                case 'newsletter':
                    return {
                        description: 'Sign up for our newsletter',
                        button_text: 'Subscribe',
                        service: 'mailchimp',
                        form_action: ''
                    };
                default:
                    return {};
            }
        }
        
        // Helper function: Get settings form for a widget type
        function getSettingsForm(widget) {
            let html = `
                <div class="form-group">
                    <label for="widget-title">Widget Title</label>
                    <input type="text" id="widget-title" value="${widget.title || ''}">
                </div>
            `;
            
            // Add type-specific settings
            switch (widget.type) {
                case 'text':
                    html += `
                        <div class="form-group">
                            <label for="widget-content">Content</label>
                            <textarea id="widget-content" rows="5">${widget.settings.content || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_title"> 
                                Show title
                            </label>
                        </div>
                    `;
                    break;
                
                case 'recent_posts':
                    html += `
                        <div class="form-group">
                            <label for="widget-count">Number of posts</label>
                            <input type="number" id="widget-count" min="1" max="20" value="${widget.settings.count || 5}">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_date"> 
                                Show date
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_excerpt"> 
                                Show excerpt
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="widget-category">Category (leave empty for all)</label>
                            <input type="text" id="widget-category" value="${widget.settings.category || ''}">
                        </div>
                    `;
                    break;
                
                case 'categories':
                    html += `
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_count"> 
                                Show post count
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-hide_empty"> 
                                Hide empty categories
                            </label>
                        </div>
                    `;
                    break;
                
                case 'tags':
                    html += `
                        <div class="form-group">
                            <label for="widget-max_tags">Maximum number of tags</label>
                            <input type="number" id="widget-max_tags" min="1" max="100" value="${widget.settings.max_tags || 20}">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_count"> 
                                Show post count
                            </label>
                        </div>
                    `;
                    break;
                
                case 'search':
                    html += `
                        <div class="form-group">
                            <label for="widget-placeholder">Placeholder text</label>
                            <input type="text" id="widget-placeholder" value="${widget.settings.placeholder || 'Search...'}">
                        </div>
                        <div class="form-group">
                            <label for="widget-button_text">Button text</label>
                            <input type="text" id="widget-button_text" value="${widget.settings.button_text || 'Search'}">
                        </div>
                    `;
                    break;
                
                case 'image':
                    html += `
                        <div class="form-group">
                            <label for="widget-image_url">Image URL</label>
                            <input type="text" id="widget-image_url" value="${widget.settings.image_url || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-alt_text">Alt text</label>
                            <input type="text" id="widget-alt_text" value="${widget.settings.alt_text || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-link_url">Link URL (optional)</label>
                            <input type="text" id="widget-link_url" value="${widget.settings.link_url || ''}">
                        </div>
                    `;
                    break;
                
                case 'html':
                    html += `
                        <div class="form-group">
                            <label for="widget-html_content">HTML content</label>
                            <textarea id="widget-html_content" rows="10">${widget.settings.html_content || ''}</textarea>
                        </div>
                    `;
                    break;
                
                case 'social':
                    html += `
                        <div class="form-group">
                            <label for="widget-facebook">Facebook URL</label>
                            <input type="text" id="widget-facebook" value="${widget.settings.facebook || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-twitter">Twitter URL</label>
                            <input type="text" id="widget-twitter" value="${widget.settings.twitter || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-instagram">Instagram URL</label>
                            <input type="text" id="widget-instagram" value="${widget.settings.instagram || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-linkedin">LinkedIn URL</label>
                            <input type="text" id="widget-linkedin" value="${widget.settings.linkedin || ''}">
                        </div>
                        <div class="form-group">
                            <label for="widget-youtube">YouTube URL</label>
                            <input type="text" id="widget-youtube" value="${widget.settings.youtube || ''}">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_labels"> 
                                Show labels
                            </label>
                        </div>
                    `;
                    break;
                
                case 'popular_posts':
                    html += `
                        <div class="form-group">
                            <label for="widget-count">Number of posts</label>
                            <input type="number" id="widget-count" min="1" max="20" value="${widget.settings.count || 5}">
                        </div>
                        <div class="form-group">
                            <label for="widget-time_range">Time range</label>
                            <select id="widget-time_range">
                                <option value="day" ${widget.settings.time_range === 'day' ? 'selected' : ''}>Day</option>
                                <option value="week" ${widget.settings.time_range === 'week' ? 'selected' : ''}>Week</option>
                                <option value="month" ${widget.settings.time_range === 'month' ? 'selected' : ''}>Month</option>
                                <option value="year" ${widget.settings.time_range === 'year' ? 'selected' : ''}>Year</option>
                                <option value="all" ${widget.settings.time_range === 'all' ? 'selected' : ''}>All Time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="widget-show_image"> 
                                Show featured image
                            </label>
                        </div>
                    `;
                    break;
                
                case 'newsletter':
                    html += `
                        <div class="form-group">
                            <label for="widget-description">Description</label>
                            <textarea id="widget-description" rows="3">${widget.settings.description || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="widget-button_text">Button text</label>
                            <input type="text" id="widget-button_text" value="${widget.settings.button_text || 'Subscribe'}">
                        </div>
                        <div class="form-group">
                            <label for="widget-service">Service</label>
                            <select id="widget-service">
                                <option value="mailchimp" ${widget.settings.service === 'mailchimp' ? 'selected' : ''}>Mailchimp</option>
                                <option value="convertkit" ${widget.settings.service === 'convertkit' ? 'selected' : ''}>ConvertKit</option>
                                <option value="custom" ${widget.settings.service === 'custom' ? 'selected' : ''}>Custom</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="widget-form_action">Form action URL</label>
                            <input type="text" id="widget-form_action" value="${widget.settings.form_action || ''}">
                        </div>
                    `;
                    break;
            }
            
            return html;
        }
        
        // Helper function: Get widget preview HTML
        function getWidgetPreview(widget) {
            switch (widget.type) {
                case 'text':
                    return `<p>${widget.settings.content ? widget.settings.content.substring(0, 100) + (widget.settings.content.length > 100 ? '...' : '') : ''}</p>`;
                
                case 'recent_posts':
                    return `<p>Shows ${widget.settings.count || 5} recent posts${widget.settings.category ? ` from category "${widget.settings.category}"` : ''}.</p>`;
                
                case 'categories':
                    return `<p>Displays category list${widget.settings.show_count ? ' with post counts' : ''}.</p>`;
                
                case 'tags':
                    return `<p>Displays up to ${widget.settings.max_tags || 20} tags${widget.settings.show_count ? ' with post counts' : ''}.</p>`;
                
                case 'search':
                    return `<p>Search box with placeholder "${widget.settings.placeholder || 'Search...'}"</p>`;
                
                case 'image':
                    return widget.settings.image_url 
                        ? `<p><img src="${widget.settings.image_url}" alt="${widget.settings.alt_text || ''}" style="max-width: 100%; max-height: 100px;"></p>` 
                        : `<p>Image widget (no image set)</p>`;
                
                case 'html':
                    return `<p>Custom HTML content (${widget.settings.html_content ? widget.settings.html_content.length : 0} characters)</p>`;
                
                case 'social':
                    const networks = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube'].filter(n => widget.settings[n]).length;
                    return `<p>Displays ${networks} social media links.</p>`;
                
                case 'popular_posts':
                    return `<p>Shows ${widget.settings.count || 5} popular posts from ${widget.settings.time_range || 'month'} timeframe.</p>`;
                
                case 'newsletter':
                    return `<p>Newsletter signup form using ${widget.settings.service || 'mailchimp'} service.</p>`;
                
                default:
                    return `<p>Widget preview not available.</p>`;
            }
        }
    </script>
</body>
</html>

<?php
// Helper function to get widget icon
function getWidgetIcon($type) {
    $icons = [
        'text' => '📝',
        'recent_posts' => '📰',
        'categories' => '📑',
        'tags' => '🏷️',
        'search' => '🔍',
        'image' => '🖼️',
        'html' => '📄',
        'social' => '📱',
        'popular_posts' => '🔥',
        'newsletter' => '📧'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : '📦';
}
?>