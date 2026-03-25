<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
ob_start();

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/students.php';

ob_clean();

// We will use the student "team shady" (01299887744) who is stored as +201299887744
$testPhoneShort = '01299887744';
$testPhoneIntl = '+201299887744';
$testCollection = 'senior2_mechanics';
$sessionNum = 5;

echo "--- FINAL SYSTEM VERIFICATION ---\n";

// 1. Test Phone Matching: Send SHORT number (+2 formatting in DB)
echo "Request 1: Using '01...' (Database has +201...)\n";
ob_start();
updateVideoProgress([
    'phone' => $testPhoneShort,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'Normalized_Test',
    'incrementSeconds' => 60,
    'collection' => $testCollection
]);
$res1 = json_decode(ob_get_clean(), true);
echo "Result: Success (Modified: " . ($res1['modifiedCount'] ?? 0) . ")\n";

// 2. Test Phone Matching: Send INTERNATIONAL number
// Wait slightly for cooldown if necessary, but different video title might help
echo "\nRequest 2: Using '+201...' (Unique Video Title to skip cooldown check for this test)\n";
ob_start();
updateVideoProgress([
    'phone' => $testPhoneIntl,
    'sessionNumber' => $sessionNum,
    'videoTitle' => 'Intl_Test',
    'incrementSeconds' => 60,
    'collection' => $testCollection
]);
$res2 = json_decode(ob_get_clean(), true);
echo "Result: Success (Modified: " . ($res2['modifiedCount'] ?? 0) . ")\n";

// 3. Verify Final State
function finalCheck($phone, $collection) {
    $query = new MongoDB\Driver\Query(['phone' => $phone]);
    $cursor = $GLOBALS['mongoClient']->executeQuery($GLOBALS['databaseName'].".$collection", $query);
    return current($cursor->toArray());
}

$student = finalCheck($testPhoneIntl, $testCollection);
echo "\n--- VERIFICATION RESULT ---\n";
echo "Student Found: " . ($student ? "YES (" . $student->studentName . ")" : "NO") . "\n";
echo "Normalized_Test Time: " . ($student->{"session_5"}->video_watch_history->Normalized_Test ?? 0) . "s\n";
echo "Intl_Test Time: " . ($student->{"session_5"}->video_watch_history->Intl_Test ?? 0) . "s\n";

echo "\n--- TEST COMPLETE ---\n";
?>
