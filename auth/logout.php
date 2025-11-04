<?php
/**
 * User Logout Handler - Inkora Blog Platform
 * 
 * Modern logout page with smooth animations and confirmation
 * 
 * Features:
 * - Beautiful logout confirmation screen
 * - Smooth animations and transitions
 * - Session cleanup with progress indicator
 * - Remember me cookie removal
 * - Security logging
 * - Cyan-to-purple gradient theme
 * - Automatic redirect after logout
 * 
 * @author Inkora Team
 * @version 2.0 - Enhanced with Confirmation Screen
 */

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Already logged out, redirect to home
    header('Location: ' . url('index.php'));
    exit();
}

// Get user info before destroying session
$username = getCurrentUsername();
$userId = getCurrentUserId();

// Check if this is a confirmation (actual logout action)
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Log logout action for security monitoring
    error_log("User logout: " . $username . " (ID: " . $userId . ") from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Clear "Remember Me" cookie if it exists
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/', '', COOKIE_SECURE, COOKIE_HTTPONLY);
    }
    
    // Destroy session
    destroySession();
    
    // Start a new session to set flash message
    session_start();
    setFlashMessage(MSG_LOGOUT_SUCCESS, 'success');
    
    // Redirect to home page
    header('Location: ' . url('index.php'));
    exit();
}

// If not confirmed, show logout confirmation page
$pageTitle = "Logout - Inkora";
require_once '../includes/header.php';
?>

<style>
/* Cyan to Purple Gradient Theme */
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
}

/* Logout Container Styles */
.logout-container {
    min-height: 80vh;
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
    position: relative;
    overflow: hidden;
}

.logout-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
}

.logout-card {
    position: relative;
    z-index: 1;
    background: white;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 90%;
    padding: 3rem;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.logout-icon {
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
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

.gradient-text {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.btn-gradient-primary {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 0.75rem 2rem;
    border-radius: 12px;
}

.btn-gradient-primary:hover {
    background: linear-gradient(135deg, var(--gradient-cyan-light) 0%, var(--gradient-purple-deep) 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 206, 209, 0.3);
    color: white;
}

.btn-outline-custom {
    border: 2px solid var(--gradient-purple);
    color: var(--gradient-purple);
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    background: white;
}

.btn-outline-custom:hover {
    background: var(--gradient-purple);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(106, 90, 205, 0.2);
}

.user-info {
    background: var(--bg-light);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(11, 26, 45, 0.95);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-top-color: var(--accent-warm);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.security-info {
    background: rgba(0, 206, 209, 0.05);
    border: 1px solid rgba(0, 206, 209, 0.2);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1.5rem;
}

/* Responsive Design */
@media (max-width: 576px) {
    .logout-card {
        padding: 2rem 1.5rem;
    }
    
    .btn-gradient-primary,
    .btn-outline-custom {
        padding: 0.65rem 1.5rem;
        font-size: 0.95rem;
    }
}
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="loading-spinner mx-auto mb-3"></div>
        <p class="text-white mb-0">Logging you out...</p>
    </div>
</div>

<!-- Logout Confirmation Section -->
<section class="logout-container">
    <div class="logout-card text-center">
        
        <!-- Logout Icon -->
        <div class="logout-icon mx-auto">
            <i class="bi bi-box-arrow-right gradient-text" style="font-size: 2.5rem;"></i>
        </div>
        
        <!-- Heading -->
        <h2 class="fw-bold mb-3" style="color: var(--text-dark);">
            Ready to Leave?
        </h2>
        <p class="text-muted mb-4" style="font-size: 1.05rem; line-height: 1.6;">
            We'll miss you! Are you sure you want to end your session?
        </p>
        
        <!-- User Info -->
        <div class="user-info">
            <div class="d-flex align-items-center justify-content-center">
                <div class="user-avatar me-3">
                    <?php 
                    $initials = '';
                    $words = explode(' ', trim($username));
                    if (count($words) >= 2) {
                        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($username, 0, 2));
                    }
                    echo $initials;
                    ?>
                </div>
                <div class="text-start">
                    <div class="fw-bold" style="color: var(--text-dark);">
                        <?php echo htmlspecialchars($username); ?>
                    </div>
                    <small class="text-muted">Currently logged in</small>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-grid gap-3">
            <button onclick="confirmLogout()" class="btn btn-gradient-primary btn-lg">
                <i class="bi bi-box-arrow-right me-2"></i>
                Yes, Logout
            </button>
            <a href="<?php echo url('index.php'); ?>" class="btn btn-outline-custom btn-lg">
                <i class="bi bi-arrow-left me-2"></i>
                Stay Logged In
            </a>
        </div>
        
        <!-- Security Info -->
        <div class="security-info text-start">
            <div class="d-flex align-items-start">
                <i class="bi bi-shield-check me-2 mt-1" style="color: var(--gradient-cyan);"></i>
                <div>
                    <small class="text-muted" style="line-height: 1.6;">
                        <strong>Security Notice:</strong> Logging out will end your current session and clear all session data. You'll need to sign in again to access your account.
                    </small>
                </div>
            </div>
        </div>
        
    </div>
</section>

<script>
function confirmLogout() {
    // Show loading overlay
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.add('active');
    
    // Simulate a brief delay for smooth transition (optional)
    setTimeout(() => {
        // Redirect to logout with confirmation
        window.location.href = '<?php echo url("auth/logout.php?confirm=yes"); ?>';
    }, 800);
}

// Add keyboard shortcut (Enter to logout, Esc to cancel)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        confirmLogout();
    } else if (e.key === 'Escape') {
        window.location.href = '<?php echo url('index.php'); ?>';
    }
});

// Prevent accidental back button navigation
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};
</script>

<?php
require_once '../includes/footer.php';
?>