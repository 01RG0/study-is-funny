<?php
// Direct MongoDB test - No config includes
echo "PHP Version: " . phpversion() . "<br>";
echo "MongoDB loaded: " . (extension_loaded('mongodb') ? "YES ✅" : "NO ❌") . "<br>";
echo "Class exists: " . (class_exists('MongoDB\\Driver\\Manager') ? "YES ✅" : "NO ❌") . "<br>";

if (class_exists('MongoDB\\Driver\\Manager')) {
    try {
        $client = new MongoDB\Driver\Manager('mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0');
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $client->executeCommand('admin', $command);
        echo "Connection: SUCCESS ✅<br>";
    } catch (Exception $e) {
        echo "Connection: FAILED - " . $e->getMessage() . "<br>";
    }
}
?>
