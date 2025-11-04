<?php
/**
 * Session Management File
 * 
 * This file handles all session-related operations with enhanced security
 * 
 * Features:
 * - Secure session configuration
 * - Session hijacking prevention
 * - CSRF token generation and validation
 * - User authentication helpers
 * - Flash message system
 * - Session timeout handling
 * 
 * @author Your Name
 * @version 1.0
 */

// Require database and constants
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';

/**
 * Initialize Secure Session
 * Configures and starts a secure PHP session
 */
function initSession() {
    // Check if session is already started
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Configure session parameters for security
    ini_set('session.cookie_httponly', COOKIE_HTTPONLY ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', COOKIE_SAMESITE);
    
    // Use secure cookies only on HTTPS
    if (COOKIE_SECURE) {
        ini_set('session.cookie_secure', 1);
    }
    
    // Set session name
    session_name(SESSION_NAME);
    
    // Set session lifetime
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    // Start the session
    session_start();
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Check for session timeout
    checkSessionTimeout();
    
    // Validate session to prevent hijacking
    validateSession();
}

/**
 * Validate Session
 * Prevents session hijacking by checking user agent and IP
 */
function validateSession() {
    // Initialize session fingerprint if not set
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
    }
    
    // Check if session is valid
    $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Strict validation (uncomment if needed - may cause issues with dynamic IPs)
    // if ($_SESSION['fingerprint']['user_agent'] !== $currentUserAgent ||
    //     $_SESSION['fingerprint']['ip_address'] !== $currentIP) {
    //     destroySession();
    //     die('Session validation failed. Please log in again.');
    // }
    
    // Less strict validation (only check user agent)
    if (isset($_SESSION['user_id']) && $_SESSION['fingerprint']['user_agent'] !== $currentUserAgent) {
        destroySession();
        header('Location: ' . url('auth/login.php?error=session_invalid'));
        exit();
    }
}

/**
 * Check Session Timeout
 * Logs out user if session has expired
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        
        // If inactive time exceeds session lifetime, destroy session
        if ($inactiveTime > SESSION_LIFETIME) {
            destroySession();
            header('Location: ' . url('auth/login.php?error=session_timeout'));
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Set Session User Data
 * Stores user information in session after login
 * 
 * @param array $user User data from database
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_picture'] = $user['profile_picture'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Store session in database for tracking
    storeSessionInDB($user['id']);
}

/**
 * Store Session in Database
 * Keeps track of active sessions
 * 
 * @param int $userId User ID
 */
function storeSessionInDB($userId) {
    global $conn;
    
    try {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check if session already exists
        $stmt = $conn->prepare("SELECT id FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing session
            $stmt = $conn->prepare("UPDATE sessions SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$sessionId]);
        } else {
            // Insert new session
            $stmt = $conn->prepare("INSERT INTO sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent]);
        }
    } catch (PDOException $e) {
        // Log error but don't stop execution
        error_log("Session DB Error: " . $e->getMessage());
    }
}

/**
 * Check if User is Logged In
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
}

/**
 * Check if User is Admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
}

/**
 * Get Current User ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get Current Username
 * 
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get Current User Role
 * 
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Require Login
 * Redirects to login page if user is not logged in
 * 
 * @param string $redirectUrl URL to redirect to after login
 */
function requireLogin($redirectUrl = '') {
    if (!isLoggedIn()) {
        $redirect = !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '';
        header('Location: ' . url('auth/login.php' . $redirect));
        exit();
    }
}

/**
 * Require Admin
 * Redirects to home page if user is not an admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        setFlashMessage('You do not have permission to access this page.', 'danger');
        header('Location: ' . url('index.php'));
        exit();
    }
}

/**
 * Destroy Session
 * Completely destroys the session and cleans up
 */
function destroySession() {
    global $conn;
    
    // Remove session from database
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([session_id()]);
        } catch (PDOException $e) {
            error_log("Session cleanup error: " . $e->getMessage());
        }
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

// ========================================
// CSRF TOKEN FUNCTIONS
// ========================================

/**
 * Generate CSRF Token
 * Creates a unique token for form protection
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF Token
 * Checks if submitted token matches session token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    // Check if token exists in session
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    // Check if token has expired
    if (isset($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
        $tokenAge = time() - $_SESSION[CSRF_TOKEN_NAME . '_time'];
        if ($tokenAge > CSRF_TOKEN_EXPIRY) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            return false;
        }
    }
    
    // Compare tokens using timing-safe comparison
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF Token Input Field
 * Returns HTML input field with CSRF token
 * 
 * @return string HTML input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

// ========================================
// FLASH MESSAGE FUNCTIONS
// ========================================

/**
 * Set Flash Message
 * Stores a one-time message to display on next page load
 * 
 * @param string $message Message text
 * @param string $type Message type (success, danger, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get Flash Message
 * Retrieves and removes flash message from session
 * 
 * @return array|null Flash message array or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display Flash Message
 * Outputs HTML for flash message with Bootstrap styling
 * 
 * @return string HTML for flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $alertClass = 'alert-' . htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        
        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                    ' . $message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    
    return '';
}

// ========================================
// INITIALIZE SESSION
// ========================================

// Automatically initialize session when this file is included
initSession();

/**
 * USAGE EXAMPLES:
 * 
 * 1. Check if user is logged in:
 *    if (isLoggedIn()) { ... }
 * 
 * 2. Require login for a page:
 *    requireLogin();
 * 
 * 3. Require admin for a page:
 *    requireAdmin();
 * 
 * 4. Get current user ID:
 *    $userId = getCurrentUserId();
 * 
 * 5. Set flash message:
 *    setFlashMessage('Post created!', 'success');
 * 
 * 6. Display flash message:
 *    echo displayFlashMessage();
 * 
 * 7. Add CSRF token to form:
 *    echo csrfField();
 * 
 * 8. Validate CSRF token:
 *    if (validateCSRFToken($_POST['csrf_token'])) { ... }
 */

?>