<?php

// Set page title
$pageTitle = "Delete Post - Inkora";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('Invalid post ID.', 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

// Initialize variables
$post = null;
$errors = [];
$canDelete = false;

try {
    // Get post data with author information
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.username as author_name,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
            (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        WHERE bp.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    // Check if post exists
    if (!$post) {
        setFlashMessage('Post not found.', 'danger');
        header('Location: ' . url('posts/index.php'));
        exit();
    }
    
    // Check if user has permission to delete
    $currentUserId = getCurrentUserId();
    $isAuthor = ($post['user_id'] == $currentUserId);
    $isAdministrator = isAdmin();
    
    if (!$isAuthor && !$isAdministrator) {
        setFlashMessage('You do not have permission to delete this post.', 'danger');
        header('Location: ' . url('posts/view.php?id=' . $postId));
        exit();
    }
    
    $canDelete = true;
    
} catch (PDOException $e) {
    error_log("Delete post fetch error: " . $e->getMessage());
    setFlashMessage('An error occurred. Please try again.', 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

// Handle POST request (actual deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canDelete) {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    }
    
    // Validate confirmation
    if (!isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
        $errors[] = "Deletion not confirmed.";
    }
    
    // Proceed with deletion if no errors
    if (empty($errors)) {
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Delete related data
            try {
                $deleteReactions = $conn->prepare("DELETE FROM reactions WHERE blog_post_id = ?");
                $deleteReactions->execute([$postId]);
            } catch (PDOException $e) {
                error_log("Could not delete reactions: " . $e->getMessage());
            }
            
            try {
                $deleteComments = $conn->prepare("DELETE FROM comments WHERE blog_post_id = ?");
                $deleteComments->execute([$postId]);
            } catch (PDOException $e) {
                error_log("Could not delete comments: " . $e->getMessage());
            }
            
            // Delete the blog post
            $deletePost = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
            $deletePost->execute([$postId]);
            
            if ($deletePost->rowCount() === 0) {
                throw new Exception("Post deletion failed");
            }
            
            // Commit transaction
            $conn->commit();
            
            // Delete featured image file if exists
            if (!empty($post['featured_image'])) {
                $imagePath = BLOG_IMG_PATH . '/' . $post['featured_image'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            setFlashMessage('Post deleted successfully.', 'success');
            
            $redirectUrl = ($post['user_id'] == getCurrentUserId())
                ? url('posts/my_posts.php')
                : url('posts/index.php');
            
            header('Location: ' . $redirectUrl);
            exit();
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Delete post error: " . $e->getMessage());
            $errors[] = "Unable to delete the post. Please try again.";
        }
    }
}

// Include header
require_once '../includes/header.php';
?>

<style>
/* Simple Professional Styling */
.delete-container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 0 1rem;
}

.warning-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px 10px 0 0;
    text-align: center;
}

.warning-header i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.warning-header h1 {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
}

.warning-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.delete-card {
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 10px 10px;
    padding: 2rem;
}

.alert-box {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.alert-box h5 {
    color: #856404;
    margin-bottom: 0.75rem;
}

.alert-box p {
    color: #856404;
    margin: 0;
    line-height: 1.6;
}

.info-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.info-section h5 {
    margin-bottom: 1rem;
    color: #495057;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #6c757d;
    font-weight: 500;
}

.info-value {
    color: #212529;
    font-weight: 600;
    text-align: right;
}

.consequence-list {
    margin-bottom: 1.5rem;
}

.consequence-list h5 {
    margin-bottom: 1rem;
    color: #495057;
}

.consequence-item {
    background: #f8f9fa;
    padding: 1rem;
    border-left: 3px solid #ffc107;
    margin-bottom: 0.75rem;
    border-radius: 4px;
}

.consequence-item strong {
    display: block;
    color: #495057;
    margin-bottom: 0.25rem;
}

.consequence-item small {
    color: #6c757d;
}

.confirm-box {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.125rem;
}

.form-check-label {
    font-weight: 600;
    color: #495057;
}

.button-group {
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    margin-top: 1.5rem;
}

.btn-delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-delete:hover:not(:disabled) {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-cancel {
    background: white;
    color: #6c757d;
    border: 2px solid #dee2e6;
    padding: 0.75rem 2rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #495057;
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: white;
    border-radius: 4px;
    font-size: 0.875rem;
    color: #6c757d;
}

.security-note {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1.5rem;
}

.security-note i {
    color: #0066cc;
}

.security-note small {
    color: #004080;
}

/* Responsive */
@media (max-width: 768px) {
    .delete-container {
        margin: 1.5rem auto;
    }
    
    .warning-header {
        padding: 1.5rem;
    }
    
    .delete-card {
        padding: 1.5rem;
    }
    
    .info-row {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .info-value {
        text-align: left;
    }
    
    .button-group {
        flex-direction: column-reverse;
    }
    
    .btn-delete,
    .btn-cancel {
        width: 100%;
    }
}
</style>

<div class="delete-container">
    
    <!-- Warning Header -->
    <div class="warning-header">
        <i class="bi bi-exclamation-triangle"></i>
        <h1>Delete Post</h1>
        <p>This action cannot be undone. Please review carefully before proceeding.</p>
    </div>
    
    <!-- Main Card -->
    <div class="delete-card">
        
        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Warning Message -->
        <div class="alert-box">
            <h5><i class="bi bi-exclamation-circle me-2"></i>Important Warning</h5>
            <p>This is a permanent action. Once deleted, this post and all its associated data cannot be recovered.</p>
        </div>
        
        <!-- Post Information -->
        <div class="info-section">
            <h5>Post Details</h5>
            
            <div class="info-row">
                <span class="info-label">Title</span>
                <span class="info-value"><?php echo htmlspecialchars($post['title']); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Author</span>
                <span class="info-value"><?php echo htmlspecialchars($post['author_name']); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Created</span>
                <span class="info-value"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value">
                    <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($post['status']); ?>
                    </span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Engagement</span>
                <span class="info-value">
                    <span class="stats-badge"><i class="bi bi-eye"></i> <?php echo number_format($post['views']); ?></span>
                    <span class="stats-badge"><i class="bi bi-chat"></i> <?php echo number_format($post['comment_count']); ?></span>
                    <span class="stats-badge"><i class="bi bi-heart"></i> <?php echo number_format($post['reaction_count']); ?></span>
                </span>
            </div>
        </div>
        
        <!-- What Will Be Deleted -->
        <div class="consequence-list">
            <h5>What Will Be Deleted</h5>
            
            <div class="consequence-item">
                <strong><i class="bi bi-file-text me-2"></i>Post Content</strong>
                <small>The complete post including all text, metadata, and settings</small>
            </div>
            
            <?php if ($post['comment_count'] > 0): ?>
            <div class="consequence-item">
                <strong><i class="bi bi-chat-dots me-2"></i><?php echo number_format($post['comment_count']); ?> Comment<?php echo $post['comment_count'] != 1 ? 's' : ''; ?></strong>
                <small>All user comments and discussions on this post</small>
            </div>
            <?php endif; ?>
            
            <?php if ($post['reaction_count'] > 0): ?>
            <div class="consequence-item">
                <strong><i class="bi bi-heart me-2"></i><?php echo number_format($post['reaction_count']); ?> Reaction<?php echo $post['reaction_count'] != 1 ? 's' : ''; ?></strong>
                <small>All likes and reactions from readers</small>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($post['featured_image'])): ?>
            <div class="consequence-item">
                <strong><i class="bi bi-image me-2"></i>Featured Image</strong>
                <small>The uploaded image file will be deleted from the server</small>
            </div>
            <?php endif; ?>
            
            <div class="consequence-item">
                <strong><i class="bi bi-graph-up me-2"></i>Analytics Data</strong>
                <small>All view counts and engagement metrics</small>
            </div>
        </div>
        
        <!-- Confirmation Form -->
        <form method="POST" action="" id="deleteForm">
            
            <?php echo csrfField(); ?>
            
            <div class="confirm-box">
                <div class="form-check">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="confirm_delete" 
                           name="confirm_delete" 
                           value="yes" 
                           required>
                    <label class="form-check-label" for="confirm_delete">
                        I understand this action is permanent and cannot be undone
                    </label>
                </div>
            </div>
            
            <div class="button-group">
                <a href="<?php echo url('posts/view.php?id=' . $postId); ?>" class="btn btn-cancel">
                    <i class="bi bi-arrow-left me-2"></i>Cancel
                </a>
                
                <button type="submit" class="btn btn-delete" id="deleteButton" disabled>
                    <i class="bi bi-trash me-2"></i>Delete Post
                </button>
            </div>
            
        </form>
        
        <!-- Security Notice -->
        <div class="security-note">
            <i class="bi bi-shield-check me-2"></i>
            <small><strong>Security:</strong> This action is logged and requires confirmation to prevent accidental deletion.</small>
        </div>
        
    </div>
</div>

<script>
// Enable delete button when checkbox is checked
document.getElementById('confirm_delete').addEventListener('change', function() {
    document.getElementById('deleteButton').disabled = !this.checked;
});

// Show loading state on form submit
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('deleteButton');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
});
</script>

<?php
require_once '../includes/footer.php';
?>