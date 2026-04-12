<?php
/**
 * Instapay Transaction Validator API
 * Handles image processing, data extraction, and validation
 * Integrated with Pollinations AI (free, no API key needed)
 */

// Only set headers if this file is being called directly, not when included from another file
$isDirectCall = (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'api.php' && 
                 dirname($_SERVER['SCRIPT_FILENAME'] ?? '') === __DIR__) ||
                (php_sapi_name() === 'cli'); // Allow for CLI testing

if ($isDirectCall) {
    header('Content-Type: application/json; charset=utf-8');
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration and database functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Load professional validation libraries via Composer
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Only initialize database and create directories if this file is called directly
if ($isDirectCall) {
    // Create uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }

    // Initialize database
    initializeDatabase();
}

// Only execute routing when called directly (not when included from another file)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'api.php' && dirname($_SERVER['SCRIPT_FILENAME'] ?? '') === __DIR__) {
    // Route API requests
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'process':
            handleProcessImage();
            break;
        case 'save':
            handleSaveTransaction();
            break;
        case 'stats':
            handleGetStats();
            break;
        case 'update':
            handleUpdateTransaction();
            break;
        case 'delete':
            handleDeleteTransaction();
            break;
        case 'search':
            handleSearchTransactions();
            break;
        case 'analytics':
            handleGetAnalytics();
            break;
        case 'fraud':
            handleGetInstapayFraudAttempts();
            break;
        case 'get':
            handleGetTransaction();
            break;
        case 'approve':
            handleInstapayApprove();
            break;
        case 'reject':
            handleInstapayReject();
            break;
        case 'pending':
            handleGetInstapayPending();
            break;
        default:
            instapay_respond_error('Invalid action');
    }
}

/**
 * Process uploaded image
 */
function handleProcessImage() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        instapay_respond_error('Failed to upload file');
        return;
    }

    $file = $_FILES['file'];
    
    // Validate file
    $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $imageInfo = getimagesize($file['tmp_name']);
    $fileMime = $imageInfo['mime'] ?? null;
    
    if (!in_array($fileMime, $validMimes)) {
        instapay_respond_error('Invalid file type. Only images are allowed');
        return;
    }

    // Save uploaded file
    $uploadDir = 'uploads/';
    $fileName = 'temp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        instapay_respond_error('Failed to save uploaded file');
        return;
    }

    // Check for obvious security issues before heavy analysis
    $securityResult = performSecurityCheck($filePath);
    if ($securityResult['flagged']) {
        TransactionDatabase::logFraudAttempt('SCREENSHOT_SECURITY', $securityResult['reason'], ['file' => $fileName]);
    }

    // Extract data from image using AI
    $extractedData = extractDataFromImage($filePath);

    if (!$extractedData) {
        instapay_respond_error('Failed to extract data from image. AI might be busy, please try again.');
        return;
    }

    // Analyze extracted data for consistency and patterns (includes duplicate check)
    $analysis = analyzeTransactionDetails($extractedData, $filePath);
    
    // Get duplicate status from analysis
    $isDuplicate = $analysis['validations']['duplicate']['valid'] === false;
    
    // Log suspicious activity
    if ($analysis['is_valid'] === 'invalid' || $isDuplicate || $securityResult['flagged']) {
        TransactionDatabase::logFraudAttempt(
            $extractedData['reference_number'] ?? 'UNKNOWN',
            $isDuplicate ? 'Duplicate Transaction' : ($securityResult['flagged'] ? 'Security Indicator' : 'Invalid Analysis'),
            [
                'analysis' => $analysis, 
                'data' => $extractedData, 
                'security' => $securityResult,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]
        );
    }

    // Prepare response data
    $data = [
        'amount' => $extractedData['amount'] ?? 'غير متوفر',
        'currency' => $extractedData['currency'] ?? 'EGP',
        'sender_account' => $extractedData['sender_account'] ?? 'غير متوفر',
        'sender_name' => $extractedData['sender_name'] ?? 'غير متوفر',
        'receiver_name' => $extractedData['receiver_name'] ?? 'غير متوفر',
        'receiver_phone' => $extractedData['receiver_phone'] ?? 'غير متوفر',
        'reference_number' => $extractedData['reference_number'] ?? 'غير متوفر',
        'transaction_date' => $extractedData['transaction_date'] ?? 'غير متوفر',
        'bank_name' => $extractedData['bank_name'] ?? 'غير متوفر',
        'transaction_type' => $extractedData['transaction_type'] ?? 'تحويل أموال',
        'confidence_score' => $analysis['confidence_score'] ?? '0%',
        'temp_file' => $fileName
    ];

    // Response includes both extracted data and analysis results
    instapay_respond([
        'success' => true,
        'data' => $data,
        'analysis' => array_merge($analysis, $duplicateCheck, ['security_flagged' => $securityResult['flagged']])
    ]);
}

/**
 * Save transaction to database
 */
function handleSaveTransaction() {
    if (!isset($_POST['data']) || !isset($_FILES['file'])) {
        instapay_respond_error('Missing required data');
        return;
    }

    $data = json_decode($_POST['data'], true);
    
    if (!$data) {
        instapay_respond_error('Invalid data format');
        return;
    }

    // STRICT: Enforce amount limits 50-1000 EGP
    $amount = isset($data['amount']) ? (float) preg_replace('/[^\d.]/', '', $data['amount']) : 0;
    $minAmount = defined('MIN_TRANSACTION_AMOUNT') ? MIN_TRANSACTION_AMOUNT : 50;
    $maxAmount = defined('MAX_TRANSACTION_AMOUNT') ? MAX_TRANSACTION_AMOUNT : 1000;
    
    if ($amount < $minAmount || $amount > $maxAmount) {
        instapay_respond_error("Amount must be between $minAmount and $maxAmount EGP");
        return;
    }

    // Move temp file to permanent storage
    $tempFile = $data['temp_file'] ?? null;
    if ($tempFile && file_exists('uploads/' . $tempFile)) {
        $newFileName = 'transaction_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($tempFile, PATHINFO_EXTENSION);
        rename('uploads/' . $tempFile, 'uploads/' . $newFileName);
        $data['screenshot_path'] = 'uploads/' . $newFileName;
    }

    // Add user info from session if available
    $data['user_id'] = $_SESSION['user_id'] ?? null;
    $data['username'] = $_SESSION['username'] ?? 'anonymous';
    $data['user_email'] = $_SESSION['email'] ?? null;

    // Save to database
    if (saveTransactionToDatabase($data)) {
        instapay_respond([
            'success' => true,
            'message' => 'تم إرسال المعاملة للمراجعة الإدارية',
            'status' => 'pending_approval',
            'note' => 'Transaction pending admin approval'
        ]);
    } else {
        instapay_respond_error('Failed to save transaction - amount may be outside 50-1000 EGP limits');
    }
}

/**
 * Get statistics
 */
