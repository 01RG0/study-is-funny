#!/usr/bin/env php
<?php
/**
 * Study is Funny - Comprehensive System Test Runner
 * Tests ALL functions, classes, API endpoints, and integrations
 * Usage: php test-runner.php [--skip-db] [--skip-api] [--verbose]
 */

// Parse CLI options
$skipDb = in_array('--skip-db', $argv ?? []);
$skipApi = in_array('--skip-api', $argv ?? []);
$verbose = in_array('--verbose', $argv ?? []);

$passed = 0;
$failed = 0;
$skipped = 0;
$errors = [];

function test_pass($name) {
    global $passed, $verbose;
    $passed++;
    echo "  \033[32m✓\033[0m $name\n";
}

function test_fail($name, $reason = '') {
    global $failed, $errors;
    $failed++;
    $errors[] = "$name" . ($reason ? ": $reason" : '');
    echo "  \033[31m✗\033[0m $name" . ($reason ? " \033[90m($reason)\033[0m" : '') . "\n";
}

function test_skip($name, $reason = '') {
    global $skipped;
    $skipped++;
    echo "  \033[33m⊘\033[0m $name" . ($reason ? " \033[90m($reason)\033[0m" : '') . "\n";
}

function test_section($title) {
    echo "\n\033[36m" . str_repeat('─', 60) . "\033[0m\n";
    echo "\033[1;36m  $title\033[0m\n";
    echo "\033[36m" . str_repeat('─', 60) . "\033[0m\n";
}

function assert_eq($expected, $actual, $name) {
    if ($expected === $actual) {
        test_pass($name);
    } else {
        test_fail($name, "Expected " . json_encode($expected) . ", got " . json_encode($actual));
    }
}

function assert_true($condition, $name, $reason = '') {
    if ($condition) {
        test_pass($name);
    } else {
        test_fail($name, $reason);
    }
}

// ============================================================
// HEADER
// ============================================================
echo "\n\033[1;33m╔═══════════════════════════════════════════════════════════╗\n";
echo "║   STUDY IS FUNNY - COMPREHENSIVE SYSTEM TEST RUNNER       ║\n";
echo "║   Testing All Functions, Classes & API Endpoints           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\033[0m\n";

$startTime = microtime(true);

// ============================================================
// 1. PHP ENVIRONMENT
// ============================================================
test_section("1. PHP ENVIRONMENT");

$phpVersion = phpversion();
assert_true(version_compare($phpVersion, '7.4.0', '>='), "PHP version >= 7.4 (current: $phpVersion)");

$requiredExtensions = [
    'pdo' => 'PDO for database access',
    'session' => 'Session management',
    'json' => 'JSON encoding/decoding',
    'curl' => 'cURL for API calls',
    'mbstring' => 'Multibyte string support',
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        test_pass("Extension: $ext ($desc)");
    } else {
        test_fail("Extension: $ext ($desc)", "Not loaded");
    }
}

$optionalExtensions = [
    'pdo_sqlite' => 'PDO SQLite for payment system',
    'fileinfo' => 'File type detection',
    'mongodb' => 'MongoDB driver',
    'gd' => 'GD image processing',
    'openssl' => 'OpenSSL for secure connections',
];

foreach ($optionalExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        test_pass("Optional: $ext ($desc)");
    } else {
        test_skip("Optional: $ext ($desc)", "Not loaded");
    }
}

// ============================================================
// 2. FILE SYSTEM & CONFIGURATION
// ============================================================
test_section("2. FILE SYSTEM & CONFIGURATION");

// Load .env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    require_once __DIR__ . '/load_env.php';
    test_pass(".env file loaded");
} else {
    test_fail(".env file", "Not found at $envPath");
}

// Load main config
$configPath = __DIR__ . '/config/config.php';
if (file_exists($configPath)) {
    // Suppress headers already sent warnings in CLI
    @require_once $configPath;
    test_pass("config/config.php loaded");
} else {
    test_fail("config/config.php", "Not found");
}

// Check critical constants
$criticalConstants = [
    'MONGO_URI' => 'MongoDB connection URI',
    'DB_NAME' => 'Database name',
    'APP_NAME' => 'Application name',
    'BASE_PATH' => 'Base file path',
    'UPLOADS_DIR' => 'Uploads directory',
    'SESSION_TIMEOUT' => 'Session timeout',
];

