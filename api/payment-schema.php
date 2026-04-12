<?php
/**
 * Payment Transaction Schema & Database Management
 * Handles payment transactions, verifications, and fraud tracking
 */

require_once 'config.php';

/**
 * Check if SQLite is available and return database connection
 * Returns null if SQLite is not available
 */
function getSQLiteConnection() {
    if (!extension_loaded('pdo_sqlite')) {
        error_log('SQLite PDO driver not available');
        return null;
    }
    
    try {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (Exception $e) {
        error_log('SQLite connection error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Initialize payment transaction tables
 */
function initializePaymentTables() {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        
        // Create submission patterns table - Track user submission patterns
        $db->exec("
            CREATE TABLE IF NOT EXISTS submission_patterns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                student_phone TEXT NOT NULL,
                submission_count INTEGER DEFAULT 1,
                last_submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                first_submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                rejected_count INTEGER DEFAULT 0,
                suspicious_count INTEGER DEFAULT 0,
                ip_address TEXT,
                user_agent TEXT,
                UNIQUE(student_id)
            )
        ");
        
        // Create transactions table - LEVEL 1: Initial submission
        
        // Create transactions table - LEVEL 1: Initial submission
        $db->exec("
            CREATE TABLE IF NOT EXISTS payment_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                student_phone TEXT NOT NULL,
                student_email TEXT,
                amount_requested REAL NOT NULL,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'failed')),
                submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                screenshot_path TEXT NOT NULL,
                screenshot_size INTEGER,
                screenshot_hash TEXT UNIQUE,
                
                -- Instapay transaction details extracted
                instapay_amount REAL,
                instapay_sender_account TEXT,
                instapay_receiver_name TEXT,
                instapay_receiver_phone TEXT,
                instapay_reference_number TEXT,
                instapay_transaction_date TEXT,
                
                -- Validation results
                validation_score INTEGER DEFAULT 0,
                is_valid_format BOOLEAN DEFAULT FALSE,
                confidence_level TEXT CHECK(confidence_level IN ('high', 'medium', 'low', 'reject')),
                
                -- Fraud detection
                is_duplicate BOOLEAN DEFAULT FALSE,
                duplicate_of_id INTEGER,
                has_been_used BOOLEAN DEFAULT FALSE,
                used_by_student_id INTEGER,
                phone_not_in_db BOOLEAN DEFAULT FALSE,
                
                -- Status tracking
                level2_submitted BOOLEAN DEFAULT FALSE,
                level2_submitted_date TIMESTAMP,
                admin_notes TEXT,
                admin_decision TEXT CHECK(admin_decision IN ('approved', 'declined', NULL)),
                admin_decided_by INTEGER,
                admin_decided_date TIMESTAMP,
                
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (duplicate_of_id) REFERENCES payment_transactions(id),
                FOREIGN KEY (used_by_student_id) REFERENCES users(id),
                FOREIGN KEY (admin_decided_by) REFERENCES users(id)
            )
        ");

        // Create fraud detection log
        $db->exec("
            CREATE TABLE IF NOT EXISTS fraud_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                student_name TEXT NOT NULL,
                student_phone TEXT NOT NULL,
                student_email TEXT,
                current_balance REAL,
                
                transaction_id INTEGER,
                screenshot_hash TEXT,
                
                fraud_type TEXT NOT NULL CHECK(fraud_type IN (
                    'duplicate_screenshot',
                    'screenshot_reuse',
                    'phone_not_registered',
                    'fake_detection_high',
                    'amount_mismatch',
                    'reference_anomaly',
                    'multiple_attempts'
                )),
                
                fraud_reason TEXT NOT NULL,
                confidence_score INTEGER,
                suspicious_details TEXT,
                
                detected_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address TEXT,
                user_agent TEXT,
                
                admin_reviewed BOOLEAN DEFAULT FALSE,
                admin_notes TEXT,
                admin_reviewed_by INTEGER,
                admin_reviewed_date TIMESTAMP,
                
                action_taken TEXT CHECK(action_taken IN ('none', 'warning', 'suspension', 'account_lock')),
                
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id),
                FOREIGN KEY (admin_reviewed_by) REFERENCES users(id)
            )
        ");

        // Create balance history
        $db->exec("
            CREATE TABLE IF NOT EXISTS balance_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                transaction_type TEXT CHECK(transaction_type IN ('add', 'use', 'refund', 'admin_adjustment')),
                reason TEXT,
                balance_after REAL,
                transaction_id INTEGER,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INTEGER,
                
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ");

        // Create indexes for performance
        $db->exec("CREATE INDEX IF NOT EXISTS idx_payment_student ON payment_transactions(student_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_payment_status ON payment_transactions(status)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_payment_date ON payment_transactions(submission_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_fraud_student ON fraud_attempts(student_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_fraud_date ON fraud_attempts(detected_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_fraud_type ON fraud_attempts(fraud_type)");

        return true;
    } catch (Exception $e) {
        error_log('Payment tables initialization error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get IP geolocation information using free API
 * Returns location data or null if failed
 */
function getIPGeolocation($ip) {
    if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
        return null;
    }
    
    try {
        // Use ip-api.com free API (no API key required for basic usage)
        $url = "http://ip-api.com/json/" . $ip . "?fields=country,city,isp,proxy,hosting";
        $response = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 5]
        ]));
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'is_proxy' => $data['proxy'] ?? false,
                    'is_hosting' => $data['hosting'] ?? false,
                    'suspicious' => ($data['proxy'] ?? false) || ($data['hosting'] ?? false)
                ];
            }
        }
    } catch (Exception $e) {
        error_log('IP geolocation error: ' . $e->getMessage());
    }
    
    return null;
}

