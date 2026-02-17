<?php
include 'api/config.php';
$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

$collections = ['senior1_math', 'senior2_pure_math', 'senior2_physics', 'senior2_mechanics', 'senior3_math', 'senior3_physics', 'senior3_statistics'];

echo "=== Checking FREE students (paymentAmount = 0) ===\n\n";

foreach ($collections as $col) {
    $query = new MongoDB\Driver\Query(['paymentAmount' => 0], ['limit' => 5]);
    $cursor = $client->executeQuery("$databaseName.$col", $query);
    $docs = $cursor->toArray();
    
    if (count($docs) > 0) {
        echo "Collection: $col\n";
        foreach ($docs as $doc) {
            echo "  - " . ($doc->studentName ?? 'unknown') . " (Phone: " . ($doc->phone ?? 'N/A') . ")\n";
            echo "    paymentAmount: " . ($doc->paymentAmount ?? 'NOT SET') . "\n";
            echo "    balance: " . ($doc->balance ?? 'NOT SET') . "\n";
        }
        echo "\n";
    }
}
?>
