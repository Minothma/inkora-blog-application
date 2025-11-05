<?php
/**
 * User Profile View Page
 */

// Set page title
$pageTitle = "User Profile";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Get user ID from URL (if not provided, show current user's profile)
$profileUserId = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? getCurrentUserId() : 0);

if ($profileUserId <= 0) {
    setFlashMessage("Please log in to view your profile.", 'warning');
    header('Location: ' . url('auth/login.php'));
    exit();
}

try {
    // Get user information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$profileUserId]);
    $profileUser = $stmt->fetch();
    
    // Check if user exists
    if (!$profileUser) {
        setFlashMessage("User not found.", 'danger');
        header('Location: ' . url('index.php'));
        exit();
    }
    
    // Update page title
    $pageTitle = $profileUser['username'] . "'s Profile";
    
    // Get user's statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_posts,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_posts,
            SUM(views) as total_views,
            (SELECT COUNT(*) FROM comments c WHERE c.user_id = ?) as total_comments_made,
            (SELECT COUNT(*) FROM comments c JOIN blog_posts bp ON c.blog_post_id = bp.id WHERE bp.user_id = ?) as total_comments_received,
            (SELECT COUNT(*) FROM reactions r JOIN blog_posts bp ON r.blog_post_id = bp.id WHERE bp.user_id = ?) as total_reactions
        FROM blog_posts
        WHERE user_id = ?
    ");
    $statsStmt->execute([$profileUserId, $profileUserId, $profileUserId, $profileUserId]);
    $stats = $statsStmt->fetch();
    
    // Get user's published posts (or all posts if viewing own profile)
    if (isLoggedIn() && getCurrentUserId() == $profileUserId) {
        // Own profile - show all posts
        $postsStmt = $conn->prepare("
            SELECT 
                bp.*,
                (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
                (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
            FROM blog_posts bp
            WHERE bp.user_id = ?
            ORDER BY bp.created_at DESC
            LIMIT 10
        ");
        $postsStmt->execute([$profileUserId]);
    } else {
        // Other user's profile - show only published posts
        $postsStmt = $conn->prepare("
            SELECT 
                bp.*,
                (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
                (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
            FROM blog_posts bp
            WHERE bp.user_id = ? AND bp.status = 'published'
            ORDER BY bp.created_at DESC
            LIMIT 10
        ");
        $postsStmt->execute([$profileUserId]);
    }
    $posts = $postsStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Profile view error: " . $e->getMessage());
    setFlashMessage("An error occurred. Please try again.", 'danger');
    header('Location: ' . url('index.php'));
    exit();
}

// Helper function
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) return 'Just now';
    elseif ($difference < 3600) return floor($difference / 60) . ' min ago';
    elseif ($difference < 86400) return floor($difference / 3600) . ' hour' . (floor($difference / 3600) > 1 ? 's' : '') . ' ago';
    elseif ($difference < 604800) return floor($difference / 86400) . ' day' . (floor($difference / 86400) > 1 ? 's' : '') . ' ago';
    else return date('M j, Y', $timestamp);
}

// Include header
require_once '../includes/header.php';
?>

<!-- User Profile Page -->
<div class="container my-5">
    
    <!-- Profile Header -->
    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="row align-items-center">
                        
                        <!-- Profile Picture -->
                        <div class="col-md-3 text-center mb-4 mb-md-0">
                            <img src="<?php echo upload('avatar', $profileUser['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($profileUser['username']); ?>"
                                 class="rounded-circle img-fluid shadow"
                                 style="width: 180px; height: 180px; object-fit: cover; border: 5px solid #fff;">
                        </div>
                        
                        <!-- Profile Info -->
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h1 class="display-5 fw-bold mb-2">
                                        <?php echo htmlspecialchars($profileUser['username']); ?>
                                        <?php if ($profileUser['role'] === 'admin'): ?>
                                            <span class="badge bg-danger ms-2">
                                                <i class="bi bi-shield-check"></i> Admin
                                            </span>
                                        <?php endif; ?>
                                    </h1>
                                    <p class="text-muted mb-3">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($profileUser['email']); ?>
                                    </p>
                                </div>
                                
                                <!-- Edit Button (for own profile) -->
                                <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                                    <a href="<?php echo url('profile/edit.php'); ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Edit Profile
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bio -->
                            <?php if (!empty($profileUser['bio'])): ?>
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">About</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Member Since -->
                            <p class="text-muted mb-0">
                                <i class="bi bi-calendar"></i> Member since <?php echo date('F Y', strtotime($profileUser['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-5">
        <div class="col-lg-10 mx-auto">
            <div class="row g-4">
                
                <!-- Total Posts -->
<div class="col-md-3 col-sm-6">
    <div class="card border-0 shadow-sm text-center h-100">
        <div class="card-body">
            <i class="bi bi-file-text text-primary display-4 mb-3"></i>
            <h3 class="fw-bold mb-0"><?php echo number_format($stats['published_posts'] ?? 0); ?></h3>
            <p class="text-muted mb-0">Published Posts</p>
        </div>
    </div>
</div>

<!-- Total Views -->
<div class="col-md-3 col-sm-6">
    <div class="card border-0 shadow-sm text-center h-100">
        <div class="card-body">
            <i class="bi bi-eye text-success display-4 mb-3"></i>
            <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
            <p class="text-muted mb-0">Total Views</p>
        </div>
    </div>
</div>

<!-- Comments Received -->
<div class="col-md-3 col-sm-6">
    <div class="card border-0 shadow-sm text-center h-100">
        <div class="card-body">
            <i class="bi bi-chat-dots text-info display-4 mb-3"></i>
            <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_comments_received'] ?? 0); ?></h3>
            <p class="text-muted mb-0">Comments</p>
        </div>
    </div>
</div>

<!-- Reactions -->
<div class="col-md-3 col-sm-6">
    <div class="card border-0 shadow-sm text-center h-100">
        <div class="card-body">
            <i class="bi bi-heart text-danger display-4 mb-3"></i>
            <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_reactions'] ?? 0); ?></h3>
            <p class="text-muted mb-0">Reactions</p>
        </div>
    </div>
</div>
                
            </div>
        </div>
    </div>
    
    <!-- User's Blog Posts -->
    <div class="row">
        <div class="col-lg-10 mx-auto">
            
            <!-- Section Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">
                    <i class="bi bi-journal-text text-primary"></i> 
                    <?php echo (isLoggedIn() && getCurrentUserId() == $profileUserId) ? 'My' : htmlspecialchars($profileUser['username']) . "'s"; ?> Stories
                </h3>
                <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                    <a href="<?php echo url('posts/my_posts.php'); ?>" class="btn btn-outline-primary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Posts List -->
            <?php if (empty($posts)): ?>
                <div class="alert alert-info text-center p-5">
                    <i class="bi bi-inbox display-1 text-info mb-3"></i>
                    <h4>No Posts Yet</h4>
                    <p class="mb-0">
                        <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                            You haven't published any posts yet. Start sharing your stories!
                        <?php else: ?>
                            This user hasn't published any posts yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <!-- Blog Post Card -->
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm blog-post-card">
                                
                                <!-- Featured Image -->
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-gradient" 
                                         style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image text-white" style="font-size: 3rem; opacity: 0.5;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <!-- Status Badge (if own profile) -->
                                    <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                                        <?php if ($post['status'] === 'draft'): ?>
                                            <span class="badge bg-warning text-dark mb-2">
                                                <i class="bi bi-pencil"></i> Draft
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success mb-2">
                                                <i class="bi bi-check-circle"></i> Published
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Post Title -->
                                    <h5 class="card-title">
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <!-- Post Date -->
                                    <p class="text-muted small mb-3">
                                        <i class="bi bi-calendar"></i> <?php echo timeAgo($post['created_at']); ?>
                                    </p>
                                    
                                    <!-- Stats -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted me-3">
                                                <i class="bi bi-eye"></i> <?php echo number_format($post['views']); ?>
                                            </small>
                                            <small class="text-muted me-3">
                                                <i class="bi bi-chat"></i> <?php echo number_format($post['comment_count']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-heart"></i> <?php echo number_format($post['reaction_count']); ?>
                                            </small>
                                        </div>
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Read <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>