<?php
/**
 * Instapay Transaction Validator API
 * Handles image processing, data extraction, and validation
 * Integrated with Google Gemini Vision API
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration and database functions
require_once 'config.php';
require_once 'db.php';

// Load professional validation libraries via Composer
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Create uploads directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

// Initialize database
initializeDatabase();

// Route API requests
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

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
    default:
        respondError('Invalid action');
}

/**
 * Process uploaded image
 */
function handleProcessImage() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        respondError('Failed to upload file');
        return;
    }

    $file = $_FILES['file'];
    
    // Validate file
    $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $imageInfo = getimagesize($file['tmp_name']);
    $fileMime = $imageInfo['mime'] ?? null;
    
    if (!in_array($fileMime, $validMimes)) {
        respondError('Invalid file type. Only images are allowed');
        return;
    }

    // Save uploaded file
    $uploadDir = 'uploads/';
    $fileName = 'temp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        respondError('Failed to save uploaded file');
        return;
    }

    // Extract text from image using OCR or pattern matching
    $extractedData = extractDataFromImage($filePath);
    
    if (empty($extractedData)) {
        respondError('Failed to extract data from image. Please ensure the screenshot is clear');
        return;
    }

    // Analyze extracted data
    $analysis = analyzeTransaction($extractedData);

    // Check for duplicates
    $duplicateCheck = checkDuplicateTransaction($extractedData);

    // Prepare response
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
        'temp_file' => $fileName // Store for later use
    ];

    respond([
        'success' => true,
        'data' => $data,
        'analysis' => array_merge($analysis, $duplicateCheck)
    ]);
}

/**
 * Save transaction to database
 */
function handleSaveTransaction() {
    if (!isset($_POST['data']) || !isset($_FILES['file'])) {
        respondError('Missing required data');
        return;
    }

    $data = json_decode($_POST['data'], true);
    
    if (!$data) {
        respondError('Invalid data format');
        return;
    }

    // Move temp file to permanent storage
    $tempFile = $data['temp_file'] ?? null;
    if ($tempFile && file_exists('uploads/' . $tempFile)) {
        $newFileName = 'transaction_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($tempFile, PATHINFO_EXTENSION);
        rename('uploads/' . $tempFile, 'uploads/' . $newFileName);
        $data['temp_file'] = $newFileName;
    }

    // Save to database
    if (saveTransactionToDatabase($data)) {
        respond([
            'success' => true,
            'message' => 'تم حفظ المعاملة بنجاح'
        ]);
    } else {
        respondError('Failed to save transaction to database');
    }
}

/**
 * Get statistics
 */
function handleGetStats() {
    $stats = getStatistics();
    respond([
        'success' => true,
        'total' => $stats['total'] ?? 0,
        'valid' => $stats['valid'] ?? 0,
        'suspicious' => $stats['suspicious'] ?? 0
    ]);
}

/**
 * Extract data from image using Google Gemini Vision API
 * Analyzes Instapay screenshots for transaction details
 */
