<?php
// Register demo students in MongoDB
echo "Registering Demo Students in MongoDB:\n";
echo "=====================================\n\n";

require_once 'api/config.php';

$demoStudents = [
    [
        'name' => 'أحمد محمد',
        'phone' => '01280912031',
        'password' => '123456',
        'grade' => 'senior1'
    ],
    [
        'name' => 'فاطمة أحمد',
        'phone' => '01234567890',
        'password' => '123456',
        'grade' => 'senior2'
    ],
    [
        'name' => 'محمد علي',
        'phone' => '01111111111',
        'password' => '123456',
        'grade' => 'senior1'
    ]
];

foreach ($demoStudents as $student) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Check if student already exists
        $filter = ['phone' => $student['phone']];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $existingStudent = current($cursor->toArray());

        if ($existingStudent) {
            echo "✓ Student {$student['name']} ({$student['phone']}) already exists\n";
            continue;
        }

        // Create new student
        $studentData = [
            'name' => $student['name'],
            'phone' => $student['phone'],
            'password' => $student['password'],
            'grade' => $student['grade'],
            'subjects' => $student['grade'] === 'senior1' ? ["physics", "mathematics", "statistics"] : ["physics", "mathematics", "statistics"],
            'joinDate' => new MongoDB\BSON\UTCDateTime(),
            'lastLogin' => new MongoDB\BSON\UTCDateTime(),
            'isActive' => true,
            'totalSessionsViewed' => rand(0, 10),
            'totalWatchTime' => rand(0, 300),
            'activityLog' => []
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($studentData);
        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        if ($result->getInsertedCount() > 0) {
            echo "✓ Student {$student['name']} ({$student['phone']}) registered successfully\n";
        } else {
            echo "✗ Failed to register {$student['name']}\n";
        }

    } catch (Exception $e) {
        echo "✗ Error registering {$student['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\nDemo student registration completed!\n";
echo "You can now login with these credentials:\n";
echo "- Ahmed: 01280912031 / 123456\n";
echo "- Fatima: 01234567890 / 123456\n";
echo "- Mohamed: 01111111111 / 123456\n";
?>