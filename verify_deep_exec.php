<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
ob_start();

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/students.php';

ob_clean();

$testPhoneShort = '01299887744';
$testCollection = 'senior2_mechanics';
$sessionNum = 5;

echo "--- DEEP VERIFICATION: $testPhoneShort ---\n";

function fetchStudent($phone, $collection) {
    $cleanPhone = ltrim($phone, '+');
    $localPhone = '0' . ltrim($cleanPhone, '0');
    $intlPhone = '+2' . $localPhone;

    $query = new MongoDB\Driver\Query([
        '$or' => [['phone' => $localPhone], ['phone' => $intlPhone]]
    ]);
    $cursor = $GLOBALS['mongoClient']->executeQuery($GLOBALS['databaseName'].".$collection", $query);
    return current($cursor->toArray());
}

// 1. Run update
echo "Running update for 'Normalized_Test'...\n";
ob_start();
updateVideoProgress([
    'phone' => $testPhoneShort,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'Normalized_Test',
    'incrementSeconds' => 600,
    'collection' => $testCollection
]);
$res = json_decode(ob_get_clean(), true);
echo "Update Result: " . ($res['success'] ? "Success" : "Failed") . " (Modified: " . ($res['modifiedCount'] ?? 0) . ")\n";

// 2. Fetch and Print Everything
$student = fetchStudent($testPhoneShort, $testCollection);
if ($student) {
    echo "Student: " . $student->studentName . " (" . $student->phone . ")\n";
    echo "SESSION 5 STATE:\n";
    echo json_encode($student->{"session_$sessionNum"}, JSON_PRETTY_PRINT) . "\n";
    echo "\nGLOBAL TOTAL: " . ($student->totalWatchTime ?? 0) . " mins\n";
} else {
    echo "STUDENT NOT FOUND IN $testCollection\n";
}

echo "\n--- TEST COMPLETE ---\n";
?>
