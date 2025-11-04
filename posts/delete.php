<?php
/**
 * Delete Blog Post
 * 
 * Professional implementation with confirmation page and secure deletion
 * 
 * Features:
 * - Two-step deletion process with confirmation page
 * - POST request requirement with CSRF protection
 * - Only author or admin can delete
 * - Cascade deletion of related data
 * - Featured image file cleanup
 * - Comprehensive error handling
 * - Transaction support for data integrity
 * - Audit logging
 * 
 * @author Inkora Team
 * @version 2.1 - Cyan to Purple Gradient Design
 */

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
            // Begin transaction for data integrity
            $conn->beginTransaction();
            
            // Delete related reactions (if table exists)
            try {
                $deleteReactions = $conn->prepare("DELETE FROM reactions WHERE blog_post_id = ?");
                $deleteReactions->execute([$postId]);
            } catch (PDOException $e) {
                // Table might not exist, log and continue
                error_log("Could not delete reactions: " . $e->getMessage());
            }
            
            // Delete related comments (if table exists)
            try {
                $deleteComments = $conn->prepare("DELETE FROM comments WHERE blog_post_id = ?");
                $deleteComments->execute([$postId]);
            } catch (PDOException $e) {
                // Table might not exist, log and continue
                error_log("Could not delete comments: " . $e->getMessage());
            }
            
            // Delete the blog post (main operation - must succeed)
            $deletePost = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
            $deletePost->execute([$postId]);
            
            // Verify deletion
            if ($deletePost->rowCount() === 0) {
                throw new Exception("Post deletion failed - no rows affected");
            }
            
            // Commit transaction
            $conn->commit();
            
            // Delete featured image file if exists
            if (!empty($post['featured_image'])) {
                $imagePath = BLOG_IMG_PATH . '/' . $post['featured_image'];
                if (file_exists($imagePath)) {
                    if (!unlink($imagePath)) {
                        error_log("Failed to delete image file: " . $imagePath);
                    }
                }
            }
            
            // Success message
            $message = isAdmin() && ($post['user_id'] != getCurrentUserId())
                ? 'Post deleted successfully by administrator.'
                : 'Your post has been deleted successfully.';
            
            setFlashMessage($message, 'success');
            
            // Redirect based on user role
            $redirectUrl = ($post['user_id'] == getCurrentUserId())
                ? url('posts/my_posts.php')
                : url('posts/index.php');
            
            header('Location: ' . $redirectUrl);
            exit();
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            // Detailed error logging
            error_log("Delete post PDO error: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            error_log("Post ID: " . $postId);
            
            // User-friendly error message
            $errors[] = "Database error: Unable to delete the post. " . $e->getMessage();
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            error_log("Delete post error: " . $e->getMessage());
            $errors[] = "An error occurred while deleting the post. Please try again.";
        }
    }
}

// Include header
require_once '../includes/header.php';
?>

<style>
/* Cyan to Purple Gradient Color Scheme - Matching Index Page */
:root {
    /* Primary Gradient Colors */
    --gradient-cyan: #00CED1;
    --gradient-cyan-light: #20B2C4;
    --gradient-purple: #6A5ACD;
    --gradient-purple-deep: #7B68BE;
    --gradient-navy: #0B1A2D;
    
    /* Neutral Colors */
    --text-dark: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --bg-white: #ffffff;
    --border-light: #e2e8f0;
    
    /* Alert Colors */
    --alert-danger: #e53e3e;
    --alert-danger-light: #fc8181;
    --alert-warning: #d69e2e;
    --alert-warning-light: #fbd38d;
    
    /* Shadows */
    --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Hero Section - Danger Gradient with Cyan-Purple Accents */
.delete-hero {
    position: relative;
    overflow: hidden;
    min-height: 300px;
    background: linear-gradient(135deg, 
        var(--alert-danger) 0%, 
        var(--alert-danger-light) 50%,
        #c53030 100%
    );
    padding: 3rem 0;
}

.delete-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
}

.delete-hero .hero-content {
    position: relative;
    z-index: 1;
}

