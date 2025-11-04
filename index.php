<?php

/**
 * Home/Landing Page - Inkora Blog Platform
 * 
 * Premium landing page showcasing the power of creative storytelling
 * 
 * Features:
 * - Modern hero section with animated elements
 * - Dynamic statistics dashboard
 * - Featured blog posts with rich cards
 * - Interactive features showcase
 * - Compelling call-to-action sections
 * 
 * @author Inkora Team
 * @version 2.2 - Cyan to Purple Gradient Design
 */

// Set page title
$pageTitle = "Home - Where Creativity Finds Its Voice";

// Include required files
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/session.php';

try {
    // Get latest published blog posts with enhanced data
    $stmt = $conn->prepare("
        SELECT 
            bp.id,
            bp.title,
            bp.slug,
            bp.excerpt,
            bp.content,
            bp.featured_image,
            bp.views,
            bp.created_at,
            bp.updated_at,
            u.id as author_id,
            u.username as author_name,
            u.profile_picture as author_avatar,
            u.bio as author_bio,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
            (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        WHERE bp.status = 'published'
        ORDER BY bp.created_at DESC
        LIMIT 6
    ");
    
    $stmt->execute();
    $latestPosts = $stmt->fetchAll();
    
    // Get comprehensive statistics
    $statsStmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') as total_posts,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT SUM(views) FROM blog_posts WHERE status = 'published') as total_views,
            (SELECT COUNT(*) FROM comments) as total_comments
    ");
    $stats = $statsStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Home page error: " . $e->getMessage());
    $latestPosts = [];
    $stats = [
        'total_posts' => 0,
        'total_users' => 0,
        'total_views' => 0,
        'total_comments' => 0
    ];
}

/**
 * Enhanced Helper Functions
 */
function generateExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
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

function getInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getAvatarColor($name) {
    $colors = [
        'linear-gradient(135deg, #00CED1 0%, #6A5ACD 100%)',
        'linear-gradient(135deg, #20B2C4 0%, #7B68BE 100%)',
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

function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return number_format($number);
}

// Include header
require_once 'includes/header.php';
?>

<style>
/* Cyan to Purple Gradient Color Scheme - 2025 Standards */
:root {
    /* Primary Gradient Colors */
    --gradient-cyan: #00CED1;
    --gradient-cyan-light: #20B2C4;
    --gradient-purple: #6A5ACD;
    --gradient-purple-deep: #7B68BE;
    --gradient-navy: #0B1A2D;
    --gradient-navy-light: #1A1F3A;
    
    /* Accent Colors */
    --accent-warm: #FFE4B5;
    --accent-gold: #FDB94E;
    
    /* Neutral Colors */
    --text-dark: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --bg-white: #ffffff;
    --border-light: #e2e8f0;
    
    /* Shadows */
    --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Hero Section - Cyan to Purple Gradient */
.hero-section {
    position: relative;
    overflow: hidden;
    min-height: 600px;
    background: linear-gradient(180deg, 
        var(--gradient-cyan) 0%, 
        var(--gradient-cyan-light) 25%,
        var(--gradient-purple) 60%, 
        var(--gradient-purple-deep) 80%,
        var(--gradient-navy) 100%
    );
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.stat-card {
    transition: all 0.3s ease;
    cursor: pointer;
    background: var(--bg-white);
    border: 1px solid var(--border-light);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--gradient-cyan);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.blog-post-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-light);
    overflow: hidden;
    background: var(--bg-white);
}

.blog-post-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--gradient-cyan);
}

.blog-post-image {
    height: 220px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.blog-post-card:hover .blog-post-image {
    transform: scale(1.05);
}

.blog-post-title {
    transition: color 0.3s ease;
    font-weight: 600;
    line-height: 1.4;
    color: var(--text-dark);
}

.blog-post-title:hover {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feature-card {
    transition: all 0.3s ease;
    border-radius: 16px;
    padding: 2rem;
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--gradient-purple);
}

.feature-card:hover .feature-icon-wrapper {
    transform: scale(1.05);
}

.feature-icon-wrapper {
    width: 70px;
    height: 70px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
    margin-bottom: 1.5rem;
}

.pulse-animation {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.floating-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.35rem 0.65rem;
    background: var(--bg-light);
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--text-muted);
    font-weight: 500;
}

.btn-gradient-primary {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-gradient-primary:hover {
    background: linear-gradient(135deg, var(--gradient-cyan-light) 0%, var(--gradient-purple-deep) 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    color: white;
}

.section-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.1) 0%, rgba(106, 90, 205, 0.1) 100%);
    color: var(--gradient-purple);
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border: 1px solid rgba(106, 90, 205, 0.2);
}