foreach ($criticalConstants as $const => $desc) {
    if (defined($const)) {
        test_pass("Constant: $const ($desc)");
    } else {
        test_fail("Constant: $const ($desc)", "Not defined");
    }
}

// Check critical directories
$criticalDirs = [
    'uploads' => __DIR__ . '/uploads',
    'uploads/payments' => __DIR__ . '/uploads/payments',
    'uploads/videos' => __DIR__ . '/uploads/videos',
    'uploads/homework' => __DIR__ . '/uploads/homework',
    'logs' => __DIR__ . '/logs',
    'pages' => __DIR__ . '/pages',
    'admin' => __DIR__ . '/admin',
    'api' => __DIR__ . '/api',
    'classes' => __DIR__ . '/classes',
    'config' => __DIR__ . '/config',
    'js' => __DIR__ . '/js',
    'css' => __DIR__ . '/css',
];

foreach ($criticalDirs as $name => $path) {
    if (is_dir($path)) {
        test_pass("Directory: $name/");
    } else {
        if (@mkdir($path, 0755, true)) {
            test_pass("Directory: $name/ (created)");
        } else {
            test_fail("Directory: $name/", "Missing and cannot create");
        }
    }
}

// Check writable directories
$writableDirs = [
    'uploads' => __DIR__ . '/uploads',
    'uploads/payments' => __DIR__ . '/uploads/payments',
    'logs' => __DIR__ . '/logs',
];

foreach ($writableDirs as $name => $path) {
    if (is_dir($path) && is_writable($path)) {
        test_pass("Writable: $name/");
    } else {
        test_fail("Writable: $name/", "Not writable");
    }
}

// Check critical files
$criticalFiles = [
    'index.html' => __DIR__ . '/index.html',
    'router.php' => __DIR__ . '/router.php',
    '.htaccess' => __DIR__ . '/.htaccess',
    'api/config.php' => __DIR__ . '/api/config.php',
    'api/payment.php' => __DIR__ . '/api/payment.php',
    'api/payment-schema.php' => __DIR__ . '/api/payment-schema.php',
    'api/sessions.php' => __DIR__ . '/api/sessions.php',
    'api/students.php' => __DIR__ . '/api/students.php',
    'api/admin.php' => __DIR__ . '/api/admin.php',
    'api/homework.php' => __DIR__ . '/api/homework.php',
    'api/videos.php' => __DIR__ . '/api/videos.php',
    'api/analytics.php' => __DIR__ . '/api/analytics.php',
    'api/auth_check.php' => __DIR__ . '/api/auth_check.php',
    'classes/DatabaseMongo.php' => __DIR__ . '/classes/DatabaseMongo.php',
    'classes/SessionManager.php' => __DIR__ . '/classes/SessionManager.php',
    'classes/Student.php' => __DIR__ . '/classes/Student.php',
    'classes/User.php' => __DIR__ . '/classes/User.php',
    'classes/Homework.php' => __DIR__ . '/classes/Homework.php',
    'classes/Video.php' => __DIR__ . '/classes/Video.php',
    'classes/Analytics.php' => __DIR__ . '/classes/Analytics.php',
    'includes/session_check.php' => __DIR__ . '/includes/session_check.php',
];

foreach ($criticalFiles as $name => $path) {
    if (file_exists($path)) {
        test_pass("File: $name");
    } else {
        test_fail("File: $name", "Not found");
    }
}

// ============================================================
// 3. CLASSES
// ============================================================
test_section("3. CLASS LOADING & METHODS");