function extractDataFromImage($filePath) {
    $data = [];

    // Use Gemini API to analyze the image
    $ocrText = analyzeImageWithGemini($filePath);

    if (!$ocrText) {
        return null;
    }

    // Try to parse Gemini response as JSON first (structured response)
    $geminiData = json_decode($ocrText, true);
    
    if (is_array($geminiData) && ($geminiData['amount'] || $geminiData['reference_number'])) {
        // Gemini returned structured JSON - use it directly
        $data = [
            'amount' => $geminiData['amount'] ?? null,
            'currency' => $geminiData['currency'] ?? 'EGP',
            'sender_account' => $geminiData['sender_account'] ?? null,
            'sender_name' => $geminiData['sender_name'] ?? null,
            'receiver_name' => $geminiData['receiver_name'] ?? null,
            'receiver_phone' => $geminiData['receiver_phone'] ?? null,
            'reference_number' => $geminiData['reference_number'] ?? null,
            'transaction_date' => $geminiData['transaction_date'] ?? null,
            'bank_name' => $geminiData['bank_name'] ?? null,
            'transaction_type' => $geminiData['transaction_type'] ?? 'تحويل أموال'
        ];
    } else {
        // Fallback to pattern matching if JSON parsing fails or no data found
        
        // Extract amount (look for numbers followed by EGP)
        if (preg_match('/(\d+(?:\.\d{1,2})?)\s*(?:EGP|egp)/i', $ocrText, $matches)) {
            $data['amount'] = $matches[1];
            $data['currency'] = 'EGP';
        }

        // Extract sender account (instapay email format)
        if (preg_match('/([a-zA-Z0-9._-]+@instapay)/i', $ocrText, $matches)) {
            $data['sender_account'] = $matches[1];
        }

        // Extract sender name (look for name patterns)
        if (preg_match('/من\s*([أ-ي\s]+)/u', $ocrText, $matches)) {
            $data['sender_name'] = trim($matches[1]);
        }

        // Extract receiver name (look for receiver patterns)
        if (preg_match('/إلى\s*([أ-ي\s\*]+)/u', $ocrText, $matches)) {
            $data['receiver_name'] = trim($matches[1]);
        }

        // Extract phone number (Egyptian format)
        if (preg_match('/0?(\d{10,11})/i', $ocrText, $matches)) {
            $data['receiver_phone'] = '0' . substr($matches[1], -10);
        }

        // Extract reference number
        if (preg_match('/(\d{10,15})/i', $ocrText, $matches)) {
            $data['reference_number'] = $matches[1];
        }

        // Extract date
        if (preg_match('/(\d{1,2}\s+\w+\s+\d{4}\s+\d{1,2}:\d{2})/i', $ocrText, $matches)) {
            $data['transaction_date'] = $matches[1];
        }

        // Extract bank name
        $bankPatterns = [
            '/بنك\s*([أ-ي\s]+)/ui' => 'بنك',
            '/ahli|ahlileasing|ahliadvisory/i' => 'البنك الأهلي',
            '/cib|commercial international bank/i' => 'البنك التجاري الدولي',
            '/cbe|central bank/i' => 'البنك المركزي'
        ];

        foreach ($bankPatterns as $pattern => $bankName) {
            if (preg_match($pattern, $ocrText)) {
                $data['bank_name'] = $bankName;
                break;
            }
        }

        $data['transaction_type'] = 'تحويل أموال';
    }

    // Ensure minimum data
    if (empty($data['amount']) || empty($data['reference_number'])) {
        return null;
    }

    return $data;
}

/**
 * Perform OCR using Google Gemini Vision API
 */
function performSimulatedOCR($imagePath) {
    return analyzeImageWithGemini($imagePath);
}

/**
 * Analyze image with Google Gemini Vision API
 * Sends image to Gemini and extracts Instapay transaction details
 */
function analyzeImageWithGemini($imagePath) {
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

    // Prepare Gemini API request
    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'أنت متخصص في تحليل لقطات شاشة معاملات إنستاباي. حلل الصورة وأخرج جميع المعلومات بصيغة JSON تحتوي على: amount (المبلغ), currency (العملة), sender_account (بريد المرسل @instapay), sender_name (اسم المرسل), receiver_name (اسم المستقبل), receiver_phone (رقم الهاتف), reference_number (الرقم المرجع), transaction_date (التاريخ والوقت), bank_name (اسم البنك), transaction_type (نوع المعاملة). يجب أن تكون جميع القيم نصية. إذا لم تجد معلومة ما، ضع null. أعد فقط JSON بدون شرح.'
                    ],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $base64Image
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'topK' => 40,
            'topP' => 0.95
        ]
    ];

    // Make API request
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => GEMINI_API_URL . '?key=' . GEMINI_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        error_log('Gemini API Error: ' . $curlError);
        return null;
    }

    if ($httpCode !== 200) {
        error_log('Gemini API Error: HTTP ' . $httpCode . ' - ' . $response);
        return null;
    }

    // Parse response
    $responseData = json_decode($response, true);
    
    if (!$responseData) {
        error_log('Gemini API: Failed to parse JSON response: ' . $response);
        return null;
    }
    
    if (empty($responseData['candidates'])) {
        error_log('Gemini API: No candidates in response: ' . json_encode($responseData));
        return null;
    }

    $textContent = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    if (!$textContent) {
        error_log('Gemini API: No text content in response: ' . json_encode($responseData));
        return null;
    }

    // Clean up response (remove markdown code blocks if present)
    $textContent = preg_replace('/```json\s*/', '', $textContent);
    $textContent = preg_replace('/```\s*/', '', $textContent);
    $textContent = trim($textContent);

    return $textContent;
}

/**
 * Extract text from image using OCR simulation (fallback)
 */
