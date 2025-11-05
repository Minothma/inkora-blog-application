<?php

// Set page title
$pageTitle = "Edit Post";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage(MSG_POST_NOT_FOUND, 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

// Initialize variables
$errors = [];
$post = null;

try {
    // Get post data
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    // Check if post exists
    if (!$post) {
        setFlashMessage(MSG_POST_NOT_FOUND, 'danger');
        header('Location: ' . url('posts/index.php'));
        exit();
    }
    
    // Check if user is the author
    if ($post['user_id'] != getCurrentUserId()) {
        setFlashMessage(MSG_UNAUTHORIZED, 'danger');
        header('Location: ' . url('posts/view.php?id=' . $postId));
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Edit post fetch error: " . $e->getMessage());
    setFlashMessage("An error occurred. Please try again.", 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get and sanitize inputs
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $status = $_POST['status'] ?? 'published';
        
        // Title validation
        if (empty($title)) {
            $errors[] = "Title is required.";
        } elseif (strlen($title) < POST_TITLE_MIN_LENGTH) {
            $errors[] = "Title must be at least " . POST_TITLE_MIN_LENGTH . " characters.";
        } elseif (strlen($title) > POST_TITLE_MAX_LENGTH) {
            $errors[] = "Title must not exceed " . POST_TITLE_MAX_LENGTH . " characters.";
        }
        
        // Content validation
        if (empty($content)) {
            $errors[] = "Content is required.";
        } elseif (strlen(strip_tags($content)) < POST_CONTENT_MIN_LENGTH) {
            $errors[] = "Content must be at least " . POST_CONTENT_MIN_LENGTH . " characters.";
        }
        
        // Status validation
        if (!in_array($status, ['draft', 'published'])) {
            $status = 'published';
        }
        
        // If no validation errors, proceed
        if (empty($errors)) {
            try {
                // Generate slug if title changed
                $slug = $post['slug'];
                if ($title !== $post['title']) {
                    $slug = generateSlug($title);
                    
                    // Check if new slug already exists (excluding current post)
                    $slugExists = true;
                    $slugCounter = 1;
                    $originalSlug = $slug;
                    
                    while ($slugExists) {
                        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
                        $stmt->execute([$slug, $postId]);
                        
                        if ($stmt->rowCount() > 0) {
                            $slug = $originalSlug . '-' . $slugCounter;
                            $slugCounter++;
                        } else {
                            $slugExists = false;
                        }
                    }
                }
                
                // Auto-generate excerpt if empty
                if (empty($excerpt)) {
                    $excerpt = generateExcerptFromContent($content, 200);
                }
                
                // Handle featured image upload
                $featuredImage = $post['featured_image'];
                
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleImageUpload($_FILES['featured_image'], 'blog');
                    
                    if ($uploadResult['success']) {
                        // Delete old image if exists
                        if (!empty($post['featured_image'])) {
                            $oldImagePath = BLOG_IMG_PATH . '/' . $post['featured_image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $featuredImage = $uploadResult['filename'];
                    } else {
                        $errors[] = $uploadResult['error'];
                    }
                }
                
                // Update blog post if no upload errors
                if (empty($errors)) {
                    $stmt = $conn->prepare("
                        UPDATE blog_posts 
                        SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $title,
                        $slug,
                        $content,
                        $excerpt,
                        $featuredImage,
                        $status,
                        $postId
                    ]);
                    
                    // Success
                    setFlashMessage(MSG_POST_UPDATED, 'success');
                    header("Location: " . url('posts/view.php?id=' . $postId));
                    exit();
                }
                
            } catch (PDOException $e) {
                error_log("Update post error: " . $e->getMessage());
                $errors[] = "An error occurred while updating the post. Please try again.";
            }
        }
        
        // If there were errors, update post array with submitted values
        if (!empty($errors)) {
            $post['title'] = $title;
            $post['content'] = $content;
            $post['excerpt'] = $excerpt;
            $post['status'] = $status;
        }
    }
}

// Helper functions (same as create.php)
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = str_replace(' ', '-', $slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function generateExcerptFromContent($content, $length = 200) {
    $text = strip_tags($content);
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

function handleImageUpload($file, $type = 'blog') {
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
    
    $newFilename = uniqid($type . '_', true) . '.' . $fileExtension;
    $uploadPath = ($type === 'avatar' ? AVATAR_PATH : BLOG_IMG_PATH) . '/' . $newFilename;
    
    $dir = ($type === 'avatar' ? AVATAR_PATH : BLOG_IMG_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
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

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Edit Post Form -->
<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="display-5 fw-bold">
                    <i class="bi bi-pencil-square text-primary"></i> Edit Post
                </h1>
                <p class="text-muted">Update your story</p>
            </div>
            
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
            
            <!-- Edit Post Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="editPostForm">
                
                <!-- CSRF Token -->
                <?php echo csrfField(); ?>
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        
                        <!-- Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">
                                <i class="bi bi-type"></i> Title *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($post['title']); ?>"
                                   placeholder="Give your story a compelling title..."
                                   minlength="<?php echo POST_TITLE_MIN_LENGTH; ?>"
                                   maxlength="<?php echo POST_TITLE_MAX_LENGTH; ?>"
                                   required>
                            <div class="form-text">
                                <?php echo POST_TITLE_MIN_LENGTH; ?>-<?php echo POST_TITLE_MAX_LENGTH; ?> characters
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="mb-4">
                            <label for="content" class="form-label fw-bold">
                                <i class="bi bi-file-text"></i> Content *
                            </label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="20"><?php echo htmlspecialchars($post['content']); ?></textarea>
                            <div class="form-text">
                                Minimum <?php echo POST_CONTENT_MIN_LENGTH; ?> characters. Use the editor toolbar to format your content.
                            </div>
                        </div>
                        
                        <!-- Current Featured Image -->
                        <?php if (!empty($post['featured_image'])): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Featured Image</label>
                                <div>
                                    <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                         alt="Current featured image" 
                                         class="img-thumbnail" 
                                         style="max-height: 200px;">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Featured Image -->
                        <div class="mb-4">
                            <label for="featured_image" class="form-label fw-bold">
                                <i class="bi bi-image"></i> Change Featured Image (Optional)
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="featured_image" 
                                   name="featured_image" 
                                   accept="image/*">
                            <div class="form-text">
                                Leave empty to keep current image. Max size: <?php echo MAX_UPLOAD_SIZE_MB; ?>MB. Formats: JPG, PNG, GIF, WEBP
                            </div>
                            <div id="image-preview" class="mt-3"></div>
                        </div>
                        
                        <!-- Status -->
                        <div class="mb-4">
                            <label for="status" class="form-label fw-bold">
                                <i class="bi bi-check-circle"></i> Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="published" <?php echo ($post['status'] === 'published') ? 'selected' : ''; ?>>
                                    Published
                                </option>
                                <option value="draft" <?php echo ($post['status'] === 'draft') ? 'selected' : ''; ?>>
                                    Draft
                                </option>
                            </select>
                            <div class="form-text">
                                Published posts are visible to everyone. Drafts are only visible to you.
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo url('posts/view.php?id=' . $postId); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" name="status" value="draft" class="btn btn-outline-primary me-2">
                            <i class="bi bi-save"></i> Save as Draft
                        </button>
                        <button type="submit" name="status" value="published" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update & Publish
                        </button>
                    </div>
                </div>
                
            </form>
            
        </div>
    </div>
</div>

<!-- Initialize TinyMCE Editor -->
<script>
tinymce.init({
    selector: '#content',
    height: 500,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | removeformat | help',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    branding: false,
    setup: function(editor) {
        editor.on('init', function() {
            console.log('TinyMCE initialized');
        });
    }
});

// Fix form submission
document.getElementById('editPostForm').addEventListener('submit', function(e) {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
    
    const submitBtns = this.querySelectorAll('button[type="submit"]');
    submitBtns.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    });
});

// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="border rounded p-2">
                    <p class="mb-2"><strong>New Preview:</strong></p>
                    <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 300px;">
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