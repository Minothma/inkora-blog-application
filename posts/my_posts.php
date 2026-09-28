<?php

// Set page title
$pageTitle = "My Stories";

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

<!-- Nordic Slate Hero Banner -->
<div class="my-posts-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge-pill badge-gradient mb-2 d-inline-block">
                    <i class="bi bi-person-workspace me-1"></i> Author Dashboard
                </span>
                <h1 class="text-white fw-bold display-6 mb-1">My Stories</h1>
                <p class="text-slate-300 mb-0">Manage, edit, and track the performance of your published works.</p>
            </div>
            <div>
                <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-indigo-glow btn-lg">
                    <i class="bi bi-pencil-square me-1"></i> Write New Story
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-box">
                <div class="stat-icon-wrapper bg-indigo-light text-indigo">
                    <i class="bi bi-journal-richtext"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_posts'] ?? 0); ?></h3>
                    <span class="text-muted small">Total Stories</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-box">
                <div class="stat-icon-wrapper bg-emerald-light text-emerald">
                    <i class="bi bi-eye"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                    <span class="text-muted small">Total Views</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-box">
                <div class="stat-icon-wrapper bg-sky-light text-sky">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_comments'] ?? 0); ?></h3>
                    <span class="text-muted small">Comments</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-box">
                <div class="stat-icon-wrapper bg-rose-light text-rose">
                    <i class="bi bi-heart"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-slate-900"><?php echo number_format($stats['total_reactions'] ?? 0); ?></h3>
                    <span class="text-muted small">Reactions</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div class="filter-pills-wrapper">
            <a href="?status=all" class="filter-pill <?php echo ($filterStatus === 'all') ? 'active' : ''; ?>">
                All Stories (<?php echo $stats['total_posts'] ?? 0; ?>)
            </a>
            <a href="?status=published" class="filter-pill <?php echo ($filterStatus === 'published') ? 'active' : ''; ?>">
                Published (<?php echo $stats['published_count'] ?? 0; ?>)
            </a>
            <a href="?status=draft" class="filter-pill <?php echo ($filterStatus === 'draft') ? 'active' : ''; ?>">
                Drafts (<?php echo $stats['draft_count'] ?? 0; ?>)
            </a>
        </div>
    </div>
    
    <!-- Posts List / Table -->
    <?php if (empty($posts)): ?>
        <div class="card border-0 shadow-sm text-center p-5" style="border-radius: 20px;">
            <div class="py-4">
                <div class="empty-icon-circle mx-auto mb-3">
                    <i class="bi bi-journal-plus text-indigo" style="font-size: 2.2rem;"></i>
                </div>
                <h3 class="fw-bold text-slate-800">No Stories Found</h3>
                <p class="text-muted mb-4" style="max-width: 420px; margin: 0 auto;">
                    <?php if ($filterStatus === 'all'): ?>
                        You haven't created any stories yet. Start sharing your ideas with the world!
                    <?php elseif ($filterStatus === 'published'): ?>
                        You don't have any published stories under this filter.
                    <?php else: ?>
                        You have no drafts in progress.
                    <?php endif; ?>
                </p>
                <a href="<?php echo url('posts/create.php'); ?>" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-pencil-square me-1"></i> Start Writing Now
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0;">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0;">
                        <tr>
                            <th class="ps-4 py-3 text-slate-600 fw-semibold small text-uppercase" style="width: 48%;">Story</th>
                            <th class="text-center py-3 text-slate-600 fw-semibold small text-uppercase">Status</th>
                            <th class="text-center py-3 text-slate-600 fw-semibold small text-uppercase">Stats</th>
                            <th class="py-3 text-slate-600 fw-semibold small text-uppercase">Date</th>
                            <th class="pe-4 text-end py-3 text-slate-600 fw-semibold small text-uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr class="story-row">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 class="rounded-3 me-3 flex-shrink-0"
                                                 width="64"
                                                 height="64"
                                                 style="object-fit: cover; border: 1px solid #E2E8F0;">
                                        <?php else: ?>
                                            <div class="rounded-3 me-3 flex-shrink-0 d-flex align-items-center justify-content-center bg-slate-100 text-slate-400" 
                                                 style="width: 64px; height: 64px; border: 1px solid #E2E8F0;">
                                                <i class="bi bi-image" style="font-size: 1.4rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                               class="story-title-link fw-bold text-slate-900 d-block mb-1">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                            <div class="d-flex align-items-center gap-2 text-muted small">
                                                <span><i class="bi bi-clock me-1"></i><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                                <?php if ($post['created_at'] != $post['updated_at']): ?>
                                                    <span>&bull;</span>
                                                    <span><i class="bi bi-pencil me-1"></i>Edited</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill me-1"></i> Published
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-pencil-fill me-1"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center py-3">
                                    <div class="d-inline-flex gap-3 text-slate-600 small">
                                        <span title="Views"><i class="bi bi-eye me-1 text-slate-400"></i><?php echo number_format($post['views'] ?? 0); ?></span>
                                        <span title="Comments"><i class="bi bi-chat me-1 text-slate-400"></i><?php echo number_format($post['comment_count'] ?? 0); ?></span>
                                        <span title="Reactions"><i class="bi bi-heart me-1 text-slate-400"></i><?php echo number_format($post['reaction_count'] ?? 0); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 text-slate-600 small">
                                    <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </td>
                                <td class="pe-4 text-end py-3">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?php echo url('posts/view.php?id=' . $post['id']); ?>" 
                                           class="action-btn" 
                                           title="View Story">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo url('posts/edit.php?id=' . $post['id']); ?>" 
                                           class="action-btn" 
                                           title="Edit Story">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo url('posts/delete.php?id=' . $post['id']); ?>" 
                                           class="action-btn action-btn-danger" 
                                           title="Delete Story"
                                           onclick="return confirm('Are you sure you want to delete this story? This action cannot be undone.')">
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
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Stories pagination">
                    <ul class="pagination">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $filterStatus; ?>&page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left me-1"></i> Previous
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
                                Next <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
</div>

<style>
.my-posts-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    padding: 55px 0;
    margin-bottom: 35px;
    border-radius: 0 0 28px 28px;
    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3);
}
.badge-pill {
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
}
.badge-gradient {
    background: rgba(255, 255, 255, 0.12);
    color: #c7d2fe;
}
.btn-indigo-glow {
    background: #4F46E5;
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    transition: all 0.25s ease;
}
.btn-indigo-glow:hover {
    background: #4338CA;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
}
.stat-box {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.25s ease;
}
.stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
}
.stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
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

.filter-pills-wrapper {
    background: #F1F5F9;
    padding: 4px;
    border-radius: 12px;
    display: inline-flex;
    gap: 4px;
}
.filter-pill {
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748B;
    text-decoration: none;
    transition: all 0.2s ease;
}
.filter-pill:hover {
    color: #0F172A;
}
.filter-pill.active {
    background: #FFFFFF;
    color: #4F46E5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.story-row {
    transition: background 0.15s ease;
}
.story-row:hover {
    background: #F8FAFC;
}
.story-title-link {
    font-size: 1.05rem;
    transition: color 0.2s ease;
}
.story-title-link:hover {
    color: #4F46E5 !important;
}

.action-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #F1F5F9;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    text-decoration: none;
}
.action-btn:hover {
    background: #EEF2FF;
    color: #4F46E5;
    transform: translateY(-1px);
}
.action-btn-danger:hover {
    background: #FEF2F2;
    color: #EF4444;
}
.empty-icon-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #EEF2FF;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>