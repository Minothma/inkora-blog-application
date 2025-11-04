<?php
/**
 * Search API
 * 
 * Handles blog post search functionality
 * 
 * Features:
 * - Search by title and content
 * - Pagination
 * - Published posts only
 * - Author information
 * - Statistics
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "Search Results";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Get search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * SEARCH_RESULTS_PER_PAGE;

// Initialize results
$posts = [];
$totalResults = 0;
$totalPages = 0;

// Only search if query is not empty
if (!empty($searchQuery)) {
    
    try {
        // Prepare search term for LIKE query
        $searchTerm = '%' . $searchQuery . '%';
        
        // Get total count of search results
        $countStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM blog_posts 
            WHERE status = 'published' 
            AND (title LIKE ? OR content LIKE ?)
        ");
        $countStmt->execute([$searchTerm, $searchTerm]);
        $totalResults = $countStmt->fetch()['total'];
        
        // Calculate total pages
        $totalPages = ceil($totalResults / SEARCH_RESULTS_PER_PAGE);
        
        // Get search results
        if ($totalResults > 0) {
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
                    u.id as author_id,
                    u.username as author_name,
                    u.profile_picture as author_avatar,
                    (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
                    (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
                FROM blog_posts bp
                JOIN users u ON bp.user_id = u.id
                WHERE bp.status = 'published'
                AND (bp.title LIKE ? OR bp.content LIKE ?)
                ORDER BY bp.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$searchTerm, $searchTerm, SEARCH_RESULTS_PER_PAGE, $offset]);
            $posts = $stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        $posts = [];
        $totalResults = 0;
    }
}

/**
 * Helper function to highlight search terms in text
 */
function highlightSearchTerm($text, $searchQuery) {
    if (empty($searchQuery)) {
        return $text;
    }
    
    $pattern = '/(' . preg_quote($searchQuery, '/') . ')/i';
    return preg_replace($pattern, '<mark>$1</mark>', $text);
}

/**
 * Generate excerpt from content
 */
function generateExcerpt($content, $length = 150) {
    $text = strip_tags($content);
    
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    
    return $text;
}

/**
 * Time ago function
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

// Include header
require_once '../includes/header.php';
?>

<!-- Search Results Page -->
<div class="container my-5">
    
    <!-- Search Header -->
    <div class="row mb-4">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-5 fw-bold mb-3">
                <i class="bi bi-search text-primary"></i> Search Results
            </h1>
            
            <!-- Search Form -->
            <form method="GET" action="" class="mb-4">
                <div class="input-group input-group-lg">
                    <input type="text" 
                           class="form-control" 
                           name="q" 
                           placeholder="Search for stories, ideas, and more..."
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           required>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
            
            <!-- Results Count -->
            <?php if (!empty($searchQuery)): ?>
                <p class="text-muted">
                    <?php if ($totalResults > 0): ?>
                        Found <strong><?php echo number_format($totalResults); ?></strong> result<?php echo ($totalResults !== 1) ? 's' : ''; ?> for 
                        "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                    <?php else: ?>
                        No results found for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Search Results -->
    <?php if (empty($searchQuery)): ?>
        <!-- No Search Query -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-info text-center p-5">
                    <i class="bi bi-search display-1 text-info mb-3"></i>
                    <h3>Start Searching</h3>
                    <p class="mb-0">Enter keywords to search for stories on Inkora</p>
                </div>
            </div>
        </div>
        
    <?php elseif (empty($posts)): ?>
        <!-- No Results Found -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-warning text-center p-5">
                    <i class="bi bi-exclamation-triangle display-1 text-warning mb-3"></i>
                    <h3>No Results Found</h3>
                    <p class="mb-4">We couldn't find any stories matching your search. Try different keywords or browse all posts.</p>
                    <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-primary">
                        <i class="bi bi-book"></i> Browse All Stories
                    </a>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Results List -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <?php foreach ($posts as $post): ?>
                    <!-- Search Result Item -->
                    <div class="card mb-4 shadow-sm hover-shadow">
                        <div class="card-body">
                            <div class="row">
                                
                                <!-- Featured Image (if exists) -->
                                <?php if (!empty($post['featured_image'])): ?>
                                    <div class="col-md-3">
                                        <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                                             class="img-fluid rounded"
                                             style="height: 150px; width: 100%; object-fit: cover;">
                                    </div>
                                    <div class="col-md-9">
                                <?php else: ?>
                                    <div class="col-12">
                                <?php endif; ?>
                                
                                    <!-- Post Title -->
                                    <h4 class="card-title mb-2">
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo highlightSearchTerm(htmlspecialchars($post['title']), $searchQuery); ?>
                                        </a>
                                    </h4>
                                    
                                    <!-- Author & Date -->
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?php echo upload('avatar', $post['author_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                             class="rounded-circle me-2"
                                             width="24"
                                             height="24"
                                             style="object-fit: cover;">
                                        <small class="text-muted">
                                            <strong><?php echo htmlspecialchars($post['author_name']); ?></strong>
                                            · <?php echo timeAgo($post['created_at']); ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Excerpt -->
                                    <p class="card-text text-muted">
                                        <?php 
                                        $excerpt = !empty($post['excerpt']) ? $post['excerpt'] : generateExcerpt($post['content']);
                                        echo highlightSearchTerm(htmlspecialchars($excerpt), $searchQuery); 
                                        ?>
                                    </p>
                                    
                                    <!-- Stats & Read More -->
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
                                            Read More <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Search results pagination">
                        <ul class="pagination justify-content-center">
                            
                            <!-- Previous Button -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page - 1; ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next Button -->
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page + 1; ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            </div>
        </div>
    <?php endif; ?>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>