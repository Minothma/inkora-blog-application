<?php

// Set page title
$pageTitle = "All Blogs";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Calculate offset for SQL query
$offset = ($page - 1) * POSTS_PER_PAGE;

try {
    // Get total number of published posts
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'");
    $totalPosts = $countStmt->fetch()['total'];
    
    // Calculate total pages
    $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
    
    // Get blog posts with author information
    $stmt = $conn->prepare("
        SELECT 
            bp.id,
            bp.title,
            bp.slug,
            bp.content,
            bp.excerpt,
            bp.featured_image,
            bp.views,
            bp.created_at,
            bp.updated_at,
            u.id as author_id,
            u.username as author_name,
            u.profile_picture as author_avatar,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
            (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        WHERE bp.status = 'published'
        ORDER BY bp.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([POSTS_PER_PAGE, $offset]);
    $posts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Blog listing error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
}

/**
 * Generate excerpt from content if not provided
 * 
 * @param string $content Full content
 * @param int $length Maximum length
 * @return string Excerpt
 */
function generateExcerpt($content, $length = 150) {
    // Remove HTML tags
    $text = strip_tags($content);
    
    // Trim to length
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        // Cut at last space to avoid cutting words
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    
    return $text;
}

/**
 * Format time ago
 * 
 * @param string $datetime Database datetime
 * @return string Formatted time
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Get initials from name for avatar fallback
 */
function getInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Get avatar color gradient
 */
function getAvatarColor($name) {
    $colors = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)'
    ];
    $index = ord(strtolower($name[0])) % count($colors);
    return $colors[$index];
}

/**
 * Generate random gradient for featured image placeholder
 * 
 * @param int $seed Seed for consistent gradient per post
 * @return string CSS gradient
 */
function generateGradient($seed) {
    $gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
        'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
        'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)'
    ];
    return $gradients[$seed % count($gradients)];
}

// Include header
require_once '../includes/header.php';
?>

<style>
/* Enhanced Blog Card Styles */
.blog-post-card {
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.blog-post-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
}

