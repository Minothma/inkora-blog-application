<?php

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
            $errors[] = "Username can only contain letters, numbers, spaces and underscores.";
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

<div class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                
                <!-- Register Card -->
                <div class="card auth-card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="auth-icon-badge mb-3">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-1" style="color: #0F172A; font-size: 1.6rem; letter-spacing: -0.02em;">Create an Account</h2>
                            <p class="text-muted small">Join Inkora to publish stories and follow writers</p>
                        </div>
                        
                        <!-- Success Message -->
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                    <div>
                                        <strong>Success!</strong> Registration completed. Redirecting to login...
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <!-- Register Form -->
                        <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                            
                            <!-- CSRF Token -->
                            <?php echo csrfField(); ?>
                            
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label text-slate-700 fw-semibold small">
                                    Username *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>"
                                           placeholder="Choose your handle"
                                           style="border-radius: 0 10px 10px 0;"
                                           required
                                           autofocus>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label text-slate-700 fw-semibold small">
                                    Email Address *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control border-start-0" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>"
                                           placeholder="name@example.com"
                                           style="border-radius: 0 10px 10px 0;"
                                           required>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label text-slate-700 fw-semibold small">
                                    Password *
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
                                           required>
                                    <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                            type="button" 
                                            onclick="togglePasswordVisibility('password', 'toggleIcon1')"
                                            style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                        <i class="bi bi-eye" id="toggleIcon1"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label text-slate-700 fw-semibold small">
                                    Confirm Password *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-slate-400" style="border-radius: 10px 0 0 10px; border-color: #CBD5E1;">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0 border-end-0" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Re-enter password"
                                           required>
                                    <button class="btn btn-outline-secondary border-start-0 text-slate-400" 
                                            type="button" 
                                            onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')"
                                            style="border-radius: 0 10px 10px 0; border-color: #CBD5E1;">
                                        <i class="bi bi-eye" id="toggleIcon2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Optional Avatar Upload -->
                            <div class="mb-4">
                                <label for="profile_picture" class="form-label text-slate-700 fw-semibold small">
                                    Profile Picture <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="profile_picture" 
                                       name="profile_picture" 
                                       accept="image/*">
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; font-size: 1rem; padding: 0.75rem;">
                                    <span>Create Account</span>
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Divider -->
                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1 my-0" style="border-color: #E2E8F0;">
                            <span class="px-3 text-muted small">Already registered?</span>
                            <hr class="flex-grow-1 my-0" style="border-color: #E2E8F0;">
                        </div>
                        
                        <!-- Login Link -->
                        <div class="text-center">
                            <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-secondary w-100" style="border-radius: 10px; font-weight: 600; padding: 0.65rem;">
                                Sign In Instead
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