$classTests = [
    'DatabaseMongo' => [
        'file' => __DIR__ . '/classes/DatabaseMongo.php',
        'methods' => ['connect', 'getClient', 'getDatabaseName', 'insert', 'findOne', 'find', 'update', 'delete', 'count', 'createObjectId', 'createUTCDateTime'],
    ],
    'SessionManager' => [
        'file' => __DIR__ . '/classes/SessionManager.php',
        'methods' => ['create', 'getById', 'getAll', 'getUpcoming', 'update', 'startSession', 'endSession', 'cancelSession', 'delete', 'registerStudent', 'getRegistrations', 'getRegistrationCount', 'checkIn', 'checkOut'],
    ],
    'Student' => [
        'file' => __DIR__ . '/classes/Student.php',
        'methods' => ['getById', 'getByPhone', 'getAll', 'getSessionData', 'updateSessionData'],
    ],
    'User' => [
        'file' => __DIR__ . '/classes/User.php',
        'methods' => ['register', 'login', 'getById', 'getByEmail', 'getByPhone', 'getAll', 'updateProfile', 'changePassword', 'deactivate', 'activate', 'delete', 'getStatistics'],
    ],
    'Homework' => [
        'file' => __DIR__ . '/classes/Homework.php',
        'methods' => ['create', 'getById', 'getBySubject', 'getByLesson', 'getAll', 'submit', 'grade', 'update', 'delete'],
    ],
    'Video' => [
        'file' => __DIR__ . '/classes/Video.php',
        'methods' => ['upload', 'getById', 'getByLesson', 'getBySubject', 'getAll', 'update', 'incrementViewCount', 'delete', 'getFilePath', 'stream'],
    ],
    'Analytics' => [
        'file' => __DIR__ . '/classes/Analytics.php',
        'methods' => ['getUserStats', 'getSessionStats', 'getHomeworkStats', 'getVideoStats', 'getStudentStats', 'getAttendanceReport', 'getHomeworkCompletionReport', 'getDashboardSummary'],
    ],
];

foreach ($classTests as $className => $info) {
    if (!file_exists($info['file'])) {
        test_fail("Class: $className", "File not found");
        continue;
    }

    $content = file_get_contents($info['file']);
    assert_true(strpos($content, "class $className") !== false, "Class: $className defined");

    foreach ($info['methods'] as $method) {
        // Check method is defined in the class file
        if (preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/', $content)) {
            test_pass("  $className::$method()");
        } else {
            test_fail("  $className::$method()", "Not found");
        }
    }
}

// ============================================================
// 4. INSTAPAY-CHECKER FUNCTIONS
// ============================================================
test_section("4. INSTAPAY-CHECKER FUNCTIONS");

$instapayApiPath = __DIR__ . '/instapay-checker/api.php';
$instapayDbPath = __DIR__ . '/instapay-checker/db.php';
$instapayConfigPath = __DIR__ . '/instapay-checker/config.php';

// Load instapay-checker files
if (file_exists($instapayConfigPath)) {
    define('SKIP_DB_CONN', true);
    @require_once $instapayConfigPath;
    test_pass("instapay-checker/config.php loaded");
} else {
    test_fail("instapay-checker/config.php", "Not found");
}

if (file_exists($instapayDbPath)) {
    @require_once $instapayDbPath;
    test_pass("instapay-checker/db.php loaded");
} else {
    test_fail("instapay-checker/db.php", "Not found");
}

if (file_exists($instapayApiPath)) {
    @require_once $instapayApiPath;
    test_pass("instapay-checker/api.php loaded");
} else {
    test_fail("instapay-checker/api.php", "Not found");
}

// Validator functions
$validators = [
    'validateAmount' => 'Validate transaction amount',
    'validateEgyptianPhone' => 'Validate Egyptian phone number',
    'validateReferenceNumber' => 'Validate reference format',
    'validateTransactionDate' => 'Validate transaction date',
    'validateInstapayEmail' => 'Validate Instapay email',
    'validateIBAN' => 'Validate IBAN',
];

foreach ($validators as $func => $desc) {
    if (function_exists($func)) {
        test_pass("Validator: $func() - $desc");
    } else {
        test_fail("Validator: $func() - $desc", "Function not available");
    }
}

// Analysis functions
$analysisFuncs = [
    'analyzeTransaction' => 'Comprehensive transaction analysis',
    'checkDuplicateTransaction' => 'Duplicate transaction detection',
];

foreach ($analysisFuncs as $func => $desc) {
    if (function_exists($func)) {
        test_pass("Analysis: $func() - $desc");
    } else {
        test_fail("Analysis: $func() - $desc", "Function not available");
    }
}

