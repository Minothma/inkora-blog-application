<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    
<?php
/**
 * View Single Blog Post - Inkora Platform
 * 
 * Professional blog post view with spacious, modern design
 * 
 * @author Inkora Team
 * @version 3.0 - Spacious Professional Design
 */

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Get post ID from URL
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage(MSG_POST_NOT_FOUND, 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

try {
    // Get blog post with author information
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.username as author_name,
            u.profile_picture as author_avatar,
            u.bio as author_bio,
            u.email as author_email
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        WHERE bp.id = ?
    ");
    
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    // Check if post exists
    if (!$post) {
        setFlashMessage(MSG_POST_NOT_FOUND, 'danger');
        header('Location: ' . url('posts/index.php'));
        exit();
    }
    
    // Check if user can view this post
    if ($post['status'] === 'draft' && (!isLoggedIn() || getCurrentUserId() != $post['user_id'])) {
        setFlashMessage('This post is not published yet.', 'warning');
        header('Location: ' . url('posts/index.php'));
        exit();
    }
    
    // Update view count (only for published posts and not for author)
    if ($post['status'] === 'published' && (!isLoggedIn() || getCurrentUserId() != $post['user_id'])) {
        $updateStmt = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
        $updateStmt->execute([$postId]);
        $post['views']++; // Update local variable
    }
    
    // Get comments for this post
    $commentsStmt = $conn->prepare("
        SELECT 
            c.*,
            u.username as commenter_name,
            u.profile_picture as commenter_avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.blog_post_id = ?
        ORDER BY c.created_at DESC
    ");
    $commentsStmt->execute([$postId]);
    $comments = $commentsStmt->fetchAll();
    
    // Get reaction counts
    $reactionsStmt = $conn->prepare("
        SELECT reaction_type, COUNT(*) as count 
        FROM reactions 
        WHERE blog_post_id = ? 
        GROUP BY reaction_type
    ");
    $reactionsStmt->execute([$postId]);
    $reactions = $reactionsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Check if current user has reacted
    $userReaction = null;
    if (isLoggedIn()) {
        $userReactionStmt = $conn->prepare("SELECT reaction_type FROM reactions WHERE blog_post_id = ? AND user_id = ?");
        $userReactionStmt->execute([$postId, getCurrentUserId()]);
        $userReactionResult = $userReactionStmt->fetch();
        if ($userReactionResult) {
            $userReaction = $userReactionResult['reaction_type'];
        }
    }
    
    // Get related posts (same author, recent)
    $relatedStmt = $conn->prepare("
        SELECT 
            bp.id, 
            bp.title, 
            bp.slug, 
            bp.featured_image, 
            bp.created_at,
            bp.views,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count
        FROM blog_posts bp
        WHERE bp.user_id = ? AND bp.id != ? AND bp.status = 'published'
        ORDER BY bp.created_at DESC
        LIMIT 3
    ");
    $relatedStmt->execute([$post['user_id'], $postId]);
    $relatedPosts = $relatedStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("View post error: " . $e->getMessage());
    setFlashMessage("An error occurred. Please try again.", 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

// Helper functions
function getInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

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

// Set page title
$pageTitle = $post['title'];

// Include header
require_once '../includes/header.php';
?>

<style>
/* Spacious Professional Design */
:root {
    --primary: #667eea;
    --primary-dark: #764ba2;
    --text-dark: #1a202c;
    --text-body: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --bg-white: #ffffff;
    --border-light: #e2e8f0;
}

body {
    background: #fafbfc;
}

/* Reading Progress Bar */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
    z-index: 1000;
    transition: width 0.1s ease;
}

/* Hero Header Section */
.article-header {
    background: var(--bg-white);
    border-bottom: 1px solid var(--border-light);
    padding: 60px 0 40px;
}

.article-title {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1.2;
    color: var(--text-dark);
    margin-bottom: 2rem;
    letter-spacing: -0.02em;
}

.article-excerpt {
    font-size: 1.35rem;
    line-height: 1.7;
    color: var(--text-muted);
    margin-bottom: 2.5rem;
    font-weight: 400;
}

/* Author Meta Section */
.author-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 2rem 0;
    border-top: 1px solid var(--border-light);
    border-bottom: 1px solid var(--border-light);
}

/* Avatar Styles - FIXED */
.avatar-wrapper {
    position: relative;
    display: inline-block;
    flex-shrink: 0;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    font-weight: bold;
}

.avatar-lg {
    width: 56px;
    height: 56px;
    border: 2px solid var(--border-light);
}

.avatar-md {
    width: 48px;
    height: 48px;
    border: 2px solid var(--border-light);
}

.avatar-sm {
    width: 40px;
    height: 40px;
    border: 2px solid var(--border-light);
}

/* Featured Image */
.featured-image-wrapper {
    margin: 3rem 0 4rem;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}

.featured-image {
    width: 100%;
    height: auto;
    max-height: 600px;
    object-fit: cover;
    display: block;
}

/* Article Content */
.article-content {
    background: var(--bg-white);
    border-radius: 12px;
    padding: 4rem;
    margin-bottom: 3rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.article-body {
    font-size: 1.2rem;
    line-height: 1.9;
    color: var(--text-body);
    max-width: 720px;
    margin: 0 auto;
}

.article-body h1,
.article-body h2,
.article-body h3 {
    margin-top: 3rem;
    margin-bottom: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    line-height: 1.3;
}

.article-body h1 {
    font-size: 2.25rem;
}

.article-body h2 {
    font-size: 1.875rem;
}

.article-body h3 {
    font-size: 1.5rem;
}

.article-body p {
    margin-bottom: 1.75rem;
}

.article-body img {
    border-radius: 12px;
    margin: 3rem 0;
    width: 100%;
    height: auto;
}

.article-body a {
    color: var(--primary);
    text-decoration: none;
    border-bottom: 2px solid var(--primary);
    transition: opacity 0.3s ease;
}

.article-body a:hover {
    opacity: 0.7;
}

.article-body blockquote {
    border-left: 4px solid var(--primary);
    padding-left: 2rem;
    margin: 2.5rem 0;
    font-size: 1.3rem;
    font-style: italic;
    color: var(--text-muted);
}

.article-body ul,
.article-body ol {
    padding-left: 2rem;
    margin-bottom: 2rem;
}

.article-body li {
    margin-bottom: 0.75rem;
}

/* Action Cards */
.action-card {
    background: var(--bg-white);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid var(--border-light);
}

.action-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

/* Reaction Buttons */
.reactions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
}

.reaction-btn {
    padding: 1rem;
    border: 2px solid var(--border-light);
    border-radius: 12px;
    background: var(--bg-white);
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.reaction-btn:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
}

.reaction-btn.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-color: var(--primary);
    color: white;
}

.reaction-emoji {
    font-size: 2rem;
}

.reaction-count {
    font-weight: 700;
    font-size: 1.1rem;
}

/* Comments Section */
.comment-item {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.comment-item:hover {
    background: #edf2f7;
}

.comment-text {
    font-size: 1.05rem;
    line-height: 1.7;
    color: var(--text-body);
    margin-top: 1rem;
}

/* Sidebar Cards */
.sidebar-section {
    background: var(--bg-white);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-light);
}

.sidebar-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

/* Related Posts */
.related-post {
    display: block;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
}

.related-post:hover {
    background: var(--bg-light);
    border-color: var(--border-light);
    transform: translateX(8px);
}

.related-post-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

/* Stats Badge */
.stats-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-light);
    border-radius: 20px;
    font-size: 0.95rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* Buttons */
.btn-primary-custom {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    color: white;
    padding: 0.875rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-secondary-custom {
    background: var(--bg-white);
    border: 2px solid var(--border-light);
    color: var(--text-dark);
    padding: 0.875rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary-custom:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* Draft Badge */
.draft-badge {
    display: inline-block;
    background: #fed7d7;
    color: #c53030;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .article-title {
        font-size: 2rem;
    }
    
    .article-excerpt {
        font-size: 1.1rem;
    }
    
    .article-content {
        padding: 2rem 1.5rem;
    }
    
    .article-body {
        font-size: 1.1rem;
    }
    
    .reactions-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Smooth Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.6s ease-out;
}
</style>

<!-- Reading Progress Bar -->
<div class="reading-progress" id="readingProgress"></div>

<!-- Article Header -->
<section class="article-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-xl-8 mx-auto">
                
                <?php if ($post['status'] === 'draft'): ?>
                    <div class="draft-badge">
                        <i class="bi bi-eye-slash"></i> Draft - Not Published
                    </div>
                <?php endif; ?>
                
                <h1 class="article-title fade-in">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>
                
                <?php if (!empty($post['excerpt'])): ?>
                    <p class="article-excerpt fade-in">
                        <?php echo htmlspecialchars($post['excerpt']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="author-meta fade-in">
                    <?php 
                    // Check if avatar exists
                    $avatarPath = '';
                    $showImage = false;
                    
                    if (!empty($post['author_avatar'])) {
                        if (file_exists('../uploads/avatars/' . $post['author_avatar'])) {
                            $avatarPath = upload('avatar', $post['author_avatar']);
                            $showImage = true;
                        } elseif (file_exists('../uploads/profile/' . $post['author_avatar'])) {
                            $avatarPath = '../uploads/profile/' . $post['author_avatar'];
                            $showImage = true;
                        }
                    }
                    ?>
                    
                    <div class="avatar-wrapper avatar-lg">
                        <?php if ($showImage): ?>
                            <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                 alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                 class="avatar-img">
                        <?php else: ?>
                            <div class="avatar-fallback avatar-lg" 
                                 style="background: <?php echo getAvatarColor($post['author_name']); ?>">
                                <?php echo getInitials($post['author_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 1.05rem;">
                            <?php echo htmlspecialchars($post['author_name']); ?>
                        </div>
                        <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.95rem;">
                            <span>
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </span>
                            <span>•</span>
                            <span>
                                <i class="bi bi-clock"></i>
                                <?php 
                                $wordCount = str_word_count(strip_tags($post['content']));
                                $readingTime = ceil($wordCount / 200);
                                echo $readingTime . ' min read';
                                ?>
                            </span>
                            <span>•</span>
                            <span>
                                <i class="bi bi-eye"></i>
                                <?php echo number_format($post['views']); ?> views
                            </span>
                        </div>
                    </div>
                    
                    <?php if (isLoggedIn() && getCurrentUserId() == $post['user_id']): ?>
                        <div class="btn-group">
                            <a href="<?php echo url('posts/edit.php?id=' . $post['id']); ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <div class="btn-group">
                            <a href="<?php echo url('posts/delete.php?id=' . $post['id']); ?>" 
                               class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container my-5">
    <div class="row">
        
        <!-- Article Content -->
        <div class="col-lg-10 col-xl-8 mx-auto">
            
            <!-- Featured Image -->
            <?php if (!empty($post['featured_image'])): ?>
                <div class="featured-image-wrapper fade-in">
                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="featured-image">
                </div>
            <?php endif; ?>
            
            <!-- Article Body -->
            <article class="article-content fade-in">
                <div class="article-body">
                    <?php echo $post['content']; ?>
                </div>
            </article>
            
            <!-- Reactions -->
            <div class="action-card fade-in">
                <h3 class="action-card-title">
                    <i class="bi bi-emoji-smile"></i> What did you think?
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <div class="reactions-grid">
                        <?php foreach (REACTION_EMOJIS as $type => $emoji): ?>
                            <button type="button" 
                                    class="reaction-btn <?php echo ($userReaction === $type) ? 'active' : ''; ?>"
                                    onclick="handleReaction('<?php echo $type; ?>')">
                                <span class="reaction-emoji"><?php echo $emoji; ?></span>
                                <span class="reaction-count"><?php echo $reactions[$type] ?? 0; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <a href="<?php echo url('auth/login.php'); ?>">Sign in</a> to react to this post
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Comments -->
            <div class="action-card fade-in">
                <h3 class="action-card-title">
                    <i class="bi bi-chat-dots"></i> 
                    Comments (<?php echo count($comments); ?>)
                </h3>
                
                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="<?php echo url('api/comments.php'); ?>" class="mb-4">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="blog_post_id" value="<?php echo $post['id']; ?>">
                        
                        <textarea class="form-control mb-3" 
                                  name="comment" 
                                  rows="4" 
                                  placeholder="Share your thoughts..."
                                  style="border-radius: 10px; font-size: 1.05rem;"
                                  required></textarea>
                        
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="bi bi-send"></i> Post Comment
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <a href="<?php echo url('auth/login.php'); ?>">Sign in</a> to join the conversation
                    </div>
                <?php endif; ?>
                
                <?php if (empty($comments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3;"></i>
                        <p class="text-muted mt-3">No comments yet. Be the first!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex gap-3 flex-grow-1">
                                    <?php 
                                    $commenterAvatarPath = '';
                                    $showCommenterImage = false;
                                    
                                    if (!empty($comment['commenter_avatar'])) {
                                        if (file_exists('../uploads/avatars/' . $comment['commenter_avatar'])) {
                                            $commenterAvatarPath = upload('avatar', $comment['commenter_avatar']);
                                            $showCommenterImage = true;
                                        } elseif (file_exists('../uploads/profile/' . $comment['commenter_avatar'])) {
                                            $commenterAvatarPath = '../uploads/profile/' . $comment['commenter_avatar'];
                                            $showCommenterImage = true;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="avatar-wrapper avatar-md">
                                        <?php if ($showCommenterImage): ?>
                                            <img src="<?php echo htmlspecialchars($commenterAvatarPath); ?>" 
                                                 alt="<?php echo htmlspecialchars($comment['commenter_name']); ?>"
                                                 class="avatar-img">
                                        <?php else: ?>
                                            <div class="avatar-fallback avatar-md" 
                                                 style="background: <?php echo getAvatarColor($comment['commenter_name']); ?>">
                                                <?php echo getInitials($comment['commenter_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1">
                                            <?php echo htmlspecialchars($comment['commenter_name']); ?>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            <?php echo timeAgo($comment['created_at']); ?>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (isLoggedIn() && (getCurrentUserId() == $comment['user_id'] || getCurrentUserId() == $post['user_id'])): ?>
                                    <form method="POST" action="<?php echo url('api/comments.php'); ?>">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" 
                                                class="btn btn-sm btn-link text-danger"
                                                onclick="return confirm('Delete this comment?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        </div>
        
    </div>
    
    <!-- Bottom Section -->
    <div class="row mt-5">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <div class="row g-4">
                
                <!-- Author Card -->
                <div class="col-md-4">
                    <div class="sidebar-section text-center">
                        <h6 class="sidebar-title text-start">About the Author</h6>
                        
                        <div class="avatar-wrapper mx-auto mb-3" style="width: 80px; height: 80px;">
                            <?php if ($showImage): ?>
                                <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                                     alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                     class="avatar-img">
                            <?php else: ?>
                                <div class="avatar-fallback" 
                                     style="width: 80px; height: 80px; background: <?php echo getAvatarColor($post['author_name']); ?>; font-size: 1.5rem;">
                                    <?php echo getInitials($post['author_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($post['author_name']); ?></h5>
                        
                        <?php if (!empty($post['author_bio'])): ?>
                            <p class="text-muted small mb-3" style="line-height: 1.6;">
                                <?php echo htmlspecialchars($post['author_bio']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <a href="<?php echo url('profile/view.php?id=' . $post['user_id']); ?>" 
                           class="btn btn-sm btn-secondary-custom">
                            View Profile
                        </a>
                    </div>
                </div>
                
                <!-- Share Card -->
                <div class="col-md-4">
                    <div class="sidebar-section">
                        <h6 class="sidebar-title">Share This Story</h6>
                        
                        <div class="d-grid gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url('posts/view.php?id=' . $post['id'])); ?>" 
                               target="_blank" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-facebook me-2"></i> Facebook
                            </a>
                            <a href="https://x.com/share?url=<?php echo urlencode(url('posts/view.php?id=' . $post['id'])); ?>&text=<?php echo urlencode($post['title']); ?>" 
                               target="_blank" 
                               class="btn btn-outline-info">
                                <i class="fa-brands fa-x-twitter me-2"></i> X
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(url('posts/view.php?id=' . $post['id'])); ?>&title=<?php echo urlencode($post['title']); ?>" 
                               target="_blank" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-linkedin me-2"></i> LinkedIn
                            </a>
                            <button class="btn btn-outline-secondary" onclick="copyLink()">
                                <i class="bi bi-link-45deg me-2"></i> Copy Link
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-md-4">
                    <div class="sidebar-section">
                        <h6 class="sidebar-title">Quick Actions</h6>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo url('posts/index.php'); ?>" 
                               class="btn btn-secondary-custom">
                                <i class="bi bi-arrow-left me-2"></i> All Posts
                            </a>
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo url('posts/create.php'); ?>" 
                                   class="btn btn-primary-custom">
                                    <i class="bi bi-plus-circle me-2"></i> Write Story
                                </a>
                            <?php else: ?>
                                <a href="<?php echo url('auth/register.php'); ?>" 
                                   class="btn btn-primary-custom">
                                    <i class="bi bi-rocket-takeoff me-2"></i> Join Inkora
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Related Posts -->
    <?php if (!empty($relatedPosts)): ?>
        <div class="row mt-5">
            <div class="col-lg-10 col-xl-8 mx-auto">
                <div class="action-card">
                    <h3 class="action-card-title">
                        <i class="bi bi-collection"></i> 
                        More from <?php echo htmlspecialchars($post['author_name']); ?>
                    </h3>
                    
                    <div class="row g-3">
                        <?php foreach ($relatedPosts as $related): ?>
                            <div class="col-md-12">
                                <a href="<?php echo url('posts/view.php?id=' . $related['id']); ?>" 
                                   class="related-post">
                                    <div class="d-flex gap-3 align-items-start">
                                        <?php if (!empty($related['featured_image'])): ?>
                                            <img src="<?php echo upload('blog', $related['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                 style="width: 120px; height: 80px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                                        <?php else: ?>
                                            <div style="width: 120px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-file-text text-white" style="font-size: 2rem; opacity: 0.5;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex-grow-1">
                                            <h6 class="related-post-title">
                                                <?php echo htmlspecialchars($related['title']); ?>
                                            </h6>
                                            <div class="d-flex gap-3 text-muted small">
                                                <span>
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php echo timeAgo($related['created_at']); ?>
                                                </span>
                                                <span>
                                                    <i class="bi bi-eye"></i>
                                                    <?php echo number_format($related['views']); ?>
                                                </span>
                                                <span>
                                                    <i class="bi bi-chat"></i>
                                                    <?php echo number_format($related['comment_count']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
</div>

<!-- Scripts -->
<script>
// Reading Progress Bar
window.addEventListener('scroll', function() {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    document.getElementById('readingProgress').style.width = scrolled + '%';
});

// Handle Reactions
function handleReaction(reactionType) {
    const button = event.target.closest('.reaction-btn');
    button.disabled = true;
    
    fetch('<?php echo url('api/reactions.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'toggle',
            'blog_post_id': '<?php echo $post['id']; ?>',
            'reaction_type': reactionType,
            'csrf_token': '<?php echo generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const scrollPos = window.scrollY;
            sessionStorage.setItem('scrollPos', scrollPos);
            location.reload();
        } else {
            alert(data.message || 'An error occurred');
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        button.disabled = false;
    });
}

// Restore scroll position
window.addEventListener('load', function() {
    const scrollPos = sessionStorage.getItem('scrollPos');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem('scrollPos');
    }
});

// Copy Link
function copyLink() {
    const url = window.location.href;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function() {
            showNotification('Link copied to clipboard!');
        }).catch(function(err) {
            fallbackCopyLink(url);
        });
    } else {
        fallbackCopyLink(url);
    }
}

function fallbackCopyLink(url) {
    const textArea = document.createElement('textarea');
    textArea.value = url;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Link copied to clipboard!');
    } catch (err) {
        showNotification('Failed to copy link', 'danger');
    }
    
    document.body.removeChild(textArea);
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    notification.innerHTML = `
        <i class="bi bi-check-circle-fill me-2"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s ease';
        notification.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 2500);
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Fade in animations on scroll
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

document.querySelectorAll('.fade-in').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease-out';
    observer.observe(el);
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>