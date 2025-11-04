<?php
/**
 * User Registration Page
 * 
 * This page handles new user registration with validation
 * Beautiful cyan-to-purple gradient design matching homepage
 * 
 * Features:
 * - Username, email, and password validation
 * - Password strength checking
 * - Duplicate email/username prevention
 * - Password hashing with bcrypt
 * - Profile picture upload (optional)
 * - CSRF protection
 * - Beautiful gradient UI
 * 
 * @author Your Name
 * @version 2.0 - Cyan to Purple Gradient Design
 */

// Set page title
$pageTitle = "Register";

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
$username = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Sanitize and validate inputs
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $bio = trim($_POST['bio'] ?? '');
        
        // Username validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < USERNAME_MIN_LENGTH) {
            $errors[] = "Username must be at least " . USERNAME_MIN_LENGTH . " characters.";
        } elseif (strlen($username) > USERNAME_MAX_LENGTH) {
            $errors[] = "Username must not exceed " . USERNAME_MAX_LENGTH . " characters.";
        } elseif (!preg_match(USERNAME_PATTERN, $username)) {
            $errors[] = "Username can only contain letters, numbers,spaces and underscores.";
        } elseif (preg_match('/^\s|\s$/', $username)) {
            $errors[] = "Username cannot start or end with spaces.";
        } elseif (preg_match('/\s{2,}/', $username)) {
            $errors[] = "Username cannot contain consecutive spaces.";
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } elseif (strlen($email) > EMAIL_MAX_LENGTH) {
            $errors[] = "Email must not exceed " . EMAIL_MAX_LENGTH . " characters.";
        }
        
        // Password validation
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
        } else {
            // Check password strength
            if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain at least one uppercase letter.";
            }
            if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
                $errors[] = "Password must contain at least one lowercase letter.";
            }
            if (PASSWORD_REQUIRE_NUMBER && !preg_match('/\d/', $password)) {
                $errors[] = "Password must contain at least one number.";
            }
            if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $errors[] = "Password must contain at least one special character.";
            }
        }
        
        // Confirm password validation
        if (empty($confirmPassword)) {
            $errors[] = "Please confirm your password.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        // If no validation errors, check database
        if (empty($errors)) {
            try {
                // Check if username already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Username already taken. Please choose another.";
                }
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Email already registered. Please use another or login.";
                }
                
                // If still no errors, proceed with registration
                if (empty($errors)) {
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Handle profile picture upload
                    $profilePicture = null;
                    
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = handleProfilePictureUpload($_FILES['profile_picture']);
                        
                        if ($uploadResult['success']) {
                            $profilePicture = $uploadResult['filename'];
                        } else {
                            $errors[] = $uploadResult['error'];
                        }
                    }
                    
                    // Insert new user if no upload errors
                    if (empty($errors)) {
                        $stmt = $conn->prepare("
                            INSERT INTO users (username, email, password, bio, profile_picture, role, created_at) 
                            VALUES (?, ?, ?, ?, ?, 'user', NOW())
                        ");
                        
                        $stmt->execute([$username, $email, $hashedPassword, $bio, $profilePicture]);
                        
                        // Registration successful
                        $success = true;
                        setFlashMessage(MSG_REGISTER_SUCCESS, 'success');
                        
                        // Redirect to login page after 2 seconds
                        header("refresh:2;url=" . url('auth/login.php'));
                    }
                }
                
            } catch (PDOException $e) {
                // Log error and show generic message
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "An error occurred during registration. Please try again.";
            }
        }
    }
}

/**
 * Handle Profile Picture Upload
 */