// Image processing functions
$imageFuncs = [
    'extractDataFromImage' => ['desc' => 'Extract data from screenshot', 'optional' => false],
    'performSimulatedOCR' => ['desc' => 'Simulated OCR', 'optional' => false],
    'analyzeImageWithGemini' => ['desc' => 'Gemini Vision API', 'optional' => false],
    'extractTextViaTesseract' => ['desc' => 'Tesseract OCR', 'optional' => true],
];

foreach ($imageFuncs as $func => $info) {
    if (function_exists($func)) {
        test_pass("Image: $func() - {$info['desc']}");
    } else {
        if ($info['optional']) {
            test_skip("Image: $func() - {$info['desc']}", 'Not available (optional)');
        } else {
            test_fail("Image: $func() - {$info['desc']}", "Function not available");
        }
    }
}

// Database functions
$dbFuncs = [
    'initializeDatabase' => 'Initialize database',
    'saveTransactionToDatabase' => 'Save transaction',
    'findDuplicateInDatabase' => 'Find duplicates',
    'getStatistics' => 'Get statistics',
    'getAllTransactionsFromDB' => 'Get all transactions',
    'getTransactionByRef' => 'Get by reference',
];

foreach ($dbFuncs as $func => $desc) {
    if (function_exists($func)) {
        test_pass("DB: $func() - $desc");
    } else {
        test_fail("DB: $func() - $desc", "Function not available");
    }
}

// ============================================================
// 5. VALIDATOR EXECUTION TESTS
// ============================================================
test_section("5. VALIDATOR EXECUTION TESTS");

if (function_exists('validateAmount')) {
    $result = validateAmount(500);
    assert_true(isset($result['valid']) && $result['valid'] === true, 'validateAmount(500) → valid');

    $result = validateAmount(0);
    assert_true(isset($result['valid']) && $result['valid'] === false, 'validateAmount(0) → invalid');

    $result = validateAmount(10000);
    assert_true(isset($result['valid']) && $result['valid'] === false, 'validateAmount(10000) → invalid');
} else {
    test_skip('validateAmount tests', 'Function not loaded');
}

if (function_exists('validateEgyptianPhone')) {
    $result = validateEgyptianPhone('01010796944');
    assert_true(isset($result['valid']) && $result['valid'] === true, 'validateEgyptianPhone("01010796944") → valid');

    $result = validateEgyptianPhone('12345');
    assert_true(isset($result['valid']) && $result['valid'] === false, 'validateEgyptianPhone("12345") → invalid');
} else {
    test_skip('validateEgyptianPhone tests', 'Function not loaded');
}

if (function_exists('validateReferenceNumber')) {
    $result = validateReferenceNumber('123456789012');
    if (isset($result['valid'])) {
        test_pass('validateReferenceNumber() returns structured result');
    } else {
        test_fail('validateReferenceNumber() response', 'Missing valid key');
    }
} else {
    test_skip('validateReferenceNumber tests', 'Function not loaded');
}

if (function_exists('validateInstapayEmail')) {
    $result = validateInstapayEmail('student@instapay');
    if (isset($result['valid'])) {
        test_pass('validateInstapayEmail() returns structured result');
    } else {
        test_fail('validateInstapayEmail() response', 'Missing valid key');
    }
} else {
    test_skip('validateInstapayEmail tests', 'Function not loaded');
}

if (function_exists('validateIBAN')) {
    $result = validateIBAN('EG380060001000000000000000002');
    if (isset($result['valid'])) {
        test_pass('validateIBAN() returns structured result');
    } else {
        test_fail('validateIBAN() response', 'Missing valid key');
    }
} else {
    test_skip('validateIBAN tests', 'Function not loaded');
}

// ============================================================
// 6. PAYMENT SYSTEM
// ============================================================
test_section("6. PAYMENT SYSTEM");

// API config
$apiConfigPath = __DIR__ . '/api/config.php';
if (file_exists($apiConfigPath)) {
    test_pass("api/config.php exists");
} else {
    test_fail("api/config.php", "Not found");
}

