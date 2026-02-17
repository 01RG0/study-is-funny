<?php
// MongoDB Configuration
$mongoUri = 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
$databaseName = 'attendance_system';

// CORS headers for frontend access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Driver\Manager($mongoUri);
    $database = $databaseName;

    // Test connection
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $client->executeCommand('admin', $command);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Global variables for use in other files
$GLOBALS['mongoClient'] = $client;
$GLOBALS['databaseName'] = $databaseName;
?>