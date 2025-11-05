<?php

// Prevent direct access to this file
if (!defined('DB_HOST')) {
    die('Direct access not permitted');
}

// ========================================
// PATH CONSTANTS
// ========================================

// Root directory path (absolute server path)
define('ROOT_PATH', dirname(__DIR__));

// Base URL for the application (for links and redirects)
define('BASE_URL', env('APP_URL', 'http://localhost/BLOG-APP'));

// Asset URLs (for CSS, JS, Images)
define('CSS_URL', BASE_URL . '/assets/css');
define('JS_URL', BASE_URL . '/assets/js');
define('IMG_URL', BASE_URL . '/assets/images');

// Upload directories
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('BLOG_IMG_PATH', UPLOAD_PATH . '/blog_images');

// Upload URLs (for displaying images)
define('UPLOAD_URL', BASE_URL . '/uploads');
define('AVATAR_URL', UPLOAD_URL . '/avatars');
define('BLOG_IMG_URL', UPLOAD_URL . '/blog_images');


// ========================================
// APPLICATION SETTINGS
// ========================================

// Application name (displayed in title, emails, etc.)
define('APP_NAME', env('APP_NAME', 'My Blog Application'));

// Application version
define('APP_VERSION', '1.0.0');

// Application environment (development or production)
define('APP_ENV', env('APP_ENV', 'development'));

// Is development mode?
define('IS_DEV', APP_ENV === 'development');

// Application timezone
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Asia/Colombo'));
date_default_timezone_set(APP_TIMEZONE);


// ========================================
// SECURITY SETTINGS
// ========================================

// Session settings
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200)); // 2 hours in seconds
define('SESSION_NAME', env('SESSION_NAME', 'BLOG_APP_SESSION'));

// Cookie settings
define('COOKIE_SECURE', env('COOKIE_SECURE', 'false') === 'true'); // true for HTTPS
define('COOKIE_HTTPONLY', env('COOKIE_HTTPONLY', 'true') === 'true'); // Prevent JS access
define('COOKIE_SAMESITE', env('COOKIE_SAMESITE', 'Lax')); // Lax, Strict, or None

// Password settings
define('PASSWORD_MIN_LENGTH', 8); // Minimum password length
define('PASSWORD_REQUIRE_UPPERCASE', true); // Require uppercase letter
define('PASSWORD_REQUIRE_LOWERCASE', true); // Require lowercase letter
define('PASSWORD_REQUIRE_NUMBER', true); // Require number
define('PASSWORD_REQUIRE_SPECIAL', true); // Require special character

// CSRF token settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour


// ========================================
// FILE UPLOAD SETTINGS
// ========================================

// Maximum upload size in bytes (5MB default)
define('MAX_UPLOAD_SIZE', (int)env('MAX_UPLOAD_SIZE', 5242880));

// Maximum upload size in MB (for display)
define('MAX_UPLOAD_SIZE_MB', MAX_UPLOAD_SIZE / 1048576);

// Allowed image file extensions
define('ALLOWED_IMAGE_TYPES', explode(',', env('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif,webp')));

// Allowed image MIME types
define('ALLOWED_IMAGE_MIMES', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp'
]);

// Maximum image dimensions (for resizing)
define('MAX_IMAGE_WIDTH', (int)env('MAX_IMAGE_WIDTH', 1920));
define('MAX_IMAGE_HEIGHT', (int)env('MAX_IMAGE_HEIGHT', 1080));

// Avatar image settings
define('AVATAR_MAX_WIDTH', 500);
define('AVATAR_MAX_HEIGHT', 500);
define('DEFAULT_AVATAR', 'default-avatar.png');


// ========================================
// PAGINATION SETTINGS
// ========================================

// Number of items per page
define('POSTS_PER_PAGE', (int)env('POSTS_PER_PAGE', 10));
define('COMMENTS_PER_PAGE', (int)env('COMMENTS_PER_PAGE', 20));
define('USERS_PER_PAGE', (int)env('USERS_PER_PAGE', 15));

// Search results per page
define('SEARCH_RESULTS_PER_PAGE', 10);


// ========================================
// USER ROLE CONSTANTS
// ========================================

// User roles (matches database ENUM values)
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');

// Role names (for display)
define('ROLE_NAMES', [
    ROLE_USER => 'User',
    ROLE_ADMIN => 'Administrator'
]);


// ========================================
// BLOG POST STATUS CONSTANTS
// ========================================

// Post status (matches database ENUM values)
define('STATUS_DRAFT', 'draft');
define('STATUS_PUBLISHED', 'published');

// Status names (for display)
define('STATUS_NAMES', [
    STATUS_DRAFT => 'Draft',
    STATUS_PUBLISHED => 'Published'
]);

// Status colors (for badges)
define('STATUS_COLORS', [
    STATUS_DRAFT => 'warning',
    STATUS_PUBLISHED => 'success'
]);


// ========================================
// REACTION TYPES
// ========================================

// Reaction types (matches database ENUM values)
define('REACTION_LIKE', 'like');
define('REACTION_LOVE', 'love');
define('REACTION_WOW', 'wow');
define('REACTION_SAD', 'sad');
define('REACTION_ANGRY', 'angry');

// Reaction emojis (for display)
define('REACTION_EMOJIS', [
    REACTION_LIKE => '👍',
    REACTION_LOVE => '❤️',
    REACTION_WOW => '😮',
    REACTION_SAD => '😢',
    REACTION_ANGRY => '😠'
]);

// Reaction names (for display)
define('REACTION_NAMES', [
    REACTION_LIKE => 'Like',
    REACTION_LOVE => 'Love',
    REACTION_WOW => 'Wow',
    REACTION_SAD => 'Sad',
    REACTION_ANGRY => 'Angry'
]);