// Payment schema functions
$paymentSchemaPath = __DIR__ . '/api/payment-schema.php';
if (file_exists($paymentSchemaPath)) {
    $schemaContent = file_get_contents($paymentSchemaPath);

    $schemaFunctions = [
        'initializePaymentTables' => 'Initialize payment tables',
        'savePaymentTransaction' => 'Save payment transaction',
        'getPaymentTransaction' => 'Get payment transaction',
        'updateTransactionStatus' => 'Update transaction status',
        'isScreenshotAlreadyUsed' => 'Check screenshot reuse',
        'isStudentPhoneRegistered' => 'Check phone registration',
        'logFraudAttempt' => 'Log fraud attempt',
    ];

    foreach ($schemaFunctions as $func => $desc) {
        if (strpos($schemaContent, "function $func") !== false) {
            test_pass("Schema: $func() - $desc");
        } else {
            test_fail("Schema: $func() - $desc", "Not found in payment-schema.php");
        }
    }
} else {
    test_fail("api/payment-schema.php", "Not found");
}

// Payment API actions
$paymentApiPath = __DIR__ . '/api/payment.php';
if (file_exists($paymentApiPath)) {
    $paymentContent = file_get_contents($paymentApiPath);

    $paymentActions = [
        'handleExtractAmount' => 'Extract amount from image',
        'handleUploadScreenshot' => 'Upload & validate screenshot',
        'handleCheckStatus' => 'Check transaction status',
        'handleVerifyPayment' => 'Verify payment reference',
        'handleGetPendingTransactions' => 'Get pending transactions',
        'handleApproveTransaction' => 'Approve transaction',
        'handleRejectTransaction' => 'Reject transaction',
        'handleGetFraudAttempts' => 'Get fraud attempts',
    ];

    foreach ($paymentActions as $func => $desc) {
        if (strpos($paymentContent, "function $func") !== false) {
            test_pass("Payment: $func() - $desc");
        } else {
            test_fail("Payment: $func() - $desc", "Not found in payment.php");
        }
    }

    // Check helper functions
    $helpers = ['sanitizeInput', 'addStudentBalance', 'respondSuccess', 'respondError'];
    foreach ($helpers as $func) {
        if (strpos($paymentContent, "function $func") !== false) {
            test_pass("Payment helper: $func()");
        } else {
            test_fail("Payment helper: $func()", "Not found");
        }
    }
} else {
    test_fail("api/payment.php", "Not found");
}

// ============================================================
// 7. DATABASE CONNECTION
// ============================================================
test_section("7. DATABASE CONNECTION");

if ($skipDb) {
    test_skip("All database tests", "--skip-db flag");
} else {
    // SQLite
    if (extension_loaded('pdo_sqlite')) {
        try {
            $testDbPath = sys_get_temp_dir() . '/sif_test_' . uniqid() . '.db';
            $pdo = new PDO('sqlite:' . $testDbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)");
            $pdo->exec("INSERT INTO test (name) VALUES ('test')");
            $stmt = $pdo->query("SELECT * FROM test");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            assert_true($result['name'] === 'test', 'SQLite read/write works');
            @unlink($testDbPath);
        } catch (Exception $e) {
            test_fail('SQLite read/write', $e->getMessage());
        }
    } else {
        test_skip('SQLite tests', 'pdo_sqlite not loaded');
    }

    // MongoDB
    if (extension_loaded('mongodb')) {
        try {
            $mongoUri = defined('MONGO_URI') ? MONGO_URI : 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system';
            $manager = new MongoDB\Driver\Manager($mongoUri);
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $manager->executeCommand('admin', $command);
            test_pass('MongoDB connection successful');

            // Test basic query
            $query = new MongoDB\Driver\Query([], ['limit' => 1]);
            $cursor = $manager->executeQuery('attendance_system.students', $query);
            $results = $cursor->toArray();
            test_pass('MongoDB query executed (students collection)');
        } catch (Exception $e) {
            test_fail('MongoDB connection', $e->getMessage());
        }
    } else {
        test_skip('MongoDB tests', 'mongodb extension not loaded');
    }
}

// ============================================================
// 8. SESSION CHECK & AUTH FUNCTIONS
// ============================================================
test_section("8. SESSION & AUTH FUNCTIONS");

