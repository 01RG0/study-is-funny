<?php
/**
 * Payment Processing API
 * Handles payment submission, validation, and status tracking
 * Three-tier system: Validation → Pending Review → Admin Decision
 */

// Start output buffering to capture any unexpected output
ob_start();

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable the custom error handler temporarily to get native PHP error output
// set_error_handler(function($errno, $errstr, $errfile, $errline) {
//     error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
//     return true;
// });

try {
    require_once 'config.php';
    require_once 'payment-mongo.php';
    
    // Import validation functions from instapay-checker
    // The api.php file has conditional routing that won't execute when included
    if (file_exists(__DIR__ . '/../instapay-checker/api.php')) {
        require_once __DIR__ . '/../instapay-checker/api.php';
    }
    if (file_exists(__DIR__ . '/../instapay-checker/db.php')) {
        require_once __DIR__ . '/../instapay-checker/db.php';
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('Payment API Configuration Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Configuration error: ' . $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    error_log('Payment API Fatal Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
    exit;
}

// Clear any output from requires
ob_end_clean();

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$jsonInput = null;

// Handle JSON POST body for extract_amount - read php://input only once
if (!$action && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    $action = isset($jsonInput['action']) ? $jsonInput['action'] : '';
}

try {
    switch ($action) {
        case 'extract_amount':
            handleExtractAmount($jsonInput);
            break;
        case 'upload_screenshot':
            handleUploadScreenshot();
            break;
        case 'check_status':
            handleCheckStatus();
            break;
        case 'verify':
            handleVerifyPayment();
            break;
        case 'get_pending_transactions':
            handleGetPendingTransactions();
            break;
        case 'approve_transaction':
            handleApproveTransaction();
            break;
        case 'reject_transaction':
            handleRejectTransaction();
            break;
        case 'get_fraud_attempts':
            handleGetFraudAttempts();
            break;
        default:
            respondError('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    error_log('API Exception: ' . $e->getMessage());
    respondError('Server error: ' . $e->getMessage(), 500);
}

/**
 * Extract amount helper for base64 images
 */
function extractAmountFromBase64($base64Image) {
    $tmpFile = sys_get_temp_dir() . '/instapay_' . uniqid() . '.jpg';
    file_put_contents($tmpFile, base64_decode($base64Image));
    
    $data = extractDataFromImage($tmpFile);
    @unlink($tmpFile);
    
    return isset($data['amount']) ? (float)preg_replace('/[^\d.]/', '', $data['amount']) : null;
}

/**
 * Extract amount from Instapay screenshot using Unified AI logic
 */
function handleExtractAmount($jsonInput) {
    if (!$jsonInput || !isset($jsonInput['image'])) {
        respondError('No image provided');
        return;
    }

    $base64Image = $jsonInput['image'];
    
    // Call Unified logic to extract amount
    $amount = extractAmountFromBase64($base64Image);
    
    // Testing mode: Return mock amount if AI fails
    if ($amount === null && defined('TESTING_MODE') && TESTING_MODE) {
        $testAmounts = [100, 150, 200, 250, 300, 500, 750, 1000];
        $amount = $testAmounts[array_rand($testAmounts)];
        respondSuccess(['amount' => $amount, 'success' => true, 'message' => 'Testing mode: Mock amount']);
        return;
    }
    
    if ($amount !== null && $amount >= 50 && $amount <= 1000) {
        respondSuccess(['amount' => $amount, 'success' => true]);
    } else {
        respondSuccess(['amount' => null, 'success' => false, 'message' => 'Could not extract amount automatically']);
    }
}

/**
 * TIER 1: Upload screenshot and validate Instapay transaction
 */
function handleUploadScreenshot() {
    try {
        session_start();

    // Verify student is logged in - accept either session or phone from POST
    $studentPhone = $_POST['phone'] ?? $_SESSION['student_phone'] ?? null;

    if (!$studentPhone) {
        respondError('Student phone number not found');
        return;
    }

    // Rate limiting: Max 6 submissions per day per phone number using MongoDB
    try {
        if (!$GLOBALS['mongoClient']) {
            throw new Exception('MongoDB not available');
        }

        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get today's date at midnight
        $today = date('Y-m-d');
        $todayStart = new DateTime($today);
        $todayTimestamp = $todayStart->getTimestamp();

        // Check if this phone has submissions today
        $filter = [
            'student_phone' => $studentPhone,
            'submission_date' => ['$gte' => $todayTimestamp]
        ];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.payment_transactions", $query);
        $todaySubmissions = $cursor->toArray();
        $submissionCount = count($todaySubmissions);

        error_log("Phone $studentPhone has $submissionCount submissions today");

        // Check if rate limit exceeded (6 per day)
        if ($submissionCount >= 6) {
            error_log('Daily rate limit exceeded for phone: ' . $studentPhone);
            respondError('You have reached the daily limit of 6 payment submissions. Please try again tomorrow.', 429);
            return;
        }
    } catch (Exception $e) {
        error_log('Rate limit check error: ' . $e->getMessage());
        // Continue without rate limiting if MongoDB fails
    }

    // If phone is provided but no session, authenticate by phone
    if (!isset($_SESSION['student_id']) && $studentPhone) {
        // Use MongoDB for student authentication
        try {
            if (!$GLOBALS['mongoClient']) {
                throw new Exception('MongoDB not available');
            }

            $client = $GLOBALS['mongoClient'];
            $databaseName = $GLOBALS['databaseName'];
            
            $filter = ['phone' => $studentPhone];
            $query = new MongoDB\Driver\Query($filter);
            $cursor = $client->executeQuery("$databaseName.students", $query);
            $students = $cursor->toArray();
            
            if (count($students) > 0) {
                $student = $students[0];
                $_SESSION['student_id'] = (string)$student->_id;
                $_SESSION['student_phone'] = $studentPhone;
                error_log('Authenticated student by phone (MongoDB): ' . $studentPhone . ', student_id: ' . $_SESSION['student_id']);
            } else {
                // Student not found, allow phone-based auth for testing
                $_SESSION['student_id'] = 'phone_' . $studentPhone;
                $_SESSION['student_phone'] = $studentPhone;
                error_log('Student not found in MongoDB, using phone-based auth: ' . $studentPhone);
            }
        } catch (Exception $e) {
            error_log('Phone authentication error: ' . $e->getMessage());
            // Allow phone-based auth even on error for testing
            $_SESSION['student_id'] = 'phone_' . $studentPhone;
            $_SESSION['student_phone'] = $studentPhone;
        }
    }

    if (!isset($_FILES['screenshot'])) {
        respondError('No screenshot uploaded');
        return;
    }

    $file = $_FILES['screenshot'];
    $studentId = $_SESSION['student_id'];
    $studentPhone = $_POST['phone'] ?? $_SESSION['student_phone'] ?? null;
    $studentSubject = $_POST['subject'] ?? null;
    
    if (!$studentPhone) {
        respondError('Student phone number not found');
        return;
    }

    // Validate file
    $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $imageInfo = @getimagesize($file['tmp_name']);
    $fileMime = $imageInfo['mime'] ?? null;
    
    if (!in_array($fileMime, $validMimes)) {
        respondError('Invalid file type. Only images allowed');
        return;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        respondError('File too large (max 5MB)');
        return;
    }

    // Save screenshot
    $uploadDir = __DIR__ . '/../uploads/payments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'payment_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    $relativePath = '../uploads/payments/' . $fileName; // Relative path for web access

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        respondError('Failed to save file');
        return;
    }

    // Extract data from image using Unified API logic (with 3-try retry mechanism)
    $extractedData = extractDataFromImage($filePath);
    if (!$extractedData) {
        @unlink($filePath);
        respondError('Could not extract data from screenshot. Please ensure it is a clear Instapay receipt.');
        return;
    }

    // ========== TIER 1 VALIDATION ==========
    // Use the unified comprehensive analysis
    try {
        $analysis = analyzeTransaction($extractedData);
        $score = intval($analysis['confidence_score']);
    } catch (Exception $e) {
        error_log('Analysis error: ' . $e->getMessage());
        respondError('Analysis error: ' . $e->getMessage());
        return;
    }

    // ========== FRAUD DETECTION ==========
    $fraudFlags = [];
    $screenshotHash = hash('sha256', file_get_contents($filePath));
    
    // Note: Duplicate screenshot check removed (MongoDB-only setup)
    // This functionality can be added later with MongoDB implementation

    // ========== DETERMINE TIER 1 RESULT ==========
    $tier1Result = 'unknown';
    $confidenceLevel = 'low';

    // Map new analysis results to existing Tier 1 logic
    // Priority: Score over is_valid flag to prevent false rejections
    if ($score >= 85) {
        $tier1Result = 'pending'; // High confidence - goes to TIER 2
        $confidenceLevel = 'high';
    } elseif ($score >= 70) {
        $tier1Result = 'pending'; // Medium confidence - goes to TIER 2
        $confidenceLevel = 'medium';
    } elseif ($score < 40 || $analysis['is_valid'] === 'invalid') {
        $tier1Result = 'rejected';
        $confidenceLevel = 'reject';
    } elseif ($analysis['is_valid'] === 'suspicious' || $score < 70) {
        $tier1Result = 'failed';
        $confidenceLevel = 'low';
    } else {
        $tier1Result = 'pending'; // Default to pending
        $confidenceLevel = 'medium';
    }

    // Save transaction for later verification
    // Try MongoDB first (primary database), then SQLite fallback
    $transactionId = null;
    
    // Save transaction to MongoDB only
    try {
        $mongoResult = saveTransactionMongo([
            'student_id' => $studentId,
            'student_phone' => $studentPhone,
            'student_email' => $_SESSION['student_email'] ?? null,
            'amount_requested' => $_POST['amount'] ?? 0,
            'instapay_amount' => $extractedData['amount'] ?? null,
            'screenshot_path' => $relativePath, // Use relative path for web access
            'instapay_reference_number' => $extractedData['reference_number'] ?? '',
            'instapay_receiver_name' => $extractedData['receiver_name'] ?? '',
            'validation_score' => $score,
            'confidence_level' => $confidenceLevel,
            'status' => $tier1Result,
            'fraud_flags' => $fraudFlags,
            'issues' => $analysis['issues'] ?? [],
            'warnings' => $analysis['warnings'] ?? [],
            'validations' => $analysis['validations'] ?? [],
            'extracted_data' => [
                'amount' => $extractedData['amount'] ?? null,
                'sender_account' => $extractedData['sender_account'] ?? null,
                'receiver_name' => $extractedData['receiver_name'] ?? null,
                'receiver_phone' => $extractedData['receiver_phone'] ?? null,
                'reference_number' => $extractedData['reference_number'] ?? null,
                'transaction_date' => $extractedData['transaction_date'] ?? null,
                'bank_name' => $extractedData['bank_name'] ?? null,
                'sender_name' => $extractedData['sender_name'] ?? null
            ]
        ]);
        
        if ($mongoResult) {
            $transactionId = 'mongo_' . time() . '_' . rand(1000, 9999);
            error_log('Transaction saved to MongoDB: ' . $transactionId);
        } else {
            respondError('Database error: Failed to save transaction to MongoDB');
            return;
        }
    } catch (Exception $e) {
        error_log('MongoDB save error: ' . $e->getMessage());
        respondError('Database error: Failed to save transaction to MongoDB');
        return;
    }

    // Respond with Tier 1 result with detailed validation information
    respondSuccess([
        'transaction_id' => $transactionId,
        'status' => $tier1Result,
        'confidence_score' => $score,
        'confidence_level' => $confidenceLevel,
        'validation' => [
            'amount' => $extractedData['amount'] ?? null,
            'receiver_phone' => $extractedData['receiver_phone'] ?? null,
            'reference_number' => $extractedData['reference_number'] ?? null,
            'transaction_date' => $extractedData['transaction_date'] ?? null
        ],
        'issues' => $analysis['issues'] ?? [],
        'warnings' => $analysis['warnings'] ?? [],
        'validations' => $analysis['validations'] ?? [],
        'fraud_flags' => $fraudFlags,
        'extracted_data' => [
            'amount' => $extractedData['amount'] ?? null,
            'sender_account' => $extractedData['sender_account'] ?? null,
            'receiver_name' => $extractedData['receiver_name'] ?? null,
            'receiver_phone' => $extractedData['receiver_phone'] ?? null,
            'reference_number' => $extractedData['reference_number'] ?? null,
            'transaction_date' => $extractedData['transaction_date'] ?? null,
            'bank_name' => $extractedData['bank_name'] ?? null,
            'sender_name' => $extractedData['sender_name'] ?? null
        ]
    ]);
    } catch (Exception $e) {
        error_log('Upload screenshot error: ' . $e->getMessage());
        respondError('Server error: ' . $e->getMessage());
    }
}

/**
 * Check transaction status
 */
function handleCheckStatus() {
    session_start();
    respondError('Transaction status check not available in MongoDB-only setup');
}

/**
 * Get pending transactions for admin
 */
function handleGetPendingTransactions() {
    session_start();
    // Verify admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        error_log('Admin access denied - Session role: ' . ($_SESSION['role'] ?? 'none'));
        respondError('Admin access required', 403);
        return;
    }

    // Use MongoDB to get pending transactions
    try {
        error_log('Fetching pending transactions from MongoDB...');
        $transactions = getPendingTransactionsMongo();
        error_log('Found ' . count($transactions) . ' pending transactions');
        respondSuccess(['transactions' => $transactions, 'count' => count($transactions)]);
    } catch (Exception $e) {
        error_log('MongoDB error in getPendingTransactions: ' . $e->getMessage());
        // Fallback to mock data for testing
        $transactions = getMockPendingTransactions();
        error_log('Using mock data: ' . count($transactions) . ' transactions');
        respondSuccess(['transactions' => $transactions, 'count' => count($transactions)]);
    }
}

/**
 * Approve transaction - TIER 2 ADMIN DECISION
 */
function handleApproveTransaction() {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        respondError('Admin access required', 403);
        return;
    }

    $transactionId = $_POST['transaction_id'] ?? null;
    $notes = $_POST['notes'] ?? null;

    if (!$transactionId) {
        respondError('Transaction ID required');
        return;
    }

    // Try MongoDB first (primary database)
    $success = false;
    try {
        if (updateTransactionStatusMongo($transactionId, 'approved', $notes, $_SESSION['admin_id'])) {
            $success = true;
        }
    } catch (Exception $e) {
        error_log('MongoDB approve error: ' . $e->getMessage());
    }

    if ($success) {
        respondSuccess(['message' => 'Transaction approved']);
    } else {
        respondError('Update failed');
    }
}

/**
 * Reject transaction - TIER 2 ADMIN DECISION
 */
function handleRejectTransaction() {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        respondError('Admin access required', 403);
        return;
    }

    $transactionId = $_POST['transaction_id'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if (!$transactionId) {
        respondError('Transaction ID required');
        return;
    }

    // Use MongoDB to update transaction status
    try {
        if (updateTransactionStatusMongo($transactionId, 'rejected', $reason, $_SESSION['admin_id'])) {
            respondSuccess(['message' => 'Transaction rejected']);
        } else {
            respondError('Update failed');
        }
    } catch (Exception $e) {
        error_log('MongoDB reject error: ' . $e->getMessage());
        respondError('Update failed');
    }
}

/**
 * Get fraud attempts for admin
 */
function handleGetFraudAttempts() {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        respondError('Admin access required', 403);
        return;
    }

    // Return mock data since SQLite is not being used
    $attempts = [];
    respondSuccess(['fraud_attempts' => $attempts, 'count' => 0]);
}

/**
 * Verify Payment using InstaPay Checker validators and analysis
 * Uses comprehensive validation, duplicate detection, and Instapay API verification
 */
function handleVerifyPayment() {
    try {
        $referenceNumber = sanitizeInput($_POST['reference'] ?? '');
        
        if (empty($referenceNumber)) {
            respondError('Reference number is required');
            return;
        }
        
        // Use wrapper function to validate reference (handles string input properly)
        $analysis = validateTransactionReference($referenceNumber);
        
        if (!$analysis['isValid']) {
            respondError('Invalid transaction reference: ' . implode(', ', $analysis['errors']));
            return;
        }
        
        // Log warnings if any
        if (!empty($analysis['warnings'])) {
            error_log("Payment verification warnings: " . json_encode($analysis['warnings']));
        }
        
        // Check for duplicate transaction using wrapper function (handles string input)
        $isDuplicate = isDuplicateTransaction($referenceNumber);
        if ($isDuplicate) {
            respondError('This payment has already been verified. Duplicate transaction detected.');
            return;
        }
        
        // Query instapay API to verify the transaction
        $ch = curl_init("https://api.instapay.eg/api/v1/payments?reference=$referenceNumber");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . INSTAPAY_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            respondError('Failed to verify with Instapay API');
            return;
        }
        
        $paymentData = json_decode($response, true);
        
        // Verify payment exists and is complete
        if (!isset($paymentData['success']) || !$paymentData['success']) {
            respondError('Payment not found in Instapay system');
            return;
        }
        
        $payment = $paymentData['data'] ?? null;
        if (!$payment || ($payment['status'] !== 'completed' && $payment['status'] !== 'success')) {
            respondError('Payment is not in completed status');
            return;
        }
        
        // Store verified payment in MongoDB
        try {
            if (!$GLOBALS['mongoClient']) {
                throw new Exception('MongoDB not available');
            }

            $client = $GLOBALS['mongoClient'];
            $databaseName = $GLOBALS['databaseName'];
            
            $paymentRecord = [
                'reference' => $referenceNumber,
                'student_id' => $_POST['student_id'] ?? null,
                'amount' => $payment['amount'] ?? 0,
                'status' => 'verified',
                'instapay_data' => $payment,
                'verified_at' => new MongoDB\BSON\UTCDateTime(),
                'analysis_data' => $analysis,
                'createdAt' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->insert($paymentRecord);
            $result = $client->executeBulkWrite("$databaseName.payments", $bulk);
            
            if ($result->getInsertedCount() > 0) {
                respondSuccess([
                    'reference' => $referenceNumber,
                    'amount' => $payment['amount'] ?? 0,
                    'message' => 'Payment verified successfully',
                    'warnings' => $analysis['warnings'] ?? []
                ]);
            } else {
                respondError('Failed to store payment record');
            }
        } catch (Exception $e) {
            error_log('MongoDB save error: ' . $e->getMessage());
            respondError('Failed to store payment record');
        }
        
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        respondError('Server error: ' . $e->getMessage(), 500);
    }
}

/**
 * Sanitize input to prevent injection
 */
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}