// ========================================
// DATE & TIME FORMAT CONSTANTS
// ========================================

// Date format for display
define('DATE_FORMAT', 'F j, Y'); // Example: January 1, 2025
define('TIME_FORMAT', 'g:i A'); // Example: 3:30 PM
define('DATETIME_FORMAT', 'F j, Y g:i A'); // Example: January 1, 2025 3:30 PM

// Date format for database (MySQL)
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');


// ========================================
// ERROR & SUCCESS MESSAGES
// ========================================

// Generic messages
define('MSG_SUCCESS', 'Operation completed successfully!');
define('MSG_ERROR', 'An error occurred. Please try again.');
define('MSG_UNAUTHORIZED', 'You are not authorized to perform this action.');
define('MSG_NOT_FOUND', 'The requested resource was not found.');

// Authentication messages
define('MSG_LOGIN_SUCCESS', 'Welcome back to Inkora! You have been logged in successfully.');
define('MSG_LOGIN_FAILED', 'Invalid username or password.');
define('MSG_LOGOUT_SUCCESS', 'You have been logged out successfully. See you soon!');
define('MSG_REGISTER_SUCCESS', 'Welcome to Inkora! Your account has been created successfully.');
define('MSG_REGISTER_FAILED', 'Registration failed. Please try again.');

// Blog post messages
define('MSG_POST_CREATED', 'Blog post created successfully!');
define('MSG_POST_UPDATED', 'Blog post updated successfully!');
define('MSG_POST_DELETED', 'Blog post deleted successfully!');
define('MSG_POST_NOT_FOUND', 'Blog post not found.');

// Comment messages
define('MSG_COMMENT_ADDED', 'Comment added successfully!');
define('MSG_COMMENT_DELETED', 'Comment deleted successfully!');

// Profile messages
define('MSG_PROFILE_UPDATED', 'Profile updated successfully!');
define('MSG_PASSWORD_CHANGED', 'Password changed successfully!');

// Upload messages
define('MSG_UPLOAD_SUCCESS', 'File uploaded successfully!');
define('MSG_UPLOAD_FAILED', 'File upload failed. Please try again.');
define('MSG_INVALID_FILE_TYPE', 'Invalid file type. Please upload a valid image.');
define('MSG_FILE_TOO_LARGE', 'File size exceeds the maximum limit of ' . MAX_UPLOAD_SIZE_MB . 'MB.');


// ========================================
// VALIDATION RULES
// ========================================

// Username validation
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 50);
define('USERNAME_PATTERN', '/^[a-zA-Z0-9_ ]+$/'); // Only letters, numbers, underscore, spaces

// Email validation
define('EMAIL_MAX_LENGTH', 100);

// Blog post validation
define('POST_TITLE_MIN_LENGTH', 5);
define('POST_TITLE_MAX_LENGTH', 255);
define('POST_CONTENT_MIN_LENGTH', 50);

// Comment validation
define('COMMENT_MIN_LENGTH', 3);
define('COMMENT_MAX_LENGTH', 1000);


// ========================================
// ADMIN SETTINGS
// ========================================

// Default admin credentials (from .env)
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@inkora.com'));
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));


// ========================================
// DEBUG & LOGGING SETTINGS
// ========================================

// Display errors (only in development)
define('DISPLAY_ERRORS', IS_DEV);

// Error reporting level
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Error log file
define('ERROR_LOG_FILE', ROOT_PATH . '/logs/error.log');

// Query logging (for debugging)
define('LOG_QUERIES', env('LOG_QUERIES', 'false') === 'true');


// ========================================
// HELPER FUNCTIONS FOR CONSTANTS
// ========================================

/**
 * Get full URL for an asset
 * 
 * @param string $type Asset type (css, js, img)
 * @param string $file Filename
 * @return string Full URL to the asset
 */
function asset($type, $file) {
    $urls = [
        'css' => CSS_URL,
        'js' => JS_URL,
        'img' => IMG_URL
    ];
    
    return isset($urls[$type]) ? $urls[$type] . '/' . $file : '';
}

/**
 * Get full URL for uploaded file
 * 
 * @param string $type Upload type (avatar, blog)
 * @param string $file Filename
 * @return string Full URL to the uploaded file
 */
function upload($type, $file) {
    // Handle default avatar - return from assets/images instead of uploads
    if ($type === 'avatar' && (empty($file) || $file === DEFAULT_AVATAR)) {
        return IMG_URL . '/default-avatar.png';
    }
    
    $urls = [
        'avatar' => AVATAR_URL,
        'blog' => BLOG_IMG_URL
    ];
    
    return isset($urls[$type]) ? $urls[$type] . '/' . $file : '';
}

/**
 * Build full URL for application pages
 * 
 * @param string $path Relative path
 * @return string Full URL
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Check if current environment is development
 * 
 * @return bool
 */
function isDevelopment() {
    return IS_DEV;
}

/**
 * Check if current environment is production
 * 
 * @return bool
 */
function isProduction() {
    return !IS_DEV;
}


// ========================================
// NOTES
// ========================================
/**
 * How to use constants in your code:
 * 
 * 1. Include this file: require_once 'config/constants.php';
 * 2. Use constants directly: echo APP_NAME;
 * 3. Use helper functions: echo asset('css', 'style.css');
 * 4. Check environment: if (isDevelopment()) { ... }
 * 
 * Benefits of using constants:
 * - Easy to update values in one place
 * - Makes code more readable
 * - Prevents typos and errors
 * - Improves code maintainability
 */

?>