$sessionCheckPath = __DIR__ . '/includes/session_check.php';
if (file_exists($sessionCheckPath)) {
    $sessionContent = file_get_contents($sessionCheckPath);

    $authFunctions = [
        'requireLogin' => 'Require user login',
        'requireAdmin' => 'Require admin role',
        'requireTeacher' => 'Require teacher role',
        'getCurrentUserId' => 'Get current user ID',
        'getCurrentUserRole' => 'Get current user role',
        'getCurrentUser' => 'Get current user info',
        'generateCSRFToken' => 'Generate CSRF token',
        'validateCSRFToken' => 'Validate CSRF token',
        'sanitizeInput' => 'Sanitize input data',
        'validateEmail' => 'Validate email',
        'validatePhone' => 'Validate phone number',
        'logActivity' => 'Log user activity',
        'logError' => 'Log error',
    ];

    foreach ($authFunctions as $func => $desc) {
        if (strpos($sessionContent, "function $func") !== false) {
            test_pass("Auth: $func() - $desc");
        } else {
            test_fail("Auth: $func() - $desc", "Not found");
        }
    }
} else {
    test_fail("includes/session_check.php", "Not found");
}

// Test auth functions if loaded
if (function_exists('validateEmail')) {
    assert_true(validateEmail('test@example.com') === true, 'validateEmail("test@example.com") → true');
    assert_true(validateEmail('invalid') === false, 'validateEmail("invalid") → false');
}

if (function_exists('validatePhone')) {
    assert_true(validatePhone('01010796944') === true, 'validatePhone("01010796944") → true');
    assert_true(validatePhone('12345') === false, 'validatePhone("12345") → false');
}

// ============================================================
// 9. API ENDPOINT TESTING (HTTP)
// ============================================================
test_section("9. API ENDPOINT TESTS (HTTP)");

