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

// Process actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'approve' && isset($_POST['comment_id'])) {
            // Approve comment
            $commentId = $_POST['comment_id'];
            
            try {
                // Update comment status in Firestore
                updateDocument('comments', $commentId, [
                    'approved' => true,
                    'updatedAt' => date('c'),
                    'modifiedBy' => $_SESSION['user_id']
                ]);
                
                $message = 'Comment approved successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error approving comment: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($action === 'reject' && isset($_POST['comment_id'])) {
            // Reject comment
            $commentId = $_POST['comment_id'];
            
            try {
                // Update comment status in Firestore
                updateDocument('comments', $commentId, [
                    'approved' => false,
                    'updatedAt' => date('c'),
                    'modifiedBy' => $_SESSION['user_id']
                ]);
                
                $message = 'Comment rejected successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error rejecting comment: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($action === 'delete' && isset($_POST['comment_id'])) {
            // Delete comment
            $commentId = $_POST['comment_id'];
            
            try {
                // Delete comment from Firestore
                deleteDocument('comments', $commentId);
                
                $message = 'Comment deleted successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error deleting comment: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($action === 'bulk_action' && isset($_POST['bulk_action']) && isset($_POST['comment_ids'])) {
            // Bulk action on comments
            $bulkAction = $_POST['bulk_action'];
            $commentIds = $_POST['comment_ids'];
            
            if (empty($commentIds)) {
                $message = 'No comments selected.';
                $messageType = 'warning';
            } else {
                try {
                    $successCount = 0;
                    
                    foreach ($commentIds as $commentId) {
                        if ($bulkAction === 'approve') {
                            // Approve comment
                            updateDocument('comments', $commentId, [
                                'approved' => true,
                                'updatedAt' => date('c'),
                                'modifiedBy' => $_SESSION['user_id']
                            ]);
                            $successCount++;
                        } elseif ($bulkAction === 'reject') {
                            // Reject comment
                            updateDocument('comments', $commentId, [
                                'approved' => false,
                                'updatedAt' => date('c'),
                                'modifiedBy' => $_SESSION['user_id']
                            ]);
                            $successCount++;
                        } elseif ($bulkAction === 'delete') {
                            // Delete comment
                            deleteDocument('comments', $commentId);
                            $successCount++;
                        }
                    }
                    
                    $message = "Processed $successCount comments successfully.";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error processing comments: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'save_settings') {
            // Save comment settings
            try {
                $settings = [
                    'comments_enabled' => isset($_POST['comments_enabled']),
                    'require_approval' => isset($_POST['require_approval']),
                    'allow_guest_comments' => isset($_POST['allow_guest_comments']),
                    'notify_new_comment' => isset($_POST['notify_new_comment']),
                    'notification_email' => $_POST['notification_email'] ?? '',
                    'min_comment_length' => (int)($_POST['min_comment_length'] ?? 3),
                    'max_comment_length' => (int)($_POST['max_comment_length'] ?? 1000),
                    'spam_words' => array_filter(array_map('trim', explode("\n", $_POST['spam_words'] ?? ''))),
                    'max_links' => (int)($_POST['max_links'] ?? 2),
                    'akismet_key' => $_POST['akismet_key'] ?? '',
                    'recaptcha_enabled' => isset($_POST['recaptcha_enabled']),
                    'recaptcha_site_key' => $_POST['recaptcha_site_key'] ?? '',
                    'recaptcha_secret_key' => $_POST['recaptcha_secret_key'] ?? '',
                    'updatedAt' => date('c'),
                    'updatedBy' => $_SESSION['user_id']
                ];
                
                // Save settings to Firestore
                saveDocument('system', 'comment_settings', $settings);
                
                $message = 'Comment settings updated successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error saving comment settings: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Load comments
function loadComments($status, $search, $limit, $offset) {
    // Build query
    $conditions = [];
    
    if ($status === 'approved') {
        $conditions[] = ['approved', '==', true];
    } elseif ($status === 'pending') {
        $conditions[] = ['approved', '==', false];
    }
    
    // Execute query
    $comments = queryDocuments('comments', $conditions, 'createdAt', 'desc', $limit, $offset);
    
    // Filter by search if provided
    if (!empty($search)) {
        $comments = array_filter($comments, function($comment) use ($search) {
            $search = strtolower($search);
            return (
                strpos(strtolower($comment['name'] ?? ''), $search) !== false ||
                strpos(strtolower($comment['email'] ?? ''), $search) !== false ||
                strpos(strtolower($comment['text'] ?? ''), $search) !== false
            );
        });
    }
    
    return $comments;
}

// Count total comments
function countComments($status, $search) {
    // Build query
    $conditions = [];
    
    if ($status === 'approved') {
        $conditions[] = ['approved', '==', true];
    } elseif ($status === 'pending') {
        $conditions[] = ['approved', '==', false];
    }
    
    // Execute query
    $comments = queryDocuments('comments', $conditions);
    
    // Filter by search if provided
    if (!empty($search)) {
        $comments = array_filter($comments, function($comment) use ($search) {
            $search = strtolower($search);
            return (
                strpos(strtolower($comment['name'] ?? ''), $search) !== false ||
                strpos(strtolower($comment['email'] ?? ''), $search) !== false ||
                strpos(strtolower($comment['text'] ?? ''), $search) !== false
            );
        });
    }
    
    return count($comments);
}

// Get comment settings
function getCommentSettings() {
    $settings = getDocument('system', 'comment_settings');
    
    if (!$settings) {
        // Default settings
        return [
            'comments_enabled' => true,
            'require_approval' => true,
            'allow_guest_comments' => true,
            'notify_new_comment' => false,
            'notification_email' => '',
            'min_comment_length' => 3,
            'max_comment_length' => 1000,
            'spam_words' => [],
            'max_links' => 2,
            'akismet_key' => '',
            'recaptcha_enabled' => false,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => ''
        ];
    }
    
    return $settings;
}

// Load comments
$comments = loadComments($status, $search, $limit, $offset);
$totalComments = countComments($status, $search);
$totalPages = ceil($totalComments / $limit);

// Load comment settings
$commentSettings = getCommentSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Management - PHP Firebase CMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .comments-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .comments-container {
                grid-template-columns: 1fr;
            }
        }
        
        .comment-list {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .comment-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        
        .comment-item:last-child {
            border-bottom: none;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .comment-author {
            font-weight: 500;
        }
        
        .comment-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .comment-content {
            margin-bottom: 10px;
        }
        
        .comment-actions {
            display: flex;
            gap: 5px;
        }
        
        .comment-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .comment-filters {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .filter-group:last-child {
            margin-bottom: 0;
        }
        
        .filter-input {
            flex: 1;
        }
        
        .settings-card {
            background-color: #fff;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .settings-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 500;
        }
        
        .settings-body {
            padding: 15px;
        }
        
        .stats-card {
            background-color: #fff;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stat-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .stat-label {
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .bulk-actions {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .bulk-actions select {
            width: auto;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .tab-nav button {
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #495057;
            font-weight: 500;
        }
        
        .tab-nav button.active {
            border-bottom-color: #0077b6;
            color: #0077b6;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .spam-rules textarea {
            height: 100px;
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h1>Comment Management</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="comments-container">
                <div class="comments-main">
                    <div class="comment-filters">
                        <form action="" method="get">
                            <div class="filter-group">
                                <div class="filter-input">
                                    <input type="text" name="search" placeholder="Search comments..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div>
                                    <select name="status">
                                        <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>All Comments</option>
                                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <?php if (!empty($search) || !empty($status)): ?>
                                        <a href="comments-manager.php" class="btn">Reset</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <form id="comments-form" action="" method="post">
                        <input type="hidden" name="action" value="bulk_action">
                        
                        <div class="bulk-actions">
                            <div>
                                <input type="checkbox" id="select-all">
                                <label for="select-all">Select All</label>
                            </div>
                            <div>
                                <select name="bulk_action">
                                    <option value="">Bulk Actions</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn">Apply</button>
                            </div>
                        </div>
                        
                        <div class="comment-list">
                            <?php if (empty($comments)): ?>
                                <div class="comment-item">
                                    <p>No comments found.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $id => $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <div class="comment-author">
                                                <input type="checkbox" name="comment_ids[]" value="<?php echo $id; ?>" class="comment-checkbox">
                                                <?php echo htmlspecialchars($comment['name'] ?? 'Anonymous'); ?>
                                            </div>
                                            <div class="comment-meta">
                                                <?php if (isset($comment['email'])): ?>
                                                    <span><?php echo htmlspecialchars($comment['email']); ?></span> |
                                                <?php endif; ?>
                                                <span>
                                                    <?php 
                                                    if (isset($comment['createdAt'])) {
                                                        $date = new DateTime($comment['createdAt']);
                                                        echo $date->format('M j, Y g:i a');
                                                    } else {
                                                        echo 'Unknown date';
                                                    }
                                                    ?>
                                                </span>
                                                <?php if (isset($comment['ip'])): ?>
                                                    | <span>IP: <?php echo htmlspecialchars($comment['ip']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="comment-status <?php echo isset($comment['approved']) && $comment['approved'] ? 'status-approved' : 'status-pending'; ?>">
                                            <?php echo isset($comment['approved']) && $comment['approved'] ? 'Approved' : 'Pending'; ?>
                                        </div>
                                        
                                        <div class="comment-content">
                                            <p><?php echo nl2br(htmlspecialchars($comment['text'] ?? '')); ?></p>
                                        </div>
                                        
                                        <div class="comment-post">
                                            <small>
                                                On post: 
                                                <?php 
                                                if (isset($comment['postId'])) {
                                                    $post = getDocument('posts', $comment['postId']);
                                                    if ($post) {
                                                        echo '<a href="post.php?id=' . $comment['postId'] . '">' . htmlspecialchars($post['title']) . '</a>';
                                                    } else {
                                                        echo 'Unknown post';
                                                    }
                                                } else {
                                                    echo 'Unknown post';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                        
                                        <div class="comment-actions">
                                            <?php if (isset($comment['approved']) && !$comment['approved']): ?>
                                                <form action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="comment_id" value="<?php echo $id; ?>">
                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                </form>
                                            <?php else: ?>
                                                <form action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="comment_id" value="<?php echo $id; ?>">
                                                    <button type="submit" class="btn btn-warning">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form action="" method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?php echo $id; ?>">
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            <?php else: ?>
                                <span class="disabled">Previous</span>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            <?php else: ?>
                                <span class="disabled">Next</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="comments-sidebar">
                    <div class="stats-card">
                        <h3>Comment Statistics</h3>
                        
                        <div class="stat-item">
                            <div class="stat-label">Total Comments</div>
                            <div class="stat-value"><?php echo $totalComments; ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Approved</div>
                            <div class="stat-value"><?php echo countComments('approved', ''); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?php echo countComments('pending', ''); ?></div>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <div class="settings-header">
                            <h3>Comment Settings</h3>
                        </div>
                        <div class="settings-body">
                            <div class="tab-nav">
                                <button class="tab-button active" data-tab="general">General</button>
                                <button class="tab-button" data-tab="spam">Anti-Spam</button>
                                <button class="tab-button" data-tab="notification">Notifications</button>
                            </div>
                            
                            <form action="" method="post">
                                <input type="hidden" name="action" value="save_settings">
                                
                                <!-- General Settings Tab -->
                                <div id="general-tab" class="tab-content active">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="comments_enabled" <?php echo $commentSettings['comments_enabled'] ? 'checked' : ''; ?>>
                                            Enable comments on your site
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="require_approval" <?php echo $commentSettings['require_approval'] ? 'checked' : ''; ?>>
                                            Comments must be manually approved
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="allow_guest_comments" <?php echo $commentSettings['allow_guest_comments'] ? 'checked' : ''; ?>>
                                            Allow comments from non-registered users
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="min-comment-length">Minimum comment length</label>
                                        <input type="number" id="min-comment-length" name="min_comment_length" value="<?php echo $commentSettings['min_comment_length']; ?>" min="1" max="100">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max-comment-length">Maximum comment length</label>
                                        <input type="number" id="max-comment-length" name="max_comment_length" value="<?php echo $commentSettings['max_comment_length']; ?>" min="10" max="10000">
                                    </div>
                                </div>
                                
                                <!-- Anti-Spam Settings Tab -->
                                <div id="spam-tab" class="tab-content">
                                    <div class="form-group">
                                        <label for="max-links">Maximum links allowed in a comment</label>
                                        <input type="number" id="max-links" name="max_links" value="<?php echo $commentSettings['max_links']; ?>" min="0" max="20">
                                        <p class="help-text">Set to 0 to disallow all links</p>
                                    </div>
                                    
                                    <div class="form-group spam-rules">
                                        <label for="spam-words">Spam words (one per line)</label>
                                        <textarea id="spam-words" name="spam_words"><?php echo implode("\n", $commentSettings['spam_words']); ?></textarea>
                                        <p class="help-text">Comments containing these words will be automatically marked as spam</p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="akismet-key">Akismet API Key</label>
                                        <input type="text" id="akismet-key" name="akismet_key" value="<?php echo $commentSettings['akismet_key']; ?>">
                                        <p class="help-text">Sign up at <a href="https://akismet.com" target="_blank">akismet.com</a> to get an API key</p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="recaptcha_enabled" <?php echo $commentSettings['recaptcha_enabled'] ? 'checked' : ''; ?>>
                                            Enable Google reCAPTCHA
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="recaptcha-site-key">reCAPTCHA Site Key</label>
                                        <input type="text" id="recaptcha-site-key" name="recaptcha_site_key" value="<?php echo $commentSettings['recaptcha_site_key']; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="recaptcha-secret-key">reCAPTCHA Secret Key</label>
                                        <input type="text" id="recaptcha-secret-key" name="recaptcha_secret_key" value="<?php echo $commentSettings['recaptcha_secret_key']; ?>">
                                        <p class="help-text">Get your keys from <a href="https://www.google.com/recaptcha" target="_blank">Google reCAPTCHA</a></p>
                                    </div>
                                </div>
                                
                                <!-- Notification Settings Tab -->
                                <div id="notification-tab" class="tab-content">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="notify_new_comment" <?php echo $commentSettings['notify_new_comment'] ? 'checked' : ''; ?>>
                                            Send email notification for new comments
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notification-email">Notification email address</label>
                                        <input type="email" id="notification-email" name="notification_email" value="<?php echo $commentSettings['notification_email']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>
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
        
        // Check authentication state
        onAuthStateChanged(auth, (user) => {
            if (!user) {
                // Redirect to login page if not authenticated
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
        
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked tab
                button.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show corresponding tab content
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Select all comments
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.comment-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Confirm bulk delete
        document.getElementById('comments-form').addEventListener('submit', (e) => {
            const action = document.querySelector('select[name="bulk_action"]').value;
            
            if (action === 'delete') {
                const checked = document.querySelectorAll('.comment-checkbox:checked');
                
                if (checked.length > 0 && !confirm(`Are you sure you want to delete ${checked.length} comment(s)?`)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            if (action === '') {
                e.preventDefault();
                alert('Please select an action to perform.');
                return false;
            }
            
            const checked = document.querySelectorAll('.comment-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Please select at least one comment.');
                return false;
            }
        });
    </script>
</body>
</html>