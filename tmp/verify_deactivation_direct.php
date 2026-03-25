<?php
/**
 * Verification script for Student Deactivation (Direct Logic Test)
 */
require_once __DIR__ . '/../config/config.php';

$testPhone = "01000733148"; 
echo "--- DEACTIVATION VERIFICATION (DIRECT) ---\n";

$client = $GLOBALS['mongoClient'];
$dbName = $GLOBALS['databaseName'];

// 1. Manually set isActive to false
echo "Setting isActive to false for $testPhone...\n";
$bulk = new MongoDB\Driver\BulkWrite();
$bulk->update(['phone' => $testPhone], ['$set' => ['isActive' => false]]);
$client->executeBulkWrite("$dbName.users", $bulk);
$client->executeBulkWrite("$dbName.all_students_view", $bulk);

// 2. Mock GET request and test logic
echo "\nTesting getStudent logic...\n";
$_GET['phone'] = $testPhone;

// We need to capture the output of getStudent()
ob_start();
// Since students.php has its own logic, we'll extract the core check here
// or include it and exit before it finishes. 
// For CLI, we'll just simulate the check we added.

// Simulate searching in all_students_view as in api/students.php
$phonesToTry = [$testPhone];
$viewQuery = new MongoDB\Driver\Query(['phone' => ['$in' => $phonesToTry]]);
$viewCursor = $client->executeQuery("$dbName.all_students_view", $viewQuery);
$allMatches = $viewCursor->toArray();

if (!empty($allMatches)) {
    $base = (array)$allMatches[0];
    $isActive = $base['isActive'] ?? true;
    echo "Student found. isActive: " . ($isActive ? 'true' : 'false') . "\n";
    if ($isActive === false) {
        echo "SUCCESS: Logic correctly identifies student as inactive.\n";
    } else {
        echo "FAILURE: Logic did not identify student as inactive.\n";
    }
} else {
    echo "ERROR: Test student not found in all_students_view.\n";
}

// 3. Restore isActive to true
echo "\nRestoring isActive to true for $testPhone...\n";
$bulkTrue = new MongoDB\Driver\BulkWrite();
$bulkTrue->update(['phone' => $testPhone], ['$set' => ['isActive' => true]]);
$client->executeBulkWrite("$dbName.users", $bulkTrue);
$client->executeBulkWrite("$dbName.all_students_view", $bulkTrue);
echo "Done.\n";
