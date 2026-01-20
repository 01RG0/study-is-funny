<?php
// Check if student exists in database
echo "Checking Student in Database:\n";
echo "=============================\n\n";

$phone = isset($argv[1]) ? $argv[1] : '01280912038';
echo "Checking phone: $phone\n\n";

require_once 'api/config.php';

try {
    $client = $GLOBALS['mongoClient'];
    $databaseName = $GLOBALS['databaseName'];

    // Check if student exists
    $filter = ['phone' => $phone];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $client->executeQuery("$databaseName.users", $query);
    $student = current($cursor->toArray());

    if ($student) {
        echo "✓ Student found:\n";
        echo "  Name: " . $student->name . "\n";
        echo "  Phone: " . $student->phone . "\n";
        echo "  Grade: " . $student->grade . "\n";
        echo "  Subjects: " . (isset($student->subjects) ? implode(', ', $student->subjects) : 'None') . "\n";
        echo "  Active: " . ($student->isActive ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ Student not found\n";
        echo "Adding demo student...\n\n";

        // Add the student
        $studentData = [
            'name' => 'أحمد محمد',
            'phone' => $phone,
            'password' => '123456',
            'grade' => 'senior1',
            'subjects' => ["physics", "mathematics", "statistics"],
            'joinDate' => new MongoDB\BSON\UTCDateTime(),
            'lastLogin' => new MongoDB\BSON\UTCDateTime(),
            'isActive' => true,
            'totalSessionsViewed' => 5,
            'totalWatchTime' => 120,
            'activityLog' => []
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($studentData);
        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        if ($result->getInsertedCount() > 0) {
            echo "✓ Demo student added successfully!\n";
        } else {
            echo "✗ Failed to add student\n";
        }
    }

    // List all students
    echo "\nAll Students in Database:\n";
    echo "=========================\n";

    $filter = [];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $client->executeQuery("$databaseName.users", $query);

    foreach ($cursor as $student) {
        echo "- " . $student->name . " (" . $student->phone . ") - " . $student->grade . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>