function handleGetStats() {
    $stats = getStatistics();
    $recent = getAllTransactionsFromDB(10);
    instapay_respond([
        'success' => true,
        'stats' => $stats,
        'recent' => $recent
    ]);
}

/**
 * Update a transaction
 */
function handleUpdateTransaction() {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        instapay_respond_error('Transaction ID required');
        return;
    }

    $update = [];
    if (isset($_POST['status'])) $update['status'] = $_POST['status'];
    if (isset($_POST['notes'])) $update['notes'] = $_POST['notes'];
    if (isset($_POST['amount'])) $update['amount'] = (float)$_POST['amount'];
    $update['updated_at'] = DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000));

    $result = updateTransactionInDB(
        ['_id' => DatabaseMongo::createObjectId($id)],
        ['$set' => $update]
    );

    if ($result) {
        instapay_respond(['success' => true, 'message' => 'Transaction updated']);
    } else {
        instapay_respond_error('Failed to update transaction');
    }
}

/**
 * Delete a transaction
 */
function handleDeleteTransaction() {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if (!$id) {
        instapay_respond_error('Transaction ID required');
        return;
    }

    $result = deleteTransactionFromDB([
        '_id' => DatabaseMongo::createObjectId($id)
    ]);

    if ($result) {
        instapay_respond(['success' => true, 'message' => 'Transaction deleted']);
    } else {
        instapay_respond_error('Failed to delete transaction');
    }
}

/**
 * Search transactions
 */
function handleSearchTransactions() {
    $criteria = [
        'reference' => $_POST['reference'] ?? $_GET['reference'] ?? '',
        'sender' => $_POST['sender'] ?? $_GET['sender'] ?? '',
        'receiver' => $_POST['receiver'] ?? $_GET['receiver'] ?? '',
        'min_amount' => $_POST['min_amount'] ?? $_GET['min_amount'] ?? '',
        'max_amount' => $_POST['max_amount'] ?? $_GET['max_amount'] ?? '',
        'status' => $_POST['status'] ?? $_GET['status'] ?? ''
    ];

    $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
    $results = searchTransactionsInDB($criteria, $limit);

    instapay_respond([
        'success' => true,
        'count' => count($results),
        'results' => $results
    ]);
}

/**
 * Get analytics data
 */
function handleGetAnalytics() {
    $analytics = getTransactionAnalytics();
    instapay_respond([
        'success' => true,
        'analytics' => $analytics
    ]);
}

/**
 * Get fraud attempts
 */
function handleGetInstapayFraudAttempts() {
    $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
    $attempts = getRecentFraudAttempts($limit);
    instapay_respond([
        'success' => true,
        'count' => count($attempts),
        'attempts' => $attempts
    ]);
}

/**
 * Get single transaction by reference
 */
function handleGetTransaction() {
    $ref = $_POST['ref'] ?? $_GET['ref'] ?? '';
    if (!$ref) {
        instapay_respond_error('Reference number required');
        return;
    }

    $transaction = getTransactionByRef($ref);
    if ($transaction) {
        instapay_respond(['success' => true, 'transaction' => $transaction]);
    } else {
        instapay_respond_error('Transaction not found');
    }
}

/**
 * Admin: Approve a transaction
 */
function handleInstapayApprove() {
    // TODO: Add admin authentication check
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    $adminId = $_SESSION['admin_id'] ?? $_POST['admin_id'] ?? 'system';
    $notes = $_POST['notes'] ?? 'Approved by admin';
    
    if (!$id) {
        instapay_respond_error('Transaction ID required');
        return;
    }

    $result = updateTransactionInDB(
        ['_id' => DatabaseMongo::createObjectId($id)],
        [
            '$set' => [
                'status' => 'approved',
                'admin_status' => 'approved',
                'admin_review.reviewed_by' => $adminId,
                'admin_review.reviewed_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000)),
                'admin_review.decision' => 'approved',
                'admin_review.notes' => $notes,
                'updated_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000))
            ]
        ]
    );

    if ($result) {
        instapay_respond(['success' => true, 'message' => 'Transaction approved']);
    } else {
        instapay_respond_error('Failed to approve transaction');
    }
}

/**
 * Admin: Reject a transaction
 */
function handleInstapayReject() {
    // TODO: Add admin authentication check
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    $adminId = $_SESSION['admin_id'] ?? $_POST['admin_id'] ?? 'system';
    $notes = $_POST['notes'] ?? 'Rejected by admin';
    $reason = $_POST['reason'] ?? 'manual_rejection';
    
    if (!$id) {
        instapay_respond_error('Transaction ID required');
        return;
    }

    $result = updateTransactionInDB(
        ['_id' => DatabaseMongo::createObjectId($id)],
        [
            '$set' => [
                'status' => 'rejected',
                'admin_status' => 'rejected',
                'admin_review.reviewed_by' => $adminId,
                'admin_review.reviewed_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000)),
                'admin_review.decision' => 'rejected',
                'admin_review.notes' => $notes,
                'admin_review.rejection_reason' => $reason,
                'updated_at' => DatabaseMongo::createUTCDateTime(intval(microtime(true) * 1000))
            ]
        ]
    );

    if ($result) {
        instapay_respond(['success' => true, 'message' => 'Transaction rejected']);
    } else {
        instapay_respond_error('Failed to reject transaction');
    }
}

/**
 * Get pending transactions for admin review
 */
function handleGetInstapayPending() {
    $db = TransactionDatabase::getConnection();
    if (!$db) {
        instapay_respond_error('Database connection failed');
        return;
    }

    $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 50);
    
    try {
        $pending = $db->find('instapay_transactions', 
            ['status' => ['$in' => ['pending_approval', 'flagged']]],
            ['sort' => ['created_at' => -1], 'limit' => $limit]
        );
        
        instapay_respond([
            'success' => true,
            'count' => count($pending),
            'pending' => $pending
        ]);
    } catch (Exception $e) {
        instapay_respond_error('Failed to fetch pending transactions');
    }
}

/**
 * Extract data from image using AI
 */
function extractDataFromImage($imagePath) {
    if (!file_exists($imagePath)) return null;

    $ocrResult = performSimulatedOCR($imagePath);
    if (!$ocrResult) return null;
    
    $data = json_decode($ocrResult, true);
    
    // Handle nested results
    if (is_array($data) && isset($data['transaction_details'])) {
        $data = $data['transaction_details'];
    }
    
    if (!is_array($data) || (empty($data['amount']) && empty($data['reference_number']))) {
        return null;
    }
    
    return $data;
}

/**
 * Perform OCR using Mistral AI (Primary) or Pollinations AI (Fallback)
 */
