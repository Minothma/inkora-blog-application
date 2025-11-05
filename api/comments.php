<?php

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to perform this action.']);
    exit();
}

// Get action from POST
$action = $_POST['action'] ?? '';

/**
 * ADD COMMENT
 */
if ($action === 'add') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        // For form submissions, redirect with error
        setFlashMessage("Invalid security token. Please try again.", 'danger');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('posts/index.php')));
        exit();
    }
    
    // Get and validate inputs
    $blogPostId = isset($_POST['blog_post_id']) ? (int)$_POST['blog_post_id'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    
    // Validation
    $errors = [];
    
    if ($blogPostId <= 0) {
        $errors[] = "Invalid blog post.";
    }
    
    if (empty($comment)) {
        $errors[] = "Comment cannot be empty.";
    } elseif (strlen($comment) < COMMENT_MIN_LENGTH) {
        $errors[] = "Comment must be at least " . COMMENT_MIN_LENGTH . " characters.";
    } elseif (strlen($comment) > COMMENT_MAX_LENGTH) {
        $errors[] = "Comment must not exceed " . COMMENT_MAX_LENGTH . " characters.";
    }
    
    if (!empty($errors)) {
        setFlashMessage(implode(' ', $errors), 'danger');
        header('Location: ' . url('posts/view.php?id=' . $blogPostId));
        exit();
    }
    
    try {
        // Check if blog post exists
        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE id = ?");
        $stmt->execute([$blogPostId]);
        
        if ($stmt->rowCount() === 0) {
            setFlashMessage(MSG_POST_NOT_FOUND, 'danger');
            header('Location: ' . url('posts/index.php'));
            exit();
        }
        
        // Insert comment
        $stmt = $conn->prepare("
            INSERT INTO comments (blog_post_id, user_id, comment, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$blogPostId, getCurrentUserId(), $comment]);
        
        // Success
        setFlashMessage(MSG_COMMENT_ADDED, 'success');
        header('Location: ' . url('posts/view.php?id=' . $blogPostId) . '#commentsList');
        exit();
        
    } catch (PDOException $e) {
        error_log("Add comment error: " . $e->getMessage());
        setFlashMessage("An error occurred while adding your comment. Please try again.", 'danger');
        header('Location: ' . url('posts/view.php?id=' . $blogPostId));
        exit();
    }
}

/**
 * DELETE COMMENT
 */
elseif ($action === 'delete') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage("Invalid security token. Please try again.", 'danger');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('posts/index.php')));
        exit();
    }
    
    // Get inputs
    $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    if ($commentId <= 0) {
        setFlashMessage("Invalid comment.", 'danger');
        header('Location: ' . url('posts/index.php'));
        exit();
    }
    
    try {
        // Get comment data with post info
        $stmt = $conn->prepare("
            SELECT c.*, bp.user_id as post_author_id 
            FROM comments c
            JOIN blog_posts bp ON c.blog_post_id = bp.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();
        
        if (!$comment) {
            setFlashMessage("Comment not found.", 'danger');
            header('Location: ' . url('posts/index.php'));
            exit();
        }
        
        // Check if user can delete (comment author or post author)
        if ($comment['user_id'] != getCurrentUserId() && $comment['post_author_id'] != getCurrentUserId()) {
            setFlashMessage(MSG_UNAUTHORIZED, 'danger');
            header('Location: ' . url('posts/view.php?id=' . $comment['blog_post_id']));
            exit();
        }
        
        // Delete comment
        $deleteStmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $deleteStmt->execute([$commentId]);
        
        // Success
        setFlashMessage(MSG_COMMENT_DELETED, 'success');
        header('Location: ' . url('posts/view.php?id=' . ($postId > 0 ? $postId : $comment['blog_post_id'])));
        exit();
        
    } catch (PDOException $e) {
        error_log("Delete comment error: " . $e->getMessage());
        setFlashMessage("An error occurred while deleting the comment. Please try again.", 'danger');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('posts/index.php')));
        exit();
    }
}

/**
 * INVALID ACTION
 */
else {
    setFlashMessage("Invalid action.", 'danger');
    header('Location: ' . url('posts/index.php'));
    exit();
}

/**
 * Note: This API uses form submissions and redirects
 * For AJAX requests, modify to return JSON responses instead of redirects
 */
?>