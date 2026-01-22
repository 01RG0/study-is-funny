<?php
/**
 * Production Configuration for Hostinger
 * 
 * This file contains production-ready settings for deploying
 * Study is Funny on Hostinger Mini Plan
 * 
 * NOTE: Do NOT use default credentials from dev.config.json
 * Update all values before deploying!
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Option 1: Hostinger MySQL (Recommended for Mini Plan)
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost'); // Usually localhost on Hostinger
define('DB_USER', 'your_hostinger_username'); // Change to your Hostinger DB user
define('DB_PASSWORD', 'your_strong_password'); // Change to strong password
define('DB_NAME', 'your_database_name'); // Change to your database name

// Option 2: MongoDB Atlas (Cloud - if using MongoDB)
// Uncomment to use MongoDB instead
/*
define('DB_TYPE', 'mongodb');
define('MONGO_URI', 'mongodb+srv://username:password@cluster0.xxxxx.mongodb.net/database_name?appName=Cluster0');
define('DB_NAME', 'your_database_name');
*/

// ============================================
// APPLICATION SETTINGS
// ============================================

define('APP_NAME', 'Study is Funny');
define('APP_URL', 'https://yourdomain.com'); // Change to your actual domain
define('BASE_PATH', __DIR__ . '/..');

// ============================================
// DIRECTORY PATHS
// ============================================

define('UPLOADS_DIR', BASE_PATH . '/uploads');
define('VIDEOS_DIR', UPLOADS_DIR . '/videos');
define('HOMEWORK_DIR', UPLOADS_DIR . '/homework');
define('RESOURCES_DIR', UPLOADS_DIR . '/resources');
define('THUMBNAILS_DIR', UPLOADS_DIR . '/thumbnails');

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

// Adjusted for Hostinger Mini Plan limits
define('MAX_VIDEO_SIZE', 104857600); // 100MB (reduced from 500MB for shared hosting)
define('MAX_HOMEWORK_SIZE', 10485760); // 10MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);
define('ALLOWED_HOMEWORK_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png']);

// ============================================
// SECURITY SETTINGS
// ============================================

define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8); // Increased for better security
define('BCRYPT_COST', 12); // Increased from 10 for security

// ============================================
// SESSION CONFIGURATION
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    // Security settings for production
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access
    ini_set('session.cookie_secure', 1);   // HTTPS only (enable if using HTTPS)
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.name', 'STUDY_SESSION'); // Custom session name
    
    // Note: For local development (HTTP), set cookie_secure to 0
    // ini_set('session.cookie_secure', 0);
}

// ============================================
// ERROR HANDLING - PRODUCTION
// ============================================

// Production settings (don't show errors to users)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// ============================================
// EMAIL CONFIGURATION (Optional)
// ============================================

// For sending password reset emails, notifications, etc.
define('MAIL_FROM', 'noreply@yourdomain.com');
define('MAIL_FROM_NAME', 'Study is Funny');
define('SMTP_HOST', 'smtp.hostinger.com'); // Hostinger SMTP
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@yourdomain.com');
define('SMTP_PASSWORD', 'your_email_password');

// ============================================
// PERFORMANCE SETTINGS
// ============================================

// Enable caching for better performance
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour

// Limit API response for pagination
define('ITEMS_PER_PAGE', 20);

// ============================================
// DEBUGGING - DISABLE FOR PRODUCTION
// ============================================

// Set to false in production
define('DEBUG_MODE', false);

// ============================================
// HOSTINGER-SPECIFIC SETTINGS
// ============================================

// Hostinger uses FastCGI, which works well with PHP
define('HOSTING_PROVIDER', 'Hostinger');

// For Hostinger API integration (if needed)
define('HOSTINGER_API_KEY', 'your_hostinger_api_key_if_needed');

// ============================================
// IMPORTANT REMINDERS
// ============================================

/*
✅ BEFORE DEPLOYING:

1. Update ALL placeholder values above:
   - DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
   - APP_URL (your actual domain)
   - MAIL_* settings if using email
   - HOSTINGER_API_KEY if needed

2. Make sure these folders exist and have 755 permissions:
   - /uploads/
   - /uploads/videos/
   - /uploads/homework/
   - /logs/

3. Rename hide.htaccess to .htaccess

4. Test everything:
   - Visit your domain
   - Try uploading files
   - Check error logs

5. Delete debug files:
   - debug_sessions.php
   - debug_sessions_json.php
   - phpinfo.php (if created)

6. Keep this file secure:
   - Change file permissions to 644
   - Never commit with real credentials to git
*/

?>