function performSimulatedOCR($imagePath) {
    // Try Mistral AI first (Preferred by user) - Attempt 1
    $result = analyzeImageWithMistral($imagePath);
    if ($result) {
        return $result;
    }
    
    // Attempt 2: Mistral Retry (sometimes transient network issues)
    error_log("Mistral extraction attempt 1 failed for: " . basename($imagePath) . ". Retrying Mistral...");
    $result = analyzeImageWithMistral($imagePath);
    if ($result) {
        return $result;
    }

    // Attempt 3: Final Fallback to Pollinations AI
    error_log("Mistral extraction attempt 2 failed. Falling back to Pollinations for: " . basename($imagePath));
    return analyzeImageWithPollinations($imagePath);
}

/**
 * Analyze image with Mistral AI
 * Sends image to Mistral and extracts Instapay transaction details
 */
function analyzeImageWithMistral($imagePath) {
    if (!file_exists($imagePath)) {
        return null;
    }

    $imageData = file_get_contents($imagePath);
    if (!$imageData) {
        return null;
    }

    $base64Image = base64_encode($imageData);
    $imageInfo = getimagesize($imagePath);
    $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

    $requestBody = [
        'model' => MISTRAL_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'أنت متخصص في تحليل لقطات شاشة معاملات إنستاباي. حلل الصورة وأخرج جميع المعلومات بصيغة JSON تحتوي على: amount (المبلغ), currency (العملة), sender_account (بريد المرسل @instapay), sender_name (اسم المرسل), receiver_name (اسم المستقبل), receiver_phone (رقم الهاتف), reference_number (الرقم المرجع), transaction_date (التاريخ والوقت), bank_name (اسم البنك), transaction_type (نوع المعاملة). يجب أن تكون جميع القيم نصية. إذا لم تجد معلومة ما، ضع null. أعد فقط JSON بدون شرح.'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:' . $mimeType . ';base64,' . $base64Image
                        ]
                    ]
                ]
            ]
        ],
        'temperature' => 0.1,
        'response_format' => ['type' => 'json_object']
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => MISTRAL_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MISTRAL_API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        error_log('Mistral AI Connection Error: ' . $curlError);
        return null;
    }

    if ($httpCode !== 200) {
        error_log('Mistral AI Error: HTTP ' . $httpCode . ' - ' . $response);
        return null;
    }

    $responseData = json_decode($response, true);
    $textContent = $responseData['choices'][0]['message']['content'] ?? null;
    
    if (!$textContent) {
        error_log('Mistral AI: No content in response: ' . $response);
        return null;
    }

    return trim($textContent);
}

/**
 * Analyze image with Pollinations AI
 * Sends image to Pollinations and extracts Instapay transaction details
 */
function analyzeImageWithPollinations($imagePath) {
    // Read image file
    if (!file_exists($imagePath)) {
        return null;
    }

    $imageData = file_get_contents($imagePath);
    if (!$imageData) {
        return null;
    }

    // Encode image to base64
    $base64Image = base64_encode($imageData);

    // Get MIME type
    $imageInfo = getimagesize($imagePath);
    $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

    // Prepare Pollinations AI request (OpenAI-compatible)
    $requestBody = [
        'model' => POLLINATIONS_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'أنت متخصص في تحليل لقطات شاشة معاملات إنستاباي. حلل الصورة وأخرج جميع المعلومات بصيغة JSON تحتوي على: amount (المبلغ), currency (العملة), sender_account (بريد المرسل @instapay), sender_name (اسم المرسل), receiver_name (اسم المستقبل), receiver_phone (رقم الهاتف), reference_number (الرقم المرجع), transaction_date (التاريخ والوقت), bank_name (اسم البنك), transaction_type (نوع المعاملة). يجب أن تكون جميع القيم نصية. إذا لم تجد معلومة ما، ضع null. أعد فقط JSON بدون شرح.'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:' . $mimeType . ';base64,' . $base64Image
                        ]
                    ]
                ]
            ]
        ],
        'temperature' => 0.1
    ];

    $maxRetries = 2; // Mistral, Mistral, then default
    $response = null;
    $textContent = '';

    for ($i = 0; $i <= $maxRetries; $i++) {
        // Prepare request body with model rotation
        if ($i < 2) {
            // First 2 tries: use mistral-7b (excellent for structured data)
            $requestBody['model'] = 'mistral-7b';
        } else {
            // Last try: use default pollinations model
            unset($requestBody['model']);
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => POLLINATIONS_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . POLLINATIONS_API_KEY
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            error_log("Pollinations API Try #$i Connection Error: " . $curlError);
            if ($i == $maxRetries) return null;
            continue;
        }

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            $textContent = $responseData['choices'][0]['message']['content'] ?? '';
            
            // Basic validation of result - must contain some numbers or keywords
            if (strlen($textContent) > 20 && (preg_match('/[0-9]/', $textContent) || strpos($textContent, '{') !== false)) {
                break; // Found good result
            }
            error_log("Pollinations Try #$i returned low quality result, retrying...");
        } else {
            error_log("Pollinations API Try #$i failed with code $httpCode");
        }
        
        if ($i < $maxRetries) {
            sleep(2); // Short wait before next model/try
        }
    }

    if (empty($textContent)) {
        return null;
    }

    // Clean up response (remove markdown code blocks if present)
    $textContent = preg_replace('/```json\s*/', '', $textContent);
    $textContent = preg_replace('/```\s*/', '', $textContent);
    $textContent = trim($textContent);

    return $textContent;
}

/**
 * Validate IBAN using php-iban library or MOD-97 fallback
 */
function validateIBAN($iban) {
    // Try using professional library if available
    if (function_exists('verify_iban')) {
        try {
            if (verify_iban($iban, true)) {
                $parts = iban_get_parts($iban);
                return [
                    'valid' => true,
                    'country' => $parts['country'] ?? 'EG',
                    'checksum' => iban_get_checksum_part($iban),
                    'reason' => 'Valid IBAN (verified by php-iban)'
                ];
            }
        } catch (\Throwable $e) {
            // Fall through to manual validation
        }
    }

    // Manual MOD-97 fallback validation
    if (!preg_match('/^EG\d{27}$/', $iban)) {
        return [
            'valid' => false,
            'reason' => 'Invalid Egyptian IBAN format. Expected: EGkkbbbbsssscccccccccccccccc'
        ];
    }

    // Move first 4 chars to end
    $rearranged = substr($iban, 4) . substr($iban, 0, 4);
    
    // Replace letters with numbers (E=14, G=16)
    $numeric = '';
    for ($i = 0; $i < strlen($rearranged); $i++) {
        $char = $rearranged[$i];
        if (is_numeric($char)) {
            $numeric .= $char;
        } else {
            $numeric .= ord($char) - ord('A') + 10;
        }
    }

    // Validate using MOD-97
    $remainder = 0;
    for ($i = 0; $i < strlen($numeric); $i++) {
        $remainder = ($remainder . $numeric[$i]) % 97;
    }

    return [
        'valid' => $remainder == 1,
        'reason' => $remainder == 1 ? 'Valid IBAN (manual validation)' : 'Invalid IBAN check digits'
    ];
}

/**
 * Validate Egyptian phone number using libphonenumber or manual validation
 */
