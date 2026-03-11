<?php
include 'api/config.php';
include 'api/sessions.php';  // Include the sessions API to get the functions
$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

// Test purchase for Rodaina Ehab trying to buy session 1
$phone = '+201013044079';
$sessionNumber = 1;
$subject = 'S1 Math';
$grade = 'senior1';

echo "=== Testing Purchase for Rodaina Ehab ===\n";
echo "Phone: $phone\n";
echo "Session: $sessionNumber\n";
echo "Subject: $subject\n";
echo "Grade: $grade\n\n";

// Simulate the API purchase logic
try {
    // Find collection
    $targetCollection = 'senior1_math';
    echo "Target Collection: $targetCollection\n";
    
    // Find student
    $phoneVariations = [
        $phone,
        normalizePhoneNumber($phone),
        convertTo20Format($phone),
    ];
    $phoneVariations = array_values(array_unique(array_filter($phoneVariations)));
    echo "Phone Variations: " . implode(', ', $phoneVariations) . "\n\n";
    
    $query = new MongoDB\Driver\Query(['phone' => ['$in' => $phoneVariations], 'isActive' => true]);
    $cursor = $client->executeQuery("$databaseName.$targetCollection", $query);
    $student = current($cursor->toArray());
    
    if (!$student) {
        echo "❌ Student not found!\n";
        exit;
    }
    
    echo "✅ Student found: " . $student->studentName . "\n";
    echo "   Balance: " . $student->balance . "\n";
    echo "   Payment Amount: " . $student->paymentAmount . "\n";
    echo "   Session 1 exists: " . (isset($student->session_1) ? 'YES' : 'NO') . "\n";
    
    if (isset($student->session_1)) {
        echo "   Session 1 online_session: " . ($student->session_1->online_session ?? 'NOT SET') . "\n";
        echo "   Session 1 recordedBy type: " . gettype($student->session_1->recordedBy ?? null) . "\n";
    }
    
    // Check balance
    $balance = (float)$student->balance;
    $cost = isset($student->paymentAmount) ? (float)$student->paymentAmount : 80;
    $isFreeStudent = isset($student->paymentAmount) && (float)$student->paymentAmount === 0.0;
    
    echo "\n   Cost to purchase: $cost EGP\n";
    echo "   Free student: " . ($isFreeStudent ? 'YES' : 'NO') . "\n";
    
    if (!$isFreeStudent && $balance < $cost) {
        echo "❌ Insufficient balance!\n";
        exit;
    }
    
    echo "\n✅ All checks passed - Ready to purchase!\n";
    
    // Build session data like the API does
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $purchaseTimestamp = $now->format('Y-m-d\TH:i:s.v\Z');
    $currentTime = $now->format('H:i:s');
    $sessionKey = 'session_' . $sessionNumber;
    
    $sessionData = [
        'online_session' => true,
        'purchased_at' => $purchaseTimestamp,
        'online_session_assistant' => null,
        'online_session_completed_at' => $purchaseTimestamp,
        'attendanceStatus' => 'absent',
        'date' => date('Y-m-d'),
        'homeworkStatus' => null,
        'examMark' => null,
        'centerAttendance' => null,
        'paidAmount' => $cost,
        'books' => 0,
        'comment' => 'Online session purchased',
        'recordedBy' => null,
        'time' => $currentTime,
        'source' => 'online-purchase',
        'online_attendance' => false,
        'online_attendance_assistant' => null,
        'online_attendance_completed_at' => null
    ];
    
    echo "\nSession data to be set: " . count($sessionData) . " fields\n";
    foreach ($sessionData as $key => $value) {
        echo "  - $key: " . (is_null($value) ? 'null' : $value) . "\n";
    }
    
    // Build the $set array
    $setArray = [];
    foreach ($sessionData as $key => $value) {
        $setArray[$sessionKey . '.' . $key] = $value;
    }
    
    echo "\n✅ Test passed - Ready to execute bulk write\n";
    echo "   Will set " . count($setArray) . " fields\n";
    echo "   Will deduct balance:" . ($balance - $cost) . " EGP\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
