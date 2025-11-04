<?php
/**
 * Admin - Manage Users
 * 
 * Allows administrators to view and manage all users
 * 
 * Features:
 * - View all users
 * - Search users
 * - Change user roles
 * - Delete users (except self)
 * - User statistics
 * - Pagination
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "Manage Users";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Prevent self-deletion
    if ($userId === getCurrentUserId()) {
        setFlashMessage("You cannot delete your own account.", 'danger');
    } elseif ($userId > 0) {
        try {
            // Delete user (cascade will delete their posts, comments, reactions)
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            setFlashMessage("User deleted successfully.", 'success');
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            setFlashMessage("Error deleting user.", 'danger');
        }
    }
    
    header('Location: ' . url('admin/users.php'));
    exit();
}

// Handle role change
if (isset($_POST['change_role']) && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    if (in_array($newRole, ['user', 'admin']) && $userId !== getCurrentUserId()) {
        try {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            
            setFlashMessage("User role updated successfully.", 'success');
        } catch (PDOException $e) {
            error_log("Change role error: " . $e->getMessage());
            setFlashMessage("Error updating user role.", 'danger');
        }
    }
    
    header('Location: ' . url('admin/users.php'));
    exit();
}

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * USERS_PER_PAGE;

try {
    // Build query
    if (!empty($searchQuery)) {
        $searchTerm = '%' . $searchQuery . '%';
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR email LIKE ?");
        $countStmt->execute([$searchTerm, $searchTerm]);
        
        $stmt = $conn->prepare("
            SELECT 
                u.*,
                (SELECT COUNT(*) FROM blog_posts WHERE user_id = u.id) as post_count,
                (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
            FROM users u
            WHERE u.username LIKE ? OR u.email LIKE ?
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$searchTerm, $searchTerm, USERS_PER_PAGE, $offset]);
    } else {
        $countStmt = $conn->query("SELECT COUNT(*) as total FROM users");
        
        $stmt = $conn->prepare("
            SELECT 
                u.*,
                (SELECT COUNT(*) FROM blog_posts WHERE user_id = u.id) as post_count,
                (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count
            FROM users u
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([USERS_PER_PAGE, $offset]);
    }
    
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / USERS_PER_PAGE);
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Manage users error: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}

// Include header
require_once '../includes/header.php';
?>

<!-- Manage Users Page -->
<div class="container-fluid my-4">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold">
                        <i class="bi bi-people text-primary"></i> Manage Users
                    </h1>
                    <p class="text-muted">Total: <?php echo number_format($totalUsers); ?> users</p>
                </div>
                <a href="<?php echo url('admin/index.php'); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <form method="GET" action="" class="input-group">
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       placeholder="Search by username or email..."
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Posts</th>
                                <th>Comments</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-inbox display-1 text-muted"></i>
                                        <p class="text-muted mt-3">No users found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo upload('avatar', $user['profile_picture']); ?>" 
                                                     alt="<?php echo htmlspecialchars($user['username']); ?>"
                                                     class="rounded-circle me-2"
                                                     width="40"
                                                     height="40"
                                                     style="object-fit: cover;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['id'] === getCurrentUserId()): ?>
                                                        <span class="badge bg-info">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['id'] === getCurrentUserId()): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php else: ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="new_role" 
                                                            class="form-select form-select-sm" 
                                                            style="width: auto; display: inline-block;"
                                                            onchange="if(confirm('Change user role?')) this.form.submit();">
                                                        <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                    <input type="hidden" name="change_role" value="1">
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($user['post_count']); ?></td>
                                        <td><?php echo number_format($user['comment_count']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo url('profile/view.php?id=' . $user['id']); ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="View Profile">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($user['id'] !== getCurrentUserId()): ?>
                                                    <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       title="Delete User"
                                                       onclick="return confirm('Are you sure you want to delete this user? All their posts and comments will be deleted.')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
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
                <nav class="mt-4" aria-label="Users pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
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