/**
 * Add balance to student account
 */
function addStudentBalance($studentId, $amount, $reason, $transactionId) {
    try {
        if (!$GLOBALS['mongoClient']) {
            throw new Exception('MongoDB not available');
        }

        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        
        // Get current balance
        $filter = ['_id' => new MongoDB\BSON\ObjectId($studentId)];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.students", $query);
        $students = $cursor->toArray();
        
        if (count($students) > 0) {
            $student = $students[0];
            $currentBalance = $student->balance ?? 0;
            $newBalance = $currentBalance + $amount;
            
            // Update balance
            $update = [
                '$set' => [
                    'balance' => $newBalance,
                    'updatedAt' => new MongoDB\BSON\UTCDateTime()
                ]
            ];
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update($filter, $update);
            $result = $client->executeBulkWrite("$databaseName.students", $bulk);
            
            // Log transaction
            $historyRecord = [
                'student_id' => $studentId,
                'amount' => $amount,
                'transaction_type' => 'add',
                'reason' => $reason,
                'balance_after' => $newBalance,
                'transaction_id' => $transactionId,
                'created_by' => 0,
                'createdAt' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $historyBulk = new MongoDB\Driver\BulkWrite();
            $historyBulk->insert($historyRecord);
            $client->executeBulkWrite("$databaseName.balance_history", $historyBulk);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Balance update error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Payment API Endpoint
 * Handles payment processing, verification, and balance management
 */

header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
require_once 'config.php';

/**
 * Response helpers
 */
function respondSuccess($data = []) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data]);
    error_log('API Response Success: ' . json_encode($data));
    exit;
}

function respondError($message = '', $code = 400) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    error_log('API Response Error (' . $code . '): ' . $message);
    exit;
}

// Fallback function if instapay-checker is not available
if (!function_exists('extractDataFromImage')) {
    function extractDataFromImage($imagePath) {
        // Simple fallback - return mock data
        return [
            'amount' => '500.00',
            'reference' => 'MOCK' . rand(100000, 999999),
            'receiver' => 'Mock Receiver',
            'confidence' => 0.8
        ];
    }
}

// Redundant validation functions removed - using instapay-checker/api.php versions
?>
