<?php
// Mock server variables for CLI
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture output to prevent header issues
ob_start();

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/students.php';

// Clear the buffer
ob_clean();

$testPhone = '01299887744';
$testCollection = 'senior2_physics'; 
$sessionNum = 5;

echo "--- STARTING TEST FOR STUDENT: $testPhone ---\n";

// Helper to check student state (Renamed to avoid conflict)
function verifyStudentState($phone, $collection) {
    $client = $GLOBALS['mongoClient'];
    $db = $GLOBALS['databaseName'];
    $query = new MongoDB\Driver\Query(['phone' => $phone]);
    $cursor = $client->executeQuery("$db.$collection", $query);
    return current($cursor->toArray());
}

$student = verifyStudentState($testPhone, $testCollection);
if (!$student) {
    echo "Creating dummy student for safe test...\n";
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert([
        'phone' => $testPhone,
        'studentName' => 'Test Student',
        'totalWatchTime' => 0,
        "session_$sessionNum" => [
            'date' => date('Y-m-d'),
            'attendanceStatus' => 'Present'
        ]
    ]);
    $GLOBALS['mongoClient']->executeBulkWrite($GLOBALS['databaseName'].".$testCollection", $bulk);
}

// 1. Simulating Lecture Watch Time (300 seconds = 5 mins)
echo "Simulating 300s lecture watch time...\n";
ob_start();
updateVideoProgress([
    'phone' => $testPhone,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'Test Lecture Video',
    'incrementSeconds' => 300,
    'collection' => $testCollection
]);
$res1 = json_decode(ob_get_clean(), true);
echo "Result: " . (isset($res1['success']) && $res1['success'] ? "Success" : "Failed") . "\n\n";

// 2. Simulating Homework View (q1)
echo "Recording homework view for 'q1'...\n";
ob_start();
recordHomeworkView([
    'phone' => $testPhone,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'q1',
    'collection' => $testCollection
]);
$res2 = json_decode(ob_get_clean(), true);
echo "Result: " . (isset($res2['success']) && $res2['success'] ? "Success" : "Failed") . "\n\n";

// 3. Simulating Duplicate Homework View (q1 again)
echo "Recording duplicate homework view for 'q1'...\n";
ob_start();
recordHomeworkView([
    'phone' => $testPhone,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'q1',
    'collection' => $testCollection
]);
$res3 = json_decode(ob_get_clean(), true);
echo "Result: Success (Modified count: " . ($res3['updatedCount'] ?? 0) . " - Expected: 0)\n\n";

// 4. Final verification
$updatedStudent = verifyStudentState($testPhone, $testCollection);
echo "--- FINAL STUDENT STATE ---\n";
echo "Total Watch Time: " . $updatedStudent->totalWatchTime . " min\n";
echo "Session 5 Lecture (Test Lecture Video): " . $updatedStudent->{"session_$sessionNum"}->video_watch_history->Test_Lecture_Video . " sec\n";
echo "Session 5 Homework Count: " . $updatedStudent->{"session_$sessionNum"}->homework_completed_count . "\n";
echo "Session 5 Homework List: " . implode(', ', (array)$updatedStudent->{"session_$sessionNum"}->homework_watched_list) . "\n";

echo "\n--- TEST COMPLETE ---\n";
?>
