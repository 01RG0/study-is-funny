<?php
// Test MongoDB connection
echo "Testing MongoDB Connection:\n";
echo "==========================\n\n";

try {
    $client = new MongoDB\Driver\Manager('mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0');
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $client->executeCommand('admin', $command);
    echo "✓ MongoDB Connection: Successful\n";

    // Try to get a student
    echo "\nTesting Student Data Retrieval:\n";
    $filter = ['phone' => '01280912031'];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $client->executeQuery('attendance_system.users', $query);
    $student = current($cursor->toArray());

    if ($student) {
        echo "✓ Student found: " . $student->name . "\n";
        echo "✓ Phone: " . $student->phone . "\n";
        echo "✓ Grade: " . $student->grade . "\n";
        if (isset($student->subjects)) {
            echo "✓ Subjects: " . implode(', ', $student->subjects) . "\n";
        } else {
            echo "⚠ No subjects found for this student\n";
        }
    } else {
        echo "✗ Student not found\n";
    }

} catch (Exception $e) {
    echo "✗ MongoDB Connection: Failed - " . $e->getMessage() . "\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>