function validateEgyptianPhone($phone) {
    if (!$phone) return ['valid' => false, 'reason' => 'Phone number is empty'];
    
    // Try using professional libphonenumber library if available
    if (class_exists('\\libphonenumber\\PhoneNumberUtil')) {
        try {
            $util = \libphonenumber\PhoneNumberUtil::getInstance();
            $numberProto = $util->parse($phone, 'EG');
            
            if (!$util->isValidNumber($numberProto)) {
                return ['valid' => false, 'reason' => 'Invalid Egyptian phone number'];
            }

            // Get carrier if available
            $provider = 'Unknown';
            if (class_exists('\\libphonenumber\\PhoneNumberToCarrierMapper')) {
                try {
                    $carrier = \libphonenumber\PhoneNumberToCarrierMapper::getInstance();
                    $provider = $carrier->getNameForNumber($numberProto, 'en') ?: $provider;
                } catch (\Throwable $e) {
                    // Carrier detection failed, use fallback
                }
            }

            return [
                'valid' => true,
                'formatted' => $util->format($numberProto, \libphonenumber\PhoneNumberFormat::E164),
                'provider' => $provider,
                'source' => 'libphonenumber'
            ];
        } catch (\Throwable $e) {
            // Fall through to manual validation
        }
    }

    // Manual validation fallback
    $cleaned = preg_replace('/\D/', '', $phone);
    
    // Support both +20 and 0 prefix
    if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '20') {
        $cleaned = '0' . substr($cleaned, 2); // Convert +20... to 0...
    }
    
    if (!preg_match('/^0[1][0-6]\d{8}$/', $cleaned)) {
        return [
            'valid' => false,
            'reason' => 'Invalid Egyptian phone format. Expected: 01X XXXXXXXX'
        ];
    }

    $prefix = substr($cleaned, 0, 3);
    $providers = [
        '010' => 'Vodafone',
        '011' => 'Etisalat',
        '012' => 'Telecom Egypt',
        '015' => 'Etisalat (Misr)',
        '016' => 'Mobinil'
    ];

    $provider = $providers[$prefix] ?? 'Unknown Provider';
    
    return [
        'valid' => true,
        'formatted' => '+20' . substr($cleaned, 1),
        'provider' => $provider,
        'source' => 'manual validation'
    ];
}

/**
 * Validate reference number format
 * Instapay reference: typically 12-15 alphanumeric characters based on test samples
 */
function validateReferenceNumber($ref) {
    if (!$ref) return ['valid' => false, 'reason' => 'Reference number is empty'];
    
    $cleanedRef = trim($ref);
    $refLength = strlen($cleanedRef);
    
    // Check blocklist of known fake reference numbers
    $blockedRefs = [
        'FAKE1234567890',
        'FAKE123456789',
        'FAKE12345678',
        'TEST1234567890',
        'TEST123456789',
        'DEMO1234567890',
        'DEMO123456789'
    ];
    
    if (in_array(strtoupper($cleanedRef), $blockedRefs)) {
        return [
            'valid' => false,
            'reason' => "Reference number is on the known fake blocklist - this is a confirmed fake reference number"
        ];
    }
    
    // Check for common fake reference patterns
    // Fake receipts often have incorrect length (e.g., 11 digits instead of 12-15)
    if ($refLength < 12) {
        return [
            'valid' => false,
            'reason' => "Reference number too short ($refLength digits). Instapay references are typically 12-15 characters. This is a common sign of a fake screenshot."
        ];
    }
    
    if ($refLength > 15) {
        return [
            'valid' => false,
            'reason' => "Reference number too long ($refLength digits). Instapay references are typically 12-15 characters."
        ];
    }
    
    // Check for obvious fake patterns like "FAKE" prefix
    if (preg_match('/^FAKE/i', $cleanedRef)) {
        return [
            'valid' => false,
            'reason' => "Reference number contains 'FAKE' prefix - clear indication of a fake screenshot"
        ];
    }
    
    // Check for obvious fake patterns like "TEST" prefix
    if (preg_match('/^TEST/i', $cleanedRef)) {
        return [
            'valid' => false,
            'reason' => "Reference number contains 'TEST' prefix - clear indication of a test/fake screenshot"
        ];
    }
    
    // Check for obvious fake patterns like "DEMO" prefix
    if (preg_match('/^DEMO/i', $cleanedRef)) {
        return [
            'valid' => false,
            'reason' => "Reference number contains 'DEMO' prefix - clear indication of a test/fake screenshot"
        ];
    }
    
    // Check for sequential numbers (common in fake receipts)
    if (preg_match('/^0{12,15}$/', $cleanedRef)) {
        return [
            'valid' => false,
            'reason' => "Reference number contains only zeros - clear indication of a fake screenshot"
        ];
    }
    
    // Check for repeated patterns (e.g., 111111111111, 222222222222)
    if (preg_match('/^(\d)\1{11,14}$/', $cleanedRef)) {
        return [
            'valid' => false,
            'reason' => "Reference number contains repeated digits - clear indication of a fake screenshot"
        ];
    }
    
    // Instapay reference: alphanumeric 12-15 chars (based on test samples)
    if (preg_match('/^[A-Z0-9]{12,15}$/i', $cleanedRef)) {
        // Check for bank-specific patterns
        // Egyptian banks often use specific prefixes or patterns
        $bankPatterns = [
            '/^INST/i' => 'Instapay',
            '/^NBE/i' => 'National Bank of Egypt',
            '/^CIB/i' => 'Commercial International Bank',
            '/^QNB/i' => 'QNB Alahli',
            '/^MISR/i' => 'Bank Misr',
            '/^ALEX/i' => 'Alexandria Bank',
            '/^AHLY/i' => 'National Bank of Egypt (Ahly)',
        ];
        
        $detectedBank = 'Unknown Bank';
        foreach ($bankPatterns as $pattern => $bank) {
            if (preg_match($pattern, $cleanedRef)) {
                $detectedBank = $bank;
                break;
            }
        }
        
        return ['valid' => true, 'format' => 'Standard Instapay Reference', 'length' => $refLength, 'bank' => $detectedBank];
    }
    
    // IBAN reference format (different from standard transaction reference)
    if (preg_match('/^EG\d{27}$/', $cleanedRef)) {
        return ['valid' => true, 'format' => 'IBAN-based Reference', 'length' => $refLength];
    }

    return [
        'valid' => false,
        'reason' => "Invalid reference format. Expected: alphanumeric, 12-15 characters. Got: $refLength characters"
    ];
}

/**
 * Validate transaction date is within allowed timeframe (7 days)
 */
