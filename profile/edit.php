<?php
/**
 * Edit Profile Page
 * Theme: Nordic Slate & Deep Indigo
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
 * Handle profile picture upload
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

<!-- Hero Banner -->
<div class="edit-profile-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <span class="badge-pill badge-gradient mb-2 d-inline-block">
                    <i class="bi bi-gear-fill me-1"></i> Account Settings
                </span>
                <h1 class="text-white fw-bold display-6 mb-1">Edit Profile</h1>
                <p class="text-slate-300 mb-0">Update your personal details, biography, and avatar.</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
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
            
            <!-- Edit Profile Form -->
            <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                
                <!-- CSRF Token -->
                <?php echo csrfField(); ?>
                
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; border: 1px solid #E2E8F0; overflow: hidden;">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Current Profile Picture and Upload -->
                        <div class="d-flex flex-column flex-sm-row align-items-center gap-4 mb-4 pb-4 border-bottom" style="border-color: #F1F5F9;">
                            <div class="avatar-edit-preview flex-shrink-0">
                                <?php if (!empty($user['profile_picture']) && $user['profile_picture'] !== DEFAULT_AVATAR): ?>
                                    <img src="<?php echo upload('avatar', $user['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($user['username']); ?>"
                                         id="current-avatar"
                                         class="avatar-img-circle">
                                <?php else: ?>
                                    <div class="avatar-initials-circle" id="current-avatar-initials">
                                        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-grow-1 w-100">
                                <label for="profile_picture" class="form-label text-slate-800 fw-bold small">
                                    Change Profile Photo
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="profile_picture" 
                                       name="profile_picture" 
                                       accept="image/*"
                                       style="border-radius: 10px; border-color: #CBD5E1;">
                                <div class="form-text small text-muted mt-1">
                                    JPG, PNG, GIF, WEBP. Max <?php echo MAX_UPLOAD_SIZE_MB; ?>MB. Square image recommended.
                                </div>
                                <div id="image-preview" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <!-- Username -->
                        <div class="mb-4">
                            <label for="username" class="form-label text-slate-800 fw-bold small">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   pattern="[a-zA-Z0-9_\s]{3,50}"
                                   minlength="<?php echo USERNAME_MIN_LENGTH; ?>"
                                   maxlength="<?php echo USERNAME_MAX_LENGTH; ?>"
                                   style="border-radius: 10px; border-color: #CBD5E1;"
                                   required>
                            <div class="form-text small text-muted">
                                <?php echo USERNAME_MIN_LENGTH; ?>-<?php echo USERNAME_MAX_LENGTH; ?> characters (letters, numbers, underscores, spaces).
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label text-slate-800 fw-bold small">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   maxlength="<?php echo EMAIL_MAX_LENGTH; ?>"
                                   style="border-radius: 10px; border-color: #CBD5E1;"
                                   required>
                        </div>
                        
                        <!-- Bio -->
                        <div class="mb-4">
                            <label for="bio" class="form-label text-slate-800 fw-bold small">
                                Bio / Description
                            </label>
                            <textarea class="form-control" 
                                      id="bio" 
                                      name="bio" 
                                      rows="4" 
                                      maxlength="500" 
                                      placeholder="Share a short bio about yourself..."
                                      style="border-radius: 10px; border-color: #CBD5E1;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <div class="form-text small text-muted">Maximum 500 characters.</div>
                        </div>
                        
                        <!-- Security Shortcut -->
                        <div class="p-3 bg-slate-50 rounded-3 d-flex align-items-center justify-content-between border" style="border-color: #E2E8F0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock text-indigo fs-5"></i>
                                <span class="small text-slate-700">Need to update your account password?</span>
                            </div>
                            <a href="<?php echo url('profile/change_password.php'); ?>" class="btn btn-outline-secondary btn-sm px-3" style="border-radius: 8px;">
                                Change Password
                            </a>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo url('profile/view.php'); ?>" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-check2 me-1"></i> Save Changes
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
</div>

<style>
.edit-profile-hero {
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
.avatar-edit-preview {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 3px solid #EEF2FF;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.avatar-img-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.avatar-initials-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
}
.text-indigo {
    color: #4F46E5;
}
.bg-slate-50 {
    background-color: #F8FAFC;
}
</style>

<script>
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="small text-slate-600 fw-semibold">Preview:</span>
                    <img src="${e.target.result}" class="rounded-circle" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #4F46E5;">
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