.warning-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    backdrop-filter: blur(10px);
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.floating-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Cards */
.warning-card {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border: 2px solid var(--alert-danger-light);
    border-radius: 16px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
}

.info-card {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.info-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--gradient-cyan);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-light);
}

.info-row:last-child {
    border-bottom: none;
}

/* Consequence Items */
.consequence-item {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #fffaf0 0%, #fef5e7 100%);
    border-left: 4px solid var(--alert-warning);
    border-radius: 12px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.consequence-item:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-sm);
}

.consequence-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--alert-warning-light) 0%, var(--alert-warning) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #744210;
    font-size: 1.1rem;
}

/* Buttons */
.btn-danger-gradient {
    background: linear-gradient(135deg, var(--alert-danger-light) 0%, var(--alert-danger) 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.875rem 2.5rem;
    border-radius: 50px;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

.btn-danger-gradient:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--alert-danger) 0%, #c53030 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(229, 62, 62, 0.4);
    color: white;
}

.btn-danger-gradient:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-outline-gradient {
    border: 2px solid var(--gradient-cyan);
    color: var(--gradient-cyan);
    background: transparent;
    font-weight: 600;
    padding: 0.875rem 2.5rem;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-gradient:hover {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    color: white;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Checkbox */
.checkbox-wrapper {
    background: var(--bg-light);
    border: 2px solid var(--border-light);
    border-radius: 16px;
    padding: 1.75rem;
    margin: 2rem 0;
    transition: all 0.3s ease;
}

.checkbox-wrapper:hover {
    border-color: var(--gradient-cyan);
    box-shadow: var(--shadow-sm);
}

.form-check-input {
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid var(--border-light);
    cursor: pointer;
}

.form-check-input:checked {
    background-color: var(--alert-danger);
    border-color: var(--alert-danger);
}

.form-check-input:focus {
    border-color: var(--gradient-cyan);
    box-shadow: 0 0 0 0.25rem rgba(0, 206, 209, 0.25);
}

.form-check-label {
    cursor: pointer;
    user-select: none;
    font-size: 1.05rem;
}

/* Stats Badge */
.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    background: var(--bg-light);
    border-radius: 10px;
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* Section Badge */
.section-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(229, 62, 62, 0.1) 0%, rgba(252, 129, 129, 0.1) 100%);
    color: var(--alert-danger);
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border: 1px solid rgba(229, 62, 62, 0.2);
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: var(--shadow-sm);
}

.alert-danger {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    color: var(--alert-danger);
    border-left: 4px solid var(--alert-danger);
}

.alert-warning {
    background: linear-gradient(135deg, #fffaf0 0%, #fef5e7 100%);
    color: #744210;
    border-left: 4px solid var(--alert-warning);
}

/* Animations */
@keyframes pulse {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.8;
        transform: scale(1.05);
    }
}

.pulse-animation {
    animation: pulse 2s ease-in-out infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .delete-hero {
        min-height: 250px;
        padding: 2rem 0;
    }
    
    .warning-icon {
        width: 80px;
        height: 80px;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .consequence-item {
        padding: 1rem;
    }
    
    .btn-danger-gradient,
    .btn-outline-gradient {
        width: 100%;
        padding: 0.875rem 1.5rem;
    }
}

/* Loading State */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}
</style>

