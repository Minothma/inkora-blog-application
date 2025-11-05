<?php
/**
 * Change Password Page
 */

// Set page title
$pageTitle = "Change Password";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Initialize variables
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get inputs
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Current password validation
        if (empty($currentPassword)) {
            $errors[] = "Current password is required.";
        } else {
            // Verify current password
            try {
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([getCurrentUserId()]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $errors[] = "Current password is incorrect.";
                }
            } catch (PDOException $e) {
                error_log("Password verification error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again.";
            }
        }
        
        // New password validation
        if (empty($newPassword)) {
            $errors[] = "New password is required.";
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $errors[] = "New password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
        } else {
            // Check password strength
            if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $newPassword)) {
                $errors[] = "New password must contain at least one uppercase letter.";
            }
            if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $newPassword)) {
                $errors[] = "New password must contain at least one lowercase letter.";
            }
            if (PASSWORD_REQUIRE_NUMBER && !preg_match('/\d/', $newPassword)) {
                $errors[] = "New password must contain at least one number.";
            }
            if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
                $errors[] = "New password must contain at least one special character.";
            }
            
            // Check if new password is same as current password
            if ($currentPassword === $newPassword) {
                $errors[] = "New password must be different from current password.";
            }
        }
        
        // Confirm password validation
        if (empty($confirmPassword)) {
            $errors[] = "Please confirm your new password.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        }
        
        // Update password if no errors
        if (empty($errors)) {
            try {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                // Update password in database
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashedPassword, getCurrentUserId()]);
                
                // Success
                $success = true;
                setFlashMessage(MSG_PASSWORD_CHANGED, 'success');
                
                // Optional: Log out user to require re-login with new password
                // destroySession();
                // header('Location: ' . url('auth/login.php'));
                // exit();
            } catch (PDOException $e) {
                error_log("Change password error: " . $e->getMessage());
                $errors[] = "An error occurred while changing your password. Please try again.";
            }
        }
    }
}

// Include header
require_once '../includes/header.php';
?>

<!-- Change Password Page -->
<div class="container my-5">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="display-6 fw-bold">
                    <i class="bi bi-shield-lock text-primary"></i> Change Password
                </h1>
                <p class="text-muted">Update your account password</p>
            </div>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Success!</strong> Your password has been changed successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Change Password Form -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <form method="POST" action="" class="needs-validation" novalidate id="changePasswordForm">
                        
                        <!-- CSRF Token -->
                        <?php echo csrfField(); ?>
                        
                        <!-- Current Password -->
                        <div class="mb-4">
                            <label for="current_password" class="form-label fw-bold">
                                <i class="bi bi-lock"></i> Current Password *
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Enter your current password"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('current_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- New Password -->
                        <div class="mb-4">
                            <label for="new_password" class="form-label fw-bold">
                                <i class="bi bi-key"></i> New Password *
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password"
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('new_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters with uppercase, lowercase, number, and special character
                            </div>
                            
                            <!-- Password Strength Indicator -->
                            <div id="password-strength" class="mt-2" style="display: none;">
                                <small class="text-muted">Password Strength:</small>
                                <div class="progress" style="height: 5px;">
                                    <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="strength-text" class="text-muted"></small>
                            </div>
                        </div>
                        
                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold">
                                <i class="bi bi-key-fill"></i> Confirm New Password *
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter new password"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('confirm_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="password-match" class="form-text"></div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="alert alert-info">
                            <small>
                                <strong>Password Requirements:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>At least <?php echo PASSWORD_MIN_LENGTH; ?> characters long</li>
                                    <li>Contains uppercase and lowercase letters</li>
                                    <li>Contains at least one number</li>
                                    <li>Contains at least one special character (!@#$%^&*)</li>
                                </ul>
                            </small>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?php echo url('profile/edit.php'); ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Profile
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Change Password
                            </button>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            
            <!-- Security Tips -->
            <div class="alert alert-warning mt-4">
                <h6 class="alert-heading">
                    <i class="bi bi-shield-exclamation"></i> Security Tips
                </h6>
                <ul class="mb-0">
                    <li>Never share your password with anyone</li>
                    <li>Use a unique password for this account</li>
                    <li>Change your password regularly</li>
                    <li>Avoid using personal information in your password</li>
                </ul>
            </div>
            
        </div>
    </div>
</div>

<!-- Password Toggle and Strength Script -->
<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
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

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (password.length === 0) {
        strengthDiv.style.display = 'none';
        return;
    }
    
    strengthDiv.style.display = 'block';
    
    let strength = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) strength++;
    else feedback.push('longer');
    
    // Lowercase check
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('lowercase');
    
    // Uppercase check
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('uppercase');
    
    // Number check
    if (/\d/.test(password)) strength++;
    else feedback.push('number');
    
    // Special character check
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    else feedback.push('special char');
    
    // Update strength bar
    const percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    
    // Update colors and text
    if (strength <= 1) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Weak - Add: ' + feedback.join(', ');
        strengthText.className = 'text-danger';
    } else if (strength <= 3) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Fair - Add: ' + feedback.join(', ');
        strengthText.className = 'text-warning';
    } else if (strength <= 4) {
        strengthBar.className = 'progress-bar bg-info';
        strengthText.textContent = 'Good';
        strengthText.className = 'text-info';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong';
        strengthText.className = 'text-success';
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Passwords do not match</span>';
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>