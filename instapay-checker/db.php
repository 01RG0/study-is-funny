<?php
/**
 * Transaction Database Handler - MongoDB Version
 * Manages storage and retrieval of Instapay transactions using MongoDB
 */

// Include MongoDB driver and configuration
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../classes/DatabaseMongo.php';

class TransactionDatabase {
    private static $db = null;
    private static $collection = 'instapay_transactions';
    private static $fraudCollection = 'fraud_attempts';

    /**
     * Get MongoDB connection instance
     */
    public static function getConnection() {
        if (self::$db === null) {
            try {
                // Use the URI and database name from config.php
                global $mongoUri, $databaseName;
                self::$db = new DatabaseMongo($mongoUri, $databaseName);
            } catch (Exception $e) {
                error_log('MongoDB Connection Error: ' . $e->getMessage());
                return null;
            }
        }
        return self::$db;
    }

    /**
     * Initialize database - MongoDB creates collections on insertion
     */
    public static function initialize() {
        return true; 
    }

    /**
     * Save a transaction to MongoDB
     */
    public static function saveTransaction($data) {
        $db = self::getConnection();
        if (!$db) return false;

        // Enforce amount limits
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
        $minAmount = defined('MIN_TRANSACTION_AMOUNT') ? MIN_TRANSACTION_AMOUNT : 50;
        $maxAmount = defined('MAX_TRANSACTION_AMOUNT') ? MAX_TRANSACTION_AMOUNT : 1000;
        
        if ($amount < $minAmount || $amount > $maxAmount) {
            error_log("Transaction amount $amount outside limits ($minAmount-$maxAmount)");
            return false;
        }

        // Determine admin status based on validation results
        $isValid = ($data['is_valid'] ?? 'unknown') === 'valid';
        $hasIssues = !empty($data['issues'] ?? []);
        $isDuplicate = !empty($data['is_duplicate'] ?? false);
        
        // Status: pending_approval, approved, rejected, flagged
        if ($isDuplicate) {
            $status = 'rejected';
        } elseif ($hasIssues || !$isValid) {
            $status = 'flagged';
        } else {
            $status = 'pending_approval'; // Needs admin approval
        }

        $document = [
            'reference_number' => $data['reference_number'] ?? null,
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'EGP',
            'transaction_date' => $data['transaction_date'] ?? null,
            'sender_account' => $data['sender_account'] ?? null,
            'sender_name' => $data['sender_name'] ?? null,
            'receiver_name' => $data['receiver_name'] ?? null,
            'receiver_phone' => $data['receiver_phone'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? 'transfer',
            'full_text' => $data['full_text'] ?? null,
            'ai_response_raw' => $data['ai_response_raw'] ?? null,
            'status' => $status,
            'admin_status' => 'pending_review', // For admin workflow
            'confidence_score' => $data['confidence_score'] ?? 0,
            'is_valid' => $data['is_valid'] ?? 'unknown',
            'validation_issues' => $data['issues'] ?? [],
            'validation_warnings' => $data['warnings'] ?? [],
            'is_duplicate' => $isDuplicate,
            'screenshot_path' => $data['screenshot_path'] ?? null,
            'submitted_by' => [
                'user_id' => $data['user_id'] ?? null,
                'username' => $data['username'] ?? 'anonymous',
                'email' => $data['user_email'] ?? null,
                'ip' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ],
            'admin_review' => [
                'reviewed_by' => null,
                'reviewed_at' => null,
                'decision' => null,
                'notes' => null
            ],
            'created_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000)),
            'updated_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000))
        ];

        try {
            return $db->insert(self::$collection, $document);
        } catch (Exception $e) {
            error_log('MongoDB Insert Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find a duplicate transaction
     */
    public static function findDuplicate($ref, $amount, $date) {
        $db = self::getConnection();
        if (!$db) return null;

        $filter = [
            'reference_number' => $ref,
            'amount' => (float)$amount,
            'transaction_date' => $date
        ];

        try {
            return $db->findOne(self::$collection, $filter);
        } catch (Exception $e) {
            error_log('MongoDB Find Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get transaction by reference
     */
    public static function getByReference($ref) {
        $db = self::getConnection();
        if (!$db) return null;

        try {
            return $db->findOne(self::$collection, ['reference_number' => $ref]);
        } catch (Exception $e) {
            error_log('MongoDB Find Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get transaction by reference number (Backward Compatibility)
     */
    public static function getTransactionByRef($ref) {
        return self::getByReference($ref);
    }

    /**
     * Log a fraud attempt
     */
    public static function logFraudAttempt($data) {
        $db = self::getConnection();
        if (!$db) return false;

        $document = [
            'type' => $data['type'] ?? 'unknown',
            'reason' => $data['reason'] ?? 'No reason provided',
            'details' => $data['details'] ?? [],
            'reference_number' => $data['reference_number'] ?? null,
            'created_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000)),
            'ip_address' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        try {
            return $db->insert(self::$fraudCollection, $document);
        } catch (Exception $e) {
            error_log('MongoDB Fraud Log Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent fraud attempts
     */
    public static function getFraudAttempts($limit = 50) {
        $db = self::getConnection();
        if (!$db) return [];

        try {
            $options = [
                'sort' => ['created_at' => -1],
                'limit' => intval($limit)
            ];
            return $db->find(self::$fraudCollection, [], $options);
        } catch (Exception $e) {
            error_log('MongoDB Query Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get statistics
     */
    public static function getStats() {
        $db = self::getConnection();
        if (!$db) return [];

        try {
            return [
                'total_transactions' => $db->count(self::$collection),
                'successful_transactions' => $db->count(self::$collection, ['status' => 'success']),
                'pending_transactions' => $db->count(self::$collection, ['status' => 'pending']),
                'fraud_attempts' => $db->count(self::$fraudCollection)
            ];
        } catch (Exception $e) {
            error_log('MongoDB Count Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all transactions
     */
    public static function getAllTransactions($limit = 50, $offset = 0) {
        $db = self::getConnection();
        if (!$db) return [];

        try {
            $options = [
                'sort' => ['created_at' => -1],
                'limit' => intval($limit),
                'skip' => intval($offset)
            ];
            return $db->find(self::$collection, [], $options);
        } catch (Exception $e) {
            error_log('MongoDB Query Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Update a transaction
     */
    public static function updateTransaction($filter, $update) {
        $db = self::getConnection();
        if (!$db) return false;

        try {
            return $db->update(self::$collection, $filter, $update);
        } catch (Exception $e) {
            error_log('MongoDB Update Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a transaction
     */
    public static function deleteTransaction($filter) {
        $db = self::getConnection();
        if (!$db) return false;

        try {
            return $db->delete(self::$collection, $filter);
        } catch (Exception $e) {
            error_log('MongoDB Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggregate transactions for analytics
     */
    public static function aggregateTransactions($pipeline) {
        $db = self::getConnection();
        if (!$db) return [];

        try {
            return $db->aggregate(self::$collection, $pipeline);
        } catch (Exception $e) {
            error_log('MongoDB Aggregate Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get transaction analytics/stats using aggregation
     */
    public static function getAnalytics() {
        $db = self::getConnection();
        if (!$db) return [];

        try {
            // Aggregate by status
            $statusPipeline = [
                ['$group' => [
                    '_id' => '$status',
                    'count' => ['$sum' => 1],
                    'totalAmount' => ['$sum' => '$amount']
                ]]
            ];
            $byStatus = $db->aggregate(self::$collection, $statusPipeline);

            // Aggregate by date (daily)
            $datePipeline = [
                ['$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at']],
                    'count' => ['$sum' => 1],
                    'avgAmount' => ['$avg' => '$amount']
                ]],
                ['$sort' => ['_id' => -1]],
                ['$limit' => 7]
            ];
            $byDate = $db->aggregate(self::$collection, $datePipeline);

            // Aggregate by sender
            $senderPipeline = [
                ['$group' => [
                    '_id' => '$sender_account',
                    'count' => ['$sum' => 1],
                    'totalAmount' => ['$sum' => '$amount']
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => 5]
            ];
            $bySender = $db->aggregate(self::$collection, $senderPipeline);

            return [
                'by_status' => $byStatus,
                'by_date' => $byDate,
                'top_senders' => $bySender
            ];
        } catch (Exception $e) {
            error_log('MongoDB Analytics Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search transactions with multiple criteria
     */
    public static function searchTransactions($criteria, $limit = 50) {
        $db = self::getConnection();
        if (!$db) return [];

        $filter = [];
        if (!empty($criteria['reference'])) {
            $filter['reference_number'] = ['$regex' => $criteria['reference'], '$options' => 'i'];
        }
        if (!empty($criteria['sender'])) {
            $filter['sender_account'] = ['$regex' => $criteria['sender'], '$options' => 'i'];
        }
        if (!empty($criteria['receiver'])) {
            $filter['receiver_name'] = ['$regex' => $criteria['receiver'], '$options' => 'i'];
        }
        if (!empty($criteria['min_amount'])) {
            $filter['amount'] = ['$gte' => (float)$criteria['min_amount']];
        }
        if (!empty($criteria['max_amount'])) {
            $filter['amount'] = isset($filter['amount']) 
                ? array_merge($filter['amount'], ['$lte' => (float)$criteria['max_amount']])
                : ['$lte' => (float)$criteria['max_amount']];
        }
        if (!empty($criteria['status'])) {
            $filter['status'] = $criteria['status'];
        }

        try {
            $options = [
                'sort' => ['created_at' => -1],
                'limit' => intval($limit)
            ];
            return $db->find(self::$collection, $filter, $options);
        } catch (Exception $e) {
            error_log('MongoDB Search Error: ' . $e->getMessage());
            return [];
        }
    }
}

// Global Wrapper Functions for Backward Compatibility
function initializeDatabase() {
    return TransactionDatabase::initialize();
}

function saveTransactionToDatabase($data) {
    return TransactionDatabase::saveTransaction($data);
}

function findDuplicateInDatabase($ref, $amount, $date) {
    return TransactionDatabase::findDuplicate($ref, $amount, $date);
}

function getStatistics() {
    return TransactionDatabase::getStats();
}

function getAllTransactionsFromDB($limit = 50, $offset = 0) {
    return TransactionDatabase::getAllTransactions($limit, $offset);
}

function getTransactionByRef($ref) {
    return TransactionDatabase::getTransactionByRef($ref);
}

function getRecentFraudAttempts($limit = 50) {
    return TransactionDatabase::getFraudAttempts($limit);
}

function updateTransactionInDB($filter, $update) {
    return TransactionDatabase::updateTransaction($filter, $update);
}

function deleteTransactionFromDB($filter) {
    return TransactionDatabase::deleteTransaction($filter);
}

function getTransactionAnalytics() {
    return TransactionDatabase::getAnalytics();
}

function searchTransactionsInDB($criteria, $limit = 50) {
    return TransactionDatabase::searchTransactions($criteria, $limit);
}

function aggregateTransactionsInDB($pipeline) {
    return TransactionDatabase::aggregateTransactions($pipeline);
}
?>
