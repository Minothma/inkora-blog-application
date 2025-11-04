<?php
/**
 * Create Blog Post Page
 * 
 * Allows authenticated users to create new blog posts with modern UI
 * 
 * Features:
 * - Rich text editor (TinyMCE)
 * - Real-time validation and character counting
 * - Featured image upload with preview
 * - Auto-generate slug and excerpt
 * - Draft/Publish status
 * - CSRF protection
 * - Modern cyan-to-purple gradient design
 * 
 * @author Inkora Team
 * @version 2.0 - Cyan to Purple Gradient Design
 */

// Set page title
$pageTitle = "Create New Story";

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Require login
requireLogin();

// Initialize variables
$errors = [];
$success = false;
$title = '';
$content = '';
$excerpt = '';
$status = 'published';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please refresh and try again.";
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
            $errors[] = "Title cannot exceed " . POST_TITLE_MAX_LENGTH . " characters.";
        }
        
        // Content validation
        $contentTextLength = strlen(strip_tags($content));
        if (empty($content)) {
            $errors[] = "Content is required.";
        } elseif ($contentTextLength < POST_CONTENT_MIN_LENGTH) {
            $errors[] = "Content must be at least " . POST_CONTENT_MIN_LENGTH . " characters.";
        }
        
        // Excerpt validation
        if (!empty($excerpt) && strlen($excerpt) > 500) {
            $errors[] = "Excerpt cannot exceed 500 characters.";
        }
        
        // Status validation
        if (!in_array($status, ['draft', 'published'])) {
            $status = 'published';
        }
        
        // If no validation errors, proceed
        if (empty($errors)) {
            try {
                // Generate unique slug from title
                $slug = generateSlug($title);
                
                // Check if slug already exists
                $slugExists = true;
                $slugCounter = 1;
                $originalSlug = $slug;
                
                while ($slugExists) {
                    $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
                    $stmt->execute([$slug]);
                    
                    if ($stmt->rowCount() > 0) {
                        $slug = $originalSlug . '-' . $slugCounter;
                        $slugCounter++;
                    } else {
                        $slugExists = false;
                    }
                }
                
                // Auto-generate excerpt if empty
                if (empty($excerpt)) {
                    $excerpt = generateExcerptFromContent($content, 200);
                }
                
                // Handle featured image upload
                $featuredImage = null;
                
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleImageUpload($_FILES['featured_image'], 'blog');
                    
                    if ($uploadResult['success']) {
                        $featuredImage = $uploadResult['filename'];
                    } else {
                        $errors[] = $uploadResult['error'];
                    }
                }
                
                // Insert blog post if no upload errors
                if (empty($errors)) {
                    $stmt = $conn->prepare("
                        INSERT INTO blog_posts (user_id, title, slug, content, excerpt, featured_image, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        getCurrentUserId(),
                        $title,
                        $slug,
                        $content,
                        $excerpt,
                        $featuredImage,
                        $status
                    ]);
                    
                    $postId = $conn->lastInsertId();
                    
                    // Success
                    $success = true;
                    setFlashMessage('Story ' . ($status === 'published' ? 'published' : 'saved as draft') . ' successfully!', 'success');
                    
                    // Redirect to view page
                    header("Location: " . url('posts/view.php?id=' . $postId));
                    exit();
                }
                
            } catch (PDOException $e) {
                error_log("Create post error: " . $e->getMessage());
                $errors[] = "An error occurred while creating your post. Please try again.";
            }
        }
    }
}

/**
 * Generate URL-friendly slug from title
 */