.gradient-text {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<!-- Hero Section - Cyan to Purple Gradient -->
<section class="hero-section py-5" style="color: white;">
    <div class="container hero-content">
        <div class="row align-items-center justify-content-center" style="min-height: 500px;">
            <!-- Main Content - Centered -->
            <div class="col-lg-10 col-xl-9 text-center mb-5">
                <div class="floating-badge d-inline-block">
                    <i class="bi bi-stars"></i> Where Creativity Meets Voice
                </div>
                <h1 class="display-2 fw-bold mb-4" style="line-height: 1.2;">
                    Welcome to <br>
                    <span style="color: #FFE4B5; font-size: 1.3em;">Inkora</span>
                </h1>
                <p class="lead mb-5 fs-4 mx-auto" style="opacity: 0.95; line-height: 1.6; max-width: 800px;">
                    Transform your thoughts into powerful stories. Join a vibrant community of writers, thinkers, and storytellers shaping the future of creative expression.
                </p>
                
                <!-- Call-to-Action Buttons -->
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-light btn-lg px-5 py-3 shadow-sm" style="font-weight: 600;">
                            <i class="bi bi-pencil-square me-2"></i> Start Writing
                        </a>
                        <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-outline-light btn-lg px-5 py-3" style="font-weight: 600;">
                            <i class="bi bi-compass me-2"></i> Explore Stories
                        </a>
                    <?php else: ?>
                        <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-light btn-lg px-5 py-3 shadow-sm" style="font-weight: 600;">
                            <i class="bi bi-rocket-takeoff me-2"></i> Get Started Free
                        </a>
                        <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-outline-light btn-lg px-5 py-3" style="font-weight: 600;">
                            <i class="bi bi-compass me-2"></i> Explore Stories
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Trust Indicators - Centered -->
                <div class="d-flex gap-4 justify-content-center flex-wrap" style="opacity: 0.92;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #FFE4B5;"></i>
                        <span class="small">No Credit Card Required</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #FFE4B5;"></i>
                        <span class="small">Free Forever</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color: #FFE4B5;"></i>
                        <span class="small">Join <?php echo formatNumber($stats['total_users']); ?>+ Writers</span>
                    </div>
                </div>
            </div>
            
            <!-- Decorative Icon - Centered Below -->
            <div class="col-12 text-center mt-4">
                <div class="position-relative d-inline-block">
                    <i class="bi bi-feather pulse-animation" style="font-size: 8rem; opacity: 0.25;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section - Clean White Design -->
<section class="stats-section py-5" style="background: var(--bg-white); margin-top: -50px; position: relative; z-index: 10;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card rounded-4 shadow-sm p-4 text-center">
                    <div class="stat-icon mx-auto" style="background: linear-gradient(135deg, rgba(0, 206, 209, 0.1) 0%, rgba(106, 90, 205, 0.1) 100%); color: var(--gradient-cyan);">
                        <i class="bi bi-file-text" style="font-size: 1.75rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-1 gradient-text" style="font-size: 2.5rem;"><?php echo formatNumber($stats['total_posts']); ?>+</h2>
                    <p class="text-muted mb-0 fw-semibold">Stories Published</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card rounded-4 shadow-sm p-4 text-center">
                    <div class="stat-icon mx-auto" style="background: #f0fff4; color: #38a169;">
                        <i class="bi bi-people" style="font-size: 1.75rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-1" style="font-size: 2.5rem; color: var(--text-dark);"><?php echo formatNumber($stats['total_users']); ?>+</h2>
                    <p class="text-muted mb-0 fw-semibold">Active Writers</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card rounded-4 shadow-sm p-4 text-center">
                    <div class="stat-icon mx-auto" style="background: linear-gradient(135deg, rgba(0, 206, 209, 0.1) 0%, rgba(106, 90, 205, 0.1) 100%); color: var(--gradient-purple);">
                        <i class="bi bi-eye" style="font-size: 1.75rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-1 gradient-text" style="font-size: 2.5rem;"><?php echo formatNumber($stats['total_views']); ?>+</h2>
                    <p class="text-muted mb-0 fw-semibold">Total Views</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card rounded-4 shadow-sm p-4 text-center">
                    <div class="stat-icon mx-auto" style="background: #fff5f5; color: #e53e3e;">
                        <i class="bi bi-chat-dots" style="font-size: 1.75rem;"></i>
                    </div>
                    <h2 class="fw-bold mb-1" style="font-size: 2.5rem; color: var(--text-dark);"><?php echo formatNumber($stats['total_comments']); ?>+</h2>
                    <p class="text-muted mb-0 fw-semibold">Conversations</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Stories Section - Clean White Cards -->
<section class="latest-posts-section py-5" style="background: var(--bg-light);">
    <div class="container">
        
        <!-- Section Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <span class="section-badge">
                    <i class="bi bi-stars"></i> Featured Content
                </span>
                <h2 class="display-4 fw-bold mb-3" style="color: var(--text-dark);">
                    Latest Stories
                </h2>
                <p class="lead text-muted fs-5">Discover inspiring narratives from our creative community</p>
            </div>
        </div>
        
        <!-- Posts Grid -->
        <?php if (empty($latestPosts)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="text-center p-5 rounded-4 bg-white border">
                        <div class="mb-4">
                            <i class="bi bi-journal-text gradient-text" style="font-size: 5rem; opacity: 0.5;"></i>
                        </div>
                        <h3 class="fw-bold mb-3" style="color: var(--text-dark);">No Stories Yet</h3>
                        <p class="text-muted mb-4 fs-5">Be the pioneer! Share the first story on Inkora and inspire others.</p>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-gradient-primary btn-lg px-5 rounded-pill">
                                <i class="bi bi-plus-circle me-2"></i> Create First Story
                            </a>
                        <?php else: ?>
                            <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-gradient-primary btn-lg px-5 rounded-pill">
                                <i class="bi bi-rocket-takeoff me-2"></i> Join Inkora
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($latestPosts as $post): 
                    $readTime = ceil(str_word_count(strip_tags($post['content'])) / 200);
                ?>
                    <!-- Blog Post Card -->
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 blog-post-card rounded-4">
                            
                            <!-- Featured Image -->
                            <div style="overflow: hidden; border-radius: 1rem 1rem 0 0;">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                         class="card-img-top blog-post-image" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="blog-post-image d-flex align-items-center justify-content-center" 
                                         style="background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);">
                                        <i class="bi bi-image-fill text-white" style="font-size: 4rem; opacity: 0.3;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column p-4">
                                <!-- Post Title -->
                                <h5 class="card-title mb-3">
                                    <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                       class="text-decoration-none blog-post-title">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h5>
                                
                                <!-- Post Excerpt -->
                                <p class="card-text text-muted flex-grow-1 mb-4" style="line-height: 1.7; font-size: 0.95rem;">
                                    <?php 
                                    $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : generateExcerpt($post['content']);
                                    echo htmlspecialchars($excerpt); 
                                    ?>
                                </p>
                                
                                <!-- Post Meta -->
                                <div class="mt-auto">
                                    <!-- Author Info -->
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <?php 
                                        $avatarPath = '';
                                        $showImage = false;
                                        
                                        if (!empty($post['author_avatar'])) {
                                            if (file_exists('uploads/avatars/' . $post['author_avatar'])) {
                                                $avatarPath = 'uploads/avatars/' . $post['author_avatar'];
                                                $showImage = true;
                                            } elseif (file_exists('uploads/profile/' . $post['author_avatar'])) {
                                                $avatarPath = 'uploads/profile/' . $post['author_avatar'];
                                                $showImage = true;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="avatar-wrapper me-2">
                                            <?php if ($showImage): ?>
                                                <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                                     alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                                     class="rounded-circle"
                                                     width="40"
                                                     height="40"
                                                     style="object-fit: cover;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="rounded-circle d-none align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px; background: <?php echo getAvatarColor($post['author_name']); ?>; color: white; font-weight: bold; font-size: 14px;">
                                                    <?php echo getInitials($post['author_name']); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px; background: <?php echo getAvatarColor($post['author_name']); ?>; color: white; font-weight: bold; font-size: 14px;">
                                                    <?php echo getInitials($post['author_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold" style="font-size: 0.95rem; color: var(--text-dark);">
                                                <?php echo htmlspecialchars($post['author_name']); ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
                                                <span><?php echo timeAgo($post['created_at']); ?></span>
                                                <span>•</span>
                                                <span><?php echo $readTime; ?> min read</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats & CTA -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <div class="stats-badge">
                                                <i class="bi bi-eye"></i>
                                                <span><?php echo formatNumber($post['views']); ?></span>
                                            </div>
                                            <div class="stats-badge">
                                                <i class="bi bi-chat"></i>
                                                <span><?php echo formatNumber($post['comment_count']); ?></span>
                                            </div>
                                            <div class="stats-badge">
                                                <i class="bi bi-heart"></i>
                                                <span><?php echo formatNumber($post['reaction_count']); ?></span>
                                            </div>
                                        </div>
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="btn btn-sm btn-gradient-primary rounded-pill px-3">
                                            Read <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- View All Button -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-gradient-primary btn-lg px-5 rounded-pill">
                        <i class="bi bi-grid-3x3-gap me-2"></i> View All Stories
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<!-- Features Section - Gradient Accents -->
<section class="features-section py-5" style="background: var(--bg-white);">
    <div class="container">
        
        <!-- Section Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <span class="section-badge">
                    <i class="bi bi-lightning-charge"></i> Platform Features
                </span>
                <h2 class="display-4 fw-bold mb-3" style="color: var(--text-dark);">Why Choose Inkora?</h2>
                <p class="lead text-muted fs-5">Everything you need to craft and share amazing stories</p>
            </div>
        </div>
        
        <!-- Features Grid -->
        <div class="row g-4">
            
            <!-- Feature 1 -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center">
                    <div class="feature-icon-wrapper mx-auto" style="background: linear-gradient(135deg, rgba(0, 206, 209, 0.15) 0%, rgba(106, 90, 205, 0.15) 100%); color: var(--gradient-cyan);">
                        <i class="bi bi-pencil-square" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: var(--text-dark);">Intuitive Editor</h4>
                    <p class="text-muted" style="line-height: 1.7;">
                        Powerful rich-text editor with formatting tools. Write, format, and publish beautiful stories in minutes.
                    </p>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center">
                    <div class="feature-icon-wrapper mx-auto" style="background: #f0fff4; color: #38a169;">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: var(--text-dark);">Vibrant Community</h4>
                    <p class="text-muted" style="line-height: 1.7;">
                        Connect with passionate writers worldwide. Share ideas, get feedback, and grow together as creators.
                    </p>
                </div>
            </div>
            
            <!-- Feature 3 -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center">
                    <div class="feature-icon-wrapper mx-auto" style="background: #fff5f5; color: #e53e3e;">
                        <i class="bi bi-chat-heart" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: var(--text-dark);">Real Engagement</h4>
                    <p class="text-muted" style="line-height: 1.7;">
                        Get meaningful feedback through comments and reactions. Build your audience and watch your influence grow.
                    </p>
                </div>
            </div>
            
            <!-- Feature 4 -->
            <div class="col-md-6 col-lg-3">
                <div class="feature-card text-center">
                    <div class="feature-icon-wrapper mx-auto" style="background: linear-gradient(135deg, rgba(0, 206, 209, 0.15) 0%, rgba(106, 90, 205, 0.15) 100%); color: var(--gradient-purple);">
                        <i class="bi bi-shield-check" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: var(--text-dark);">Secure & Private</h4>
                    <p class="text-muted" style="line-height: 1.7;">
                        Your data and content are protected with enterprise-grade security. Write with complete peace of mind.
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Testimonial/Social Proof Section -->
<section class="testimonial-section py-5" style="background: var(--bg-light);">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <span class="section-badge">
                    <i class="bi bi-chat-quote"></i> What Writers Say
                </span>
                <h2 class="display-4 fw-bold mb-3" style="color: var(--text-dark);">Loved by Writers</h2>
                <p class="lead text-muted fs-5">Join thousands who've found their creative voice</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="mb-3">
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                    </div>
                    <p class="text-muted mb-4" style="line-height: 1.7;">
                        "Inkora has transformed how I share my stories. The community is incredibly supportive and the platform is so easy to use!"
                    </p>
                    <div class="d-flex align-items-center mt-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%); color: white; font-weight: bold;">
                            SJ
                        </div>
                        <div>
                            <div class="fw-bold" style="color: var(--text-dark);">Sarah Johnson</div>
                            <small class="text-muted">Travel Writer</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="mb-3">
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                    </div>
                    <p class="text-muted mb-4" style="line-height: 1.7;">
                        "Finally, a platform that understands writers! The editor is powerful yet simple, and I love the engagement from readers."
                    </p>
                    <div class="d-flex align-items-center mt-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; font-weight: bold;">
                            MP
                        </div>
                        <div>
                            <div class="fw-bold" style="color: var(--text-dark);">Michael Park</div>
                            <small class="text-muted">Tech Blogger</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="mb-3">
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                        <i class="bi bi-star-fill" style="color: #FDB94E;"></i>
                    </div>
                    <p class="text-muted mb-4" style="line-height: 1.7;">
                        "Inkora gave me the confidence to share my creative writing. The community feedback has been invaluable to my growth."
                    </p>
                    <div class="d-flex align-items-center mt-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; font-weight: bold;">
                            ER
                        </div>
                        <div>
                            <div class="fw-bold" style="color: var(--text-dark);">Emma Rodriguez</div>
                            <small class="text-muted">Fiction Writer</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section - Cyan to Purple Gradient -->
<?php if (!isLoggedIn()): ?>
<section class="cta-section py-5 position-relative overflow-hidden" style="background: linear-gradient(180deg, var(--gradient-cyan) 0%, var(--gradient-cyan-light) 25%, var(--gradient-purple) 60%, var(--gradient-purple-deep) 80%, var(--gradient-navy) 100%); color: white;">
    <!-- Background Pattern -->
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1;">
        <div style="background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="2" fill="white"/></svg>'); background-size: 60px 60px; width: 100%; height: 100%;"></div>
    </div>
    
    <div class="container position-relative" style="z-index: 1;">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center py-5">
                <div class="floating-badge mb-4">
                    <i class="bi bi-megaphone"></i> Join Our Community Today
                </div>
                <h2 class="display-3 fw-bold mb-4" style="line-height: 1.2;">
                    Ready to Share Your Voice?
                </h2>
                <p class="lead mb-5 fs-4" style="opacity: 0.95; line-height: 1.7;">
                    Join <?php echo formatNumber($stats['total_users']); ?>+ writers on Inkora. Your stories deserve to be heard. Start writing today—completely free, forever.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-5">
                    <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-light btn-lg px-5 py-3 shadow" style="font-weight: 600;">
                        <i class="bi bi-rocket-takeoff me-2"></i> Start Writing Free
                    </a>
                    <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-light btn-lg px-5 py-3" style="font-weight: 600;">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                    </a>
                </div>
                
                <!-- Trust Badges -->
                <div class="d-flex gap-4 justify-content-center flex-wrap" style="opacity: 0.92;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check" style="color: #FFE4B5;"></i>
                        <span class="small">100% Secure</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock" style="color: #FFE4B5;"></i>
                        <span class="small">Setup in 30 Seconds</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-infinity" style="color: #FFE4B5;"></i>
                        <span class="small">Free Forever</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle" style="color: #FFE4B5;"></i>
                        <span class="small">No Credit Card</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<!-- Logged In CTA -->
<section class="cta-section py-5" style="background: linear-gradient(180deg, var(--gradient-cyan) 0%, var(--gradient-cyan-light) 25%, var(--gradient-purple) 60%, var(--gradient-purple-deep) 80%, var(--gradient-navy) 100%); color: white;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center py-5">
                <h2 class="display-4 fw-bold mb-4">Your Next Story Awaits</h2>
                <p class="lead mb-4 fs-5">
                    Keep the momentum going. Share your thoughts, experiences, and creativity with the world.
                </p>
                <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-light btn-lg px-5 py-3 shadow" style="font-weight: 600;">
                    <i class="bi bi-pencil-square me-2"></i> Write New Story
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Add animation on scroll
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

document.querySelectorAll('.blog-post-card, .feature-card, .stat-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'all 0.6s ease-out';
    observer.observe(el);
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>