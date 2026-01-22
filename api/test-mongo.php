<?php
/**
 * Test MongoDB through API route
 * Upload to: public_html/study-is-funny/api/
 * Visit: https://studyisfunny.online/study-is-funny/api/test-mongo.php
 */

echo "Testing MongoDB through API path...\n\n";

// Test 1: Check extension
echo "1. Extension loaded: " . (extension_loaded('mongodb') ? "YES ✅" : "NO ❌") . "\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   SAPI: " . php_sapi_name() . "\n\n";

// Test 2: Check class
echo "2. MongoDB\\Driver\\Manager class exists: " . (class_exists('MongoDB\\Driver\\Manager') ? "YES ✅" : "NO ❌") . "\n\n";

// Test 3: Try to create Manager
echo "3. Creating MongoDB Manager...\n";
try {
    $uri = 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
    $client = new MongoDB\Driver\Manager($uri);
    echo "   Result: SUCCESS ✅\n\n";
    
    // Test ping
    echo "4. Testing connection...\n";
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $result = $client->executeCommand('admin', $command);
    echo "   Result: CONNECTION SUCCESS ✅\n";
} catch (Error $e) {
    echo "   ERROR: " . $e->getMessage() . " ❌\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "   EXCEPTION: " . $e->getMessage() . " ⚠️\n";
}

// Test 4: Check if config can be loaded
echo "\n5. Testing config.php load...\n";
try {
    require_once dirname(__DIR__) . '/config/config.php';
    echo "   Config loaded: SUCCESS ✅\n";
    echo "   MongoClient exists: " . (isset($GLOBALS['mongoClient']) ? "YES" : "NO") . "\n";
} catch (Error $e) {
    echo "   ERROR loading config: " . $e->getMessage() . " ❌\n";
}
?>