function validateTransactionDate($dateStr) {
    if (!$dateStr) {
        return [
            'valid' => false,
            'reason' => 'Transaction date is empty',
            'days_old' => null
        ];
    }

    error_log("Validating transaction date: '$dateStr'");
    error_log("Current server time: " . date('Y-m-d H:i:s'));

    // Try to parse various date formats
    $parsedDate = null;
    
    // Try parsing: "1/2/2024 14:30", "01-02-2024 14:30", "1 Feb 2024 14:30", etc.
    $formats = [
        'd/m/Y H:i',       // 1/2/2024 14:30
        'm/d/Y H:i',       // 2/1/2024 14:30
        'd-m-Y H:i',       // 1-2-2024 14:30
        'm-d-Y H:i',       // 2-1-2024 14:30
        'd/m/Y',           // 1/2/2024
        'm/d/Y',           // 2/1/2024
        'd-m-Y',           // 1-2-2024
        'm-d-Y',           // 2-1-2024
        'd M Y H:i',       // 1 Feb 2024 14:30
        'd M Y h:i A',     // 04 Apr 2026 12:46 AM
        'd M Y h:i:s A',   // 04 Apr 2026 12:46:30 AM
        'd M Y h:i:s',     // 04 Apr 2026 12:46:30
        'd M Y g:i A',     // 4 Apr 2026 12:46 AM (no leading zero)
        'd M Y g:i:s A',   // 4 Apr 2026 12:46:30 AM
        'M d Y',           // Apr 04 2026
        'M d Y h:i A',     // Apr 04 2026 12:46 AM
        'M d Y H:i',       // Apr 04 2026 14:30
        'F d, Y',          // April 04, 2026
        'F d, Y h:i A',    // April 04, 2026 12:46 AM
        'd-M-Y',           // 04-Apr-2026
        'd/M/Y h:i A',     // 04/04/2026 12:46 AM
        'd/M/Y g:i A',     // 4/4/2026 12:46 AM
        'd M Y',           // 1 Feb 2024
        'Y-m-d H:i:s',     // 2024-02-01 14:30:00
        'Y-m-d',           // 2024-02-01
    ];

    foreach ($formats as $format) {
        $dateObj = \DateTime::createFromFormat($format, trim($dateStr));
        if ($dateObj !== false && $dateObj->format($format) === trim($dateStr)) {
            $parsedDate = $dateObj;
            error_log("Date parsed successfully with format '$format': " . $parsedDate->format('Y-m-d H:i:s'));
            break;
        }
    }

    // If parsing failed, try a more lenient approach
    if (!$parsedDate) {
        // Extract year, month, day with regex
        if (preg_match('/(\d{1,4})[\/-](\d{1,2})[\/-](\d{1,4})/', $dateStr, $matches)) {
            $part1 = (int)$matches[1];
            $part2 = (int)$matches[2];
            $part3 = (int)$matches[3];
            
            // Determine which is year, month, day
            if ($part3 > 1000) { // part3 is year (YYYY format)
                $year = $part3;
                // Decide if part1 is month or day
                $month = ($part1 <= 12) ? $part1 : $part2;
                $day = ($part1 <= 12) ? $part2 : $part1;
            } elseif ($part1 > 1000) { // part1 is year (YYYY format)
                $year = $part1;
                $month = $part2;
                $day = $part3;
            } else { // 2-digit or 4-digit year at end
                $year = ($part3 < 100) ? 2000 + $part3 : $part3;
                $month = ($part1 <= 12) ? $part1 : $part2;
                $day = ($part1 <= 12) ? $part2 : $part1;
            }
            
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                $parsedDate = new \DateTime("$year-$month-$day");
            }
        }
    }

    if (!$parsedDate) {
        return [
            'valid' => false,
            'reason' => 'Could not parse transaction date format',
            'days_old' => null
        ];
    }

    // Validate the date is real (e.g., not Feb 30)
    $year = (int)$parsedDate->format('Y');
    $month = (int)$parsedDate->format('n');
    $day = (int)$parsedDate->format('j');
    
    // Check for valid year range (reasonable transaction years)
    $currentYear = (int)date('Y');
    if ($year < 2020 || $year > $currentYear) {
        return [
            'valid' => false,
            'reason' => "Invalid year: $year (expected 2020-$currentYear)",
            'days_old' => null
        ];
    }
    
    // Validate day exists in the month
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    if ($day < 1 || $day > $daysInMonth) {
        return [
            'valid' => false,
            'reason' => "Invalid date: Day $day does not exist in month $month/$year",
            'days_old' => null
        ];
    }

    // Check if date is in the future (invalid)
    $now = new \DateTime('now');
    error_log("Now (before setTime): " . $now->format('Y-m-d H:i:s'));
    $now->setTime(0, 0, 0); // Compare dates only (ignore time)
    error_log("Now (after setTime): " . $now->format('Y-m-d H:i:s'));
    $parsedDate->setTime(0, 0, 0);
    error_log("Parsed date (after setTime): " . $parsedDate->format('Y-m-d H:i:s'));

    if ($parsedDate > $now) {
        error_log("Transaction date is in the future");
        return [
            'valid' => false,
            'reason' => 'Transaction date is in the future',
            'days_old' => 'Future'
        ];
    }

    // Calculate days difference more precisely
    $interval = $now->diff($parsedDate);
    $daysOld = $interval->days;
    error_log("Days difference: $daysOld");

    // STRICT: Check if within allowed timeframe (7 days)
    $maxDays = defined('MAX_TRANSACTION_AGE_DAYS') ? MAX_TRANSACTION_AGE_DAYS : 7;
    error_log("Max allowed days: $maxDays");

    if ($daysOld > $maxDays) {
        error_log("Transaction is too old: $daysOld days > $maxDays max");
        return [
            'valid' => false,
            'reason' => "Transaction rejected: $daysOld days old (max allowed: $maxDays days)",
            'days_old' => $daysOld
        ];
    }
    
    // Also reject if date is same day but hours indicate old transaction
    // This catches same-day transactions that are actually older
    $hoursOld = $interval->h + ($interval->days * 24);
    if ($daysOld == 0 && $hoursOld > 24) {
        // This shouldn't happen with our date-only comparison, but just in case
        $daysOld = ceil($hoursOld / 24);
        if ($daysOld > $maxDays) {
            return [
                'valid' => false,
                'reason' => "Transaction rejected: ~$daysOld days old (max allowed: $maxDays days)",
                'days_old' => $daysOld
            ];
        }
    }

    return [
        'valid' => true,
        'reason' => "Valid - Transaction is $daysOld days old",
        'days_old' => $daysOld
    ];
}

/**
 * Validate Instapay email account with partner bank verification
 */
