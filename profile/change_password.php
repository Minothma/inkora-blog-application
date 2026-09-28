<?php
/**
 * Change Password Page
 * Theme: Nordic Slate & Deep Indigo
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

<!-- Hero Banner -->
<div class="password-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <span class="badge-pill badge-gradient mb-2 d-inline-block">
                    <i class="bi bi-shield-lock-fill me-1"></i> Security Center
                </span>
                <h1 class="text-white fw-bold display-6 mb-1">Change Password</h1>
                <p class="text-slate-300 mb-0">Update and strengthen your account credentials.</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 14px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Success!</strong> Your password has been changed successfully.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 14px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 ps-3 mt-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Change Password Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; border: 1px solid #E2E8F0; overflow: hidden;">
                <div class="card-body p-4 p-md-5">
                    
                    <form method="POST" action="" class="needs-validation" novalidate id="changePasswordForm">
                        
                        <!-- CSRF Token -->
                        <?php echo csrfField(); ?>
                        
                        <!-- Current Password -->
                        <div class="mb-4">
                            <label for="current_password" class="form-label text-slate-800 fw-bold small">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control border-start-0 border-end-0" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Enter your current password"
                                       required>
                                <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                        type="button" 
                                        onclick="togglePassword('current_password', this)"
                                        style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <hr class="my-4" style="border-color: #F1F5F9;">
                        
                        <!-- New Password -->
                        <div class="mb-4">
                            <label for="new_password" class="form-label text-slate-800 fw-bold small">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="password" 
                                       class="form-control border-start-0 border-end-0" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="At least 8 characters"
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                       required>
                                <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                        type="button" 
                                        onclick="togglePassword('new_password', this)"
                                        style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Strength Indicator -->
                            <div id="password-strength" class="mt-2" style="display: none;">
                                <div class="progress mb-1" style="height: 6px; border-radius: 4px; background: #F1F5F9;">
                                    <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="strength-text" class="text-muted fw-semibold"></small>
                            </div>
                        </div>
                        
                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label text-slate-800 fw-bold small">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                    <i class="bi bi-key-fill"></i>
                                </span>
                                <input type="password" 
                                       class="form-control border-start-0 border-end-0" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Re-enter new password"
                                       required>
                                <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                        type="button" 
                                        onclick="togglePassword('confirm_password', this)"
                                        style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="password-match" class="form-text small mt-1"></div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?php echo url('profile/edit.php'); ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px;">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 600;">
                                <i class="bi bi-shield-check me-1"></i> Update Password
                            </button>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.password-hero {
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
</style>

<script>
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
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    const percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    
    if (strength <= 2) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Weak Password';
        strengthText.className = 'text-danger small';
    } else if (strength <= 3) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Fair Password';
        strengthText.className = 'text-warning small';
    } else if (strength === 4) {
        strengthBar.className = 'progress-bar bg-info';
        strengthText.textContent = 'Good Password';
        strengthText.className = 'text-info small';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong Password';
        strengthText.className = 'text-success small';
    }
});

document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i> Passwords do not match</span>';
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>