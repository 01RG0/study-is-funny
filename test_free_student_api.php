<?php
include 'api/config.php';
$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

// Test with abdelrahman elbadry (free student)
$phone = '+201229794277';
$sessionNumber = 1;
$grade = 'senior3';
$subject = 'physics';

echo "=== Testing API Response for FREE student ===\n";
echo "Phone: $phone (FREE - paymentAmount: 0)\n";
echo "Session: $sessionNumber\n";
echo "Subject: $subject\n";
echo "Grade: $grade\n\n";

// Find the student
$collection = 'senior3_physics';
$query = new MongoDB\Driver\Query(['phone' => $phone]);
$cursor = $client->executeQuery("$databaseName.$collection", $query);
$student = current($cursor->toArray());

if ($student) {
    echo "✅ Student found\n";
    echo "  Name: " . ($student->studentName ?? 'N/A') . "\n";
    echo "  PaymentAmount in DB: " . ($student->paymentAmount ?? 'NOT SET') . "\n";
    echo "  Balance: " . ($student->balance ?? 'N/A') . "\n";
    
    echo "\n=== What API will return ===\n";
    $responsePaymentAmount = $student->paymentAmount ?? 80;
    echo "API paymentAmount: $responsePaymentAmount\n";
    
    if ($responsePaymentAmount == 0) {
        echo "✅ CORRECT - Free student shows price 0\n";
    } else {
        echo "❌ WRONG - Free student should show 0, not $responsePaymentAmount\n";
    }
} else {
    echo "❌ Student not found\n";
}
?>