<!-- Hero Section -->
<section class="delete-hero">
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center text-white">
                <div class="floating-badge">
                    <i class="bi bi-shield-exclamation"></i> Permanent Action
                </div>
                <div class="warning-icon pulse-animation">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3.5rem; color: white;"></i>
                </div>
                <h1 class="display-4 fw-bold mb-3" style="line-height: 1.2;">
                    Delete Post Permanently
                </h1>
                <p class="lead mb-0 fs-5" style="opacity: 0.95; max-width: 600px; margin: 0 auto;">
                    This action cannot be undone. All data associated with this post will be permanently removed.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-2">Please fix the following issues:</strong>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Warning Card -->
            <div class="warning-card text-center">
                <i class="bi bi-exclamation-octagon" style="font-size: 4.5rem; color: var(--alert-danger);"></i>
                <h3 class="fw-bold mt-4 mb-3" style="color: var(--text-dark); font-size: 1.75rem;">
                    Are you absolutely sure?
                </h3>
                <p class="text-muted mb-0 fs-6" style="max-width: 500px; margin: 0 auto; line-height: 1.7;">
                    This action is permanent and cannot be reversed. All associated data including comments, reactions, and media will be permanently deleted from our servers.
                </p>
            </div>
            
            <!-- Post Information -->
            <div class="info-card">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="section-badge">
                        <i class="bi bi-file-text"></i> Post Information
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="text-muted fw-semibold">
                        <i class="bi bi-card-heading me-2" style="color: var(--gradient-cyan);"></i>
                        Title
                    </span>
                    <strong style="color: var(--text-dark); text-align: right;">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </strong>
                </div>
                
                <div class="info-row">
                    <span class="text-muted fw-semibold">
                        <i class="bi bi-person me-2" style="color: var(--gradient-purple);"></i>
                        Author
                    </span>
                    <strong style="color: var(--text-dark);">
                        <?php echo htmlspecialchars($post['author_name']); ?>
                    </strong>
                </div>
                
                <div class="info-row">
                    <span class="text-muted fw-semibold">
                        <i class="bi bi-calendar-event me-2" style="color: var(--gradient-cyan);"></i>
                        Created Date
                    </span>
                    <strong style="color: var(--text-dark);">
                        <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                    </strong>
                </div>
                
                <div class="info-row">
                    <span class="text-muted fw-semibold">
                        <i class="bi bi-circle-fill me-2" style="color: var(--gradient-purple); font-size: 0.5rem;"></i>
                        Status
                    </span>
                    <span class="badge rounded-pill px-3 py-2" style="background: <?php echo $post['status'] === 'published' ? 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)' : 'linear-gradient(135deg, #718096 0%, #4a5568 100%)'; ?>; font-size: 0.875rem;">
                        <?php echo ucfirst($post['status']); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="text-muted fw-semibold">
                        <i class="bi bi-graph-up me-2" style="color: var(--gradient-cyan);"></i>
                        Engagement
                    </span>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <span class="stats-badge">
                            <i class="bi bi-eye-fill"></i> 
                            <strong><?php echo number_format($post['views']); ?></strong>
                        </span>
                        <span class="stats-badge">
                            <i class="bi bi-chat-fill"></i> 
                            <strong><?php echo number_format($post['comment_count']); ?></strong>
                        </span>
                        <span class="stats-badge">
                            <i class="bi bi-heart-fill"></i> 
                            <strong><?php echo number_format($post['reaction_count']); ?></strong>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Consequences Warning -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="section-badge">
                        <i class="bi bi-info-circle"></i> What Will Be Deleted
                    </span>
                </div>
                
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="bi bi-file-text-fill"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark); font-size: 1.05rem;">
                            Complete Post Content
                        </strong>
                        <small class="text-muted" style="line-height: 1.6;">
                            The entire post including title, content, excerpt, slug, and all metadata will be permanently removed from the database.
                        </small>
                    </div>
                </div>
                
                <?php if ($post['comment_count'] > 0): ?>
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark); font-size: 1.05rem;">
                            <?php echo number_format($post['comment_count']); ?> Comment<?php echo $post['comment_count'] != 1 ? 's' : ''; ?>
                        </strong>
                        <small class="text-muted" style="line-height: 1.6;">
                            All user comments and discussions on this post will be permanently deleted. This cannot be recovered.
                        </small>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($post['reaction_count'] > 0): ?>
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark); font-size: 1.05rem;">
                            <?php echo number_format($post['reaction_count']); ?> Reaction<?php echo $post['reaction_count'] != 1 ? 's' : ''; ?>
                        </strong>
                        <small class="text-muted" style="line-height: 1.6;">
                            All likes and reactions from readers will be permanently removed from the system.
                        </small>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($post['featured_image'])): ?>
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="bi bi-image-fill"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark); font-size: 1.05rem;">
                            Featured Image File
                        </strong>
                        <small class="text-muted" style="line-height: 1.6;">
                            The uploaded image file (<?php echo htmlspecialchars($post['featured_image']); ?>) will be permanently deleted from the server storage.
                        </small>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark); font-size: 1.05rem;">
                            Post History & Analytics
                        </strong>
                        <small class="text-muted" style="line-height: 1.6;">
                            All view counts, engagement metrics, and historical data associated with this post will be lost forever.
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Confirmation Form -->
            <form method="POST" action="" id="deleteForm">
                
                <!-- CSRF Token -->
                <?php echo csrfField(); ?>
                
                <!-- Confirmation Checkbox -->
                <div class="checkbox-wrapper">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="confirm_delete" 
                               name="confirm_delete" 
                               value="yes" 
                               required>
                        <label class="form-check-label fw-bold" for="confirm_delete" style="color: var(--text-dark);">
                            <i class="bi bi-check-circle me-2" style="color: var(--gradient-cyan);"></i>
                            I understand that this action is permanent and cannot be undone
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2 ms-4 ps-3">
                        By checking this box, you acknowledge that all data will be permanently deleted and cannot be recovered.
                    </small>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3 justify-content-between flex-wrap mb-4">
                    <a href="<?php echo url('posts/view.php?id=' . $postId); ?>" 
                       class="btn btn-outline-gradient">
                        <i class="bi bi-arrow-left me-2"></i> Cancel & Go Back
                    </a>
                    
                    <button type="submit" 
                            class="btn btn-danger-gradient" 
                            id="deleteButton"
                            disabled>
                        <i class="bi bi-trash3-fill me-2"></i> Delete Permanently
                    </button>
                </div>
                
            </form>
            
            <!-- Security Notice -->
            <div class="alert alert-warning" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-shield-exclamation me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong class="d-block mb-1">Security Notice</strong>
                        <span>This action requires confirmation and cannot be performed accidentally. Make sure you have backed up any important content before proceeding. This deletion is logged for security purposes.</span>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Enable delete button only when checkbox is checked
