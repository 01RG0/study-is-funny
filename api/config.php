<?php
// Load environment variables
require_once __DIR__ . '/../load_env.php';

// SECURITY: Load sensitive configuration from environment variables
// Never commit API keys or credentials to version control

// SQLite Database for Payment Processing
if (!defined('DB_FILE')) {
    define('DB_FILE', __DIR__ . '/../instapay-checker/transactions.db');
}

// Load API keys from environment variables
// Instapay API Key - Set via environment variable or .env file
if (!defined('INSTAPAY_API_KEY')) {
    define('INSTAPAY_API_KEY', getenv('INSTAPAY_API_KEY') ?: '');
}

// MongoDB Configuration
// Load MongoDB URI from environment or use default
$mongoUri = getenv('MONGODB_URI') ?: 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
$databaseName = 'attendance_system';

// CORS headers for frontend access
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Global variables for use in other files
$client = null;
$database = $databaseName;

// Skip DB connection if requested (useful for tests or standalone includes)
if (!defined('SKIP_DB_CONN')) {
    try {
        // Connect to MongoDB
        error_log('Connecting to MongoDB...');
        $client = new MongoDB\Driver\Manager($mongoUri);
        error_log('MongoDB manager created successfully');

        // Test connection
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $client->executeCommand('admin', $command);
        error_log('MongoDB connection test successful (ping)');

    } catch (Exception $e) {
        // Log error but don't exit if it's not a critical request
        error_log('Database connection failed: ' . $e->getMessage());

        if (isset($_SERVER['REQUEST_METHOD'])) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

$GLOBALS['mongoClient'] = $client;
$GLOBALS['databaseName'] = $databaseName;
?>