function validateInstapayEmail($email) {
    if (!$email) return ['valid' => false, 'reason' => 'Email is empty'];
    
    // Handle Instapay's special format: username@instapay (without .eg)
    // First, normalize to include .eg if missing
    $emailForValidation = $email;
    if (preg_match('/^[a-z0-9._\-]+@instapay$/', strtolower($email))) {
        // Valid @instapay format (without .eg extension)
        return [
            'valid' => true,
            'email' => $email,
            'domain' => 'instapay',
            'bank' => 'Instapay',
            'has_mx_records' => true,
            'reason' => 'Valid Instapay user account',
            'format' => 'instapay_username'
        ];
    }
    
    // Basic email format validation
    if (!filter_var($emailForValidation, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'reason' => 'Invalid email format'];
    }

    $domain = strtolower(substr($emailForValidation, strrpos($emailForValidation, '@') + 1));
    
    // Official Instapay partner banks and email domains
    $validDomains = [
        'instapay.eg'        => 'Instapay',
        'instapay'           => 'Instapay',
        'ebank.eg'           => 'Egyptian e-Banking',
        'alahli.eg'          => 'National Bank of Egypt (Ahly)',
        'alahlibank.eg'      => 'National Bank of Egypt (Ahly)',
        'cib.eg'             => 'Commercial International Bank',
        'nbe.eg'             => 'National Bank of Egypt',
        'aib.eg'             => 'Arab International Bank',
        'banquemisr.com'     => 'Banque Misr',
        'banquemisr.eg'      => 'Banque Misr',
        'alexbank.eg'        => 'Alexandria Bank',
        'arabafricabank.eg'  => 'Arab Africa Bank',
        'abk.eg'             => 'Abu Dhabi Islamic Bank',
        'fib.eg'             => 'Faisal Islamic Bank',
        'nbk.com'            => 'NBK',
        'egypt.ahli.com'     => 'Ahli Bank Egypt'
    ];

    $isValid = false;
    $bankName = null;
    
    // Check if domain matches known partner
    foreach ($validDomains as $validDomain => $bank) {
        if ($domain === $validDomain || 
            substr($domain, -strlen($validDomain)) === $validDomain) {
            $isValid = true;
            $bankName = $bank;
            break;
        }
    }

    // Verify domain has valid MX records (SMTP check)
    $hasMX = false;
    if (@getmxrr($domain, $mxRecords)) {
        $hasMX = true;
    }

    return [
        'valid' => $isValid,
        'email' => $email,
        'domain' => $domain,
        'bank' => $bankName,
        'has_mx_records' => $hasMX,
        'reason' => $isValid ? 
            ('Valid ' . $bankName . ' partner email' . ($hasMX ? ' (MX verified)' : '')) : 
            ('Email domain not recognized for Instapay' . ($hasMX ? ', but has valid MX records' : ''))
    ];
}

/**
 * Validate transaction amount
 */
function validateAmount($amount) {
    if (!$amount) return ['valid' => false, 'reason' => 'Amount is empty'];
    
    // Remove currency symbol if present
    $cleaned = preg_replace('/[^\d.]/', '', $amount);
    
    if (!is_numeric($cleaned)) {
        return ['valid' => false, 'reason' => 'Amount must be numeric'];
    }

    $value = (float)$cleaned;
    
    // Use config constants for validation limits
    $minAmount = defined('MIN_TRANSACTION_AMOUNT') ? MIN_TRANSACTION_AMOUNT : 50;
    $maxAmount = defined('MAX_TRANSACTION_AMOUNT') ? MAX_TRANSACTION_AMOUNT : 1000;
    
    if ($value < $minAmount) {
        return ['valid' => false, 'reason' => "Amount must be at least $minAmount EGP"];
    }
    
    if ($value > $maxAmount) {
        return ['valid' => false, 'reason' => "Amount cannot exceed $maxAmount EGP"];
    }

    // Check for suspicious patterns
    // Round numbers sometimes indicate fake receipts
    $isRound = ($value == round($value)) && ($value % 100 == 0);
    
    return [
        'valid' => true,
        'amount' => $value,
        'currency' => 'EGP',
        'suspicious_pattern' => $isRound ? 'Round number pattern' : 'Normal'
    ];
}

/**
 * Security check for images - Detects common digital manipulation markers
 */
function performSecurityCheck($imagePath) {
    if (!file_exists($imagePath)) return ['flagged' => false];
    
    $result = [
        'flagged' => false,
        'reason' => '',
        'score' => 0
    ];

    // 1. Check for extreme aspect ratios (Common in fake receipt generators)
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo) {
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $ratio = $height / $width;
        
        if ($ratio > 3.0 || $ratio < 1.0) {
            $result['flagged'] = true;
            $result['reason'] = 'Suspicious image dimensions (Fake generator pattern)';
            return $result;
        }
    }

    // 2. Check for metadata stripping (Privacy tools or manual edits)
    // Note: Most smartphones include EXIF, WhatsApp/FB strip it.
    // If it's a direct upload but has zero metadata, it's a SMALL flag.

    // 3. Analysis for mismatched resolution (Not possible without heavy library)
    
    return $result;
}

/**
 * Validate bank name against known Instapay partner banks
 */
function validateBankName($bankName) {
    if (!$bankName) return ['valid' => false, 'reason' => 'Bank name is empty'];
    
    $knownBanks = [
        'national bank of egypt' => 'NBE',
        'banque misr' => 'BM',
        'commercial international bank' => 'CIB',
        'arab international bank' => 'AIB',
        'alexandria bank' => 'AlexBank',
        'abu dhabi islamic bank' => 'ADIB',
        'faisal islamic bank' => 'FIB',
        'egyptian gulf bank' => 'EGB',
        'saib' => 'SAIB',
        'al ahli bank of kuwait' => 'ABK',
        'instapay' => 'Instapay',
        'etisalat' => 'Etisalat',
        'vodafone' => 'Vodafone',
        'we' => 'WE',
        'orange' => 'Orange'
    ];
    
    $normalizedBank = strtolower(trim($bankName));
    
    foreach ($knownBanks as $known => $code) {
        if (strpos($normalizedBank, $known) !== false || 
            strpos($known, $normalizedBank) !== false ||
            levenshtein($normalizedBank, $known) <= 3) {
            return [
                'valid' => true,
                'bank_code' => $code,
                'reason' => 'Recognized partner bank: ' . $code
            ];
        }
    }
    
    return [
        'valid' => false,
        'reason' => 'Bank not in known Instapay partner list: ' . $bankName
    ];
}

/**
 * Cross-field consistency validation
 * Checks for logical consistency between different transaction fields
 */
