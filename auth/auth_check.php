<?php

// Include required files if not already included
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/session.php';
}

/**
 * Check if user is authenticated
 * Redirects to login page if not logged in
 */
if (!isLoggedIn()) {
    // Get current page URL for redirect after login
    $currentPage = $_SERVER['REQUEST_URI'] ?? '';
    $redirectUrl = url('auth/login.php') . '?redirect=' . urlencode($currentPage);
    
    // Set flash message
    setFlashMessage('Please log in to access this page.', 'warning');
    
    // Redirect to login
    header('Location: ' . $redirectUrl);
    exit();
}

/**
 * Optional: Check for specific role
 * Uncomment and use if you need role-based access control
 */

// Example: Require admin role
// if (!isAdmin()) {
//     setFlashMessage('You do not have permission to access this page.', 'danger');
//     header('Location: ' . url('index.php'));
//     exit();
// }

/**
 * Optional: Check if user account is active
 * Uncomment if you have an 'active' column in users table
 */

// global $conn;
// $stmt = $conn->prepare("SELECT active FROM users WHERE id = ?");
// $stmt->execute([getCurrentUserId()]);
// $user = $stmt->fetch();
// 
// if (!$user || !$user['active']) {
//     destroySession();
//     setFlashMessage('Your account has been deactivated. Please contact support.', 'danger');
//     header('Location: ' . url('auth/login.php'));
//     exit();
// }

/**
 * Update last activity timestamp
 * This is already handled in session.php, but you can add additional tracking here
 */

// Example: Update last_login in database
// global $conn;
// $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
// $stmt->execute([getCurrentUserId()]);

/**
 * Note: This file doesn't output any HTML
 * It only performs authentication checks and redirects
 */
?>