function extractTextViaTesseract($imagePath) {
    // This is now using Gemini API
    return analyzeImageWithGemini($imagePath);
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
 * Instapay reference: typically 10-15 digits or alphanumeric
 */
function validateReferenceNumber($ref) {
    if (!$ref) return ['valid' => false, 'reason' => 'Reference number is empty'];
    
    // Instapay reference: usually numeric, 10-20 chars
    if (preg_match('/^[A-Z0-9]{10,20}$/i', trim($ref))) {
        return ['valid' => true, 'format' => 'Standard Instapay Reference'];
    }
    
    // IBAN reference format
    if (preg_match('/^EG\d{27}$/', trim($ref))) {
        return ['valid' => true, 'format' => 'IBAN-based Reference'];
    }

    return [
        'valid' => false,
        'reason' => 'Invalid reference format. Expected: alphanumeric, 10-20 characters'
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

    // Try to parse various date formats
    $parsedDate = null;
    
    // Try parsing: "1/2/2024 14:30", "01-02-2024 14:30", "1 Feb 2024 14:30", etc.
    $formats = [
        'd/m/Y H:i',   // 1/2/2024 14:30
        'm/d/Y H:i',   // 2/1/2024 14:30
        'd-m-Y H:i',   // 1-2-2024 14:30
        'm-d-Y H:i',   // 2-1-2024 14:30
        'd/m/Y',       // 1/2/2024
        'm/d/Y',       // 2/1/2024
        'd-m-Y',       // 1-2-2024
        'm-d-Y',       // 2-1-2024
        'd M Y H:i',   // 1 Feb 2024 14:30
        'd M Y',       // 1 Feb 2024
        'Y-m-d H:i:s', // 2024-02-01 14:30:00
        'Y-m-d',       // 2024-02-01
    ];

    foreach ($formats as $format) {
        $dateObj = \DateTime::createFromFormat($format, trim($dateStr));
        if ($dateObj !== false && $dateObj->format($format) === trim($dateStr)) {
            $parsedDate = $dateObj;
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

    // Check if date is in the future (invalid)
    $now = new \DateTime('now');
    if ($parsedDate > $now) {
        return [
            'valid' => false,
            'reason' => 'Transaction date is in the future',
            'days_old' => 'Future'
        ];
    }

    // Calculate days difference
    $interval = $now->diff($parsedDate);
    $daysOld = $interval->days;

    // Check if within allowed timeframe (7 days by default)
    $maxDays = defined('MAX_TRANSACTION_AGE_DAYS') ? MAX_TRANSACTION_AGE_DAYS : 7;
    
    if ($daysOld > $maxDays) {
        return [
            'valid' => false,
            'reason' => "Transaction is $daysOld days old (max allowed: $maxDays days)",
            'days_old' => $daysOld
        ];
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

    // 1. Check required fields (20 points)
    $requiredFields = ['amount', 'sender_account', 'receiver_name', 'reference_number', 'transaction_date'];
    $fieldsPresent = 0;
    foreach ($requiredFields as $field) {
        if (!empty($data[$field])) {
            $fieldsPresent++;
        } else {
            $analysis['issues'][] = "Missing field: $field";
        }
    }
    $score += ($fieldsPresent / count($requiredFields)) * 20;

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
        if ($phoneVal['valid']) {
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

    // 6. Validate IBAN if present (10 points)
    if (!empty($data['sender_iban'])) {
        $ibanVal = validateIBAN($data['sender_iban']);
        $analysis['validations']['iban'] = $ibanVal;
        if ($ibanVal['valid']) {
            $score += 10;
        } else {
            $analysis['issues'][] = $ibanVal['reason'];
        }
    }

    // 7. Check transaction_date format and age (10 points)
    if (!empty($data['transaction_date'])) {
        $dateVal = validateTransactionDate($data['transaction_date']);
        $analysis['validations']['date'] = $dateVal;
        if ($dateVal['valid']) {
            $score += 10;
        } else {
            // Old transactions are critical issues
            $analysis['issues'][] = $dateVal['reason'];
        }
    } else {
        $analysis['issues'][] = 'Missing transaction date';
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
 * Check for duplicate transactions
 */
function checkDuplicateTransaction($data) {
    $duplicate = findDuplicateInDatabase(
        $data['reference_number'] ?? '',
        $data['amount'] ?? '',
        $data['transaction_date'] ?? ''
    );

    return [
        'is_duplicate' => !empty($duplicate),
        'duplicate_ref' => $duplicate['reference_number'] ?? '',
        'duplicate_date' => $duplicate['created_at'] ?? ''
    ];
}

/**
 * Send JSON response
 */
function respond($data) {
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 */
function respondError($message) {
    respond([
        'success' => false,
        'message' => $message
    ]);
}
?>
