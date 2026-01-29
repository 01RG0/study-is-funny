<?php
/**
 * Diagnostic Test for MongoDB and API
 * Visit: https://studyisfunny.online/study-is-funny/diagnose.php
 */

echo "<!DOCTYPE html>
<html>
<head>
<title>Diagnostic Test</title>
<style>
body { font-family: Arial; margin: 20px; background: #f5f5f5; }
.good { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
.bad { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
.warn { color: orange; background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid orange; }
h2 { color: #333; }
code { background: #eee; padding: 2px 5px; }
</style>
</head>
<body>
<h1>Study is Funny - Diagnostic Test</h1>";

// 1. Check MongoDB Extension
echo "<h2>1. MongoDB Extension</h2>";
if (extension_loaded('mongodb')) {
    echo '<div class="good">✅ MongoDB extension is LOADED</div>';
    echo '<div class="good">Version: ' . phpversion('mongodb') . '</div>';
} else {
    echo '<div class="bad">❌ MongoDB extension is NOT loaded</div>';
    echo '<div class="bad">Action: Enable mongodb extension in Hostinger PHP Extensions</div>';
}

// 2. Check MongoDB Class
echo "<h2>2. MongoDB\\Driver\\Manager Class</h2>";
if (class_exists('MongoDB\\Driver\\Manager')) {
    echo '<div class="good">✅ Class exists</div>';
} else {
    echo '<div class="bad">❌ Class NOT found</div>';
}

// 3. Test Config Load
echo "<h2>3. Config Loading</h2>";
// Since we're in /study-is-funny/diagnose.php, config is at /config/config.php (one level up)
$configPath = __DIR__ . '/config/config.php';
if (file_exists($configPath)) {
    echo '<div class="good">✅ Config file exists at: ' . $configPath . '</div>';
    
    // Load config
    require_once $configPath;
    
    if ($GLOBALS['mongoClient']) {
        echo '<div class="good">✅ MongoDB client initialized</div>';
        echo '<div class="good">Database: ' . $GLOBALS['databaseName'] . '</div>';
    } else {
        echo '<div class="bad">❌ MongoDB client NOT initialized</div>';
        echo '<div class="warn">This could mean: MongoDB extension is disabled, or connection failed</div>';
    }
} else {
    echo '<div class="bad">❌ Config file not found at: ' . $configPath . '</div>';
}

// 4. Test API Direct Call
echo "<h2>4. Test API Endpoint</h2>";
echo '<p>Testing: <code>/api/students.php?action=get&phone=01280912038</code></p>';
$apiPath = __DIR__ . '/api/students.php';
if (file_exists($apiPath)) {
    echo '<div class="good">✅ API file exists</div>';
} else {
    echo '<div class="bad">❌ API file not found</div>';
}

// 5. Check MongoDB Connection
echo "<h2>5. MongoDB Connection Test</h2>";
if (extension_loaded('mongodb') && class_exists('MongoDB\\Driver\\Manager')) {
    try {
        $uri = defined('MONGO_URI') ? MONGO_URI : 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
        $mgr = new MongoDB\Driver\Manager($uri);
        $cmd = new MongoDB\Driver\Command(['ping' => 1]);
        $result = $mgr->executeCommand('admin', $cmd);
        echo '<div class="good">✅ MongoDB connection SUCCESSFUL</div>';
    } catch (Exception $e) {
        echo '<div class="bad">❌ MongoDB connection FAILED</div>';
        echo '<div class="bad">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="bad">❌ Cannot test - MongoDB extension not available</div>';
}

// 6. Check error log
echo "<h2>6. Recent Errors</h2>";
$logPath = __DIR__ . '/logs/error.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $recent = array_slice($lines, -10);
    echo '<div style="background: #f0f0f0; padding: 10px; overflow-x: auto;"><pre>';
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo '</pre></div>';
} else {
    echo '<div class="warn">⚠️ No error log found</div>';
}

echo "</body></html>";
?>
