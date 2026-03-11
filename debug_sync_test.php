<?php
include 'api/config.php';
$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

// Include necessary functions
function normalizePhoneNumber($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11 && strpos($phone, '0') === 0) {
        return $phone;
    }
    if (strlen($phone) === 12 && strpos($phone, '2') === 0) {
        return '0' . substr($phone, 1);
    }
    return $phone;
}

function convertTo20Format($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11 && strpos($phone, '0') === 0) {
        return '+2' . $phone;
    }
    if (preg_match('/^\+?20/', $phone)) {
        return preg_replace('/^\+/', '', $phone);
    }
    return '+20' . ltrim($phone, '0');
}

try {
    $phone = '+201013044079';
    $sessionNumber = 1;
    $subject = 'S1 Math';
    $grade = 'senior1';
    $cost = 75;
    
    $phoneVariations = [
        $phone,
        normalizePhoneNumber($phone),
        convertTo20Format($phone),
    ];
    $phoneVariations = array_values(array_unique(array_filter($phoneVariations)));
    
    $targetCollection = 'senior1_math';
    $sessionKey = 'session_' . $sessionNumber;
    
    echo "Testing SYNC to students collection...\n\n";
    
    // This is what the API does at the end
    $syncBulk = new MongoDB\Driver\BulkWrite();
    $syncBulk->update(
        ['phone' => ['$in' => $phoneVariations], 'subject' => ['$regex' => $subject, '$options' => 'i']],
        ['$inc' => ['balance' => -$cost], '$set' => [
            $sessionKey . '.online_session' => true,
            $sessionKey . '.purchased_at' => date('Y-m-d\TH:i:s.v\Z'),
            $sessionKey . '.attendanceStatus' => 'absent'
        ]],
        ['multi' => false]
    );
    
    echo "Executing sync bulk to 'students' collection...\n";
    $result = $client->executeBulkWrite("$databaseName.students", $syncBulk);
    echo "✅ Sync succeeded!\n";
    echo "   Modified count: " . $result->getModifiedCount() . "\n";
    echo "   Matched count: " . $result->getMatchedCount() . "\n";
    
    if ($result->getModifiedCount() === 0) {
        echo "\n⚠️ Warning: No documents modified in students collection\n";
        echo "   This might be because:\n";
        echo "   1. Student doesn't exist in 'students' collection\n";
        echo "   2. Subject filter didn't match\n";
        echo "   3. Phone number didn't match\n";
    }
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
}
?>
