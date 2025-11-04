<?php
/**
 * Reset Password Page
 * 
 * Allows users to set a new password using a valid token
 * 
 * Features:
 * - Token validation
 * - Password strength requirements
 * - Secure password hashing
 * - Token expiration check
 * - CSRF protection
 * 
 * @author Your Name
 * @version 1.0
 */

// Set page title
$pageTitle = "Reset Password";

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
$success = false;
$token = '';
$tokenValid = false;
$userEmail = '';
$userId = 0;

// Get token from URL
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    $errors[] = "Invalid or missing reset token.";
} else {
    try {
        // Check if token exists and is valid
        $stmt = $conn->prepare("
            SELECT prt.id, prt.user_id, prt.email, prt.expires_at, prt.used, u.username
            FROM password_reset_tokens prt
            JOIN users u ON prt.user_id = u.id
            WHERE prt.token = ? 
            AND prt.used = 0
            AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();
        
        if ($tokenData) {
            $tokenValid = true;
            $userEmail = $tokenData['email'];
            $userId = $tokenData['user_id'];
        } else {
            // Check if token exists but is expired or used
            $expiredStmt = $conn->prepare("
                SELECT expires_at, used 
                FROM password_reset_tokens 
                WHERE token = ?
            ");
            $expiredStmt->execute([$token]);
            $expiredToken = $expiredStmt->fetch();
            
            if ($expiredToken) {
                if ($expiredToken['used'] == 1) {
                    $errors[] = "This reset link has already been used. Please request a new one.";
                } else {
                    $errors[] = "This reset link has expired. Please request a new one.";
                }
            } else {
                $errors[] = "Invalid reset link. Please request a new one.";
            }
        }
    } catch (PDOException $e) {
        error_log("Reset Password Token Check Error: " . $e->getMessage());
        $errors[] = "An error occurred. Please try again.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get passwords
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password)) {
            $errors[] = "Please enter a new password.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        
        // Validate password confirmation
        if (empty($confirmPassword)) {
            $errors[] = "Please confirm your password.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        // If no validation errors, update password
        if (empty($errors)) {
            try {
                // Hash the new password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Update user's password
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $userId]);
                
                // Mark token as used
                $markUsedStmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                $markUsedStmt->execute([$token]);
                
                // Delete old tokens for this user (cleanup)
                $cleanupStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND token != ?");
                $cleanupStmt->execute([$userId, $token]);
                
                // Set success message
                $success = true;
                setFlashMessage("Your password has been successfully reset! You can now log in with your new password.", 'success');
                
                // Log the password reset
                error_log("Password reset successful for user ID: " . $userId);
                
            } catch (PDOException $e) {
                error_log("Reset Password Update Error: " . $e->getMessage());
                $errors[] = "An error occurred while resetting your password. Please try again.";
            }
        }
    }
}

// Include header
require_once '../includes/header.php';
?>

<style>
/* Cyan to Purple Gradient Design */
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
    --border-light: #e2e8f0;
}

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

.reset-wrapper {
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    padding: 3rem 0;
}

.reset-card {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.3);
    overflow: hidden;
    animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.reset-icon {
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

.reset-icon i {
    font-size: 2.5rem;
    color: white;
}

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

.reset-card a {
    color: var(--gradient-purple);
    font-weight: 500;
    transition: all 0.2s ease;
}

.reset-card a:hover {
    color: var(--gradient-cyan);
}

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

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 4px solid #28a745;
}