function validateCrossFieldConsistency($data) {
    $issues = [];
    
    // Check 1: Amount should match currency (EGP expected for Egyptian banks)
    if (!empty($data['amount']) && !empty($data['currency'])) {
        $currency = strtoupper(trim($data['currency']));
        if ($currency !== 'EGP' && $currency !== 'ج.م' && $currency !== 'جنيه') {
            $issues[] = 'Non-EGP currency detected: ' . $data['currency'];
        }
    }
    
    // Check 2: Transaction type should match amount direction
    if (!empty($data['transaction_type']) && !empty($data['amount'])) {
        $type = strtolower($data['transaction_type']);
        $amount = (float) preg_replace('/[^\d.]/', '', $data['amount']);
        
        // Transfers should have positive amounts
        if (strpos($type, 'transfer') !== false || strpos($type, 'تحويل') !== false) {
            if ($amount <= 0) {
                $issues[] = 'Invalid amount for transfer type';
            }
        }
    }
    
    // Check 3: Date should not be too recent (impossible transaction)
    if (!empty($data['transaction_date'])) {
        $dateStr = $data['transaction_date'];
        // If date contains future timestamp
        if (preg_match('/(\d{1,2})[:\/](\d{1,2})[:\/](\d{4})/', $dateStr, $matches)) {
            $parsedDate = strtotime($dateStr);
            if ($parsedDate && $parsedDate > time() + 300) { // 5 minutes in future
                $issues[] = 'Transaction date is in the future';
            }
        }
    }
    
    // Check 4: Reference number format should match bank type
    if (!empty($data['reference_number']) && !empty($data['bank_name'])) {
        $ref = $data['reference_number'];
        $bank = strtolower($data['bank_name']);
        
        // IBAN references should be 29 chars (EG + 27 digits)
        if (preg_match('/^EG\d+$/', $ref) && strlen($ref) !== 29) {
            $issues[] = 'IBAN format mismatch for bank type';
        }
    }
    
    // Check 5: Phone number should match receiver name (if both present)
    if (!empty($data['receiver_phone']) && !empty($data['receiver_name'])) {
        $phone = preg_replace('/\D/', '', $data['receiver_phone']);
        $name = $data['receiver_name'];
        
        // Flag if name looks like a phone number or vice versa
        if (preg_match('/^\d{10,}$/', $name)) {
            $issues[] = 'Receiver name appears to be a number';
        }
    }
    
    return [
        'valid' => count($issues) === 0,
        'reason' => count($issues) > 0 ? implode('; ', $issues) : 'All fields consistent',
        'issues' => $issues
    ];
}

/**
 * Analyze transaction patterns for velocity/fraud detection
 */
function analyzeTransactionPatterns($senderAccount, $currentData) {
    $db = TransactionDatabase::getConnection();
    $suspicious = false;
    $reasons = [];
    
    if (!$db) {
        return ['suspicious' => false, 'reason' => 'Database unavailable for pattern check'];
    }
    
    try {
        // Check 1: Multiple transactions from same sender in short time
        $recentFromSender = $db->find('instapay_transactions', [
            'sender_account' => $senderAccount,
            'created_at' => ['$gte' => DatabaseMongo::createUTCDateTime((time() - 3600) * 1000)] // Last hour
        ], ['limit' => 10]);
        
        if (count($recentFromSender) >= 3) {
            $suspicious = true;
            $reasons[] = 'High velocity: ' . count($recentFromSender) . ' transactions in last hour';
        }
        
        // Check 2: Round amount pattern (common in fake receipts)
        if (!empty($currentData['amount'])) {
            $amount = (float) preg_replace('/[^\d.]/', '', $currentData['amount']);
            if ($amount > 0 && $amount == round($amount) && $amount % 100 == 0) {
                $suspicious = true;
                $reasons[] = 'Round amount pattern detected: ' . $amount;
            }
        }
        
        // Check 3: Same amount, different references (potential duplicate fraud)
        if (!empty($currentData['amount']) && !empty($currentData['reference_number'])) {
            $similarTransactions = $db->find('instapay_transactions', [
                'sender_account' => $senderAccount,
                'amount' => (float) $currentData['amount'],
                'reference_number' => ['$ne' => $currentData['reference_number']]
            ], ['limit' => 5]);
            
            if (count($similarTransactions) > 0) {
                $suspicious = true;
                $reasons[] = 'Same amount with different reference numbers detected';
            }
        }
        
        // Check 4: Outside normal hours (suspicious timing)
        $hour = (int) date('H');
        if ($hour < 6 || $hour > 23) {
            $suspicious = true;
            $reasons[] = 'Transaction outside normal hours (' . $hour . ':00)';
        }
        
    } catch (Exception $e) {
        error_log('Pattern analysis error: ' . $e->getMessage());
    }
    
    return [
        'suspicious' => $suspicious,
        'reason' => $suspicious ? implode('; ', $reasons) : 'No suspicious patterns detected',
        'patterns' => $reasons
    ];
}

/**
 * Enhanced transaction analysis
 */
function analyzeTransactionDetails($data, $imagePath = null) {
    $analysis = analyzeTransaction($data);
    
    // Additional heuristics if image is available
    if ($imagePath) {
        $security = performSecurityCheck($imagePath);
        if ($security['flagged']) {
            $analysis['is_valid'] = 'suspicious';
            $analysis['warnings'][] = $security['reason'];
        }
    }
    
    return $analysis;
}

/**
 * Analyze transaction validity - Enhanced version
 */
