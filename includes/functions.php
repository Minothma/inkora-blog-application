<?php
/**
 * Helper Functions Library
 * 
 * Contains all reusable utility functions for the blog application
 * Note: Session functions are in config/session.php
 * 
 * @author BlogHub Team
 * @version 2.0
 */

// ============================================
// URL & NAVIGATION HELPERS
// ============================================

/**
 * Generate full URL from relative path
 */
if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/blogApp';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . url($url), true, $statusCode);
    exit;
}

/**
 * Redirect back to previous page
 */
function redirectBack() {
    $referrer = $_SERVER['HTTP_REFERER'] ?? url('index.php');
    header('Location: ' . $referrer);
    exit;
}

/**
 * Build URL with query parameters
 */
function buildUrl($params = [], $baseUrl = null) {
    if ($baseUrl === null) {
        $baseUrl = strtok(currentUrl(), '?');
    }
    
    $currentParams = $_GET;
    $mergedParams = array_merge($currentParams, $params);
    
    // Remove empty values
    $mergedParams = array_filter($mergedParams, function($value) {
        return $value !== '' && $value !== null;
    });
    
    if (empty($mergedParams)) {
        return $baseUrl;
    }
    
    return $baseUrl . '?' . http_build_query($mergedParams);
}

/**
 * Remove query parameter from URL
 */
function removeQueryParam($param) {
    $params = $_GET;
    unset($params[$param]);
    return buildUrl($params);
}

// ============================================
// IMAGE & FILE HELPERS
// ============================================

/**
 * Get image URL with fallback
 */
function getImageUrl($filename, $type = 'blog') {
    if (empty($filename) || $filename === 'default-avatar.png' || $filename === 'placeholder.jpg') {
        return $type === 'avatar' 
            ? 'assets/images/default-avatar.png' 
            : 'assets/images/placeholder-blog.jpg';
    }
    
    $basePath = $type === 'avatar' ? 'uploads/avatars/' : 'uploads/blog_images/';
    $fullPath = $basePath . $filename;
    
    // Check if file exists
    if (file_exists($fullPath)) {
        return $fullPath;
    }
    
    // Return default if file doesn't exist
    return $type === 'avatar' 
        ? 'assets/images/default-avatar.png' 
        : 'assets/images/placeholder-blog.jpg';
}

/**
 * Upload image file with validation
 */
function uploadImage($file, $type = 'blog') {
    $uploadDir = $type === 'avatar' ? 'uploads/avatars/' : 'uploads/blog_images/';
    $maxSize = $type === 'avatar' ? 2 * 1024 * 1024 : 5 * 1024 * 1024; // 2MB for avatar, 5MB for blog
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Validate file
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        $maxSizeMB = $maxSize / 1024 / 1024;
        return ['success' => false, 'message' => "File too large. Maximum size is {$maxSizeMB}MB"];
    }
    
    // Validate mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed'];
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Resize image if needed
        resizeImage($destination, $type);
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Resize image to optimal dimensions
 */
function resizeImage($filepath, $type = 'blog') {
    $maxWidth = $type === 'avatar' ? 300 : 1200;
    $maxHeight = $type === 'avatar' ? 300 : 800;
    
    list($width, $height, $imageType) = getimagesize($filepath);
    
    // Check if resize is needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Create image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return false;
    }
    
    // Create new image
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save resized image
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $filepath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $filepath);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}

/**
 * Delete image file
 */
function deleteImage($filename, $type = 'blog') {
    if (empty($filename)) {
        return false;
    }
    
    $basePath = $type === 'avatar' ? 'uploads/avatars/' : 'uploads/blog_images/';
    $filepath = $basePath . $filename;
    
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

// ============================================
// TEXT & CONTENT HELPERS
// ============================================

/**
 * Format post excerpt
 */
function getExcerpt($content, $length = 150) {
    // Strip HTML tags
    $content = strip_tags($content);
    
    // Remove extra whitespace
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (strlen($content) > $length) {
        // Cut at word boundary
        $content = substr($content, 0, $length);
        $content = substr($content, 0, strrpos($content, ' '));
        $content .= '...';
    }
    
    return $content;
}

/**
 * Truncate text to specific length
 */
function truncate($text, $length = 60, $ending = '...') {
    if (strlen($text) > $length) {
        return substr($text, 0, $length - strlen($ending)) . $ending;
    }
    return $text;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Clean HTML content (for rich text editor)
 */
function cleanHTML($html) {
    // Allow specific tags
    $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';
    return strip_tags($html, $allowedTags);
}

/**
 * Generate slug from text
 */
function generateSlug($text) {
    // Convert to lowercase
    $slug = strtolower($text);
    
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Calculate reading time
 */
function calculateReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = ceil($wordCount / 200); // Average reading speed: 200 words/minute
    return max(1, $minutes);
}

// ============================================
// DATE & TIME HELPERS
// ============================================

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 0) {
        return 'Just now';
    }
    
    $periods = [
        'year' => 31536000,
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    ];
    
    foreach ($periods as $key => $value) {
        if ($difference >= $value) {
            $time = floor($difference / $value);
            $suffix = $time > 1 ? 's' : '';
            return $time . ' ' . $key . $suffix . ' ago';
        }
    }
    
    return 'Just now';
}

/**
 * Format date
 */
function formatDate($datetime, $format = 'F j, Y') {
    return date($format, strtotime($datetime));
}

/**
 * Format date with time
 */
function formatDateTime($datetime, $format = 'F j, Y g:i A') {
    return date($format, strtotime($datetime));
}

// ============================================
// NUMBER & FORMATTING HELPERS
// ============================================

/**
 * Format numbers (1K, 1M, etc.)
 */
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return number_format($num);
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
}

// ============================================
// VALIDATION HELPERS
// ============================================

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate password strength
 */
function isStrongPassword($password, $minLength = 8) {
    if (strlen($password) < $minLength) {
        return false;
    }
    
    // Check for at least one uppercase, one lowercase, one number
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
    
    return $hasUpper && $hasLower && $hasNumber && $hasSpecial;
}

/**
 * Validate username
 */
function isValidUsername($username) {
    // 3-20 characters, alphanumeric and underscore only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

// ============================================
// SECURITY HELPERS
// ============================================

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Escape output for HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ============================================
// ARRAY & DATA HELPERS
// ============================================

/**
 * Check if array key exists and is not empty
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}

/**
 * Paginate array
 */
function paginateArray($array, $page = 1, $perPage = 10) {
    $total = count($array);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($array, $offset, $perPage);
    
    return [
        'items' => $items,
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => ceil($total / $perPage)
    ];
}

// ============================================
// DEBUG HELPERS
// ============================================

/**
 * Debug dump and die
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Debug dump
 */
function dump($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}

/**
 * Check if in development mode
 */
function isDevelopment() {
    return (defined('APP_ENV') && APP_ENV === 'development') || 
           (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development');
}

// ============================================
// DATABASE HELPERS
// ============================================

/**
 * Fetch single record
 */
function fetchOne($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch all records
 */
function fetchAll($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute query
 */
function executeQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get last insert ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->lastInsertId();
}

// ============================================
// MISCELLANEOUS HELPERS
// ============================================

/**
 * Get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get user agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Generate UUID
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Success JSON response
 */
function successResponse($message = 'Success', $data = []) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Error JSON response
 */
function errorResponse($message = 'Error', $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}