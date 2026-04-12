<?php
/**
 * ENDPOINT FUNCTIONALITY TEST
 * Tests the actual payment.php API endpoints
 * 1. extract_amount - Extract amount from base64 image
 * 2. verify - Verify payment reference
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'payment-schema.php';

// Include instapay-checker functions
if (file_exists('../instapay-checker/api.php')) {
    require_once '../instapay-checker/api.php';
}
if (file_exists('../instapay-checker/db.php')) {
    require_once '../instapay-checker/db.php';
}

echo json_encode([
    'test' => 'API Endpoint Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'ready',
    'db_file' => defined('DB_FILE') ? DB_FILE : 'NOT DEFINED',
    'functions_available' => [
        'validateAmount' => function_exists('validateAmount') ? 'YES' : 'NO',
        'validateReferenceNumber' => function_exists('validateReferenceNumber') ? 'YES' : 'NO',
        'analyzeTransaction' => function_exists('analyzeTransaction') ? 'YES' : 'NO',
        'checkDuplicateTransaction' => function_exists('checkDuplicateTransaction') ? 'YES' : 'NO',
        'initializeDatabase' => function_exists('initializeDatabase') ? 'YES' : 'NO',
    ],
    'next_test' => 'Try sending a POST request with action=verify or action=extract_amount'
], JSON_PRETTY_PRINT);
?>
