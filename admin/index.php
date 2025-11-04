<?php
/**
 * Admin Dashboard
 * 
 * Main dashboard for administrators
 * 
 * Features:
 * - Overview statistics
 * - Recent activity
 * - Quick actions
 * - Charts and graphs
 * - System information
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "Admin Dashboard";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

try {
    // Get overall statistics
    $statsStmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_admins,
            (SELECT COUNT(*) FROM blog_posts) as total_posts,
            (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') as published_posts,
            (SELECT COUNT(*) FROM blog_posts WHERE status = 'draft') as draft_posts,
            (SELECT COUNT(*) FROM comments) as total_comments,
            (SELECT COUNT(*) FROM reactions) as total_reactions,
            (SELECT SUM(views) FROM blog_posts) as total_views
    ");
    $stats = $statsStmt->fetch();
    
    // Get recent users (last 5)
    $recentUsersStmt = $conn->query("
        SELECT id, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $recentUsersStmt->fetchAll();
    
    // Get recent posts (last 5)
    $recentPostsStmt = $conn->query("
        SELECT bp.id, bp.title, bp.status, bp.views, bp.created_at, u.username as author
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        ORDER BY bp.created_at DESC
        LIMIT 5
    ");
    $recentPosts = $recentPostsStmt->fetchAll();
    
    // Get recent comments (last 5)
    $recentCommentsStmt = $conn->query("
        SELECT c.id, c.comment, c.created_at, u.username as commenter, bp.title as post_title
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN blog_posts bp ON c.blog_post_id = bp.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $recentComments = $recentCommentsStmt->fetchAll();
    
    // Get posts per day (last 7 days)
    $postsPerDayStmt = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM blog_posts
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $postsPerDay = $postsPerDayStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    setFlashMessage("An error occurred loading dashboard data.", 'danger');
    $stats = ['total_users' => 0, 'total_admins' => 0, 'total_posts' => 0, 'published_posts' => 0, 
              'draft_posts' => 0, 'total_comments' => 0, 'total_reactions' => 0, 'total_views' => 0];
    $recentUsers = [];
    $recentPosts = [];
    $recentComments = [];
    $postsPerDay = [];
}

// Include header
require_once '../includes/header.php';
?>

<!-- Admin Dashboard -->
<div class="container-fluid my-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 fw-bold">
                        <i class="bi bi-speedometer2 text-danger"></i> Admin Dashboard
                    </h1>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</p>
                </div>
                <div>
                    <span class="badge bg-danger fs-6">
                        <i class="bi bi-shield-check"></i> Administrator
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        
        <!-- Total Users -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Users</p>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                            <small class="text-success">
                                <i class="bi bi-person-check"></i> 
                                <?php echo $stats['total_admins']; ?> Admins
                            </small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url('admin/users.php'); ?>" class="text-decoration-none small">
                        Manage Users <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Posts -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Posts</p>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_posts']); ?></h2>
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> 
                                <?php echo $stats['published_posts']; ?> Published
                            </small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-file-text text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url('admin/posts.php'); ?>" class="text-decoration-none small">
                        Manage Posts <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Comments -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Comments</p>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_comments']); ?></h2>
                            <small class="text-info">
                                <i class="bi bi-chat-dots"></i> Engagement
                            </small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="bi bi-chat-left-text text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url('admin/comments.php'); ?>" class="text-decoration-none small">
                        Moderate Comments <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total Views -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Views</p>
                            <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_views']); ?></h2>
                            <small class="text-warning">
                                <i class="bi bi-heart"></i> 
                                <?php echo number_format($stats['total_reactions']); ?> Reactions
                            </small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-eye text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url('posts/index.php'); ?>" class="text-decoration-none small">
                        View All Posts <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Two Column Layout -->
    <div class="row g-4">
        
        <!-- Left Column -->
        <div class="col-lg-8">
            
            <!-- Recent Posts -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-text text-primary"></i> Recent Posts
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentPosts)): ?>
                        <p class="text-muted text-center py-4">No posts yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPosts as $post): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['author']); ?></td>
                                            <td>
                                                <?php if ($post['status'] === 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($post['views']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Comments -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-chat-dots text-info"></i> Recent Comments
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentComments)): ?>
                        <p class="text-muted text-center">No comments yet</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentComments as $comment): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">
                                            <strong><?php echo htmlspecialchars($comment['commenter']); ?></strong>
                                            on "<?php echo htmlspecialchars($comment['post_title']); ?>"
                                        </small>
                                        <small class="text-muted">
                                            <?php echo date('M j, H:i', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 mt-1"><?php echo htmlspecialchars(substr($comment['comment'], 0, 100)); ?>...</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning text-warning"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="<?php echo url('admin/posts.php'); ?>" class="btn btn-outline-success">
                            <i class="bi bi-file-text"></i> Manage Posts
                        </a>
                        <a href="<?php echo url('admin/comments.php'); ?>" class="btn btn-outline-info">
                            <i class="bi bi-chat-dots"></i> Moderate Comments
                        </a>
                        <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-plus-circle"></i> Create New Post
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus text-success"></i> Recent Users
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentUsers)): ?>
                        <p class="text-muted text-center">No users yet</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>