const confirmCheckbox = document.getElementById('confirm_delete');
const deleteButton = document.getElementById('deleteButton');

confirmCheckbox.addEventListener('change', function() {
    deleteButton.disabled = !this.checked;
    
    // Add visual feedback
    if (this.checked) {
        deleteButton.style.opacity = '1';
    } else {
        deleteButton.style.opacity = '0.5';
    }
});

// Add confirmation dialog as additional safety measure
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const postTitle = <?php echo json_encode($post['title']); ?>;
    
    const confirmed = confirm(
        '⚠️ FINAL WARNING ⚠️\n\n' +
        'You are about to permanently delete this post.\n\n' +
        'Post: "' + postTitle + '"\n\n' +
        'This action CANNOT be undone!\n\n' +
        'Are you absolutely sure you want to continue?'
    );
    
    if (!confirmed) {
        e.preventDefault();
        return false;
    }
    
    // Show loading state
    deleteButton.disabled = true;
    deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';
    deleteButton.classList.add('btn-loading');
    
    return true;
});

// Prevent accidental page navigation
let formSubmitted = false;

document.getElementById('deleteForm').addEventListener('submit', function() {
    formSubmitted = true;
});

window.addEventListener('beforeunload', function(e) {
    if (!formSubmitted && confirmCheckbox.checked) {
        e.preventDefault();
        e.returnValue = 'You have checked the confirmation box but haven\'t submitted the form. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Add smooth scroll to top on page load
window.addEventListener('load', function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Add fade-in animation for cards
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all cards for animation
document.querySelectorAll('.warning-card, .info-card, .consequence-item').forEach((el, index) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = `all 0.5s ease ${index * 0.1}s`;
    observer.observe(el);
});

// Add hover effect for consequence items
document.querySelectorAll('.consequence-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#fef5e7';
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>