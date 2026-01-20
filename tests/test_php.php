<?php
// Simple PHP Test File
echo "PHP Test Results:\n";
echo "================\n\n";

// PHP Version
echo "PHP Version: " . phpversion() . "\n";

// Check MongoDB extension
echo "MongoDB Extension: " . (extension_loaded('mongodb') ? 'Available' : 'Not Available') . "\n";

// Check other common extensions
$extensions = ['curl', 'json', 'mbstring', 'openssl'];
echo "\nExtensions Check:\n";
foreach ($extensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? '✓' : '✗') . "\n";
}

// Server info
echo "\nServer Info:\n";
echo "OS: " . php_uname('s') . " " . php_uname('r') . "\n";
echo "Architecture: " . php_uname('m') . "\n";

// Test basic functionality
echo "\nFunctionality Tests:\n";
echo "- JSON encode/decode: " . (function_exists('json_encode') ? '✓' : '✗') . "\n";
echo "- cURL: " . (function_exists('curl_init') ? '✓' : '✗') . "\n";

// Test MongoDB connection (if extension available)
if (extension_loaded('mongodb')) {
    echo "\nMongoDB Connection Test:\n";
    try {
        $mongoUri = 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
        $client = new MongoDB\Driver\Manager($mongoUri);
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $client->executeCommand('admin', $command);
        echo "✓ MongoDB connection successful\n";
    } catch (Exception $e) {
        echo "✗ MongoDB connection failed: " . $e->getMessage() . "\n";
    }
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>