<?php
/**
 * User Profile View Page
 * Theme: Nordic Slate & Deep Indigo
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
    elseif ($difference < 86400) return floor($difference / 3600) . 'h ago';
    elseif ($difference < 604800) return floor($difference / 86400) . 'd ago';
    else return date('M j, Y', $timestamp);
}

// Include header
require_once '../includes/header.php';
?>

<!-- Profile Hero Banner -->
<div class="profile-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-10 mx-auto">
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
                    
                    <!-- Avatar -->
                    <div class="profile-avatar-wrapper flex-shrink-0">
                        <?php if (!empty($profileUser['profile_picture']) && $profileUser['profile_picture'] !== DEFAULT_AVATAR): ?>
                            <img src="<?php echo upload('avatar', $profileUser['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($profileUser['username']); ?>"
                                 class="profile-avatar-img">
                        <?php else: ?>
                            <div class="profile-avatar-initials">
                                <?php echo strtoupper(substr($profileUser['username'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Profile Info -->
                    <div class="text-center text-md-start flex-grow-1">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-start gap-3">
                            <div>
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                                    <h1 class="text-white fw-bold display-6 mb-0"><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                                    <?php if ($profileUser['role'] === 'admin'): ?>
                                        <span class="badge bg-danger" style="font-size: 0.75rem;">
                                            <i class="bi bi-shield-check me-1"></i> Admin
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-slate-300 small mb-2">
                                    <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($profileUser['email']); ?>
                                    <span class="mx-2">&bull;</span>
                                    <i class="bi bi-calendar3 me-1"></i> Member since <?php echo date('M Y', strtotime($profileUser['created_at'])); ?>
                                </p>
                            </div>
                            
                            <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo url('profile/edit.php'); ?>" class="btn btn-outline-light btn-sm px-3 py-2" style="border-radius: 10px;">
                                        <i class="bi bi-pencil me-1"></i> Edit Profile
                                    </a>
                                    <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-indigo-glow btn-sm px-3 py-2">
                                        <i class="bi bi-plus-lg me-1"></i> Write Story
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Bio -->
                        <?php if (!empty($profileUser['bio'])): ?>
                            <div class="profile-bio-box mt-3">
                                <p class="mb-0 text-slate-200 small"><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-5">
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-icon-wrapper bg-indigo-light text-indigo">
                            <i class="bi bi-journal-richtext"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['published_posts'] ?? 0); ?></h4>
                            <span class="text-muted small">Stories</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-icon-wrapper bg-emerald-light text-emerald">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_views'] ?? 0); ?></h4>
                            <span class="text-muted small">Total Views</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-icon-wrapper bg-sky-light text-sky">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_comments_received'] ?? 0); ?></h4>
                            <span class="text-muted small">Comments</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-box">
                        <div class="stat-icon-wrapper bg-rose-light text-rose">
                            <i class="bi bi-heart"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_reactions'] ?? 0); ?></h4>
                            <span class="text-muted small">Reactions</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Published Stories Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-slate-900 mb-0">
                        <?php echo (isLoggedIn() && getCurrentUserId() == $profileUserId) ? 'My Published Stories' : htmlspecialchars($profileUser['username']) . "'s Stories"; ?>
                    </h3>
                    <span class="text-muted small">Recent articles and publications</span>
                </div>
                <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                    <a href="<?php echo url('posts/my_posts.php'); ?>" class="btn btn-outline-primary btn-sm px-3" style="border-radius: 8px;">
                        Manage All Stories <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($posts)): ?>
                <div class="card border-0 shadow-sm text-center p-5" style="border-radius: 16px; border: 1px solid #E2E8F0;">
                    <div class="py-3">
                        <i class="bi bi-journal-x text-slate-300 display-4 mb-3 d-block"></i>
                        <h5 class="fw-bold text-slate-800">No Stories Published Yet</h5>
                        <p class="text-muted small mb-0">
                            <?php if (isLoggedIn() && getCurrentUserId() == $profileUserId): ?>
                                You haven't published any stories yet. Click "Write Story" above to start writing.
                            <?php else: ?>
                                This author hasn't published any public stories yet.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6">
                            <div class="card story-card h-100 border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0;">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         style="height: 190px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-slate-100 text-slate-400" 
                                         style="height: 190px;">
                                        <i class="bi bi-image" style="font-size: 2.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge badge-indigo">
                                                <i class="bi bi-clock me-1"></i><?php echo timeAgo($post['created_at']); ?>
                                            </span>
                                            <?php if ($post['status'] === 'draft'): ?>
                                                <span class="badge bg-warning">Draft</span>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="fw-bold mb-2">
                                            <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" class="text-slate-900 story-link">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h5>
                                        <?php if (!empty($post['excerpt'])): ?>
                                            <p class="text-muted small mb-3">
                                                <?php echo htmlspecialchars(substr($post['excerpt'], 0, 110)) . '...'; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top" style="border-color: #F1F5F9;">
                                        <div class="d-flex gap-3 text-slate-500 small">
                                            <span><i class="bi bi-eye me-1"></i><?php echo number_format($post['views']); ?></span>
                                            <span><i class="bi bi-chat me-1"></i><?php echo number_format($post['comment_count']); ?></span>
                                            <span><i class="bi bi-heart me-1"></i><?php echo number_format($post['reaction_count']); ?></span>
                                        </div>
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" class="text-indigo fw-semibold small">
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

<style>
.profile-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    padding: 60px 0 45px 0;
    margin-bottom: 40px;
    border-radius: 0 0 32px 32px;
    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3);
}
.profile-avatar-wrapper {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    padding: 4px;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
}
.profile-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    background: #FFFFFF;
}
.profile-avatar-initials {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
}
.profile-bio-box {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 12px 16px;
    backdrop-filter: blur(8px);
}
.btn-indigo-glow {
    background: #4F46E5;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    transition: all 0.2s ease;
}
.btn-indigo-glow:hover {
    background: #4338CA;
    color: #ffffff;
    transform: translateY(-1px);
}
.stat-box {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    transition: all 0.2s ease;
}
.stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}
.stat-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.bg-indigo-light { background: #EEF2FF; }
.text-indigo { color: #4F46E5; }
.bg-emerald-light { background: #ECFDF5; }
.text-emerald { color: #10B981; }
.bg-sky-light { background: #F0F9FF; }
.text-sky { color: #0EA5E9; }
.bg-rose-light { background: #FFF1F2; }
.text-rose { color: #F43F5E; }
.story-card {
    transition: all 0.25s ease;
}
.story-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.08) !important;
}
.story-link {
    text-decoration: none;
    transition: color 0.2s ease;
}
.story-link:hover {
    color: #4F46E5 !important;
}
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>