.blog-post-image {
    height: 250px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.blog-post-card:hover .blog-post-image {
    transform: scale(1.1);
}

.blog-post-title {
    font-weight: 700;
    transition: color 0.3s ease;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-post-title:hover {
    color: #667eea !important;
}

/* FIXED: Avatar Styles - No More Shaking! */
.avatar-wrapper {
    position: relative;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: inline-block;
}

.avatar-wrapper img {
    width: 40px;
    height: 40px;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    object-fit: cover;
}

.avatar-wrapper img:hover {
    transform: scale(1.1);
}

.avatar-fallback {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.avatar-fallback:hover {
    transform: scale(1.1);
}

.stat-icon {
    transition: all 0.3s ease;
}

.stat-icon:hover {
    transform: scale(1.2);
    color: #667eea !important;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    margin-bottom: 50px;
    border-radius: 0 0 50px 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-gradient:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.reading-time {
    color: #6c757d;
    font-size: 0.85rem;
    font-weight: 500;
}

.card-excerpt {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 50px 0;
        border-radius: 0 0 30px 30px;
    }
}
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown">
                    <i class="bi bi-book-half"></i> Discover Amazing Stories
                </h1>
                <p class="lead mb-4 animate__animated animate__fadeInUp">
                    Explore inspiring stories, innovative ideas, and unique experiences from talented writers around the world
                </p>
                
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-light btn-lg px-5 animate__animated animate__fadeInUp">
                        <i class="bi bi-plus-circle me-2"></i> Share Your Story
                    </a>
                <?php else: ?>
                    <div class="animate__animated animate__fadeInUp">
                        <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-light btn-lg px-4 me-3">
                            <i class="bi bi-person-plus me-2"></i> Join Inkora
                        </a>
                        <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container my-5">
    
    <!-- Posts Count & Filter -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0">
                <i class="bi bi-journal-text text-primary"></i> 
                <strong><?php echo number_format($totalPosts ?? 0); ?></strong> Stories Found
            </h4>
            <p class="text-muted small mb-0">
                Showing <?php echo min(($page - 1) * POSTS_PER_PAGE + 1, $totalPosts); ?>-<?php echo min($page * POSTS_PER_PAGE, $totalPosts); ?> of <?php echo $totalPosts; ?>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo url('posts/my_posts.php'); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-file-text"></i> My Posts
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Blog Posts Grid -->
    <?php if (empty($posts)): ?>
        <!-- No Posts Found -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm text-center p-5">
                    <div class="card-body">
                        <div class="mb-4">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                        </div>
                        <h3 class="fw-bold mb-3">No Stories Yet</h3>
                        <p class="text-muted mb-4">
                            Be the first to share your story with the Inkora community!<br>
                            Your voice matters, and we can't wait to hear what you have to say.
                        </p>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-gradient btn-lg px-5">
                                <i class="bi bi-plus-circle me-2"></i> Create Your First Post
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-gradient btn-lg px-5">
                                <i class="bi bi-person-plus me-2"></i> Join Now to Post
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $index => $post): ?>
                <!-- Blog Post Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm blog-post-card position-relative">
                        
                        <!-- Featured Badge (for recent posts) -->
                        <?php 
                        $postAge = time() - strtotime($post['created_at']);
                        if ($postAge < 86400 * 3): // Less than 3 days old
                        ?>
                            <span class="featured-badge">
                                <i class="bi bi-star-fill text-warning"></i> New
                            </span>
                        <?php endif; ?>
                        
                        <!-- Featured Image -->
                        <div style="overflow: hidden; height: 250px;">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                     class="card-img-top blog-post-image" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100\' style=\'background: <?php echo generateGradient($post['id']); ?>\'><i class=\'bi bi-image text-white\' style=\'font-size: 4rem; opacity: 0.5;\'></i></div>'">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100" 
                                     style="background: <?php echo generateGradient($post['id']); ?>">
                                    <i class="bi bi-image text-white" style="font-size: 4rem; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <!-- Post Title -->
                            <h5 class="card-title mb-3">
                                <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                   class="text-decoration-none text-dark blog-post-title">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h5>
                            
                            <!-- Post Excerpt -->
                            <p class="card-text text-muted flex-grow-1 card-excerpt">
                                <?php 
                                $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : generateExcerpt($post['content']);
                                echo htmlspecialchars($excerpt); 
                                ?>
                            </p>
                            
                            <!-- Reading Time -->
                            <div class="reading-time mb-3">
                                <i class="bi bi-clock"></i>
                                <?php 
                                $wordCount = str_word_count(strip_tags($post['content']));
                                $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words/min
                                echo $readingTime . ' min read';
                                ?>
                            </div>
                            
                            <!-- Divider -->
                            <hr class="my-3">
                            
                            <!-- Post Meta -->
                            <div>
                                <!-- Author Info - FIXED VERSION -->
                                <div class="d-flex align-items-center mb-3">
                                    <?php 
                                    // Check if avatar exists in multiple possible locations
                                    $avatarPath = '';
                                    $showImage = false;
                                    
                                    if (!empty($post['author_avatar'])) {
                                        // Check in uploads/avatars/
                                        if (file_exists('../uploads/avatars/' . $post['author_avatar'])) {
                                            $avatarPath = upload('avatar', $post['author_avatar']);
                                            $showImage = true;
                                        }
                                        // Check in uploads/profile/
                                        elseif (file_exists('../uploads/profile/' . $post['author_avatar'])) {
                                            $avatarPath = '../uploads/profile/' . $post['author_avatar'];
                                            $showImage = true;
                                        }
                                    }
                                    ?>
                                    
                                    <a href="<?php echo url('profile/view.php?id=' . $post['author_id']); ?>" 
                                       class="text-decoration-none">
                                        <div class="avatar-wrapper me-2">
                                            <?php if ($showImage): ?>
                                                <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                                     alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                                     class="rounded-circle">
                                            <?php else: ?>
                                                <div class="avatar-fallback rounded-circle" 
                                                     style="background: <?php echo getAvatarColor($post['author_name']); ?>">
                                                    <?php echo getInitials($post['author_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    
                                    <div class="flex-grow-1">
                                        <a href="<?php echo url('profile/view.php?id=' . $post['author_id']); ?>" 
                                           class="text-decoration-none">
                                            <strong class="d-block text-dark">
                                                <?php echo htmlspecialchars($post['author_name']); ?>
                                            </strong>
                                        </a>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo timeAgo($post['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Stats -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-3">
                                        <small class="text-muted stat-icon" title="Views">
                                            <i class="bi bi-eye-fill"></i> 
                                            <strong><?php echo number_format($post['views'] ?? 0); ?></strong>
                                        </small>
                                        <small class="text-muted stat-icon" title="Comments">
                                            <i class="bi bi-chat-fill"></i> 
                                            <strong><?php echo number_format($post['comment_count'] ?? 0); ?></strong>
                                        </small>
                                        <small class="text-muted stat-icon" title="Reactions">
                                            <i class="bi bi-heart-fill text-danger"></i> 
                                            <strong><?php echo number_format($post['reaction_count'] ?? 0); ?></strong>
                                        </small>
                                    </div>
                                    <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Read <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <nav aria-label="Blog posts pagination">
                        <ul class="pagination justify-content-center">
                            
                            <!-- Previous Button -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <i class="bi bi-chevron-double-left"></i> Previous
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            // Show max 5 page numbers
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // First page
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Page numbers -->
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Last page -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Next Button -->
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    Next <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <!-- Page Info -->
                    <p class="text-center text-muted small mt-3">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>