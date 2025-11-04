<?php
/**
 * Edit Profile Page
 * 
 * Allows users to edit their profile information
 * 
 * Features:
 * - Update username (with spaces allowed)
 * - Update email
 * - Update bio
 * - Change profile picture
 * - Link to change password
 * - CSRF protection
 * - Validation
 * 
 * @author Your Name
 * @version 1.1
 */

// Set page title
$pageTitle = "Edit Profile";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Initialize variables
$errors = [];
$success = false;

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage("User not found.", 'danger');
        header('Location: ' . url('index.php'));
        exit();
    }
} catch (PDOException $e) {
    error_log("Get user error: " . $e->getMessage());
    setFlashMessage("An error occurred. Please try again.", 'danger');
    header('Location: ' . url('index.php'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get and sanitize inputs
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Username validation (NOW ALLOWS SPACES)
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < USERNAME_MIN_LENGTH) {
            $errors[] = "Username must be at least " . USERNAME_MIN_LENGTH . " characters.";
        } elseif (strlen($username) > USERNAME_MAX_LENGTH) {
            $errors[] = "Username must not exceed " . USERNAME_MAX_LENGTH . " characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_\s]+$/u', $username)) {
            $errors[] = "Username can only contain letters, numbers, underscores, and spaces.";
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } elseif (strlen($email) > EMAIL_MAX_LENGTH) {
            $errors[] = "Email must not exceed " . EMAIL_MAX_LENGTH . " characters.";
        }
        
        // If no validation errors, check database
        if (empty($errors)) {
            try {
                // Check if username already exists (excluding current user)
                if ($username !== $user['username']) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, getCurrentUserId()]);
                    if ($stmt->rowCount() > 0) {
                        $errors[] = "Username already taken. Please choose another.";
                    }
                }
                
                // Check if email already exists (excluding current user)
                if ($email !== $user['email']) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, getCurrentUserId()]);
                    if ($stmt->rowCount() > 0) {
                        $errors[] = "Email already registered. Please use another.";
                    }
                }
                
                // Handle profile picture upload
                $profilePicture = $user['profile_picture'];
                
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleProfilePictureUpload($_FILES['profile_picture']);
                    
                    if ($uploadResult['success']) {
                        // Delete old profile picture if it's not the default
                        if ($user['profile_picture'] !== DEFAULT_AVATAR) {
                            $oldImagePath = AVATAR_PATH . '/' . $user['profile_picture'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $profilePicture = $uploadResult['filename'];
                    } else {
                        $errors[] = $uploadResult['error'];
                    }
                }
                
                // Update user if no errors
                if (empty($errors)) {
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, bio = ?, profile_picture = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([$username, $email, $bio, $profilePicture, getCurrentUserId()]);
                    
                    // Update session data
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['profile_picture'] = $profilePicture;
                    
                    // Success
                    setFlashMessage(MSG_PROFILE_UPDATED, 'success');
                    header('Location: ' . url('profile/view.php'));
                    exit();
                }
                
            } catch (PDOException $e) {
                error_log("Update profile error: " . $e->getMessage());
                $errors[] = "An error occurred while updating your profile. Please try again.";
            }
        }
    }
}

/**
 * Handle profile picture upload (WITHOUT RESIZING - No GD required)
 * 
 * This version works without GD extension
 * Images are uploaded at their original size
 * 
 * @param array $file Uploaded file from $_FILES
 * @return array Result with success status and filename or error
 */
function handleProfilePictureUpload($file) {
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => MSG_FILE_TOO_LARGE];
    }
    
    // Check file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => MSG_INVALID_FILE_TYPE];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_MIMES)) {
        return ['success' => false, 'error' => MSG_INVALID_FILE_TYPE];
    }
    
    // Generate unique filename
    $newFilename = uniqid('avatar_', true) . '.' . $fileExtension;
    $uploadPath = AVATAR_PATH . '/' . $newFilename;
    
    // Create directory if it doesn't exist
    if (!is_dir(AVATAR_PATH)) {
        mkdir(AVATAR_PATH, 0755, true);
    }
    
    // Move uploaded file (NO RESIZING)
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $newFilename];
    } else {
        return ['success' => false, 'error' => MSG_UPLOAD_FAILED];
    }
}

// Include header
require_once '../includes/header.php';
?>

<!-- Edit Profile Page -->
<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="display-5 fw-bold">
                    <i class="bi bi-person-circle text-primary"></i> Edit Profile
                </h1>
                <p class="text-muted">Update your account information</p>
            </div>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Success!</strong> Your profile has been updated.
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
            
            <!-- Edit Profile Form -->
            <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                
                <!-- CSRF Token -->
                <?php echo csrfField(); ?>
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        
                        <!-- Current Profile Picture -->
                        <div class="text-center mb-4">
                            <img src="<?php echo upload('avatar', $user['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="rounded-circle shadow"
                                 id="current-avatar"
                                 style="width: 150px; height: 150px; object-fit: cover; border: 5px solid #fff;">
                        </div>
                        
                        <!-- Profile Picture Upload -->
                        <div class="mb-4">
                            <label for="profile_picture" class="form-label fw-bold">
                                <i class="bi bi-image"></i> Change Profile Picture
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="profile_picture" 
                                   name="profile_picture" 
                                   accept="image/*">
                            <div class="form-text">
                                Max size: <?php echo MAX_UPLOAD_SIZE_MB; ?>MB. 
                                <strong>Please upload a square image (500x500px recommended)</strong>. 
                                Formats: JPG, PNG, GIF, WEBP
                            </div>
                            <div id="image-preview" class="mt-3"></div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Username (NOW ALLOWS SPACES) -->
                        <div class="mb-4">
                            <label for="username" class="form-label fw-bold">
                                <i class="bi bi-person"></i> Username *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   pattern="[a-zA-Z0-9_\s]{3,50}"
                                   minlength="<?php echo USERNAME_MIN_LENGTH; ?>"
                                   maxlength="<?php echo USERNAME_MAX_LENGTH; ?>"
                                   required>
                            <div class="form-text">
                                <?php echo USERNAME_MIN_LENGTH; ?>-<?php echo USERNAME_MAX_LENGTH; ?> characters, letters, numbers, underscores, and spaces allowed
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">
                                <i class="bi bi-envelope"></i> Email Address *
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   maxlength="<?php echo EMAIL_MAX_LENGTH; ?>"
                                   required>
                        </div>
                        
                        <!-- Bio -->
                        <div class="mb-4">
                            <label for="bio" class="form-label fw-bold">
                                <i class="bi bi-textarea-t"></i> Bio
                            </label>
                            <textarea class="form-control" 
                                      id="bio" 
                                      name="bio" 
                                      rows="4" 
                                      maxlength="500"
                                      placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <div class="form-text">Maximum 500 characters</div>
                        </div>
                        
                        <!-- Password Change Link -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Want to change your password? 
                            <a href="<?php echo url('profile/change_password.php'); ?>" class="alert-link">
                                Click here
                            </a>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo url('profile/view.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
</div>

<!-- Image Preview Script -->
<script>
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    const currentAvatar = document.getElementById('current-avatar');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="text-center border rounded p-3">
                    <p class="mb-2"><strong>New Profile Picture Preview:</strong></p>
                    <img src="${e.target.result}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                </div>
            `;
        };
        
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>