/**
 * Analyze EXIF metadata from image to detect editing software
 * Returns analysis results with suspicious indicators
 */
function analyzeImageEXIF($imagePath) {
    $result = [
        'is_suspicious' => false,
        'suspicious_reasons' => [],
        'software_detected' => null,
        'has_metadata' => false,
        'metadata_stripped' => false,
        'device_info' => null
    ];
    
    if (!file_exists($imagePath)) {
        return $result;
    }
    
    // Check if EXIF extension is available
    if (!function_exists('exif_read_data')) {
        error_log('EXIF extension not available');
        return $result;
    }
    
    try {
        $exif = @exif_read_data($imagePath);
        
        if ($exif === false) {
            // No EXIF data could mean metadata was stripped (suspicious)
            $result['metadata_stripped'] = true;
            $result['suspicious_reasons'][] = 'No EXIF metadata found (may indicate editing or metadata stripping)';
            $result['is_suspicious'] = true;
            return $result;
        }
        
        $result['has_metadata'] = true;
        
        // Check for editing software signatures
        $softwareSignatures = [
            'Photoshop',
            'Adobe',
            'GIMP',
            'Paint.NET',
            'Microsoft Office',
            'Preview',
            'Skitch',
            'Snipping Tool',
            'Lightshot',
            'Greenshot',
            'ShareX',
            'Snagit'
        ];
        
        if (isset($exif['Software'])) {
            $software = $exif['Software'];
            $result['software_detected'] = $software;
            
            foreach ($softwareSignatures as $signature) {
                if (stripos($software, $signature) !== false) {
                    $result['is_suspicious'] = true;
                    $result['suspicious_reasons'][] = "Editing software detected: $software";
                    break;
                }
            }
        }
        
        // Check device info
        if (isset($exif['Model']) || isset($exif['Make'])) {
            $result['device_info'] = [
                'make' => $exif['Make'] ?? null,
                'model' => $exif['Model'] ?? null
            ];
            
            // Check if it's a known screenshot tool
            $screenshotTools = ['Screenshot', 'Screen Capture', 'Snipping', 'Lightshot', 'ShareX'];
            $deviceString = ($exif['Make'] ?? '') . ' ' . ($exif['Model'] ?? '');
            
            foreach ($screenshotTools as $tool) {
                if (stripos($deviceString, $tool) !== false) {
                    $result['is_suspicious'] = true;
                    $result['suspicious_reasons'][] = "Screenshot tool detected: $deviceString";
                    break;
                }
            }
        }
        
        // Check for inconsistent timestamps
        if (isset($exif['DateTime']) && isset($exif['DateTimeOriginal'])) {
            $dateTime = strtotime($exif['DateTime']);
            $dateTimeOriginal = strtotime($exif['DateTimeOriginal']);
            
            // If timestamps differ significantly (> 1 hour), may indicate editing
            if (abs($dateTime - $dateTimeOriginal) > 3600) {
                $result['is_suspicious'] = true;
                $result['suspicious_reasons'][] = 'Timestamp inconsistency detected (possible editing)';
            }
        }
        
    } catch (Exception $e) {
        error_log('EXIF analysis error: ' . $e->getMessage());
    }
    
    return $result;
}