.password-requirements {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.password-requirements ul {
    margin-bottom: 0;
    padding-left: 1.5rem;
}

.password-requirements li {
    margin-bottom: 0.3rem;
}

.requirement-met {
    color: #28a745;
}

.requirement-unmet {
    color: var(--text-muted);
}

.login-link-box {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 1.5rem;
}

@media (max-width: 576px) {
    .reset-card {
        border-radius: 16px;
        margin: 1rem;
    }
    .reset-icon {
        width: 60px;
        height: 60px;
    }
    .reset-icon i {
        font-size: 2rem;
    }
}
</style>

<!-- Reset Password Form -->
<div class="reset-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                
                <!-- Reset Password Card -->
                <div class="reset-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <?php if ($success): ?>
                            <!-- Success State -->
                            <div class="text-center mb-4">
                                <div class="reset-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Password Reset!</h2>
                                <p class="text-muted mb-0">Your password has been successfully changed</p>
                            </div>
                            
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Success!</strong> Your password has been reset successfully.
                            </div>
                            
                            <div class="login-link-box">
                                <p class="mb-3">You can now log in with your new password</p>
                                <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-gradient-primary btn-lg w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Go to Login
                                </a>
                            </div>
                            
                        <?php elseif (!$tokenValid): ?>
                            <!-- Invalid Token State -->
                            <div class="text-center mb-4">
                                <div class="reset-icon" style="background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Invalid Link</h2>
                                <p class="text-muted mb-0">This password reset link is invalid or has expired</p>
                            </div>
                            
                            <!-- Error Messages -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?php foreach ($errors as $error): ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="login-link-box">
                                <p class="mb-3">Request a new password reset link</p>
                                <a href="<?php echo url('auth/forgot_password.php'); ?>" class="btn btn-gradient-primary btn-lg w-100 mb-2">
                                    <i class="bi bi-key-fill me-2"></i> Reset Password
                                </a>
                                <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <!-- Reset Password Form State -->
                            <div class="text-center mb-4">
                                <div class="reset-icon">
                                    <i class="bi bi-shield-lock-fill"></i>
                                </div>
                                <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Set New Password</h2>
                                <p class="text-muted mb-0">Create a strong password for your account</p>
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
                            
                            <!-- Reset Password Form -->
                            <form method="POST" action="" id="resetPasswordForm" class="needs-validation" novalidate>
                                
                                <!-- CSRF Token -->
                                <?php echo csrfField(); ?>
                                
                                <!-- Email Display (Read-only) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill me-1"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($userEmail); ?>"
                                           disabled>
                                </div>
                                
                                <!-- New Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock-fill me-1"></i> New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Enter new password"
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword('password', 'toggleIcon1')">
                                            <i class="bi bi-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="bi bi-lock-fill me-1"></i> Confirm Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Confirm new password"
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                            <i class="bi bi-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Password Requirements -->
                                <div class="password-requirements">
                                    <h6 class="fw-bold mb-2" style="color: var(--text-dark);">
                                        <i class="bi bi-info-circle me-1"></i> Password Requirements:
                                    </h6>
                                    <ul id="requirements">
                                        <li id="req-length" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> At least 8 characters
                                        </li>
                                        <li id="req-uppercase" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> One uppercase letter
                                        </li>
                                        <li id="req-lowercase" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> One lowercase letter
                                        </li>
                                        <li id="req-number" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> One number
                                        </li>
                                        <li id="req-special" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> One special character
                                        </li>
                                        <li id="req-match" class="requirement-unmet">
                                            <i class="bi bi-circle"></i> Passwords match
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-gradient-primary btn-lg" id="submitBtn">
                                        <i class="bi bi-shield-check me-2"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Back to Login -->
                            <div class="text-center mt-4">
                                <a href="<?php echo url('auth/login.php'); ?>" class="text-decoration-none">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                                </a>
                            </div>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
// Toggle password visibility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Real-time password validation
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    
    if (passwordInput && confirmInput) {
        function validatePassword() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            // Check length
            updateRequirement('req-length', password.length >= 8);
            
            // Check uppercase
            updateRequirement('req-uppercase', /[A-Z]/.test(password));
            
            // Check lowercase
            updateRequirement('req-lowercase', /[a-z]/.test(password));
            
            // Check number
            updateRequirement('req-number', /[0-9]/.test(password));
            
            // Check special character
            updateRequirement('req-special', /[^A-Za-z0-9]/.test(password));
            
            // Check match
            updateRequirement('req-match', password.length > 0 && password === confirm);
            
            // Enable/disable submit button
            const allMet = document.querySelectorAll('.requirement-met').length === 6;
            submitBtn.disabled = !allMet;
        }
        
        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (met) {
                element.classList.remove('requirement-unmet');
                element.classList.add('requirement-met');
                icon.classList.remove('bi-circle');
                icon.classList.add('bi-check-circle-fill');
            } else {
                element.classList.remove('requirement-met');
                element.classList.add('requirement-unmet');
                icon.classList.remove('bi-check-circle-fill');
                icon.classList.add('bi-circle');
            }
        }
        
        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>