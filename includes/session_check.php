<?php
/**
 * Session Security & Authentication Check
 * Include this file at the top of protected pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

/**
 * Check if user is logged in
 * Redirects to login if not authenticated
 */
function requireLogin($redirectUrl = '/login/index.html') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: ' . $redirectUrl . '?msg=session_expired');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user has admin role
 */
function requireAdmin($redirectUrl = '/') {
    requireLogin();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('Unauthorized Access - Admin privileges required');
    }
}

/**
 * Check if user has teacher or admin role
 */
function requireTeacher($redirectUrl = '/') {
    requireLogin();
    
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher', 'assistant'])) {
        http_response_code(403);
        die('Unauthorized Access - Teacher/Admin privileges required');
    }
}

/**
 * Get current user ID
 * @return string|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user info
 * @return array|null
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student'
    ];
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF token from POST request
 * Dies if token is invalid
 */
function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

/**
 * Sanitize input data
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Egyptian format)
 * @param string $phone Phone number
 * @return bool
 */
function validatePhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Egyptian phone: +2 or 01 followed by 9 digits
    return preg_match('/^(\+?2)?01[0-9]{9}$/', $phone);
}

/**
 * Log user activity
 * @param string $action Action performed
 * @param string $entityType Type of entity
 * @param string $entityId Entity ID
 * @param string $description Description
 */
function logActivity($action, $entityType, $entityId, $description = '') {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        $db = new DatabaseMongo();
        
        $logData = [
            'user_id' => DatabaseMongo::createObjectId($_SESSION['user_id']),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'createdAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        $db->insert('activitylogs', $logData);
    } catch (Exception $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

/**
 * Log error
 * @param string $errorType Error type
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($errorType, $message, $context = []) {
    try {
        $db = new DatabaseMongo();
        
        $errorData = [
            'error_type' => $errorType,
            'message' => $message,
            'context' => $context,
            'user_id' => isset($_SESSION['user_id']) ? DatabaseMongo::createObjectId($_SESSION['user_id']) : null,
            'request_url' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'severity' => 'medium',
            'resolved' => false,
            'createdAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        $db->insert('errorlogs', $errorData);
    } catch (Exception $e) {
        error_log('Failed to log error: ' . $e->getMessage());
    }
}

/**
 * Format MongoDB date for display
 * @param MongoDB\BSON\UTCDateTime $date MongoDB date
 * @param string $format Date format
 * @return string
 */
function formatMongoDate($date, $format = 'Y-m-d H:i:s') {
    if (!$date) {
        return '';
    }
    
    try {
        return $date->toDateTime()->format($format);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Convert ObjectId to string safely
 * @param mixed $objectId ObjectId or string
 * @return string
 */
function objectIdToString($objectId) {
    if (is_string($objectId)) {
        return $objectId;
    }
    
    if ($objectId instanceof MongoDB\BSON\ObjectId) {
        return (string) $objectId;
    }
    
    return '';
}
?>
