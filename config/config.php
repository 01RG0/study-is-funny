<?php
/**
 * MongoDB Configuration File
 * Central configuration for Study is Funny platform
 */

// MongoDB Configuration
define('MONGO_URI', 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0');
define('DB_NAME', 'attendance_system');

// Application Settings
define('APP_NAME', 'Study is Funny');
define('APP_URL', 'http://localhost:8000');
define('BASE_PATH', __DIR__ . '/..');

// Directory Paths
define('UPLOADS_DIR', BASE_PATH . '/uploads');
define('VIDEOS_DIR', UPLOADS_DIR . '/videos');
define('HOMEWORK_DIR', UPLOADS_DIR . '/homework');
define('RESOURCES_DIR', UPLOADS_DIR . '/resources');
define('THUMBNAILS_DIR', UPLOADS_DIR . '/thumbnails');

// File Upload Settings
define('MAX_VIDEO_SIZE', 524288000); // 500MB in bytes
define('MAX_HOMEWORK_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/avi', 'video/quicktime']);
define('ALLOWED_HOMEWORK_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png']);

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);
define('BCRYPT_COST', 10);

// Session Configuration
// Only set session ini settings if session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
}

// Error Reporting (Development)
// For production, change these settings:
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);  // Hide errors from users
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// For local development, uncomment:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// error_reporting(0);
// ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('Africa/Cairo'); // Egypt timezone

// CORS Headers (for API endpoints)
if (php_sapi_name() !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Autoload Classes
spl_autoload_register(function ($class) {
    $classFile = BASE_PATH . '/classes/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Create upload directories if they don't exist
$directories = [
    UPLOADS_DIR,
    VIDEOS_DIR,
    HOMEWORK_DIR,
    RESOURCES_DIR,
    THUMBNAILS_DIR,
    BASE_PATH . '/logs'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
