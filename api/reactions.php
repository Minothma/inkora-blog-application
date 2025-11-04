<?php
/**
 * Reactions API
 * 
 * Handles reaction operations (add, remove, toggle)
 * 
 * Actions:
 * - toggle: Add reaction or change existing reaction
 * - remove: Remove reaction
 * 
 * Returns JSON response for AJAX requests
 * 
 * @author Your Name
 * @version 1.0
 */

// Include required files
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to react to posts.']);
    exit();
}

// Get action from POST
$action = $_POST['action'] ?? '';

/**
 * TOGGLE REACTION
 * Adds a new reaction or updates existing one
 */
if ($action === 'toggle') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit();
    }
    
    // Get and validate inputs
    $blogPostId = isset($_POST['blog_post_id']) ? (int)$_POST['blog_post_id'] : 0;
    $reactionType = $_POST['reaction_type'] ?? '';
    
    // Validation
    if ($blogPostId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid blog post.']);
        exit();
    }
    
    // Validate reaction type
    $validReactions = array_keys(REACTION_EMOJIS);
    if (!in_array($reactionType, $validReactions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid reaction type.']);
        exit();
    }
    
    try {
        // Check if blog post exists
        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE id = ? AND status = 'published'");
        $stmt->execute([$blogPostId]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Blog post not found.']);
            exit();
        }
        
        // Check if user already reacted to this post
        $stmt = $conn->prepare("SELECT reaction_type FROM reactions WHERE blog_post_id = ? AND user_id = ?");
        $stmt->execute([$blogPostId, getCurrentUserId()]);
        $existingReaction = $stmt->fetch();
        
        if ($existingReaction) {
            // User already reacted
            if ($existingReaction['reaction_type'] === $reactionType) {
                // Same reaction - remove it (toggle off)
                $deleteStmt = $conn->prepare("DELETE FROM reactions WHERE blog_post_id = ? AND user_id = ?");
                $deleteStmt->execute([$blogPostId, getCurrentUserId()]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reaction removed.',
                    'action' => 'removed'
                ]);
                exit();
            } else {
                // Different reaction - update it
                $updateStmt = $conn->prepare("
                    UPDATE reactions 
                    SET reaction_type = ?, created_at = NOW() 
                    WHERE blog_post_id = ? AND user_id = ?
                ");
                $updateStmt->execute([$reactionType, $blogPostId, getCurrentUserId()]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Reaction updated.',
                    'action' => 'updated'
                ]);
                exit();
            }
        } else {
            // No existing reaction - add new one
            $insertStmt = $conn->prepare("
                INSERT INTO reactions (blog_post_id, user_id, reaction_type, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $insertStmt->execute([$blogPostId, getCurrentUserId(), $reactionType]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reaction added.',
                'action' => 'added'
            ]);
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Reaction error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        exit();
    }
}

/**
 * REMOVE REACTION
 */
elseif ($action === 'remove') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit();
    }
    
    // Get blog post ID
    $blogPostId = isset($_POST['blog_post_id']) ? (int)$_POST['blog_post_id'] : 0;
    
    if ($blogPostId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid blog post.']);
        exit();
    }
    
    try {
        // Delete user's reaction
        $stmt = $conn->prepare("DELETE FROM reactions WHERE blog_post_id = ? AND user_id = ?");
        $stmt->execute([$blogPostId, getCurrentUserId()]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Reaction removed.',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No reaction found to remove.'
            ]);
        }
        exit();
        
    } catch (PDOException $e) {
        error_log("Remove reaction error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        exit();
    }
}

/**
 * GET REACTIONS COUNT (Optional - for live updates)
 */
elseif ($action === 'get_counts') {
    
    $blogPostId = isset($_POST['blog_post_id']) ? (int)$_POST['blog_post_id'] : 0;
    
    if ($blogPostId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid blog post.']);
        exit();
    }
    
    try {
        // Get reaction counts by type
        $stmt = $conn->prepare("
            SELECT reaction_type, COUNT(*) as count 
            FROM reactions 
            WHERE blog_post_id = ? 
            GROUP BY reaction_type
        ");
        $stmt->execute([$blogPostId]);
        $reactions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Get user's reaction if logged in
        $userReaction = null;
        if (isLoggedIn()) {
            $userStmt = $conn->prepare("SELECT reaction_type FROM reactions WHERE blog_post_id = ? AND user_id = ?");
            $userStmt->execute([$blogPostId, getCurrentUserId()]);
            $userReactionResult = $userStmt->fetch();
            if ($userReactionResult) {
                $userReaction = $userReactionResult['reaction_type'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'reactions' => $reactions,
            'user_reaction' => $userReaction
        ]);
        exit();
        
    } catch (PDOException $e) {
        error_log("Get reactions error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
        exit();
    }
}

/**
 * INVALID ACTION
 */
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

/**
 * USAGE EXAMPLES:
 * 
 * 1. Toggle reaction (AJAX):
 * fetch('/api/reactions.php', {
 *     method: 'POST',
 *     body: new URLSearchParams({
 *         action: 'toggle',
 *         blog_post_id: 123,
 *         reaction_type: 'like',
 *         csrf_token: 'token_here'
 *     })
 * }).then(res => res.json()).then(data => console.log(data));
 * 
 * 2. Remove reaction:
 * Same as above but action: 'remove'
 * 
 * 3. Get counts:
 * action: 'get_counts'
 */
?>