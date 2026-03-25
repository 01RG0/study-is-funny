<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
ob_start();

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/students.php';

ob_clean();

$testPhoneShort = '01299887744';
$testCollection = 'senior2_mechanics';
$sessionNum = 5;

echo "--- ULTIMATE VERIFICATION: $testPhoneShort ---\n";

// Helper to ensure student exists and clear cooldown
function clearCooldown($phone, $collection) {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['phone' => $phone],
        ['$unset' => ['lastVideoUpdate' => "", 'lastHomeworkUpdate' => ""]]
    );
    $GLOBALS['mongoClient']->executeBulkWrite($GLOBALS['databaseName'].".$collection", $bulk);
}

clearCooldown('+201299887744', $testCollection);

// 1. Run update
echo "Running update for 'Successful_Test'...\n";
ob_start();
updateVideoProgress([
    'phone' => $testPhoneShort,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'Successful_Test',
    'incrementSeconds' => 600,
    'collection' => $testCollection
]);
$res = json_decode(ob_get_clean(), true);
echo "Update Result: " . ($res['success'] ? "Success" : "Failed") . " (Modified: " . ($res['modifiedCount'] ?? 0) . ")\n";

// 2. Fetch and Print Everything
function fetchStudentFinal($phone, $collection) {
    $query = new MongoDB\Driver\Query(['phone' => $phone]);
    $cursor = $GLOBALS['mongoClient']->executeQuery($GLOBALS['databaseName'].".$collection", $query);
    return current($cursor->toArray());
}

$student = fetchStudentFinal('+201299887744', $testCollection);
if ($student) {
    echo "Student: " . $student->studentName . " (" . $student->phone . ")\n";
    echo "SESSION 5 STATE:\n";
    echo json_encode($student->{"session_$sessionNum"}, JSON_PRETTY_PRINT) . "\n";
    echo "\nGLOBAL TOTAL: " . ($student->totalWatchTime ?? 0) . " mins\n";
} else {
    echo "STUDENT NOT FOUND\n";
}

echo "\n--- TEST COMPLETE ---\n";
?>
