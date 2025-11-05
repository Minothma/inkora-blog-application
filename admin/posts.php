<?php

// Set page title
$pageTitle = "Manage Posts";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

// Handle post deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $postId = (int)$_GET['id'];
    
    if ($postId > 0) {
        try {
            // Get post data to delete featured image
            $stmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            // Delete post
            $deleteStmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
            $deleteStmt->execute([$postId]);
            
            // Delete featured image if exists
            if ($post && !empty($post['featured_image'])) {
                $imagePath = BLOG_IMG_PATH . '/' . $post['featured_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            setFlashMessage("Post deleted successfully.", 'success');
        } catch (PDOException $e) {
            error_log("Delete post error: " . $e->getMessage());
            setFlashMessage("Error deleting post.", 'danger');
        }
    }
    
    header('Location: ' . url('admin/posts.php'));
    exit();
}

// Get filter status
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$validStatuses = ['all', 'published', 'draft'];
if (!in_array($filterStatus, $validStatuses)) {
    $filterStatus = 'all';
}

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * POSTS_PER_PAGE;

try {
    // Build query
    $whereConditions = [];
    $params = [];
    
    if ($filterStatus !== 'all') {
        $whereConditions[] = "bp.status = ?";
        $params[] = $filterStatus;
    }
    
    if (!empty($searchQuery)) {
        $whereConditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM blog_posts bp $whereClause";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalPosts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
    
    // Get posts
    $query = "
        SELECT 
            bp.*,
            u.username as author_name,
            (SELECT COUNT(*) FROM comments WHERE blog_post_id = bp.id) as comment_count,
            (SELECT COUNT(*) FROM reactions WHERE blog_post_id = bp.id) as reaction_count
        FROM blog_posts bp
        JOIN users u ON bp.user_id = u.id
        $whereClause
        ORDER BY bp.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = POSTS_PER_PAGE;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    // Get status counts for filter buttons
    $statusCountsStmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
        FROM blog_posts
    ");
    $statusCounts = $statusCountsStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Manage posts error: " . $e->getMessage());
    $posts = [];
    $totalPosts = 0;
    $totalPages = 0;
    $statusCounts = ['total' => 0, 'published' => 0, 'draft' => 0];
}

// Include header
require_once '../includes/header.php';
?>

<!-- Manage Posts Page -->
<div class="container-fluid my-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold">
                        <i class="bi bi-file-text text-success"></i> Manage Posts
                    </h1>
                    <p class="text-muted">Total: <?php echo number_format($totalPosts); ?> posts</p>
                </div>
                <a href="<?php echo url('admin/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="row mb-4">
        <!-- Status Filter -->
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="btn-group" role="group">
                <a href="?status=all<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn btn-outline-primary <?php echo ($filterStatus === 'all') ? 'active' : ''; ?>">
                    All (<?php echo $statusCounts['total']; ?>)
                </a>
                <a href="?status=published<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn btn-outline-success <?php echo ($filterStatus === 'published') ? 'active' : ''; ?>">
                    Published (<?php echo $statusCounts['published']; ?>)
                </a>
                <a href="?status=draft<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn btn-outline-warning <?php echo ($filterStatus === 'draft') ? 'active' : ''; ?>">
                    Drafts (<?php echo $statusCounts['draft']; ?>)
                </a>
            </div>
        </div>
        
        <!-- Search -->
        <div class="col-md-6">
            <form method="GET" action="" class="input-group">
                <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       placeholder="Search posts..."
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="?status=<?php echo $filterStatus; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Posts Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 35%;">Title</th>
                                <th style="width: 15%;">Author</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 8%;">Views</th>
                                <th style="width: 8%;">Comments</th>
                                <th style="width: 12%;">Date</th>
                                <th style="width: 7%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-inbox display-1 text-muted"></i>
                                        <p class="text-muted mt-3">No posts found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?php echo $post['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($post['featured_image'])): ?>
                                                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                                         alt=""
                                                         class="rounded me-2"
                                                         width="50"
                                                         height="50"
                                                         style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                                       class="text-decoration-none text-dark">
                                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                        <td>
                                            <?php if ($post['status'] === 'published'): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-eye text-muted"></i> 
                                            <?php echo number_format($post['views']); ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-chat text-muted"></i> 
                                            <?php echo number_format($post['comment_count']); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this post?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4" aria-label="Posts pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filterStatus; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filterStatus; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                Next
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