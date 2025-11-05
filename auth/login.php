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

<style>
/* Cyan to Purple Gradient Design */
:root {
    /* Primary Gradient Colors */
    --gradient-cyan: #00CED1;
    --gradient-cyan-light: #20B2C4;
    --gradient-purple: #6A5ACD;
    --gradient-purple-deep: #7B68BE;
    --gradient-navy: #0B1A2D;
    
    /* Accent Colors */
    --accent-warm: #FFE4B5;
    --accent-gold: #FDB94E;
    
    /* Neutral Colors */
    --text-dark: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --border-light: #e2e8f0;
}

/* Full Page Gradient Background */
body {
    background: linear-gradient(180deg, 
        var(--gradient-cyan) 0%, 
        var(--gradient-cyan-light) 25%,
        var(--gradient-purple) 60%, 
        var(--gradient-purple-deep) 80%,
        var(--gradient-navy) 100%
    );
    min-height: 100vh;
    position: relative;
}

/* Background Pattern Overlay */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.4;
    z-index: 0;
}

/* Content Wrapper */
.login-wrapper {
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    padding: 3rem 0;
}

/* Login Card - Modern Glass Effect */
.login-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.3);
    overflow: hidden;
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

/* Header Icon with Gradient */
.login-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 206, 209, 0.3);
}

.login-icon i {
    font-size: 2.5rem;
    color: white;
}

/* Form Inputs */
.form-control {
    border-radius: 12px;
    padding: 0.85rem 1rem;
    border: 2px solid var(--border-light);
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--gradient-cyan);
    box-shadow: 0 0 0 4px rgba(0, 206, 209, 0.1);
    background: rgba(0, 206, 209, 0.02);
}

.form-label {
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

/* Password Toggle Button */
.input-group .btn-outline-secondary {
    border-radius: 0 12px 12px 0;
    border: 2px solid var(--border-light);
    border-left: none;
    background: white;
    transition: all 0.3s ease;
}

.input-group .btn-outline-secondary:hover {
    background: var(--bg-light);
    border-color: var(--gradient-cyan);
}

.input-group .form-control {
    border-radius: 12px 0 0 12px;
}

/* Gradient Button */
.btn-gradient-primary {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.85rem 2rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 206, 209, 0.3);
}

.btn-gradient-primary:hover {
    background: linear-gradient(135deg, var(--gradient-cyan-light) 0%, var(--gradient-purple-deep) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 206, 209, 0.4);
    color: white;
}

.btn-gradient-primary:active {
    transform: translateY(0);
}

/* Checkbox Custom Style */
.form-check-input {
    width: 1.2em;
    height: 1.2em;
    border-radius: 6px;
    border: 2px solid var(--border-light);
    cursor: pointer;
}

.form-check-input:checked {
    background-color: var(--gradient-cyan);
    border-color: var(--gradient-cyan);
}

.form-check-label {
    cursor: pointer;
    color: var(--text-dark);
    font-size: 0.95rem;
}

/* Links */
.login-card a {
    color: var(--gradient-purple);
    font-weight: 500;
    transition: all 0.2s ease;
}

.login-card a:hover {
    color: var(--gradient-cyan);
    text-decoration: none;
}

/* Alert Messages */
.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.alert-danger {
    background: linear-gradient(135deg, #fee, #fdd);
    color: #c33;
    border-left: 4px solid #e53e3e;
}

.alert-info {
    background: linear-gradient(135deg, #e6f7ff, #d4edff);
    color: #0066cc;
    border-left: 4px solid #0066cc;
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.5rem 0;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid var(--border-light);
}

.divider span {
    padding: 0 1rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* Register Link Box */
.register-link-box {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 1.5rem;
}

/* Responsive Design */
@media (max-width: 576px) {
    .login-card {
        border-radius: 16px;
        margin: 1rem;
    }
    
    .login-icon {
        width: 60px;
        height: 60px;
    }
    
    .login-icon i {
        font-size: 2rem;
    }
}
</style>

<!-- Login Form -->
<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                
                <!-- Login Card -->
                <div class="login-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="login-icon">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Welcome Back</h2>
                            <p class="text-muted mb-0">Sign in to continue to Inkora</p>
                        </div>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php if (count($errors) === 1): ?>
                                    <strong><?php echo htmlspecialchars($errors[0]); ?></strong>
                                <?php else: ?>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
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
                                <label for="username_email" class="form-label">
                                    <i class="bi bi-person-fill me-1"></i> Username or Email
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username_email" 
                                       name="username_email" 
                                       value="<?php echo htmlspecialchars($username_email); ?>"
                                       placeholder="Enter username or email"
                                       required
                                       autofocus>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock-fill me-1"></i> Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            onclick="togglePasswordVisibility()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remember Me & Forgot Password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="remember_me" 
                                           name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        Remember me
                                    </label>
                                </div>
                                <a href="<?php echo url('auth/forgot_password.php'); ?>" class="text-decoration-none small">
                                    Forgot password?
                                </a>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-gradient-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                                </button>
                            </div>
                        </form>
                        
                        <!-- Divider -->
                        <div class="divider">
                            <span>or</span>
                        </div>
                        
                        <!-- Register Link -->
                        <div class="register-link-box">
                            <p class="text-muted mb-2">Don't have an account?</p>
                            <a href="<?php echo url('auth/register.php'); ?>" class="btn btn-outline-secondary w-100" style="border-radius: 12px; font-weight: 600;">
                                <i class="bi bi-person-plus-fill me-2"></i> Create Account
                            </a>
                        </div>
                        
                        <!-- Demo Credentials (Remove in production!) -->
                        <?php if (isDevelopment()): ?>
                            <div class="alert alert-info mt-3 mb-0" role="alert">
                                <small>
                                    <strong><i class="bi bi-info-circle me-1"></i> Demo Credentials:</strong><br>
                                    <strong>Admin:</strong> admin / Admin@123<br>
                                    <strong>User:</strong> johndoe / Admin@123
                                </small>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

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