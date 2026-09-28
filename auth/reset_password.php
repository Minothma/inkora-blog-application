<?php

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
                setFlashMessage("Your password has been reset successfully! You can now log in.", 'success');
                
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

<div class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                
                <!-- Reset Password Card -->
                <div class="card auth-card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="auth-icon-badge mb-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-1" style="color: #0F172A; font-size: 1.5rem; letter-spacing: -0.02em;">Set New Password</h2>
                            <p class="text-muted small">Choose a strong, unique password for your account</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <!-- Success Message -->
                            <div class="alert alert-success mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Password Reset Complete!</strong>
                                <p class="mb-0 small mt-1">Your password has been changed. You can now log in.</p>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; padding: 0.75rem;">
                                    <span>Proceed to Sign In</span>
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            
                        <?php else: ?>
                            
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
                            
                            <?php if ($tokenValid): ?>
                                <!-- Reset Password Form -->
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    
                                    <!-- CSRF Token -->
                                    <?php echo csrfField(); ?>
                                    
                                    <!-- New Password -->
                                    <div class="mb-3">
                                        <label for="password" class="form-label text-slate-700 fw-semibold small">
                                            New Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control border-start-0 border-end-0" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="At least 8 characters"
                                                   required
                                                   autofocus>
                                            <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                                    type="button" 
                                                    onclick="togglePasswordVisibility('password', 'toggleIcon1')"
                                                    style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                                <i class="bi bi-eye" id="toggleIcon1"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Confirm Password -->
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label text-slate-700 fw-semibold small">
                                            Confirm New Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control border-start-0 border-end-0" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="Re-enter new password"
                                                   required>
                                            <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                                    type="button" 
                                                    onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')"
                                                    style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                                <i class="bi bi-eye" id="toggleIcon2"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; font-size: 1rem; padding: 0.75rem;">
                                            <span>Save New Password</span>
                                            <i class="bi bi-check-circle ms-1"></i>
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center my-3">
                                    <a href="<?php echo url('auth/forgot_password.php'); ?>" class="btn btn-outline-primary" style="border-radius: 10px;">
                                        Request New Link
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                        
                        <!-- Back to Login -->
                        <div class="text-center mt-4 pt-3 border-top" style="border-color: #F1F5F9;">
                            <a href="<?php echo url('auth/login.php'); ?>" class="text-decoration-none text-slate-600 small fw-semibold">
                                <i class="bi bi-arrow-left me-1"></i> Back to Sign In
                            </a>
                        </div>
                        
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
</style>

<script>
function togglePasswordVisibility(fieldId, iconId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(iconId);
    
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
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>