function generateSlug($title) {
    $slug = strtolower($title);
    $slug = str_replace(' ', '-', $slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Generate excerpt from content
 */
function generateExcerptFromContent($content, $length = 200) {
    $text = strip_tags($content);
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        $text .= '...';
    }
    return $text;
}

/**
 * Handle image upload
 */
function handleImageUpload($file, $type = 'blog') {
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds ' . MAX_UPLOAD_SIZE_MB . 'MB limit.'];
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed.'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_MIMES)) {
        return ['success' => false, 'error' => 'Invalid file format detected.'];
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
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

// Include header
require_once '../includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Matching Home Page Color Scheme */
:root {
    --gradient-cyan: #00CED1;
    --gradient-cyan-light: #20B2C4;
    --gradient-purple: #6A5ACD;
    --gradient-purple-deep: #7B68BE;
    --gradient-navy: #0B1A2D;
    --gradient-navy-light: #1A1F3A;
    --accent-warm: #FFE4B5;
    --accent-gold: #FDB94E;
    --text-dark: #2d3748;
    --text-muted: #718096;
    --bg-light: #f7fafc;
    --bg-white: #ffffff;
    --border-light: #e2e8f0;
}

/* Hero Header Section */
.create-hero {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    padding: 3rem 0;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.create-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="1.5" fill="white"/></svg>');
    background-size: 60px 60px;
    opacity: 0.1;
}

.create-hero .container {
    position: relative;
    z-index: 1;
}

.badge-pill {
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge-gradient {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    backdrop-filter: blur(10px);
}

/* Form Card Styling */
.form-card {
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.form-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.form-section {
    padding: 2rem;
    border-bottom: 1px solid var(--border-light);
}

.form-section:last-child {
    border-bottom: none;
}

.form-label {
    color: var(--text-dark);
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label i {
    color: var(--gradient-cyan);
}

.form-control, .form-select {
    border: 2px solid var(--border-light);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--gradient-cyan);
    box-shadow: 0 0 0 0.2rem rgba(0, 206, 209, 0.15);
}

.form-control-lg {
    font-size: 1.25rem;
    font-weight: 600;
    padding: 1rem 1.25rem;
}

/* Character Counters */
.counter-display {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--bg-light);
    border-radius: 8px;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.counter-display.success {
    background: #f0fff4;
    color: #38a169;
    border: 1px solid #9ae6b4;
}

.counter-display.warning {
    background: #fffaf0;
    color: #d69e2e;
    border: 1px solid #fbd38d;
}

.counter-display.danger {
    background: #fff5f5;
    color: #e53e3e;
    border: 1px solid #feb2b2;
}

/* Image Preview */
.image-preview-container {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--bg-light);
    border-radius: 12px;
    border: 2px dashed var(--border-light);
}

.image-preview-container img {
    max-height: 300px;
    border-radius: 8px;
}

/* Buttons */
.btn-gradient-primary {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-gradient-primary:hover {
    background: linear-gradient(135deg, var(--gradient-cyan-light) 0%, var(--gradient-purple-deep) 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
    color: white;
}

.btn-outline-gradient {
    border: 2px solid var(--gradient-cyan);
    color: var(--gradient-cyan);
    background: transparent;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-outline-gradient:hover {
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    color: white;
    border-color: transparent;
}

/* Alert Styling */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1.25rem;
}

.alert-danger {
    background: #fff5f5;
    color: #c53030;
    border-left: 4px solid #e53e3e;
}

/* Info Cards */
.info-card {
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.1) 0%, rgba(106, 90, 205, 0.1) 100%);
    border: 1px solid rgba(106, 90, 205, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.info-card i {
    font-size: 2rem;
    background: linear-gradient(135deg, var(--gradient-cyan) 0%, var(--gradient-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* TinyMCE Container */
.tox-tinymce {
    border-radius: 12px !important;
    border: 2px solid var(--border-light) !important;
}

/* Tips Section */
.tips-section {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1.5rem;
}

.tip-item {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: white;
    border-radius: 8px;
    border-left: 3px solid var(--gradient-cyan);
}

.tip-item:last-child {
    margin-bottom: 0;
}

.tip-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(0, 206, 209, 0.15) 0%, rgba(106, 90, 205, 0.15) 100%);
    color: var(--gradient-purple);
    flex-shrink: 0;
}

/* Action Buttons */
.action-buttons {
    background: var(--bg-white);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 20px;
}

@media (max-width: 768px) {
    .create-hero {
        padding: 2rem 0;
    }
    
    .form-section {
        padding: 1.5rem;
    }
    
    .action-buttons {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<!-- Hero Section -->
<section class="create-hero">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto text-center text-white">
                <span class="badge badge-pill badge-gradient mb-3">
                    <i class="bi bi-sparkles me-1"></i> Create Your Story
                </span>
                <h1 class="display-4 fw-bold mb-3">Share Your Voice</h1>
                <p class="lead mb-0" style="opacity: 0.95;">
                    Craft compelling stories that inspire, educate, and connect with readers worldwide
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container my-5">
    <div class="row">
        
        <!-- Main Form Column -->
        <div class="col-lg-8 mb-4">
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong class="d-block mb-2">Please fix the following issues:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Create Post Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="createPostForm">
                
                <!-- CSRF Token -->
                <?php echo csrfField(); ?>
                
                <!-- Title Section -->
                <div class="form-card mb-4">
                    <div class="form-section">
                        <label for="title" class="form-label">
                            <i class="bi bi-type-h1"></i> Story Title
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="title" 
                               name="title" 
                               value="<?php echo htmlspecialchars($title); ?>"
                               placeholder="Enter a compelling title that captures attention..."
                               minlength="<?php echo POST_TITLE_MIN_LENGTH; ?>"
                               maxlength="<?php echo POST_TITLE_MAX_LENGTH; ?>"
                               required>
                        <div id="titleCounter" class="counter-display mt-2">
                            <i class="bi bi-pencil-fill"></i>
                            <span><strong><span id="titleCount">0</span></strong> / <?php echo POST_TITLE_MAX_LENGTH; ?> characters</span>
                            <span id="titleStatus" class="ms-2"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Content Section -->
                <div class="form-card mb-4">
                    <div class="form-section">
                        <label for="content" class="form-label">
                            <i class="bi bi-file-text"></i> Story Content
                        </label>
                        <textarea class="form-control" 
                                  id="content" 
                                  name="content" 
                                  rows="20"><?php echo htmlspecialchars($content); ?></textarea>
                        <div id="contentCounter" class="counter-display mt-2">
                            <i class="bi bi-file-earmark-text"></i>
                            <span><strong><span id="contentCharCount">0</span></strong> characters</span>
                            <span class="mx-2">•</span>
                            <i class="bi bi-fonts"></i>
                            <span><strong><span id="contentWordCount">0</span></strong> words</span>
                            <span id="contentStatus" class="ms-2"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Excerpt Section -->
                <div class="form-card mb-4">
                    <div class="form-section">
                        <label for="excerpt" class="form-label">
                            <i class="bi bi-text-paragraph"></i> Excerpt
                            <span class="badge bg-secondary ms-2" style="font-size: 0.7rem; font-weight: 500;">Optional</span>
                        </label>
                        <textarea class="form-control" 
                                  id="excerpt" 
                                  name="excerpt" 
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Write a brief summary (auto-generated if left empty)..."><?php echo htmlspecialchars($excerpt); ?></textarea>
                        <div id="excerptCounter" class="counter-display mt-2">
                            <i class="bi bi-card-text"></i>
                            <span><strong><span id="excerptCount">0</span></strong> / 500 characters</span>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Image Section -->
                <div class="form-card mb-4">
                    <div class="form-section">
                        <label for="featured_image" class="form-label">
                            <i class="bi bi-image"></i> Featured Image
                            <span class="badge bg-secondary ms-2" style="font-size: 0.7rem; font-weight: 500;">Optional</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="featured_image" 
                               name="featured_image" 
                               accept="image/*">
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Max <?php echo MAX_UPLOAD_SIZE_MB; ?>MB • Recommended 1200x630px • JPG, PNG, GIF, WEBP
                        </small>
                        <div id="image-preview"></div>
                    </div>
                </div>
                
                <!-- Status Section -->
                <div class="form-card mb-4">
                    <div class="form-section">
                        <label for="status" class="form-label">
                            <i class="bi bi-eye"></i> Visibility
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="published" <?php echo ($status === 'published') ? 'selected' : ''; ?>>
                                📢 Publish Now - Visible to everyone
                            </option>
                            <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>
                                📝 Save as Draft - Only you can see
                            </option>
                        </select>
                    </div>
                </div>
                
                <!-- Mobile Action Buttons -->
                <div class="d-lg-none">
                    <div class="d-flex gap-2">
                        <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" name="status" value="draft" class="btn btn-outline-gradient flex-fill">
                            <i class="bi bi-save"></i> Draft
                        </button>
                        <button type="submit" name="status" value="published" class="btn btn-gradient-primary flex-fill">
                            <i class="bi bi-send"></i> Publish
                        </button>
                    </div>
                </div>
                
            </form>
            
        </div>
        
        <!-- Sidebar Column -->
        <div class="col-lg-4">
            
            <!-- Action Buttons (Desktop) -->
            <div class="action-buttons d-none d-lg-block mb-4">
                <h5 class="fw-bold mb-3" style="color: var(--text-dark);">
                    <i class="bi bi-sliders text-primary"></i> Actions
                </h5>
                <div class="d-grid gap-2">
                    <button type="submit" form="createPostForm" name="status" value="published" class="btn btn-gradient-primary btn-lg">
                        <i class="bi bi-send me-2"></i> Publish Story
                    </button>
                    <button type="submit" form="createPostForm" name="status" value="draft" class="btn btn-outline-gradient">
                        <i class="bi bi-save me-2"></i> Save as Draft
                    </button>
                    <a href="<?php echo url('posts/index.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </a>
                </div>
            </div>
            
            <!-- Writing Tips -->
            <div class="tips-section">
                <h5 class="fw-bold mb-3" style="color: var(--text-dark);">
                    <i class="bi bi-lightbulb text-warning"></i> Writing Tips
                </h5>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-type-bold"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark);">Compelling Title</strong>
                        <small class="text-muted">Use clear, engaging titles that tell readers what to expect</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-card-image"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark);">Visual Appeal</strong>
                        <small class="text-muted">Add a featured image to make your story stand out</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-text-paragraph"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark);">Structure Matters</strong>
                        <small class="text-muted">Break content into paragraphs for better readability</small>
                    </div>
                </div>
                
                <div class="tip-item">
                    <div class="tip-icon">
                        <i class="bi bi-chat-quote"></i>
                    </div>
                    <div>
                        <strong class="d-block mb-1" style="color: var(--text-dark);">Engaging Start</strong>
                        <small class="text-muted">Hook readers in the first paragraph</small>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- Initialize TinyMCE Editor -->
<script>
const POST_TITLE_MIN = <?php echo POST_TITLE_MIN_LENGTH; ?>;
const POST_TITLE_MAX = <?php echo POST_TITLE_MAX_LENGTH; ?>;
const POST_CONTENT_MIN = <?php echo POST_CONTENT_MIN_LENGTH; ?>;

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
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.6; }',
    branding: false,
    setup: function(editor) {
        editor.on('init', function() {
            updateContentCounter();
        });
        editor.on('keyup change paste', function() {
            updateContentCounter();
        });
    }
});

function updateContentCounter() {
    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
        const content = tinymce.get('content').getContent({format: 'text'});
        const charCount = content.trim().length;
        const wordCount = content.trim().split(/\s+/).filter(word => word.length > 0).length;
        
        document.getElementById('contentCharCount').textContent = charCount;
        document.getElementById('contentWordCount').textContent = wordCount;
        
        const counterDiv = document.getElementById('contentCounter');
        const statusSpan = document.getElementById('contentStatus');
        
        if (charCount === 0) {
            counterDiv.className = 'counter-display';
            statusSpan.innerHTML = '(min ' + POST_CONTENT_MIN + ' chars)';
        } else if (charCount < POST_CONTENT_MIN) {
            counterDiv.className = 'counter-display danger';
            const remaining = POST_CONTENT_MIN - charCount;
            statusSpan.innerHTML = '⚠️ ' + remaining + ' more needed';
        } else {
            counterDiv.className = 'counter-display success';
            statusSpan.innerHTML = '✓ Looking good!';
        }
    }
}

// Title counter
document.getElementById('title').addEventListener('input', function() {
    const length = this.value.trim().length;
    document.getElementById('titleCount').textContent = length;
    
    const counterDiv = document.getElementById('titleCounter');
    const statusSpan = document.getElementById('titleStatus');
    
    if (length === 0) {
        counterDiv.className = 'counter-display';
        statusSpan.innerHTML = '(min ' + POST_TITLE_MIN + ' chars)';
    } else if (length < POST_TITLE_MIN) {
        counterDiv.className = 'counter-display danger';
        const remaining = POST_TITLE_MIN - length;
        statusSpan.innerHTML = '⚠️ ' + remaining + ' more needed';
    } else if (length > POST_TITLE_MAX) {
        counterDiv.className = 'counter-display warning';
        const excess = length - POST_TITLE_MAX;
        statusSpan.innerHTML = '⚠️ ' + excess + ' too many';
    } else {
        counterDiv.className = 'counter-display success';
        statusSpan.innerHTML = '✓ Perfect!';
    }
});

// Excerpt counter
document.getElementById('excerpt').addEventListener('input', function() {
    const length = this.value.length;
    document.getElementById('excerptCount').textContent = length;
    
    const counterDiv = document.getElementById('excerptCounter');
    
    if (length === 0) {
        counterDiv.className = 'counter-display';
    } else if (length > 450) {
        counterDiv.className = 'counter-display warning';
    } else {
        counterDiv.className = 'counter-display success';
    }
});

// Form validation
document.getElementById('createPostForm').addEventListener('submit', function(e) {
    let hasErrors = false;
    let errorMessages = [];
    
    // Trigger TinyMCE save
    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
        tinymce.get('content').save();
        
        const content = tinymce.get('content').getContent();
        const textContent = content.replace(/<[^>]*>/g, '').trim();
        const contentLength = textContent.length;
        
        if (!content || textContent === '' || textContent === '\n') {
            hasErrors = true;
            errorMessages.push('Content is required');
        } else if (contentLength < POST_CONTENT_MIN) {
            hasErrors = true;
            errorMessages.push('Content must be at least ' + POST_CONTENT_MIN + ' characters');
        }
    }
    
    const title = document.getElementById('title').value.trim();
    const titleLength = title.length;
    
    if (!title || titleLength === 0) {
        hasErrors = true;
        errorMessages.push('Title is required');
    } else if (titleLength < POST_TITLE_MIN) {
        hasErrors = true;
        errorMessages.push('Title must be at least ' + POST_TITLE_MIN + ' characters');
    } else if (titleLength > POST_TITLE_MAX) {
        hasErrors = true;
        errorMessages.push('Title cannot exceed ' + POST_TITLE_MAX + ' characters');
    }
    
    const excerpt = document.getElementById('excerpt').value;
    if (excerpt.length > 500) {
        hasErrors = true;
        errorMessages.push('Excerpt cannot exceed 500 characters');
    }
    
    if (hasErrors) {
        e.preventDefault();
        
        let errorMessage = '⚠️ Please fix the following issues:\n\n';
        errorMessages.forEach((msg, index) => {
            errorMessage += (index + 1) + '. ' + msg + '\n';
        });
        
        alert(errorMessage);
        return false;
    }
    
    // Show loading state
    const submitBtns = document.querySelectorAll('button[type="submit"]');
    submitBtns.forEach(btn => {
        btn.disabled = true;
        const isDraft = btn.value === 'draft';
        const icon = isDraft ? 'save' : 'send';
        const text = isDraft ? 'Saving...' : 'Publishing...';
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + text;
    });
    
    return true;
});

// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    
    if (file) {
        const maxSize = <?php echo MAX_UPLOAD_SIZE; ?>;
        if (file.size > maxSize) {
            const maxSizeMB = <?php echo MAX_UPLOAD_SIZE_MB; ?>;
            alert('⚠️ File size too large!\n\nYour file: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB\nMaximum: ' + maxSizeMB + ' MB');
            this.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('⚠️ Invalid file type!\n\nAllowed: JPG, PNG, GIF, WEBP');
            this.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="image-preview-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <strong class="ms-2">Image Preview</strong>
                        </div>
                        <span class="badge bg-success">✓ Valid</span>
                    </div>
                    <img src="${e.target.result}" class="img-fluid" alt="Preview">
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-file-earmark-image"></i> ${file.name} 
                        <span class="mx-2">•</span>
                        <i class="bi bi-hdd"></i> ${(file.size / 1024 / 1024).toFixed(2)} MB
                    </div>
                </div>
            `;
        };
        
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Initialize counters
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('title').dispatchEvent(new Event('input'));
    document.getElementById('excerpt').dispatchEvent(new Event('input'));
    
    setTimeout(function() {
        updateContentCounter();
    }, 1000);
});

// Auto-save to localStorage (optional feature)
let autoSaveTimer;
function autoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            const title = document.getElementById('title').value;
            const content = tinymce.get('content').getContent();
            const excerpt = document.getElementById('excerpt').value;
            
            if (title || content) {
                const draftData = {
                    title: title,
                    content: content,
                    excerpt: excerpt,
                    timestamp: new Date().toISOString()
                };
                
                try {
                    localStorage.setItem('inkora_draft', JSON.stringify(draftData));
                    console.log('Draft auto-saved');
                } catch (e) {
                    console.warn('Could not save draft to localStorage');
                }
            }
        }
    }, 5000); // Auto-save after 5 seconds of inactivity
}

// Attach auto-save to form inputs
document.getElementById('title').addEventListener('input', autoSave);
document.getElementById('excerpt').addEventListener('input', autoSave);

if (typeof tinymce !== 'undefined') {
    tinymce.init({
        setup: function(editor) {
            editor.on('keyup change', autoSave);
        }
    });
}

// Load draft on page load (optional)
window.addEventListener('load', function() {
    try {
        const savedDraft = localStorage.getItem('inkora_draft');
        if (savedDraft) {
            const draftData = JSON.parse(savedDraft);
            const timestamp = new Date(draftData.timestamp);
            const hoursSince = (new Date() - timestamp) / (1000 * 60 * 60);
            
            if (hoursSince < 24) { // Only restore if less than 24 hours old
                const restore = confirm('Found an unsaved draft from ' + timestamp.toLocaleString() + '\n\nWould you like to restore it?');
                
                if (restore) {
                    document.getElementById('title').value = draftData.title || '';
                    document.getElementById('excerpt').value = draftData.excerpt || '';
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        tinymce.get('content').setContent(draftData.content || '');
                    }
                    
                    // Update counters
                    document.getElementById('title').dispatchEvent(new Event('input'));
                    document.getElementById('excerpt').dispatchEvent(new Event('input'));
                    updateContentCounter();
                }
            }
        }
    } catch (e) {
        console.warn('Could not load draft from localStorage');
    }
});

// Clear draft after successful submission
window.addEventListener('beforeunload', function(e) {
    const form = document.getElementById('createPostForm');
    if (form.querySelector('button[disabled]')) {
        // Form is being submitted, clear the draft
        try {
            localStorage.removeItem('inkora_draft');
        } catch (e) {
            console.warn('Could not clear draft');
        }
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>