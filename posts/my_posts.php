<?php
/**
 * My Blog Posts Page
 * 
 * Displays all blog posts created by the logged-in user
 * 
 * Features:
 * - List all user's posts (published and drafts)
 * - Show post statistics
 * - Quick edit/delete buttons
 * - Filter by status
 * - Pagination
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "My Blog Posts";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Get filter status
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$validStatuses = ['all', 'published', 'draft'];
if (!in_array($filterStatus, $validStatuses)) {
    $filterStatus = 'all';
}

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * POSTS_PER_PAGE;

try {
    // Build query based on filter
    $whereClause = "user_id = ?";
    $params = [getCurrentUserId()];
    
    if ($filterStatus !== 'all') {
        $whereClause .= " AND status = ?";
        $params[] = $filterStatus;
    }
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM blog_posts WHERE $whereClause");
    $countStmt->execute($params);
    $totalPosts = $countStmt->fetch()['total'];
    
    // Calculate total pages
    $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
    
    // Get user's posts
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
            (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
        FROM blog_posts bp
        WHERE $whereClause
        ORDER BY bp.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = POSTS_PER_PAGE;
    $params[] = $offset;
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Get statistics
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_posts,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            SUM(views) as total_views,
            (SELECT COUNT(*) FROM comments c JOIN blog_posts bp ON c.blog_post_id = bp.id WHERE bp.user_id = ?) as total_comments,
            (SELECT COUNT(*) FROM reactions r JOIN blog_posts bp ON r.blog_post_id = bp.id WHERE bp.user_id = ?) as total_reactions
        FROM blog_posts
        WHERE user_id = ?
    ");
    $statsStmt->execute([getCurrentUserId(), getCurrentUserId(), getCurrentUserId()]);
    $stats = $statsStmt->fetch();
    
} catch (PDOException $e) {
    error_log("My posts error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
    $stats = [
        'total_posts' => 0,
        'published_count' => 0,
        'draft_count' => 0,
        'total_views' => 0,
        'total_comments' => 0,
        'total_reactions' => 0
    ];
}

// Include header
require_once '../includes/header.php';
?>

<!-- My Posts Page -->
<div class="container my-5">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold">
                <i class="bi bi-journal-text text-primary"></i> My Stories
            </h1>
            <p class="text-muted">Manage your blog posts</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="bi bi-file-text display-4 text-primary"></i>
                <h3 class="mt-2 mb-0"><?php echo number_format($stats['total_posts'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Total Posts</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="bi bi-eye display-4 text-success"></i>
                <h3 class="mt-2 mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Total Views</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="bi bi-chat display-4 text-info"></i>
                <h3 class="mt-2 mb-0"><?php echo number_format($stats['total_comments'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Comments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="bi bi-heart display-4 text-danger"></i>
                <h3 class="mt-2 mb-0"><?php echo number_format($stats['total_reactions'] ?? 0); ?></h3>
                <p class="text-muted mb-0">Reactions</p>
            </div>
        </div>
    </div>
</div>
    
<!-- Filter and Create Button -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="btn-group" role="group">
            <a href="?status=all" class="btn btn-outline-primary <?php echo ($filterStatus === 'all') ? 'active' : ''; ?>">
                All (<?php echo $stats['total_posts'] ?? 0; ?>)
            </a>
            <a href="?status=published" class="btn btn-outline-success <?php echo ($filterStatus === 'published') ? 'active' : ''; ?>">
                Published (<?php echo $stats['published_count'] ?? 0; ?>)
            </a>
            <a href="?status=draft" class="btn btn-outline-warning <?php echo ($filterStatus === 'draft') ? 'active' : ''; ?>">
                Drafts (<?php echo $stats['draft_count'] ?? 0; ?>)
            </a>
        </div>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Post
        </a>
    </div>
</div>
    
    <!-- Posts List -->
    <?php if (empty($posts)): ?>
        <div class="alert alert-info text-center p-5">
            <i class="bi bi-inbox display-1 text-info mb-3"></i>
            <h3>No Posts Found</h3>
            <p class="mb-4">
                <?php if ($filterStatus === 'all'): ?>
                    You haven't created any posts yet. Start sharing your stories!
                <?php elseif ($filterStatus === 'published'): ?>
                    You don't have any published posts yet.
                <?php else: ?>
                    You don't have any draft posts.
                <?php endif; ?>
            </p>
            <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Your First Post
            </a>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%;">Title</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Views</th>
                            <th class="text-center">Comments</th>
                            <th class="text-center">Reactions</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 class="rounded me-3"
                                                 width="60"
                                                 height="60"
                                                 style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                               class="text-decoration-none text-dark fw-bold">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                            <?php if ($post['created_at'] != $post['updated_at']): ?>
                                                <br><small class="text-muted"><i class="bi bi-pencil"></i> Edited</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> Published
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-pencil"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
<td class="text-center">
    <i class="bi bi-eye text-muted"></i>
    <?php echo number_format($post['views'] ?? 0); ?>
</td>
<td class="text-center">
    <i class="bi bi-chat text-muted"></i>
    <?php echo number_format($post['comment_count'] ?? 0); ?>
</td>
<td class="text-center">
    <i class="bi bi-heart text-muted"></i>
    <?php echo number_format($post['reaction_count'] ?? 0); ?>
</td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="btn btn-outline-primary btn-sm"
                                           title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('posts/edit.php?id=' . $post['id']); ?>" 
                                           class="btn btn-outline-secondary btn-sm"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('posts/delete.php?id=' . $post['id']); ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="My posts pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo $filterStatus; ?>&page=<?php echo $page - 1; ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $filterStatus; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo $filterStatus; ?>&page=<?php echo $page + 1; ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>