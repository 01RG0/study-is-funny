<?php
include 'api/config.php';
$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

$collections = ['senior1_math', 'senior2_pure_math', 'senior2_physics', 'senior2_mechanics', 'senior3_math', 'senior3_physics', 'senior3_statistics'];

echo "=== Checking paymentAmount in student documents ===\n\n";

foreach ($collections as $col) {
    $query = new MongoDB\Driver\Query([], ['limit' => 3]);
    $cursor = $client->executeQuery("$databaseName.$col", $query);
    $docs = $cursor->toArray();
    
    if (count($docs) > 0) {
        echo "Collection: $col\n";
        foreach ($docs as $doc) {
            $paymentAmount = $doc->paymentAmount ?? 'NOT SET';
            echo "  - " . ($doc->studentName ?? 'unknown') . ": paymentAmount = $paymentAmount\n";
        }
        echo "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Students with paymentAmount = 0: These should probably be 80 (default price)\n";
echo "Students with paymentAmount NOT SET: These will default to 80 in API response\n";
?>
