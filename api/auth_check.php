<?php
/**
 * Admin Authentication Guard
 * Include this file at the top of all admin pages to ensure user is authenticated
 */

session_start();

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Determine correct login path based on current URL structure
function getLoginPath() {
    $pathname = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($pathname, '/study-is-funny/') !== false) {
        return '/study-is-funny/admin/login.html';
    }
    return '/admin/login.html';
}

// Check if admin session exists
if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
    // Also check for token in Authorization header (for API calls)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        // Redirect to login if not authenticated and not API call
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            header('Location: ' . getLoginPath());
            exit;
        }
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Validate token if provided
    $token = str_replace('Bearer ', '', $authHeader);
    if (!validateAdminToken($token)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
    
    // Set session variables from token
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_token'] = $token;
}

/**
 * Validate admin token from database
 */
function validateAdminToken($token) {
    try {
        // Check if token exists in database
        require_once dirname(__DIR__) . '/config/config.php';
        
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        
        $filter = [
            'token' => $token,
            'type' => 'admin',
            'expiresAt' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
        ];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.auth_tokens", $query);
        $results = $cursor->toArray();
        
        if (count($results) > 0) {
            $tokenData = $results[0];
            $_SESSION['admin_username'] = $tokenData->username ?? 'admin';
            $_SESSION['admin_authenticated'] = true;
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get current admin username
 */
function getCurrentAdminUsername() {
    return $_SESSION['admin_username'] ?? 'admin';
}

/**
 * Get current admin token
 */
function getCurrentAdminToken() {
    return $_SESSION['admin_token'] ?? '';
}

/**
 * Logout admin
 */
function logoutAdmin() {
    $token = $_SESSION['admin_token'] ?? '';
    
    // Remove token from database
    try {
        require_once dirname(__DIR__) . '/config/config.php';
        
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete(['token' => $token], ['limit' => 0]);
        $client->executeBulkWrite("$databaseName.auth_tokens", $bulk);
    } catch (Exception $e) {
        // Log error but continue with logout
    }
    
    // Clear session
    session_destroy();
}
?>
