<?php
/**
 * Verification script for Student Deactivation
 */
require_once __DIR__ . '/../config/config.php';

$testPhone = "01000733148"; // A test student phone

echo "--- DEACTIVATION VERIFICATION ---\n";

// 1. Manually set isActive to false for test student
echo "Setting isActive to false for $testPhone...\n";
$client = $GLOBALS['mongoClient'];
$dbName = $GLOBALS['databaseName'];

$bulk = new MongoDB\Driver\BulkWrite();
$bulk->update(['phone' => $testPhone], ['$set' => ['isActive' => false]]);
$client->executeBulkWrite("$dbName.users", $bulk);
$client->executeBulkWrite("$dbName.all_students_view", $bulk);
$client->executeBulkWrite("$dbName.senior2_pure_math", $bulk);

// 2. Test getStudent API
echo "\nTesting getStudent API...\n";
$url = "http://localhost:8000/api/students.php?action=get&phone=" . urlencode($testPhone);
$resp = @file_get_contents($url);
if ($resp === false) {
    echo "ERROR: Could not connect to API at $url. Is the server running on port 8000?\n";
} else {
    echo "Response: " . $resp . "\n";
    $data = json_decode($resp, true);
    if (isset($data['success']) && $data['success'] === false && strpos($data['message'] ?? '', 'deactivated') !== false) {
        echo "SUCCESS: getStudent blocked deactivated student.\n";
    } else {
        echo "FAILURE: getStudent did not block deactivated student correctly.\n";
    }
}

// 3. Test check-access API
echo "\nTesting check-access API...\n";
$url = "http://localhost:8000/api/sessions.php?action=check-access&sessionNumber=2&phone=" . urlencode($testPhone) . "&grade=senior2&subject=mathematics";
$resp = file_get_contents($url);
echo "Response: " . $resp . "\n";
$data = json_decode($resp, true);
if (isset($data['success']) && $data['success'] === false) {
    echo "SUCCESS: check-access blocked deactivated student.\n";
} else {
    echo "FAILURE: check-access granted access to deactivated student.\n";
}

// 4. Restore isActive to true
echo "\nRestoring isActive to true for $testPhone...\n";
$bulkTrue = new MongoDB\Driver\BulkWrite();
$bulkTrue->update(['phone' => $testPhone], ['$set' => ['isActive' => true]]);
$client->executeBulkWrite("$dbName.users", $bulkTrue);
$client->executeBulkWrite("$dbName.all_students_view", $bulkTrue);
echo "Done.\n";