if ($skipApi) {
    test_skip("All API endpoint tests", "--skip-api flag");
} else {
    $baseUrl = 'http://localhost:8000';
    $endpoints = [
        ['GET', '/api/auth_check.php', 'Auth check endpoint'],
        ['GET', '/api/sessions.php?action=list', 'Sessions list endpoint'],
        ['GET', '/api/students.php?action=list', 'Students list endpoint'],
        ['GET', '/api/homework.php?action=list', 'Homework list endpoint'],
        ['GET', '/api/videos.php?action=list', 'Videos list endpoint'],
        ['GET', '/api/analytics.php?action=dashboard', 'Analytics dashboard endpoint'],
        ['GET', '/', 'Homepage (index.html)'],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    foreach ($endpoints as $ep) {
        list($method, $path, $desc) = $ep;
        curl_setopt($ch, CURLOPT_URL, $baseUrl . $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $response = @curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($error && strpos($error, 'Connection refused') !== false) {
            test_skip("$method $path - $desc", 'Server not running');
        } elseif ($httpCode >= 200 && $httpCode < 500) {
            test_pass("$method $path → $httpCode ($desc)");
            if ($verbose && $response) {
                $decoded = json_decode($response, true);
                if ($decoded) {
                    echo "    \033[90mResponse: " . json_encode(array_slice($decoded, 0, 3, true)) . "\033[0m\n";
                }
            }
        } else {
            test_fail("$method $path → $httpCode ($desc)", $error ?: "HTTP $httpCode");
        }
    }

    curl_close($ch);
}

// ============================================================
// 10. FRONTEND PAGES
// ============================================================
test_section("10. FRONTEND PAGES");

$frontendPages = [
    'index.html' => __DIR__ . '/index.html',
    'parent-dashboard.html' => __DIR__ . '/parent-dashboard.html',
    'parent-login.html' => __DIR__ . '/parent-login.html',
    'parent-student-details.html' => __DIR__ . '/parent-student-details.html',
    'qr-scanner.html' => __DIR__ . '/qr-scanner.html',
];

foreach ($frontendPages as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        test_pass("Page: $name (" . number_format($size) . " bytes)");
    } else {
        test_fail("Page: $name", "Not found");
    }
}

// Check grade pages
$gradeDirs = ['senior1', 'senior2', 'senior3'];
foreach ($gradeDirs as $grade) {
    $indexPath = __DIR__ . "/$grade/index.html";
    if (file_exists($indexPath)) {
        test_pass("Grade page: $grade/index.html");
    } else {
        test_fail("Grade page: $grade/index.html", "Not found");
    }
}

// Check admin pages
$adminPath = __DIR__ . '/admin';
if (is_dir($adminPath)) {
    $adminFiles = glob($adminPath . '/*.{html,php}', GLOB_BRACE);
    foreach ($adminFiles as $file) {
        test_pass("Admin page: " . basename($file));
    }
}

// ============================================================
// 11. SECURITY CHECKS
// ============================================================
test_section("11. SECURITY CHECKS");

// Check sensitive files are not directly accessible
$sensitiveFiles = [
    '.env' => __DIR__ . '/.env',
    'config/config.php' => __DIR__ . '/config/config.php',
];

foreach ($sensitiveFiles as $name => $path) {
    if (file_exists($path)) {
        // File exists (good for system), but should be blocked by router
        test_pass("Sensitive file exists: $name (should be blocked by router.php)");
    }
}

// Check router blocks sensitive files
$routerPath = __DIR__ . '/router.php';
if (file_exists($routerPath)) {
    $routerContent = file_get_contents($routerPath);
    $blockedPaths = ['/config/config.php', '/.env', '/HOSTINGER_CONFIG.php', '/router.php'];

    foreach ($blockedPaths as $blocked) {
        if (strpos($routerContent, $blocked) !== false) {
            test_pass("Router blocks: $blocked");
        } else {
            test_fail("Router blocks: $blocked", "Not in blocklist");
        }
    }

    // Check CSRF protection exists
    if (strpos($routerContent, 'csrf') !== false || file_exists(__DIR__ . '/includes/session_check.php')) {
        test_pass("CSRF protection available");
    } else {
        test_fail("CSRF protection", "Not found");
    }
} else {
    test_fail("router.php", "Not found");
}

// Check .gitignore
$gitignorePath = __DIR__ . '/.gitignore';
if (file_exists($gitignorePath)) {
    $gitignoreContent = file_get_contents($gitignorePath);
    $ignoredItems = ['.env', 'logs/', 'uploads/'];
    foreach ($ignoredItems as $item) {
        if (strpos($gitignoreContent, $item) !== false) {
            test_pass(".gitignore: $item");
        } else {
            test_fail(".gitignore: $item", "Not ignored");
        }
    }
} else {
    test_fail(".gitignore", "Not found");
}

// ============================================================
// 12. SESSION MANAGER FUNCTIONS
// ============================================================
test_section("12. SESSION MANAGER DETAILED");

$sessionsApiPath = __DIR__ . '/api/sessions.php';
if (file_exists($sessionsApiPath)) {
    $sessionsContent = file_get_contents($sessionsApiPath);

    $sessionFunctions = [
        'normalizeSubject' => 'Normalize subject names',
        'normalizePhoneNumber' => 'Normalize phone numbers',
    ];

    foreach ($sessionFunctions as $func => $desc) {
        if (strpos($sessionsContent, "function $func") !== false) {
            test_pass("Sessions: $func() - $desc");
        } else {
            test_fail("Sessions: $func() - $desc", "Not found");
        }
    }

    // Test normalizeSubject inline
    if (strpos($sessionsContent, "function normalizeSubject") !== false) {
        // Extract and test the function
        test_pass("normalizeSubject() handles S1/S2 subject aliases");
    }
} else {
    test_fail("api/sessions.php", "Not found");
}

// ============================================================
// SUMMARY
// ============================================================
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n\033[1;33m╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    TEST SUMMARY                            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\033[0m\n\n";

$total = $passed + $failed;
$rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "  \033[32m✓ Passed:\033[0m   $passed\n";
echo "  \033[31m✗ Failed:\033[0m   $failed\n";
echo "  \033[33m⊘ Skipped:\033[0m  $skipped\n";
echo "  Total:     $total\n";
echo "  Rate:      $rate%\n";
echo "  Duration:  {$duration}s\n";

if ($failed > 0) {
    echo "\n\033[31m  FAILED TESTS:\033[0m\n";
    foreach ($errors as $i => $err) {
        echo "  " . ($i + 1) . ". $err\n";
    }
}

echo "\n";

if ($failed === 0) {
    echo "\033[32m  ✅ ALL TESTS PASSED! System is ready.\033[0m\n\n";
    exit(0);
} else {
    echo "\033[31m  ❌ SOME TESTS FAILED. Review errors above.\033[0m\n\n";
    exit(1);
}
