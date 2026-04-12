<?php
/**
 * MongoDB-based Payment Transaction Handler
 * Fallback for when SQLite is not available
 */

require_once 'config.php';

/**
 * Get pending transactions from MongoDB
 */
function getPendingTransactionsMongo() {
    try {
        if (!$GLOBALS['mongoClient']) {
            error_log('MongoDB client not available');
            throw new Exception('MongoDB not available');
        }

        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        error_log("MongoDB connected to database: $databaseName");

        // Query for pending transactions
        $filter = ['status' => 'pending'];
        $options = ['sort' => ['submission_date' => -1]];
        $query = new MongoDB\Driver\Query($filter, $options);

        error_log("Executing query on collection: $databaseName.payment_transactions with filter: " . json_encode($filter));

        $cursor = $client->executeQuery("$databaseName.payment_transactions", $query);
        $transactions = $cursor->toArray();

        error_log("Query returned " . count($transactions) . " transactions");
        
        // Convert BSON to arrays for JSON response
        $result = [];
        foreach ($transactions as $transaction) {
            $result[] = [
                'id' => (string)$transaction->_id,
                'student_id' => $transaction->student_id ?? 0,
                'student_phone' => $transaction->student_phone ?? '',
                'student_email' => $transaction->student_email ?? '',
                'amount_requested' => $transaction->amount_requested ?? 0,
                'instapay_amount' => $transaction->instapay_amount ?? 0,
                'status' => $transaction->status ?? 'pending',
                'submission_date' => isset($transaction->submission_date)
                    ? $transaction->submission_date->toDateTime()->format('Y-m-d H:i:s')
                    : date('Y-m-d H:i:s'),
                'instapay_reference_number' => $transaction->instapay_reference_number ?? '',
                'instapay_receiver_name' => $transaction->instapay_receiver_name ?? '',
                'validation_score' => $transaction->validation_score ?? 0,
                'confidence_level' => $transaction->confidence_level ?? 'low',
                'screenshot_path' => $transaction->screenshot_path ?? '',
                'fraud_flags' => $transaction->fraud_flags ?? [],
                'issues' => $transaction->issues ?? [],
                'warnings' => $transaction->warnings ?? [],
                'validations' => $transaction->validations ?? [],
                'extracted_data' => $transaction->extracted_data ?? []
            ];
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('MongoDB payment error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Save transaction to MongoDB
 */
function saveTransactionMongo($data) {
    try {
        if (!$GLOBALS['mongoClient']) {
            error_log('MongoDB client not available for save');
            throw new Exception('MongoDB not available');
        }

        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        error_log("Saving transaction to MongoDB - Database: $databaseName, Status: " . ($data['status'] ?? 'pending'));
        
        $transaction = [
            'student_id' => $data['student_id'],
            'student_phone' => $data['student_phone'],
            'student_email' => $data['student_email'] ?? null,
            'amount_requested' => $data['amount_requested'],
            'instapay_amount' => $data['instapay_amount'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'submission_date' => new MongoDB\BSON\UTCDateTime(),
            'screenshot_path' => $data['screenshot_path'] ?? '',
            'instapay_reference_number' => $data['instapay_reference_number'] ?? '',
            'instapay_receiver_name' => $data['instapay_receiver_name'] ?? '',
            'validation_score' => $data['validation_score'] ?? 0,
            'confidence_level' => $data['confidence_level'] ?? 'low',
            'fraud_flags' => $data['fraud_flags'] ?? [],
            'issues' => $data['issues'] ?? [],
            'warnings' => $data['warnings'] ?? [],
            'validations' => $data['validations'] ?? [],
            'extracted_data' => $data['extracted_data'] ?? [],
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($transaction);
        $result = $client->executeBulkWrite("$databaseName.payment_transactions", $bulk);

        $insertedCount = $result->getInsertedCount();
        error_log("MongoDB insert result: $insertedCount transaction(s) inserted");

        return $insertedCount > 0;
    } catch (Exception $e) {
        error_log('MongoDB save error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update transaction status in MongoDB
 */
function updateTransactionStatusMongo($transactionId, $status, $notes = null, $adminId = null) {
    try {
        if (!$GLOBALS['mongoClient']) {
            throw new Exception('MongoDB not available');
        }

        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        
        $filter = ['_id' => new MongoDB\BSON\ObjectId($transactionId)];
        $update = [
            '$set' => [
                'status' => $status,
                'admin_notes' => $notes,
                'admin_decided_by' => $adminId,
                'admin_decided_date' => new MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]
        ];
        
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $update);
        $result = $client->executeBulkWrite("$databaseName.payment_transactions", $bulk);
        
        return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
        error_log('MongoDB update error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get mock data for testing when no database is available
 */
function getMockPendingTransactions() {
    return [
        [
            'id' => '1',
            'student_id' => '1001',
            'student_phone' => '01010796944',
            'student_email' => 'student1@example.com',
            'amount_requested' => 500,
            'instapay_amount' => 500,
            'status' => 'pending',
            'submission_date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'instapay_reference_number' => '123456789012',
            'instapay_receiver_name' => 'Test Receiver',
            'validation_score' => 85,
            'confidence_level' => 'high'
        ],
        [
            'id' => '2', 
            'student_id' => '1002',
            'student_phone' => '01012345678',
            'student_email' => 'student2@example.com',
            'amount_requested' => 1000,
            'instapay_amount' => 1000,
            'status' => 'pending',
            'submission_date' => date('Y-m-d H:i:s', strtotime('-4 hours')),
            'instapay_reference_number' => '987654321098',
            'instapay_receiver_name' => 'Another Receiver',
            'validation_score' => 72,
            'confidence_level' => 'medium'
        ],
        [
            'id' => '3',
            'student_id' => '1003', 
            'student_phone' => '01098765432',
            'student_email' => 'student3@example.com',
            'amount_requested' => 750,
            'instapay_amount' => 750,
            'status' => 'pending',
            'submission_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'instapay_reference_number' => '555666777888',
            'instapay_receiver_name' => 'Third Receiver',
            'validation_score' => 68,
            'confidence_level' => 'medium'
        ]
    ];
}
?>