/**
 * Track submission patterns for a student
 * Returns pattern analysis with suspicious behavior detection
 */
function trackSubmissionPattern($studentId, $studentPhone, $status, $ipAddress = null, $userAgent = null) {
    $db = getSQLiteConnection();
    if (!$db) {
        // Return default pattern analysis if SQLite is not available
        return [
            'is_suspicious' => false,
            'suspicious_reasons' => [],
            'submission_count' => 1,
            'rejected_count' => $status === 'rejected' ? 1 : 0,
            'rejection_rate' => $status === 'rejected' ? '100%' : '0%',
            'error' => 'SQLite not available'
        ];
    }
    
    try {
        
        // Check if student has previous submissions
        $stmt = $db->prepare('SELECT * FROM submission_patterns WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $submissionCount = $existing['submission_count'] + 1;
            $rejectedCount = $existing['rejected_count'];
            $suspiciousCount = $existing['suspicious_count'];
            
            if ($status === 'rejected') {
                $rejectedCount++;
            }
            
            // Check for suspicious patterns
            $isSuspicious = false;
            $suspiciousReasons = [];
            
            // High rejection rate (more than 50%)
            if ($rejectedCount > 0 && ($rejectedCount / $submissionCount) > 0.5) {
                $isSuspicious = true;
                $suspiciousReasons[] = 'High rejection rate (>50%)';
                $suspiciousCount++;
            }
            
            // Rapid submissions (more than 10 in 24 hours)
            $lastSubmission = strtotime($existing['last_submission_date']);
            $timeSinceLast = time() - $lastSubmission;
            if ($timeSinceLast < 3600 && $submissionCount > 5) {
                $isSuspicious = true;
                $suspiciousReasons[] = 'Rapid submissions (>5 in 1 hour)';
                $suspiciousCount++;
            }
            
            $stmt = $db->prepare('
                UPDATE submission_patterns 
                SET submission_count = ?, 
                    last_submission_date = CURRENT_TIMESTAMP,
                    rejected_count = ?,
                    suspicious_count = ?,
                    ip_address = ?,
                    user_agent = ?
                WHERE student_id = ?
            ');
            $stmt->execute([$submissionCount, $rejectedCount, $suspiciousCount, $ipAddress, $userAgent, $studentId]);
            
            return [
                'is_suspicious' => $isSuspicious,
                'suspicious_reasons' => $suspiciousReasons,
                'submission_count' => $submissionCount,
                'rejected_count' => $rejectedCount,
                'rejection_rate' => $rejectedCount > 0 ? round(($rejectedCount / $submissionCount) * 100, 1) . '%' : '0%'
            ];
        } else {
            // Create new record
            $stmt = $db->prepare('
                INSERT INTO submission_patterns 
                (student_id, student_phone, submission_count, rejected_count, suspicious_count, ip_address, user_agent)
                VALUES (?, ?, 1, ?, ?, ?, ?)
            ');
            $stmt->execute([$studentId, $studentPhone, $status === 'rejected' ? 1 : 0, 0, $ipAddress, $userAgent]);
            
            return [
                'is_suspicious' => false,
                'suspicious_reasons' => [],
                'submission_count' => 1,
                'rejected_count' => $status === 'rejected' ? 1 : 0,
                'rejection_rate' => $status === 'rejected' ? '100%' : '0%'
            ];
        }
    } catch (Exception $e) {
        error_log('Submission pattern tracking error: ' . $e->getMessage());
        return [
            'is_suspicious' => false,
            'suspicious_reasons' => [],
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Check if student phone exists in database
 */
function isStudentPhoneRegistered($phone) {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if screenshot already used for another student
 */
function isScreenshotAlreadyUsed($screenshotHash) {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare('
            SELECT id, student_id, status FROM payment_transactions 
            WHERE screenshot_hash = ? AND status != "rejected"
            LIMIT 1
        ');
        $stmt->execute([$screenshotHash]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Save transaction to database - LEVEL 1
 */
function savePaymentTransaction($data) {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        
        // Calculate screenshot hash to detect duplicates
        $screenshotHash = hash('sha256', file_get_contents($data['screenshot_path']));
        
        // Check for duplicate/reused screenshots
        $existingScreenshot = isScreenshotAlreadyUsed($screenshotHash);
        
        $stmt = $db->prepare('
            INSERT INTO payment_transactions (
                student_id, student_phone, student_email, amount_requested,
                screenshot_path, screenshot_size, screenshot_hash,
                instapay_amount, instapay_sender_account, instapay_receiver_name,
                instapay_receiver_phone, instapay_reference_number, instapay_transaction_date,
                validation_score, is_valid_format, confidence_level,
                is_duplicate, duplicate_of_id, has_been_used, used_by_student_id,
                phone_not_in_db, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $data['student_id'],
            $data['student_phone'],
            $data['student_email'] ?? null,
            $data['amount_requested'],
            $data['screenshot_path'],
            filesize($data['screenshot_path']),
            $screenshotHash,
            $data['instapay_amount'] ?? null,
            $data['instapay_sender_account'] ?? null,
            $data['instapay_receiver_name'] ?? null,
            $data['instapay_receiver_phone'] ?? null,
            $data['instapay_reference_number'] ?? null,
            $data['instapay_transaction_date'] ?? null,
            $data['validation_score'] ?? 0,
            $data['is_valid_format'] ?? false,
            $data['confidence_level'] ?? 'low',
            $existingScreenshot ? true : false,
            $existingScreenshot ? $existingScreenshot['id'] : null,
            $existingScreenshot ? true : false,
            $existingScreenshot ? $existingScreenshot['student_id'] : null,
            !isStudentPhoneRegistered($data['student_phone']),
            $data['status'] ?? 'pending'
        ]);
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log('Payment transaction save error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Add fraud attempt to tracking
 */
function logFraudAttempt($fraudData) {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        
        $stmt = $db->prepare('
            INSERT INTO fraud_attempts (
                student_id, student_name, student_phone, student_email, current_balance,
                transaction_id, screenshot_hash, fraud_type, fraud_reason, 
                confidence_score, suspicious_details, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $fraudData['student_id'],
            $fraudData['student_name'] ?? 'Unknown',
            $fraudData['student_phone'],
            $fraudData['student_email'] ?? null,
            $fraudData['current_balance'] ?? 0,
            $fraudData['transaction_id'] ?? null,
            $fraudData['screenshot_hash'] ?? null,
            $fraudData['fraud_type'],
            $fraudData['fraud_reason'],
            $fraudData['confidence_score'] ?? 0,
            $fraudData['suspicious_details'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log('Fraud logging error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get transaction by ID
 */
function getPaymentTransaction($transactionId) {
    $db = getSQLiteConnection();
    if (!$db) {
        return null;
    }
    
    try {
        $stmt = $db->prepare('SELECT * FROM payment_transactions WHERE id = ?');
        $stmt->execute([$transactionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Update transaction status
 */
function updateTransactionStatus($transactionId, $status, $notes = null, $adminId = null) {
    $db = getSQLiteConnection();
    if (!$db) {
        return false;
    }
    
    try {
        
        $stmt = $db->prepare('
            UPDATE payment_transactions 
            SET status = ?, admin_notes = ?, admin_decision = ?, 
                admin_decided_by = ?, admin_decided_date = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        
        $stmt->execute([
            $status,
            $notes,
            in_array($status, ['approved', 'rejected']) ? $status : null,
            $adminId,
            $transactionId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Update transaction error: ' . $e->getMessage());
        return false;
    }
}

// Initialize tables on require (only if SQLite is available)
if (extension_loaded('pdo_sqlite')) {
    initializePaymentTables();
}
?>