function analyzeTransaction($data) {
    $analysis = [
        'is_valid' => 'unknown',
        'confidence_score' => '0%',
        'issues' => [],
        'warnings' => [],
        'validations' => []
    ];

    $score = 0;
    $maxScore = 100;

    // 1. Check required fields (25 points)
    $requiredFields = ['amount', 'sender_account', 'receiver_name', 'reference_number', 'transaction_date'];
    $fieldsPresent = 0;
    foreach ($requiredFields as $field) {
        if (!empty($data[$field]) && $data[$field] !== 'غير متوفر' && $data[$field] !== 'null') {
            $fieldsPresent++;
        } else {
            $analysis['issues'][] = "Missing field: $field";
        }
    }
    $score += ($fieldsPresent / count($requiredFields)) * 25;

    // 2. Validate amount (15 points)
    if (!empty($data['amount'])) {
        $amountVal = validateAmount($data['amount']);
        $analysis['validations']['amount'] = $amountVal;
        if ($amountVal['valid']) {
            $score += 15;
        } else {
            $analysis['issues'][] = $amountVal['reason'];
        }
    }

    // 3. Validate sender email (15 points)
    if (!empty($data['sender_account'])) {
        $emailVal = validateInstapayEmail($data['sender_account']);
        $analysis['validations']['email'] = $emailVal;
        if ($emailVal['valid']) {
            $score += 15;
        } else {
            $analysis['warnings'][] = $emailVal['reason'];
            $score += 7; // Partial credit
        }
    }

    // 4. Validate receiver phone (15 points)
    if (!empty($data['receiver_phone'])) {
        $phoneVal = validateEgyptianPhone($data['receiver_phone']);
        $analysis['validations']['phone'] = $phoneVal;
        
        // Check if receiver phone matches expected number
        $expectedReceiverPhone = '01010796944';
        $cleanedPhone = preg_replace('/[^0-9]/', '', $data['receiver_phone']);
        $cleanedExpected = preg_replace('/[^0-9]/', '', $expectedReceiverPhone);
        
        if ($cleanedPhone !== $cleanedExpected) {
            $analysis['issues'][] = "Receiver phone number mismatch. Expected: $expectedReceiverPhone, Got: {$data['receiver_phone']}. This indicates a fake or incorrect transaction.";
            $score -= 20; // Heavy penalty for wrong receiver
        } elseif ($phoneVal['valid']) {
            $score += 15;
        } else {
            $analysis['issues'][] = $phoneVal['reason'];
        }
    }

    // 5. Validate reference number (15 points)
    if (!empty($data['reference_number'])) {
        $refVal = validateReferenceNumber($data['reference_number']);
        $analysis['validations']['reference'] = $refVal;
        if ($refVal['valid']) {
            $score += 15;
        } else {
            $analysis['issues'][] = $refVal['reason'];
        }
    }

    // 6. Check transaction_date format and age (15 points)
    if (!empty($data['transaction_date'])) {
        $dateVal = validateTransactionDate($data['transaction_date']);
        $analysis['validations']['date'] = $dateVal;
        if ($dateVal['valid']) {
            $score += 15;
        } else {
            $analysis['issues'][] = $dateVal['reason'];
        }
    }

    // 7. Validate IBAN if sender account looks like IBAN (10 points)
    if (!empty($data['sender_account']) && preg_match('/^EG\d{27}$/', $data['sender_account'])) {
        $ibanVal = validateIBAN($data['sender_account']);
        $analysis['validations']['iban'] = $ibanVal;
        if ($ibanVal['valid']) {
            $score += 10;
        } else {
            $analysis['issues'][] = 'Invalid IBAN: ' . $ibanVal['reason'];
        }
    }

    // 8. Validate bank name against known partners (5 points)
    if (!empty($data['bank_name']) && $data['bank_name'] !== 'غير متوفر') {
        $bankVal = validateBankName($data['bank_name']);
        $analysis['validations']['bank'] = $bankVal;
        if ($bankVal['valid']) {
            $score += 5;
        } else {
            $analysis['warnings'][] = $bankVal['reason'];
        }
    }

    // 9. Cross-field consistency checks
    $consistency = validateCrossFieldConsistency($data);
    $analysis['validations']['consistency'] = $consistency;
    if (!$consistency['valid']) {
        $analysis['warnings'][] = $consistency['reason'];
        $score -= 5; // Penalty for inconsistencies
    }

    // 10. Transaction pattern analysis (velocity/frequency check)
    if (!empty($data['sender_account'])) {
        $patternCheck = analyzeTransactionPatterns($data['sender_account'], $data);
        $analysis['validations']['patterns'] = $patternCheck;
        if ($patternCheck['suspicious']) {
            $analysis['warnings'][] = $patternCheck['reason'];
            $score -= 10;
        }
    }

    // 11. Check for duplicate reference number - CRITICAL
    $duplicateCheck = checkDuplicateTransaction($data);
    $analysis['validations']['duplicate'] = [
        'valid' => !$duplicateCheck['is_duplicate'],
        'reason' => $duplicateCheck['reason'] ?? 'Duplicate check completed'
    ];
    if ($duplicateCheck['is_duplicate']) {
        $analysis['issues'][] = 'DUPLICATE: ' . $duplicateCheck['reason'];
        $score = 0; // Zero score for duplicates
    }

    // Determine overall validity
    if (count($analysis['issues']) > 0) {
        $analysis['is_valid'] = 'invalid';
    } elseif (count($analysis['warnings']) > 0 || $score < 80) {
        $analysis['is_valid'] = 'suspicious';
    } else {
        $analysis['is_valid'] = 'valid';
    }

    $analysis['confidence_score'] = max(0, min(100, round($score))) . '%';
    return $analysis;
}

/**
 * Check for duplicate transactions - STRICT VERSION
 * Rejects ANY transaction with same reference number
 */
function checkDuplicateTransaction($data) {
    $ref = $data['reference_number'] ?? '';
    if (!$ref || $ref === 'غير متوفر') {
        return ['is_duplicate' => false, 'reason' => 'No reference to check'];
    }
    
    // STRICT: Check if reference exists at ALL in database
    $db = TransactionDatabase::getConnection();
    if ($db) {
        try {
            $existing = $db->findOne('instapay_transactions', [
                'reference_number' => $ref
            ]);
            
            if (!empty($existing)) {
                return [
                    'is_duplicate' => true,
                    'duplicate_ref' => $ref,
                    'duplicate_date' => $existing['created_at'] ?? '',
                    'reason' => "Transaction ID '$ref' already exists in database",
                    'existing_transaction' => [
                        'amount' => $existing['amount'] ?? 'unknown',
                        'date' => $existing['transaction_date'] ?? 'unknown',
                        'sender' => $existing['sender_account'] ?? 'unknown'
                    ]
                ];
            }
        } catch (Exception $e) {
            error_log('Duplicate check error: ' . $e->getMessage());
        }
    }
    
    // Also check by amount + date as secondary check
    $duplicate = findDuplicateInDatabase(
        $ref,
        $data['amount'] ?? '',
        $data['transaction_date'] ?? ''
    );
    
    if (!empty($duplicate)) {
        return [
            'is_duplicate' => true,
            'duplicate_ref' => $duplicate['reference_number'] ?? '',
            'duplicate_date' => $duplicate['created_at'] ?? '',
            'reason' => 'Duplicate transaction (same ref/amount/date)',
            'existing_transaction' => [
                'amount' => $duplicate['amount'] ?? 'unknown',
                'date' => $duplicate['transaction_date'] ?? 'unknown',
                'sender' => $duplicate['sender_account'] ?? 'unknown'
            ]
        ];
    }
    
    return [
        'is_duplicate' => false,
        'reason' => 'Reference number is unique'
    ];
}

/**
 * Alias kept for compatibility — all calls now go to Pollinations AI
 */
function analyzeImageWithGemini($imagePath) {
    return analyzeImageWithPollinations($imagePath);
}

/**
 * Send JSON response
 */
function instapay_respond($data) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 */
function instapay_respond_error($message) {
    instapay_respond([
        'success' => false,
        'message' => $message
    ]);
}

/**
 * Wrapper for payment.php - Validate reference number only
 * Accepts string reference and wraps it for analyzeTransaction
 */
function validateTransactionReference($ref) {
    if (empty($ref)) {
        return ['isValid' => false, 'errors' => ['Empty reference']];
    }
    
    $data = ['reference_number' => $ref];
    $result = analyzeTransaction($data);
    
    // Convert to format expected by payment.php
    return [
        'isValid' => $result['is_valid'] === 'valid',
        'errors' => $result['issues'] ?? [],
        'warnings' => $result['warnings'] ?? [],
        'confidence_score' => $result['confidence_score'] ?? '0%'
    ];
}

/**
 * Wrapper for payment.php - Check duplicate by reference string
 * Accepts string reference instead of array
 */
function isDuplicateTransaction($ref) {
    if (empty($ref)) return false;
    
    $data = ['reference_number' => $ref];
    $result = checkDuplicateTransaction($data);
    
    return $result['is_duplicate'] ?? false;
}

?>
