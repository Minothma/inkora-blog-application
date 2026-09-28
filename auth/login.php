<?php

// Set page title
$pageTitle = "Login";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit();
}

// Initialize variables
$errors = [];
$username_email = '';

// Get redirect URL if provided
$redirectUrl = $_GET['redirect'] ?? url('index.php');

// Handle session timeout message
if (isset($_GET['error']) && $_GET['error'] === 'session_timeout') {
    $errors[] = "Your session has expired. Please log in again.";
}

// Handle session invalid message
if (isset($_GET['error']) && $_GET['error'] === 'session_invalid') {
    $errors[] = "Invalid session detected. Please log in again.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get and sanitize inputs
        $username_email = trim($_POST['username_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Basic validation
        if (empty($username_email)) {
            $errors[] = "Please enter your username or email.";
        }
        
        if (empty($password)) {
            $errors[] = "Please enter your password.";
        }
        
        // If no validation errors, proceed with authentication
        if (empty($errors)) {
            try {
                // Check if login attempt is with email or username
                $isEmail = filter_var($username_email, FILTER_VALIDATE_EMAIL);
                
                if ($isEmail) {
                    // Login with email
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                } else {
                    // Login with username
                    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                }
                
                $stmt->execute([$username_email]);
                $user = $stmt->fetch();
                
                // Verify user exists and password is correct
                if ($user && password_verify($password, $user['password'])) {
                    
                    // Check if password needs rehashing (security improvement)
                    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->execute([$newHash, $user['id']]);
                    }
                    
                    // Set session data
                    setUserSession($user);
                    
                    // Handle "Remember Me" functionality
                    if ($rememberMe) {
                        // Set cookie for 30 days
                        $cookieValue = base64_encode($user['id'] . ':' . $user['username']);
                        setcookie('remember_user', $cookieValue, time() + (30 * 24 * 60 * 60), '/', '', COOKIE_SECURE, COOKIE_HTTPONLY);
                    }
                    
                    // Set success flash message
                    setFlashMessage(MSG_LOGIN_SUCCESS, 'success');
                    
                    // Redirect to intended page or dashboard
                    if (!empty($redirectUrl) && $redirectUrl !== url('index.php')) {
                        header('Location: ' . $redirectUrl);
                    } else {
                        header('Location: ' . url('index.php'));
                    }
                    exit();
                    
                } else {
                    // Invalid credentials
                    $errors[] = MSG_LOGIN_FAILED;
                    
                    // Log failed login attempt (optional - for security monitoring)
                    error_log("Failed login attempt for: " . $username_email . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
                
            } catch (PDOException $e) {
                // Log error and show generic message
                error_log("Login Error: " . $e->getMessage());
                $errors[] = "An error occurred during login. Please try again.";
            }
        }
    }
}

// Check for "Remember Me" cookie
if (isset($_COOKIE['remember_user']) && !isLoggedIn()) {
    try {
        $cookieData = base64_decode($_COOKIE['remember_user']);
        list($userId, $username) = explode(':', $cookieData);
        
        // Verify user still exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND username = ?");
        $stmt->execute([$userId, $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Auto-login user
            setUserSession($user);
            header('Location: ' . url('index.php'));
            exit();
        } else {
            // Invalid cookie, remove it
            setcookie('remember_user', '', time() - 3600, '/');
        }
    } catch (Exception $e) {
        // Invalid cookie format, remove it
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                
                <!-- Login Card -->
                <div class="card auth-card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="auth-icon-badge mb-3">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <h2 class="fw-bold mb-1" style="color: #0F172A; font-size: 1.6rem; letter-spacing: -0.02em;">Welcome Back</h2>
                            <p class="text-muted small">Sign in to continue to your Inkora account</p>
                        </div>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                                    <div>
                                        <?php if (count($errors) === 1): ?>
                                            <span><?php echo htmlspecialchars($errors[0]); ?></span>
                                        <?php else: ?>
                                            <ul class="mb-0 ps-3">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" action="" class="needs-validation" novalidate>
                            
                            <!-- CSRF Token -->
                            <?php echo csrfField(); ?>
                            
                            <!-- Hidden redirect field -->
                            <?php if (!empty($redirectUrl)): ?>
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectUrl); ?>">
                            <?php endif; ?>
                            
                            <!-- Username or Email -->
                            <div class="mb-3">
                                <label for="username_email" class="form-label text-slate-700 fw-semibold small">
                                    Username or Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0" 
                                           id="username_email" 
                                           name="username_email" 
                                           value="<?php echo htmlspecialchars($username_email); ?>"
                                           placeholder="e.g. johndoe or john@example.com"
                                           style="border-radius: 0 10px 10px 0;"
                                           required
                                           autofocus>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label text-slate-700 fw-semibold small mb-0">
                                        Password
                                    </label>
                                    <a href="<?php echo url('auth/forgot_password.php'); ?>" class="text-decoration-none small text-indigo fw-medium">
                                        Forgot?
                                    </a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0 border-end-0" 
                                           id="password" 
                                           name="password" 
                                           placeholder="••••••••"
                                           required>
                                    <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                            type="button" 
                                            id="togglePassword"
                                            onclick="togglePasswordVisibility()"
                                            style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="remember_me" 
                                       name="remember_me"
                                       style="border-color: #CBD5E1;">
                                <label class="form-check-label text-slate-600 small" for="remember_me">
                                    Remember me on this device
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; font-size: 1rem; padding: 0.75rem;">
                                    <span>Sign In</span>
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Divider -->
                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1 my-0" style="border-color: #E2E8F0;">
                            <span class="px-3 text-muted small">New to Inkora?</span>
                            <hr class="flex-grow-1 my-0" style="border-color: #E2E8F0;">
                        </div>
                        
                        <!-- Register Link -->
                        <div class="text-center">
                            <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-outline-secondary w-100" style="border-radius: 10px; font-weight: 600; padding: 0.65rem;">
                                Create an Account
                            </a>
                        </div>
                        
                        <!-- Demo Credentials (DEV) -->
                        <?php if (isDevelopment()): ?>
                            <div class="alert alert-info mt-4 mb-0 py-2 px-3" role="alert" style="font-size: 0.8rem;">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Demo Credentials:</strong>
                                </div>
                                <span class="d-block">Admin: <code>admin</code> / <code>Admin@123</code></span>
                                <span class="d-block">User: <code>johndoe</code> / <code>Admin@123</code></span>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<style>
.auth-section {
    min-height: calc(100vh - 350px);
    display: flex;
    align-items: center;
}
.auth-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0 !important;
}
.auth-icon-badge {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #EEF2FF;
    color: #4F46E5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
}
.text-indigo {
    color: #4F46E5;
}
.text-indigo:hover {
    color: #4338CA;
}
</style>

<!-- Password Toggle Script -->
<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('bi-eye');
        toggleIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('bi-eye-slash');
        toggleIcon.classList.add('bi-eye');
    }
}

// Form validation feedback
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>