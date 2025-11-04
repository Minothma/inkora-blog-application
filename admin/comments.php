<?php
/**
 * Admin - Moderate Comments
 * 
 * Allows administrators to view and moderate all comments
 * 
 * Features:
 * - View all comments
 * - Delete any comment
 * - See comment context (post and author)
 * - Pagination
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "Moderate Comments";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

// Handle comment deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $commentId = (int)$_GET['id'];
    
    if ($commentId > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            
            setFlashMessage("Comment deleted successfully.", 'success');
        } catch (PDOException $e) {
            error_log("Delete comment error: " . $e->getMessage());
            setFlashMessage("Error deleting comment.", 'danger');
        }
    }
    
    header('Location: ' . url('admin/comments.php'));
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * COMMENTS_PER_PAGE;

try {
    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM comments");
    $totalComments = $countStmt->fetch()['total'];
    $totalPages = ceil($totalComments / COMMENTS_PER_PAGE);
    
    // Get comments with post and user info
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.username as commenter_name,
            u.profile_picture as commenter_avatar,
            bp.id as post_id,
            bp.title as post_title,
            bp.user_id as post_author_id
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN blog_posts bp ON c.blog_post_id = bp.id
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([COMMENTS_PER_PAGE, $offset]);
    $comments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Moderate comments error: " . $e->getMessage());
    $comments = [];
    $totalComments = 0;
    $totalPages = 0;
}

// Include header
require_once '../includes/header.php';
?>

<!-- Moderate Comments Page -->
<div class="container-fluid my-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold">
                        <i class="bi bi-chat-dots text-info"></i> Moderate Comments
                    </h1>
                    <p class="text-muted">Total: <?php echo number_format($totalComments); ?> comments</p>
                </div>
                <a href="<?php echo url('admin/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Comments List -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($comments)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <p class="text-muted mt-3">No comments yet</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <div class="row">
                                
                                <!-- Comment Info -->
                                <div class="col-md-9">
                                    <!-- Commenter -->
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo upload('avatar', $comment['commenter_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($comment['commenter_name']); ?>"
                                             class="rounded-circle me-2"
                                             width="40"
                                             height="40"
                                             style="object-fit: cover;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                            <small class="text-muted d-block">
                                                <?php echo date('F j, Y g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Comment Text -->
                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    
                                    <!-- Post Context -->
                                    <div class="alert alert-light mb-0">
                                        <small class="text-muted">
                                            <i class="bi bi-link-45deg"></i> Comment on: 
                                            <a href="<?php echo url('posts/view.php?id=' . $comment['post_id']); ?>" 
                                               class="text-decoration-none">
                                                "<?php echo htmlspecialchars($comment['post_title']); ?>"
                                            </a>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-md-3 text-end">
                                    <div class="btn-group-vertical w-100">
                                        <a href="<?php echo url('posts/view.php?id=' . $comment['post_id'] . '#commentsList'); ?>" 
                                           class="btn btn-sm btn-outline-primary mb-2">
                                            <i class="bi bi-eye"></i> View in Context
                                        </a>
                                        <a href="?action=delete&id=<?php echo $comment['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this comment?')">
                                            <i class="bi bi-trash"></i> Delete Comment
                                        </a>
                                    </div>
                                    
                                    <!-- Comment Stats -->
                                    <div class="mt-3 small text-muted">
                                        <div>ID: <?php echo $comment['id']; ?></div>
                                        <div>Post ID: <?php echo $comment['post_id']; ?></div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4" aria-label="Comments pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        </div>
    </div>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>