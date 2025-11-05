<?php

// Prevent direct access
if (!defined('DB_HOST')) {
    die('Direct access not permitted');
}

/**
 * Custom Error Handler
 * Handles PHP errors and converts them to exceptions
 * 
 * @param int $errno Error number
 * @param string $errstr Error message
 * @param string $errfile File where error occurred
 * @param int $errline Line number where error occurred
 * @return bool
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Don't handle errors that are suppressed with @
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Create error message
    $errorMessage = "Error [$errno]: $errstr in $errfile on line $errline";
    
    // Log the error
    logError($errorMessage);
    
    // Display error based on environment
    if (IS_DEV) {
        // Development: Show detailed error
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<strong>File:</strong> $errfile<br>";
        echo "<strong>Line:</strong> $errline<br>";
        echo "</div>";
    } else {
        // Production: Show generic error
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>An error occurred.</strong> Please try again later.";
        echo "</div>";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom Exception Handler
 * Handles uncaught exceptions
 * 
 * @param Exception $exception The uncaught exception
 */
function customExceptionHandler($exception) {
    // Create error message
    $errorMessage = "Uncaught Exception: " . $exception->getMessage() . 
                    " in " . $exception->getFile() . 
                    " on line " . $exception->getLine();
    
    // Log the exception
    logError($errorMessage);
    
    // Display exception based on environment
    if (IS_DEV) {
        // Development: Show detailed exception
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>Exception:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        // Production: Show generic error
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>An unexpected error occurred.</strong> Please try again later.";
        echo "</div>";
    }
}

/**
 * Log Error to File
 * Writes error messages to log file
 * 
 * @param string $message Error message to log
 */
function logError($message) {
    // Create logs directory if it doesn't exist
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Log file path
    $logFile = $logDir . '/error.log';
    
    // Format log message
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Write to log file
    error_log($logMessage, 3, $logFile);
}

/**
 * Display Error Message
 * Shows a formatted error message to the user
 * 
 * @param string $message Error message
 * @param string $type Error type (danger, warning, info)
 */
function displayError($message, $type = 'danger') {
    $alertClass = 'alert-' . $type;
    echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    echo '<strong>Error:</strong> ' . htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

/**
 * Handle Database Errors
 * Specific handler for database-related errors
 * 
 * @param PDOException $e Database exception
 * @param string $context Context where error occurred
 */
function handleDatabaseError($e, $context = '') {
    $errorMessage = "Database Error";
    if (!empty($context)) {
        $errorMessage .= " in $context";
    }
    $errorMessage .= ": " . $e->getMessage();
    
    // Log the error
    logError($errorMessage);
    
    // Display error based on environment
    if (IS_DEV) {
        displayError($e->getMessage(), 'danger');
    } else {
        displayError("A database error occurred. Please try again later.", 'danger');
    }
}

/**
 * Handle File Upload Errors
 * Converts PHP upload error codes to readable messages
 * 
 * @param int $errorCode PHP upload error code
 * @return string Error message
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "File is too large. Maximum size: " . MAX_UPLOAD_SIZE_MB . "MB";
        case UPLOAD_ERR_PARTIAL:
            return "File was only partially uploaded. Please try again.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary folder. Please contact administrator.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk. Please contact administrator.";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension. Please contact administrator.";
        default:
            return "Unknown upload error occurred.";
    }
}

/**
 * Validate and Sanitize Input
 * Helper function to clean user input
 * 
 * @param mixed $data Input data to sanitize
 * @param string $type Type of sanitization (string, email, int, etc.)
 * @return mixed Sanitized data
 */
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'string':
            return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'url':
            return filter_var(trim($data), FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Check if Request is AJAX
 * Determines if the request is an AJAX request
 * 
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON Error Response
 * Sends a JSON formatted error response for AJAX requests
 * 
 * @param string $message Error message
 * @param int $code HTTP status code
 */
function sendJsonError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ]);
    exit();
}

/**
 * Handle 404 Error
 * Displays a 404 page not found error
 */
function handle404Error() {
    http_response_code(404);
    
    if (isAjaxRequest()) {
        sendJsonError('Resource not found', 404);
    }
    
    // Include header if available
    if (file_exists(ROOT_PATH . '/includes/header.php')) {
        $pageTitle = '404 - Page Not Found';
        require_once ROOT_PATH . '/includes/header.php';
    }
    
    echo '<div class="container my-5 text-center">';
    echo '<i class="bi bi-exclamation-triangle display-1 text-warning"></i>';
    echo '<h1 class="display-4 mt-4">404 - Page Not Found</h1>';
    echo '<p class="lead">The page you are looking for does not exist.</p>';
    echo '<a href="' . BASE_URL . '" class="btn btn-primary mt-3">Go to Home</a>';
    echo '</div>';
    
    // Include footer if available
    if (file_exists(ROOT_PATH . '/includes/footer.php')) {
        require_once ROOT_PATH . '/includes/footer.php';
    }
    
    exit();
}

/**
 * Handle 403 Forbidden Error
 * Displays a 403 forbidden error
 */
function handle403Error() {
    http_response_code(403);
    
    if (isAjaxRequest()) {
        sendJsonError('Access forbidden', 403);
    }
    
    // Include header if available
    if (file_exists(ROOT_PATH . '/includes/header.php')) {
        $pageTitle = '403 - Forbidden';
        require_once ROOT_PATH . '/includes/header.php';
    }
    
    echo '<div class="container my-5 text-center">';
    echo '<i class="bi bi-shield-lock display-1 text-danger"></i>';
    echo '<h1 class="display-4 mt-4">403 - Access Forbidden</h1>';
    echo '<p class="lead">You do not have permission to access this resource.</p>';
    echo '<a href="' . BASE_URL . '" class="btn btn-primary mt-3">Go to Home</a>';
    echo '</div>';
    
    // Include footer if available
    if (file_exists(ROOT_PATH . '/includes/footer.php')) {
        require_once ROOT_PATH . '/includes/footer.php';
    }
    
    exit();
}

// Set custom error and exception handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Set error reporting based on environment
if (IS_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

/**
 * USAGE EXAMPLES:
 * 
 * 1. Log an error:
 *    logError("Something went wrong in user registration");
 * 
 * 2. Display error to user:
 *    displayError("Invalid email address", "warning");
 * 
 * 3. Handle database error:
 *    try {
 *        // database query
 *    } catch (PDOException $e) {
 *        handleDatabaseError($e, "User Registration");
 *    }
 * 
 * 4. Sanitize input:
 *    $username = sanitizeInput($_POST['username'], 'string');
 *    $email = sanitizeInput($_POST['email'], 'email');
 * 
 * 5. Handle 404:
 *    if (!$post) {
 *        handle404Error();
 *    }
 */
?>