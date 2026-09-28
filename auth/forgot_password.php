<?php

// Set page title
$pageTitle = "Forgot Password";

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
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Get and sanitize email
        $email = trim($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email)) {
            $errors[] = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        // If no validation errors, process the request
        if (empty($errors)) {
            try {
                // Check if user exists with this email
                $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Check for recent reset requests (rate limiting - 1 request per 5 minutes)
                    $checkStmt = $conn->prepare("
                        SELECT created_at 
                        FROM password_reset_tokens 
                        WHERE email = ? 
                        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ");
                    $checkStmt->execute([$email]);
                    $recentRequest = $checkStmt->fetch();
                    
                    if ($recentRequest) {
                        $errors[] = "A password reset link was already sent recently. Please check your email or try again in a few minutes.";
                    } else {
                        // Generate secure random token
                        $token = bin2hex(random_bytes(32));
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        
                        // Store token in database
                        $insertStmt = $conn->prepare("
                            INSERT INTO password_reset_tokens (user_id, email, token, expires_at) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $insertStmt->execute([$user['id'], $email, $token, $expiresAt]);
                        
                        // Create reset link
                        $resetLink = url('auth/reset_password.php?token=' . $token);
                        
                        if (isDevelopment()) {
                            // Development mode: Show reset link on page
                            $_SESSION['reset_link_dev'] = $resetLink;
                            $emailSent = true;
                        } else {
                            $emailSent = sendPasswordResetEmail($email, $user['username'], $resetLink);
                        }
                        
                        if ($emailSent) {
                            $success = true;
                        } else {
                            $success = true;
                            error_log("Failed to send password reset email to: " . $email);
                        }
                    }
                } else {
                    $success = true;
                }
                
            } catch (PDOException $e) {
                error_log("Forgot Password Error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again later.";
            }
        }
    }
}

/**
 * Send Password Reset Email
 */
function sendPasswordResetEmail($email, $username, $resetLink) {
    $subject = "Password Reset Request - " . APP_NAME;
    $message = "Hello " . htmlspecialchars($username) . ",\n\nClick the link to reset your password:\n" . $resetLink . "\n\nThis link will expire in 1 hour.";
    $headers = "From: " . NO_REPLY_EMAIL . "\r\n";
    return @mail($email, $subject, $message, $headers);
}

// Include header
require_once '../includes/header.php';
?>

<div class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                
                <!-- Forgot Password Card -->
                <div class="card auth-card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="auth-icon-badge mb-3">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-1" style="color: #0F172A; font-size: 1.5rem; letter-spacing: -0.02em;">Forgot Password?</h2>
                            <p class="text-muted small">Enter your email and we'll send you recovery instructions</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <!-- Success Message -->
                            <div class="alert alert-success mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Check Your Email</strong>
                                <p class="mb-0 small mt-1">If an account matches that email address, password reset instructions have been dispatched.</p>
                            </div>
                            
                            <?php if (isset($_SESSION['reset_link_dev'])): ?>
                                <!-- Development Mode: Show Reset Link -->
                                <div class="alert alert-info mb-4" role="alert" style="font-size: 0.85rem;">
                                    <strong><i class="bi bi-code-slash me-1"></i> Development Reset Link:</strong>
                                    <div class="mt-2 p-2 bg-white rounded border text-break">
                                        <a href="<?php echo htmlspecialchars($_SESSION['reset_link_dev']); ?>">
                                            <?php echo htmlspecialchars($_SESSION['reset_link_dev']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php unset($_SESSION['reset_link_dev']); ?>
                            <?php endif; ?>
                            
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
                            
                            <!-- Forgot Password Form -->
                            <form method="POST" action="" class="needs-validation" novalidate>
                                
                                <!-- CSRF Token -->
                                <?php echo csrfField(); ?>
                                
                                <!-- Email Address -->
                                <div class="mb-4">
                                    <label for="email" class="form-label text-slate-700 fw-semibold small">
                                        Registered Email Address
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
                                               required
                                               autofocus>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 10px; font-weight: 600; font-size: 1rem; padding: 0.75rem;">
                                        <span>Send Recovery Link</span>
                                        <i class="bi bi-send-fill ms-1"></i>
                                    </button>
                                </div>
                            </form>
                            
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

<?php
// Include footer
require_once '../includes/footer.php';
?>