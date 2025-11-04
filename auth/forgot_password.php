<?php
/**
 * Forgot Password Page
 * 
 * Allows users to request a password reset link
 * 
 * Features:
 * - Email validation
 * - Token generation
 * - Email sending
 * - Rate limiting
 * - CSRF protection
 * 
 * @author Your Name
 * @version 1.0
 */

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
                        
                        // Send email (you'll need to implement email sending)
                        // For development/testing: Display link instead of sending email
                        if (isDevelopment()) {
                            // Development mode: Show reset link on page
                            $_SESSION['reset_link_dev'] = $resetLink;
                            $emailSent = true;
                        } else {
                            // Production mode: Send actual email
                            $emailSent = sendPasswordResetEmail($email, $user['username'], $resetLink);
                        }
                        
                        if ($emailSent) {
                            $success = true;
                        } else {
                            // If email fails, still show success message for security
                            // (don't reveal if email exists or not)
                            $success = true;
                            error_log("Failed to send password reset email to: " . $email);
                        }
                    }
                } else {
                    // User not found - still show success message for security
                    // (don't reveal if email exists or not)
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
 * 
 * @param string $email User's email
 * @param string $username User's username
 * @param string $resetLink Password reset link
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $username, $resetLink) {
    // Email subject
    $subject = "Password Reset Request - " . APP_NAME;
    
    // Email body (HTML)
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #00CED1 0%, #6A5ACD 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f7fafc; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: linear-gradient(135deg, #00CED1 0%, #6A5ACD 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; color: #718096; font-size: 12px; margin-top: 30px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>" . htmlspecialchars($username) . "</strong>,</p>
                
                <p>We received a request to reset your password for your " . APP_NAME . " account.</p>
                
                <p>Click the button below to reset your password:</p>
                
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Password</a>
                </p>
                
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; background: white; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($resetLink) . "</p>
                
                <div class='warning'>
                    <strong>⚠️ Security Notice:</strong>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Never share this link with anyone</li>
                    </ul>
                </div>
                
                <p>If you have any questions, please contact our support team.</p>
                
                <p>Best regards,<br>The " . APP_NAME . " Team</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">" . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
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

.forgot-wrapper {
    position: relative;
    z-index: 1;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    padding: 3rem 0;
}

.forgot-card {
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

.forgot-icon {
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

.forgot-icon i {
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

.forgot-card a {
    color: var(--gradient-purple);
    font-weight: 500;
    transition: all 0.2s ease;
}

.forgot-card a:hover {
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

.info-box {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    border-left: 4px solid var(--gradient-cyan);
}

.back-link-box {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    margin-top: 1.5rem;
}

@media (max-width: 576px) {
    .forgot-card {
        border-radius: 16px;
        margin: 1rem;
    }
    .forgot-icon {
        width: 60px;
        height: 60px;
    }
    .forgot-icon i {
        font-size: 2rem;
    }
}
</style>

<!-- Forgot Password Form -->
<div class="forgot-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                
                <!-- Forgot Password Card -->
                <div class="forgot-card">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="forgot-icon">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <h2 class="fw-bold mb-2" style="color: var(--text-dark);">Forgot Password?</h2>
                            <p class="text-muted mb-0">No worries, we'll send you reset instructions</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <!-- Success Message -->
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Check Your Email!</strong>
                                <p class="mb-0 mt-2">If an account exists with that email, we've sent password reset instructions.</p>
                            </div>
                            
                            <?php if (isset($_SESSION['reset_link_dev'])): ?>
                                <!-- Development Mode: Show Reset Link -->
                                <div class="alert alert-info" role="alert">
                                    <h6 class="fw-bold mb-2"><i class="bi bi-code-slash me-2"></i>Development Mode</h6>
                                    <p class="mb-2 small">Since email is not configured, here's your reset link:</p>
                                    <div style="background: white; padding: 10px; border-radius: 8px; word-break: break-all; margin-bottom: 10px;">
                                        <a href="<?php echo htmlspecialchars($_SESSION['reset_link_dev']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($_SESSION['reset_link_dev']); ?>
                                        </a>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($_SESSION['reset_link_dev']); ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-arrow-right me-1"></i> Go to Reset Password
                                    </a>
                                </div>
                                <?php unset($_SESSION['reset_link_dev']); ?>
                            <?php endif; ?>
                            
                            <div class="info-box">
                                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>What's Next?</h6>
                                <ul class="mb-0 small">
                                    <li>Check your email inbox (and spam folder)</li>
                                    <li>Click the reset link in the email</li>
                                    <li>The link expires in 1 hour</li>
                                    <li>If you don't receive an email, try again in 5 minutes</li>
                                </ul>
                            </div>
                            
                        <?php else: ?>
                            
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
                            
                            <!-- Forgot Password Form -->
                            <form method="POST" action="" class="needs-validation" novalidate>
                                
                                <!-- CSRF Token -->
                                <?php echo csrfField(); ?>
                                
                                <!-- Email Address -->
                                <div class="mb-4">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope-fill me-1"></i> Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>"
                                           placeholder="Enter your registered email"
                                           required
                                           autofocus>
                                    <small class="text-muted">
                                        We'll send password reset instructions to this email
                                    </small>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-gradient-primary btn-lg">
                                        <i class="bi bi-send-fill me-2"></i> Send Reset Link
                                    </button>
                                </div>
                            </form>
                            
                        <?php endif; ?>
                        
                        <!-- Back to Login -->
                        <div class="back-link-box">
                            <a href="<?php echo url('auth/login.php'); ?>" class="text-decoration-none d-flex align-items-center justify-content-center">
                                <i class="bi bi-arrow-left me-2"></i> Back to Login
                            </a>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>