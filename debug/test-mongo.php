<?php
require_once 'config/config.php';

echo "🧪 Testing MongoDB Connection...\n\n";

if (isset($GLOBALS['mongoClient']) && $GLOBALS['mongoClient']) {
    echo "✅ MongoDB connection successful!\n";
    echo "🗄️ Database: " . $GLOBALS['databaseName'] . "\n\n";
    
    // Test a simple query
    try {
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $result = $GLOBALS['mongoClient']->executeCommand('admin', $command);
        echo "✅ MongoDB ping successful!\n";
        echo "📡 Server is responding\n";
    } catch (Exception $e) {
        echo "⚠️ Connection exists but ping failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ MongoDB connection failed\n";
    echo "Check your connection string in config/config.php\n";
}
?>
