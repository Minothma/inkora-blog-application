<?php

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit();
}

// Get user info before destroying session
$username = getCurrentUsername();
$userId = getCurrentUserId();

// Get user profile picture
try {
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $userAvatar = $user['profile_picture'] ?? null;
} catch (PDOException $e) {
    $userAvatar = null;
}

// Handle logout confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    error_log("User logout: " . $username . " (ID: " . $userId . ") from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/', '', COOKIE_SECURE, COOKIE_HTTPONLY);
    }
    
    destroySession();
    
    session_start();
    setFlashMessage(MSG_LOGOUT_SUCCESS, 'success');
    
    header('Location: ' . url('index.php'));
    exit();
}

$pageTitle = "Logout - Inkora";
require_once '../includes/header.php';
?>

<style>
/* Cyan to Purple Gradient Theme - Matching Home Page */
:root {
    --gradient-cyan: #00CED1;
    --gradient-cyan-light: #20B2C4;
    --gradient-purple: #6A5ACD;
    --gradient-purple-deep: #7B68BE;
    --gradient-navy: #0B1A2D;
    --accent-warm: #FFE4B5;
    --text-dark: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --bg-white: #ffffff;
    --border-light: #e2e8f0;
}

.logout-section {
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, 
        var(--gradient-cyan) 0%, 
        var(--gradient-cyan-light) 25%,
        var(--gradient-purple) 60%, 
        var(--gradient-purple-deep) 80%,
        var(--gradient-navy) 100%
    );
    padding: 3rem 1rem;
}

.logout-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
}

.logout-section::after {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255, 228, 181, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    top: -200px;
    right: -150px;
    animation: float 15s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(30px, 30px); }
}

.logout-card {
    position: relative;
    z-index: 1;
    background: var(--bg-white);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 100%;
    padding: 3rem 2.5rem;
    text-align: center;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.floating-badge {
    display: inline-block;
    padding: 0.4rem 0.9rem;
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.1) 0%, rgba(106, 90, 205, 0.1) 100%);
    color: var(--gradient-purple);
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(106, 90, 205, 0.2);
}

.logout-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.15) 0%, rgba(106, 90, 205, 0.15) 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
}

.logout-icon-wrapper i {
    font-size: 2.5rem;
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logout-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.75rem;
}

.logout-description {
    font-size: 1.05rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 2rem;
}

.user-card {
    background: var(--bg-light);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.user-card:hover {
    border-color: var(--gradient-cyan);
    box-shadow: 0 4px 12px rgba(0, 206, 209, 0.1);
}

.user-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.user-avatar {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 206, 209, 0.2);
}

.user-details {
    text-align: left;
}

.user-name {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.125rem;
    font-size: 1.05rem;
}

.user-status {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
    margin-bottom: 1.5rem;
}

.btn-logout {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 206, 209, 0.2);
}

.btn-logout:hover {
    background: linear-gradient(135deg, var(--gradient-cyan-light) 0%, var(--gradient-purple-deep) 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 206, 209, 0.3);
}

.btn-cancel {
    background: white;
    color: var(--text-dark);
    border: 2px solid var(--border-light);
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-cancel:hover {
    background: var(--bg-light);
    border-color: var(--gradient-purple);
    color: var(--gradient-purple);
}

.info-box {
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.05) 0%, rgba(106, 90, 205, 0.05) 100%);
    border: 1px solid rgba(0, 206, 209, 0.2);
    border-radius: 12px;
    padding: 1rem;
    text-align: left;
}

.info-box i {
    color: var(--gradient-cyan);
}

.info-box-text {
    font-size: 0.875rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, 
        rgba(11, 26, 45, 0.95) 0%, 
        rgba(27, 31, 58, 0.95) 100%
    );
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(8px);
}

.loading-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.loading-content {
    text-align: center;
}

.spinner {
    width: 56px;
    height: 56px;
    border: 4px solid rgba(0, 206, 209, 0.2);
    border-top-color: var(--gradient-cyan);
    border-right-color: var(--gradient-purple);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: white;
    font-size: 1rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 576px) {
    .logout-card {
        padding: 2rem 1.5rem;
    }
    
    .logout-title {
        font-size: 1.5rem;
    }
    
    .logout-description {
        font-size: 0.95rem;
    }
}
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p class="loading-text">Logging you out securely...</p>
    </div>
</div>

<!-- Logout Section -->
<section class="logout-section">
    <div class="logout-card">
        
        <!-- Badge -->
        <div class="floating-badge">
            <i class="bi bi-shield-exclamation"></i> Secure Logout
        </div>
        
        <!-- Icon -->
        <div class="logout-icon-wrapper">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        
        <!-- Title & Description -->
        <h1 class="logout-title">Ready to Leave?</h1>
        <p class="logout-description">
            We'll miss you! Are you sure you want to end your session?
        </p>
        
        <!-- User Info Card -->
        <div class="user-card">
            <div class="user-info">
                <?php 
                // Check if user has profile picture
                $avatarPath = '';
                $showImage = false;
                
                if (!empty($userAvatar)) {
                    if (file_exists('../uploads/avatars/' . $userAvatar)) {
                        $avatarPath = '../uploads/avatars/' . $userAvatar;
                        $showImage = true;
                    } elseif (file_exists('../uploads/profile/' . $userAvatar)) {
                        $avatarPath = '../uploads/profile/' . $userAvatar;
                        $showImage = true;
                    }
                }
                
                // If no profile picture, use default avatar
                if (!$showImage && file_exists('../uploads/avatars/default-avatar.png')) {
                    $avatarPath = '../uploads/avatars/default-avatar.png';
                    $showImage = true;
                } elseif (!$showImage && file_exists('../assets/images/default-avatar.png')) {
                    $avatarPath = '../assets/images/default-avatar.png';
                    $showImage = true;
                }
                
                // Generate initials as final fallback
                $initials = '';
                $words = explode(' ', trim($username));
                if (count($words) >= 2) {
                    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                } else {
                    $initials = strtoupper(substr($username, 0, 2));
                }
                ?>
                
                <div class="user-avatar">
                    <?php if ($showImage): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" 
                             alt="<?php echo htmlspecialchars($username); ?>"
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 14px;">
                    <?php else: ?>
                        <?php echo htmlspecialchars($initials); ?>
                    <?php endif; ?>
                </div>
                
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-status">Currently logged in</div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="button-group">
            <button onclick="handleLogout()" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>Yes, Logout
            </button>
            <a href="<?php echo url('index.php'); ?>" class="btn-cancel">
                <i class="bi bi-arrow-left me-2"></i>Stay Logged In
            </a>
        </div>
        
        <!-- Info Box -->
        <div class="info-box">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle mt-1"></i>
                <p class="info-box-text">
                    <strong>Note:</strong> Logging out will end your session and clear all data. You'll need to sign in again to access your account.
                </p>
            </div>
        </div>
        
    </div>
</section>

<script>
function handleLogout() {
    document.getElementById('loadingOverlay').classList.add('active');
    
    setTimeout(() => {
        window.location.href = '<?php echo url("auth/logout.php?confirm=yes"); ?>';
    }, 600);
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        window.location.href = '<?php echo url('index.php'); ?>';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>