function handleProfilePictureUpload($file) {
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => MSG_FILE_TOO_LARGE];
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => MSG_INVALID_FILE_TYPE];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_MIMES)) {
        return ['success' => false, 'error' => MSG_INVALID_FILE_TYPE];
    }
    
    $newFilename = uniqid('avatar_', true) . '.' . $fileExtension;
    $uploadPath = AVATAR_PATH . '/' . $newFilename;
    
    if (!is_dir(AVATAR_PATH)) {
        mkdir(AVATAR_PATH, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $newFilename];
    } else {
        return ['success' => false, 'error' => MSG_UPLOAD_FAILED];
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

.register-wrapper {
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    padding: 3rem 0;
}

.register-card {
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

.register-icon {
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

.register-icon i {
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

.form-text {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
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

.register-card a {
    color: var(--gradient-purple);
    font-weight: 500;
    transition: all 0.2s ease;
}

.register-card a:hover {
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

.login-link-box {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 1.5rem;
}

@media (max-width: 576px) {
    .register-card {
        border-radius: 16px;
        margin: 1rem;
    }
    .register-icon {
        width: 60px;
        height: 60px;
    }
    .register-icon i {
        font-size: 2rem;
    }
}
</style>

<!-- Register Form -->
<div class="register-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                
                <!-- Register Card -->
                <div class="register-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="register-icon">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Create Account</h2>
                            <p class="text-muted mb-0">Join Inkora and start sharing your stories</p>
                        </div>
                        
                        <!-- Success Message -->
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Success!</strong> Your account has been created successfully.
                                <br><small>Redirecting to login page...</small>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <!-- Registration Form -->
                        <?php if (!$success): ?>
                            <form method="POST" action="" enctype="multipart/form-data" id="registerForm" class="needs-validation" novalidate>
                                
                                <!-- CSRF Token -->
                                <?php echo csrfField(); ?>
                                
                                <!-- Username -->
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="bi bi-person-fill me-1"></i> Username
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>"
                                           placeholder="Enter your full name or username"
                                           minlength="<?php echo USERNAME_MIN_LENGTH; ?>"
                                           maxlength="<?php echo USERNAME_MAX_LENGTH; ?>"
                                           required>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php echo USERNAME_MIN_LENGTH; ?>-<?php echo USERNAME_MAX_LENGTH; ?> characters, letters, numbers, and underscores only
                                    </div>
                                </div>
                                
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope-fill me-1"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>"
                                           placeholder="your.email@example.com"
                                           maxlength="<?php echo EMAIL_MAX_LENGTH; ?>"
                                           required>
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
                                               placeholder="Create a strong password"
                                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePasswordVisibility('password', 'toggleIcon1')">
                                            <i class="bi bi-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters with uppercase, lowercase, number, and special character
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
                                               placeholder="Re-enter your password"
                                               required>
                                        <button class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')">
                                            <i class="bi bi-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Bio (Optional) -->
                                <div class="mb-3">
                                    <label for="bio" class="form-label">
                                        <i class="bi bi-textarea-t me-1"></i> Bio (Optional)
                                    </label>
                                    <textarea class="form-control" 
                                              id="bio" 
                                              name="bio" 
                                              rows="3" 
                                              placeholder="Tell us about yourself..."
                                              maxlength="500"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Maximum 500 characters
                                    </div>
                                </div>
                                
                                <!-- Profile Picture (Optional) -->
                                <div class="mb-4">
                                    <label for="profile_picture" class="form-label">
                                        <i class="bi bi-image me-1"></i> Profile Picture (Optional)
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="profile_picture" 
                                           name="profile_picture" 
                                           accept="image/*">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Max size: <?php echo MAX_UPLOAD_SIZE_MB; ?>MB. Formats: JPG, PNG, GIF, WEBP
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-gradient-primary btn-lg">
                                        <i class="bi bi-person-plus-fill me-2"></i> Create Account
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Divider -->
                            <div class="divider">
                                <span>or</span>
                            </div>
                            
                            <!-- Login Link -->
                            <div class="login-link-box">
                                <p class="mb-2">Already have an account?</p>
                                <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-secondary w-100" style="border-radius: 12px; font-weight: 600;">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                                </a>
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
function togglePasswordVisibility(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
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

// Username real-time validation
document.getElementById('username')?.addEventListener('input', function(e) {
    const username = e.target.value;
    
    // Remove any existing feedback
    const existingFeedback = e.target.parentElement.querySelector('.invalid-feedback, .valid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    e.target.classList.remove('is-invalid', 'is-valid');
    
    if (username.length === 0) return;
    
    const feedback = document.createElement('div');
    
    // Check for leading/trailing spaces
    if (username !== username.trim()) {
        feedback.className = 'invalid-feedback d-block';
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i>Username cannot start or end with spaces';
        e.target.classList.add('is-invalid');
        e.target.parentElement.appendChild(feedback);
        return;
    }
    
    // Check for consecutive spaces
    if (/\s{2,}/.test(username)) {
        feedback.className = 'invalid-feedback d-block';
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i>Username cannot contain consecutive spaces';
        e.target.classList.add('is-invalid');
        e.target.parentElement.appendChild(feedback);
        return;
    }
    
    // Check for invalid characters
    if (!/^[a-zA-Z0-9_ ]+$/.test(username)) {
        feedback.className = 'invalid-feedback d-block';
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i>Only letters, numbers, underscores, and spaces allowed';
        e.target.classList.add('is-invalid');
        e.target.parentElement.appendChild(feedback);
        return;
    }
    
    // Check minimum length
    if (username.length < <?php echo USERNAME_MIN_LENGTH; ?>) {
        feedback.className = 'invalid-feedback d-block';
        feedback.innerHTML = '<i class="bi bi-x-circle me-1"></i>Username must be at least <?php echo USERNAME_MIN_LENGTH; ?> characters';
        e.target.classList.add('is-invalid');
        e.target.parentElement.appendChild(feedback);
        return;
    }
    
    // Valid username
    feedback.className = 'valid-feedback d-block';
    feedback.innerHTML = '<i class="bi bi-check-circle me-1"></i>Username looks good!';
    e.target.classList.add('is-valid');
    e.target.parentElement.appendChild(feedback);
});

// Form submission loading state
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...';
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-success)');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>