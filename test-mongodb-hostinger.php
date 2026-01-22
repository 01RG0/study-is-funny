<?php
/**
 * MongoDB Extension Test for Hostinger
 * Upload this file to: public_html/study-is-funny/
 * Then visit: https://studyisfunny.online/study-is-funny/test-mongodb-hostinger.php
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>MongoDB Test</title></head><body>";
echo "<h1>MongoDB Extension Test</h1>";

// Check if MongoDB extension is loaded
if (extension_loaded('mongodb')) {
    echo "<p style='color: green; font-size: 20px;'>✅ MongoDB extension is LOADED</p>";
    echo "<p>Version: " . phpversion('mongodb') . "</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ MongoDB extension is NOT loaded</p>";
    echo "<p><strong>Action Required:</strong> Enable MongoDB extension in Hostinger control panel</p>";
}

echo "<hr>";
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// List all loaded extensions
echo "<h3>Loaded Extensions:</h3>";
echo "<pre>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "- " . $ext . "\n";
}
echo "</pre>";

// Test MongoDB connection if extension is available
if (extension_loaded('mongodb')) {
    echo "<hr>";
    echo "<h2>MongoDB Connection Test</h2>";
    try {
        $uri = 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
        $client = new MongoDB\Driver\Manager($uri);
        
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $result = $client->executeCommand('admin', $command);
        
        echo "<p style='color: green;'>✅ MongoDB connection successful!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Extension loaded but connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "</body></html>";
?>
