<?php
/**
 * Diagnostic Script: Check Purchase & Balance Issue
 * 
 * This script checks:
 * 1. What phone number variations exist in database
 * 2. Where the student records are stored
 * 3. Why balance isn't decreasing
 */

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/config.php';

$client = $GLOBALS['mongoClient'];
$databaseName = $GLOBALS['databaseName'];

// Get phone from query parameter
$phone = $_GET['phone'] ?? '';

if (!$phone) {
    echo json_encode([
        'error' => 'Missing phone parameter',
        'usage' => 'check_purchase_issue.php?phone=01234567890&subject=physics&grade=senior2'
    ]);
    exit;
}

$subject = $_GET['subject'] ?? '';
$grade = $_GET['grade'] ?? '';

// Helper functions (copied from sessions.php)
function normalizePhoneNumber($phone) {
    $phone = preg_replace('/[^\d+]/', '', $phone);
    if (strpos($phone, '+20') === 0) {
        return '0' . substr($phone, 3);
    }
    if (strpos($phone, '+') === 0) {
        return substr($phone, 1);
    }
    if (strpos($phone, '0') !== 0 && strpos($phone, '20') === 0) {
        return '0' . substr($phone, 2);
    }
    return $phone;
}

function convertTo20Format($phone) {
    $normalized = normalizePhoneNumber($phone);
    if (strpos($normalized, '0') === 0) {
        return '+20' . substr($normalized, 1);
    }
    return '+20' . $normalized;
}

// Generate variations
$phoneVariations = [
    $phone,
    normalizePhoneNumber($phone),
    convertTo20Format($phone),
];
$phoneVariations = array_values(array_unique(array_filter($phoneVariations)));

$results = [
    'input_phone' => $phone,
    'phone_variations' => $phoneVariations,
    'subject' => $subject,
    'grade' => $grade,
    'findings' => []
];

// Check each collection
$collections = [
    'students',
    'senior1_math',
    'senior2_pure_math',
    'senior2_mechanics',
    'senior2_physics',
    'senior3_math',
    'senior3_physics',
    'senior3_statistics'
];

foreach ($collections as $collection) {
    $query = new MongoDB\Driver\Query(['phone' => ['$in' => $phoneVariations]]);
    
    try {
        $cursor = $client->executeQuery("$databaseName.$collection", $query);
        $documents = $cursor->toArray();
        
        if (count($documents) > 0) {
            $results['findings'][$collection] = [];
            
            foreach ($documents as $doc) {
                $results['findings'][$collection][] = [
                    'id' => (string)$doc->_id,
                    'phone' => $doc->phone ?? 'N/A',
                    'name' => $doc->studentName ?? 'N/A',
                    'balance' => $doc->balance ?? 0,
                    'paymentAmount' => $doc->paymentAmount ?? 'N/A',
                    'subject' => $doc->subject ?? 'N/A',
                    'isActive' => $doc->isActive ?? 'N/A',
                    'has_session_key' => isset($doc->session_13) ? 'YES' : 'NO'
                ];
            }
        }
    } catch (Exception $e) {
        // Collection might not exist
        $results['findings'][$collection] = 'Collection query error: ' . $e->getMessage();
    }
}

// Check transactions
$results['recent_transactions'] = [];
$query = new MongoDB\Driver\Query(
    ['studentId' => ['$in' => $phoneVariations]],
    ['sort' => ['createdAt' => -1], 'limit' => 5]
);

try {
    $cursor = $client->executeQuery("$databaseName.transactions", $query);
    foreach ($cursor as $transaction) {
        $results['recent_transactions'][] = [
            'amount' => $transaction->amount ?? 0,
            'previousBalance' => $transaction->previousBalance ?? 0,
            'newBalance' => $transaction->newBalance ?? 0,
            'type' => $transaction->type ?? 'N/A',
            'note' => $transaction->note ?? 'N/A',
            'createdAt' => isset($transaction->createdAt) ? $transaction->createdAt->toDateTime()->format('Y-m-d H:i:s') : 'N/A'
        ];
    }
} catch (Exception $e) {
    $results['transactions_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
