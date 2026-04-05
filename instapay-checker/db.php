<?php
/**
 * Database Functions for Instapay Transaction Validator
 * Handles all database operations with consistent schema
 */

class TransactionDatabase {
    private static $dbFile = 'transactions.db';
    private static $pdo = null;

    /**
     * Get PDO connection (SQLite)
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO('sqlite:' . self::$dbFile);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log('Database connection error: ' . $e->getMessage());
                return null;
            }
        }
        return self::$pdo;
    }

    /**
     * Initialize database and create tables if they don't exist
     */
    public static function initialize() {
        $pdo = self::getConnection();
        if (!$pdo) {
            return false;
        }

        try {
            // Check if table exists
            $result = $pdo->query("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name='transactions'
            ");

            if ($result->fetch()) {
                return true; // Table already exists
            }

            // Create transactions table with consistent schema
            $pdo->exec("
                CREATE TABLE transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    
                    -- Amount and Currency
                    amount DECIMAL(12, 2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'EGP',
                    
                    -- Sender Information
                    sender_account VARCHAR(255) NOT NULL,
                    sender_name VARCHAR(255),
                    
                    -- Receiver Information
                    receiver_name VARCHAR(255) NOT NULL,
                    receiver_phone VARCHAR(20),
                    
                    -- Transaction Details
                    reference_number VARCHAR(50) UNIQUE NOT NULL,
                    transaction_date DATETIME,
                    transaction_type VARCHAR(100) DEFAULT 'تحويل أموال',
                    
                    -- Bank Information
                    bank_name VARCHAR(100),
                    
                    -- Validation Data
                    is_valid VARCHAR(20) DEFAULT 'valid',
                    confidence_score INT DEFAULT 100,
                    validation_issues TEXT,
                    
                    -- Files and Metadata
                    screenshot_file VARCHAR(255),
                    extracted_text LONGTEXT,
                    raw_data JSON,
                    
                    -- Timestamps
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    -- Additional Fields
                    notes TEXT,
                    status VARCHAR(20) DEFAULT 'active',
                    ip_address VARCHAR(45),
                    user_agent TEXT
                )
            ");

            // Create indexes for faster queries
            $pdo->exec("CREATE INDEX idx_reference_number ON transactions(reference_number)");
            $pdo->exec("CREATE INDEX idx_sender_account ON transactions(sender_account)");
            $pdo->exec("CREATE INDEX idx_receiver_phone ON transactions(receiver_phone)");
            $pdo->exec("CREATE INDEX idx_created_at ON transactions(created_at)");
            $pdo->exec("CREATE INDEX idx_amount ON transactions(amount)");

            return true;
        } catch (PDOException $e) {
            error_log('Database initialization error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Save transaction to database
     */
    public static function saveTransaction($transactionData) {
        $pdo = self::getConnection();
        if (!$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    amount,
                    currency,
                    sender_account,
                    sender_name,
                    receiver_name,
                    receiver_phone,
                    reference_number,
                    transaction_date,
                    transaction_type,
                    bank_name,
                    is_valid,
                    confidence_score,
                    screenshot_file,
                    raw_data,
                    ip_address,
                    user_agent,
                    created_at,
                    updated_at
                ) VALUES (
                    :amount,
                    :currency,
                    :sender_account,
                    :sender_name,
                    :receiver_name,
                    :receiver_phone,
                    :reference_number,
                    :transaction_date,
                    :transaction_type,
                    :bank_name,
                    :is_valid,
                    :confidence_score,
                    :screenshot_file,
                    :raw_data,
                    :ip_address,
                    :user_agent,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )
            ");

            // Extract confidence score (remove % sign)
            $confidenceScore = str_replace('%', '', $transactionData['confidence_score'] ?? '0');

            $params = [
                ':amount' => floatval($transactionData['amount'] ?? 0),
                ':currency' => $transactionData['currency'] ?? 'EGP',
                ':sender_account' => $transactionData['sender_account'] ?? '',
                ':sender_name' => $transactionData['sender_name'] ?? '',
                ':receiver_name' => $transactionData['receiver_name'] ?? '',
                ':receiver_phone' => $transactionData['receiver_phone'] ?? '',
                ':reference_number' => $transactionData['reference_number'] ?? '',
                ':transaction_date' => $transactionData['transaction_date'] ?? null,
                ':transaction_type' => $transactionData['transaction_type'] ?? 'تحويل أموال',
                ':bank_name' => $transactionData['bank_name'] ?? '',
                ':is_valid' => $transactionData['is_valid'] ?? 'valid',
                ':confidence_score' => intval($confidenceScore),
                ':screenshot_file' => $transactionData['temp_file'] ?? '',
                ':raw_data' => json_encode($transactionData),
                ':ip_address' => self::getClientIP(),
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Database save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find duplicate transaction
     */
    public static function findDuplicate($referenceNumber, $amount, $date) {
        $pdo = self::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            // Check for exact reference match first
            $stmt = $pdo->prepare("
                SELECT * FROM transactions 
                WHERE reference_number = :ref_number 
                LIMIT 1
            ");
            $stmt->execute([':ref_number' => $referenceNumber]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result;
            }

            // Check for similar transactions (same amount and approximate date)
            if ($amount && $date) {
                $stmt = $pdo->prepare("
                    SELECT * FROM transactions 
                    WHERE amount = :amount 
                    AND DATE(transaction_date) = DATE(:date)
                    AND sender_account = :sender_account
                    LIMIT 1
                ");
                $stmt->execute([
                    ':amount' => floatval($amount),
                    ':date' => $date,
                    ':sender_account' => '' // Would need sender account to check
                ]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return null;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get statistics
     */
    public static function getStats() {
        $pdo = self::getConnection();
        if (!$pdo) {
            return ['total' => 0, 'valid' => 0, 'suspicious' => 0];
        }

        try {
            $total = $pdo->query("SELECT COUNT(*) as count FROM transactions")
                ->fetch(PDO::FETCH_ASSOC)['count'];

            $valid = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE is_valid = 'valid'")
                ->fetch(PDO::FETCH_ASSOC)['count'];

            $suspicious = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE is_valid = 'suspicious'")
                ->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'total' => $total,
                'valid' => $valid,
                'suspicious' => $suspicious
            ];
        } catch (PDOException $e) {
            error_log('Statistics query error: ' . $e->getMessage());
            return ['total' => 0, 'valid' => 0, 'suspicious' => 0];
        }
    }

    /**
     * Get all transactions (with pagination)
     */
    public static function getAllTransactions($limit = 50, $offset = 0) {
        $pdo = self::getConnection();
        if (!$pdo) {
            return [];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id, amount, currency, sender_account, sender_name,
                    receiver_name, receiver_phone, reference_number,
                    transaction_date, is_valid, confidence_score, created_at
                FROM transactions
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', intval($limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', intval($offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get transaction by reference number
     */
    public static function getByReference($referenceNumber) {
        $pdo = self::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference_number = :ref");
            $stmt->execute([':ref' => $referenceNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return null;
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
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '0.0.0.0';
    }
}

// Wrapper functions for backward compatibility
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
    return TransactionDatabase::getByReference($ref);
}
?>
