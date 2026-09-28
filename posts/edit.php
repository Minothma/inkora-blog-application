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

// Helper functions
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
<script src="https://cdn.tiny.cloud/1/3pois542gphm7g1bk1cquotogq9pzfqqx0duum3ww2lymwbu/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Hero Section -->
<div class="edit-hero">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge-pill badge-gradient mb-2 d-inline-block">
                    <i class="bi bi-pencil-fill me-1"></i> Editor Studio
                </span>
                <h1 class="text-white fw-bold display-6 mb-1">Edit Story</h1>
                <p class="text-slate-300 mb-0">Refine your writing, update images, and manage publication settings.</p>
            </div>
            <div>
                <a href="<?php echo url('posts/view.php?id=' . $postId); ?>" class="btn btn-outline-light px-3 py-2" style="border-radius: 10px;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Story
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 14px;">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>
                    <strong>Please resolve the following:</strong>
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
    
    <!-- Edit Form -->
    <form method="POST" action="" enctype="multipart/form-data" id="editPostForm">
        
        <!-- CSRF Token -->
        <?php echo csrfField(); ?>
        
        <div class="row g-4">
            
            <!-- Left Main Column (Title & Content) -->
            <div class="col-lg-8">
                
                <div class="form-card mb-4">
                    <div class="p-4">
                        <!-- Story Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label text-slate-800 fw-bold">
                                Story Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg fw-bold" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo htmlspecialchars($post['title']); ?>"
                                   placeholder="Title of your story..."
                                   minlength="<?php echo POST_TITLE_MIN_LENGTH; ?>"
                                   maxlength="<?php echo POST_TITLE_MAX_LENGTH; ?>"
                                   style="font-size: 1.35rem; border-radius: 12px; border-color: #CBD5E1;"
                                   required>
                        </div>
                        
                        <!-- Story Content -->
                        <div class="mb-3">
                            <label for="content" class="form-label text-slate-800 fw-bold">
                                Content <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="18"><?php echo htmlspecialchars($post['content']); ?></textarea>
                        </div>
                        
                        <!-- Excerpt / Summary -->
                        <div class="mt-4">
                            <label for="excerpt" class="form-label text-slate-800 fw-bold">
                                Short Summary / Excerpt <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <textarea class="form-control" 
                                      id="excerpt" 
                                      name="excerpt" 
                                      rows="3" 
                                      placeholder="A short hook summarizing this story for preview cards..."
                                      style="border-radius: 10px; border-color: #CBD5E1;"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                            <small class="text-muted">If left blank, an excerpt will be generated automatically.</small>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Right Sidebar Column (Publish & Cover Image) -->
            <div class="col-lg-4">
                
                <!-- Publishing Controls Card -->
                <div class="form-card mb-4">
                    <div class="p-4 border-bottom" style="border-color: #E2E8F0;">
                        <h6 class="fw-bold text-slate-900 mb-0">
                            <i class="bi bi-gear-wide-connected text-indigo me-2"></i>Publishing Settings
                        </h6>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <label for="status" class="form-label text-slate-700 fw-semibold small">Story Status</label>
                            <select class="form-select" id="status" name="status" style="border-radius: 10px; border-color: #CBD5E1;">
                                <option value="published" <?php echo ($post['status'] === 'published') ? 'selected' : ''; ?>>
                                    🚀 Published (Public)
                                </option>
                                <option value="draft" <?php echo ($post['status'] === 'draft') ? 'selected' : ''; ?>>
                                    📝 Draft (Private)
                                </option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; font-size: 1rem;">
                                <i class="bi bi-check2-circle me-1"></i> Save & Update Story
                            </button>
                            <a href="<?php echo url('posts/my_posts.php'); ?>" class="btn btn-outline-secondary" style="border-radius: 10px;">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Image Card -->
                <div class="form-card mb-4">
                    <div class="p-4 border-bottom" style="border-color: #E2E8F0;">
                        <h6 class="fw-bold text-slate-900 mb-0">
                            <i class="bi bi-image text-indigo me-2"></i>Cover Image
                        </h6>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($post['featured_image'])): ?>
                            <div class="mb-3">
                                <span class="d-block text-slate-600 small fw-semibold mb-2">Current Cover Image:</span>
                                <img src="<?php echo upload('blog', $post['featured_image']); ?>" 
                                     alt="Current Cover" 
                                     class="img-fluid rounded-3" 
                                     style="max-height: 180px; width: 100%; object-fit: cover; border: 1px solid #E2E8F0;">
                            </div>
                        <?php endif; ?>
                        
                        <label for="featured_image" class="form-label text-slate-700 fw-semibold small">
                            Replace Cover Image
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="featured_image" 
                               name="featured_image" 
                               accept="image/*"
                               style="border-radius: 10px; border-color: #CBD5E1;">
                        <div class="form-text small mt-2">
                            Supported formats: JPG, PNG, WEBP, GIF. Max: <?php echo MAX_UPLOAD_SIZE_MB; ?>MB.
                        </div>
                        
                        <div id="image-preview" class="mt-3"></div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </form>
    
</div>

<style>
.edit-hero {
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
.form-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04);
}
.text-indigo {
    color: #4F46E5;
}
</style>

<!-- Initialize TinyMCE Editor -->
<script>
tinymce.init({
    selector: '#content',
    height: 520,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | blockquote code | removeformat | preview',
    content_style: 'body { font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.7; color: #334155; }',
    branding: false
});

// Form submission handler
document.getElementById('editPostForm').addEventListener('submit', function(e) {
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving changes...';
    }
});

// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="mt-2">
                    <span class="d-block text-slate-600 small fw-semibold mb-1">New Cover Preview:</span>
                    <img src="${e.target.result}" class="img-fluid rounded-3" style="max-height: 180px; width: 100%; object-fit: cover; border: 1px solid #E2E8F0;">
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