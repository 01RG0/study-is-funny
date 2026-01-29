<?php

// Improved error handling and debug logging
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

function debug_log($msg) {
    $logFile = __DIR__ . '/../logs/debug_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . print_r($msg, true) . "\n\n", FILE_APPEND);
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return true;
    }
    $msg = [
        'php_error' => true,
        'errno' => $errno,
        'errstr' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    debug_log($msg);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        debug_log($error);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
        exit;
    }
});

require_once dirname(__DIR__) . '/config/config.php';

// Initialize MongoDB directly if not available from config
if (!$GLOBALS['mongoClient']) {
    try {
        if (class_exists('MongoDB\\Driver\\Manager')) {
            $mongoUri = defined('MONGO_URI') ? MONGO_URI : 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
            $dbName = defined('DB_NAME') ? DB_NAME : 'attendance_system';
            
            $GLOBALS['mongoClient'] = new MongoDB\Driver\Manager($mongoUri);
            $GLOBALS['databaseName'] = $dbName;
            
            // Test connection
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $GLOBALS['mongoClient']->executeCommand('admin', $command);
        }
    } catch (Exception $e) {
        // Still not available
    }
}

// Final check
if (!$GLOBALS['mongoClient']) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection error: MongoDB extension not available. Please enable MongoDB in hosting control panel.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($action);
        break;
    case 'POST':
        handlePost($action);
        break;
    case 'PUT':
        handlePut($action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($action) {
    switch ($action) {
        case 'get':
            getStudent();
            break;
        case 'getByParentPhone':
            getStudentByParentPhone();
            break;
        case 'all':
            getAllStudents();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'register':
            registerStudent($data);
            break;
        case 'login':
            loginStudent($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePut($action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            updateStudent($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function registerStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Validate required fields
        if (!isset($data['name']) || !isset($data['phone']) || !isset($data['password']) || !isset($data['grade'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $phone = $data['phone'];

        // Check if student already exists
        $filter = ['phone' => $phone];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $existingStudent = current($cursor->toArray());

        if ($existingStudent) {
            echo json_encode(['success' => false, 'message' => 'Student already exists']);
            return;
        }

        // Create new student
        $studentData = [
            'name' => $data['name'],
            'phone' => $phone,
            'password' => $data['password'],
            'grade' => $data['grade'],
            'subjects' => $data['grade'] === 'senior1' ? ["mathematics"] : ["physics", "mathematics", "mechanics"],
            'joinDate' => new MongoDB\BSON\UTCDateTime(),
            'lastLogin' => new MongoDB\BSON\UTCDateTime(),
            'isActive' => true,
            'totalSessionsViewed' => 0,
            'totalWatchTime' => 0,
            'activityLog' => []
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($studentData);
        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        if ($result->getInsertedCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Student registered successfully!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Registration error: ' . $e->getMessage()]);
    }
}

function loginStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['phone']) || !isset($data['password'])) {
            echo json_encode(['success' => false, 'message' => 'Missing phone or password']);
            return;
        }

        $phone = $data['phone'];
        $password = $data['password'];

        // Find student
        $filter = ['phone' => $phone, 'password' => $password, 'isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $student = current($cursor->toArray());

        if ($student) {
            // Update last login
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(
                ['phone' => $phone],
                ['$set' => ['lastLogin' => new MongoDB\BSON\UTCDateTime()]]
            );
            $client->executeBulkWrite("$databaseName.users", $bulk);

            // Convert BSON to array for JSON response
            $studentArray = [
                'name' => $student->name,
                'phone' => $student->phone,
                'grade' => $student->grade,
                'subjects' => $student->subjects,
                'joinDate' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : null,
                'lastLogin' => date('c'),
                'isActive' => $student->isActive,
                'totalSessionsViewed' => $student->totalSessionsViewed ?? 0,
                'totalWatchTime' => $student->totalWatchTime ?? 0
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'user' => $studentArray
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid phone or password']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
    }
}

function getStudent() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $phone = $_GET['phone'] ?? '';
        if (!$phone) {
            echo json_encode(['success' => false, 'message' => 'Phone number required']);
            return;
        }

        // Normalize phone formats
        $phonesToTry = [$phone];
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
            $phonesToTry[] = '+2' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        }

        // 1. PRIMARY: Search 'all_students_view' (management system - MOST ACCURATE)
        $viewQuery = new MongoDB\Driver\Query(['phone' => ['$in' => $phonesToTry]]);
        $viewCursor = $client->executeQuery("$databaseName.all_students_view", $viewQuery);
        $allMatches = $viewCursor->toArray();

        if (!empty($allMatches)) {
            // Use the first match as the base student
            $base = (array)$allMatches[0];
            $merged = [
                'name' => $base['studentName'] ?? $base['name'] ?? 'Student',
                'phone' => $phone,
                'subjects' => [],
                'subjectIds' => [],
                'subjectBalances' => [],
                'grade' => 'senior1',
                'isActive' => true
            ];
            $subjectMapping = [
                'math' => 'mathematics',
                'pure math' => 'mathematics',
                'pure' => 'mathematics',
                'physics' => 'physics',
                'mechanics' => 'mechanics',
                'stat' => 'mathematics',
                'statistics' => 'mathematics'
            ];
            foreach ($allMatches as $match) {
                $rawSubject = strtolower($match->subject ?? '');
                // Remove grade prefix first
                $cleanedSubject = preg_replace('/^\s*S[123]\s*-?\s*/i', '', $rawSubject);
                
                if (isset($match->subject)) {
                    if (strpos($match->subject, 'S1') !== false) $merged['grade'] = 'senior1';
                    elseif (strpos($match->subject, 'S2') !== false) $merged['grade'] = 'senior2';
                    elseif (strpos($match->subject, 'S3') !== false) $merged['grade'] = 'senior3';
                }
                
                // Try to map subject - check longest matches first
                $foundSlug = null;
                foreach ($subjectMapping as $key => $slug) {
                    if (stripos($cleanedSubject, $key) !== false) {
                        $foundSlug = $slug;
                        break;
                    }
                }
                
                // If no match found, try to extract subject name directly
                if (!$foundSlug && !empty($cleanedSubject)) {
                    // Common subject names
                    if (stripos($cleanedSubject, 'mathematics') !== false || stripos($cleanedSubject, 'math') !== false) {
                        $foundSlug = 'mathematics';
                    } elseif (stripos($cleanedSubject, 'physics') !== false) {
                        $foundSlug = 'physics';
                    } elseif (stripos($cleanedSubject, 'mechanics') !== false) {
                        $foundSlug = 'mechanics';
                    } elseif (stripos($cleanedSubject, 'statistics') !== false || stripos($cleanedSubject, 'stat') !== false) {
                        $foundSlug = 'mathematics';
                    }
                }
                
                if ($foundSlug && !in_array($foundSlug, $merged['subjects'])) {
                    $merged['subjects'][] = $foundSlug;
                    $merged['subjectIds'][$foundSlug] = $match->studentId ?? null;
                    $merged['subjectBalances'][$foundSlug] = $match->balance ?? 0;
                }
            }
            
            // Calculate total balance
            $merged['balance'] = array_sum($merged['subjectBalances']);
            
            if (empty($merged['subjects'])) {
                $merged['subjects'] = ($merged['grade'] === 'senior1') ? ['mathematics'] : ['physics', 'mathematics', 'mechanics'];
            }
            // Merge all session fields from allMatches
            $sessionFields = [];
            foreach ($allMatches as $match) {
                foreach ((array)$match as $k => $v) {
                    if (strpos($k, 'session_') === 0) {
                        $sessionFields[$k] = $v;
                    }
                }
            }
            $studentArray = array_merge([
                'name' => $merged['name'],
                'phone' => $phone,
                'grade' => $merged['grade'],
                'subjects' => array_values($merged['subjects']),
                'subjectIds' => $merged['subjectIds'],
                'subjectBalances' => $merged['subjectBalances'],
                'balance' => $merged['balance'],
                'isActive' => true,
                'totalSessionsViewed' => 0,
                'totalWatchTime' => 0,
                'totalSessions' => count($merged['subjects']) * 5,
                'watchedSessions' => 0,
                'totalWatchTimeFormatted' => '0h 0m'
            ], $sessionFields);
            echo json_encode(['success' => true, 'student' => $studentArray]);
            return;
        }

        // 2. FALLBACK: Try 'users' collection (platform accounts)
        $filter = ['phone' => ['$in' => $phonesToTry]];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $platformStudent = current($cursor->toArray());

        if ($platformStudent) {
            $studentArray = [
                'name' => $platformStudent->name ?? 'Student',
                'phone' => $phone,
                'grade' => $platformStudent->grade ?? 'senior1',
                'subjects' => isset($platformStudent->subjects) ? array_values((array)$platformStudent->subjects) : (($platformStudent->grade ?? 'senior1') === 'senior1' ? ['mathematics'] : ['physics', 'mathematics', 'mechanics']),
                'isActive' => $platformStudent->isActive ?? true,
                'totalSessionsViewed' => $platformStudent->totalSessionsViewed ?? 0,
                'totalWatchTime' => $platformStudent->totalWatchTime ?? 0,
                'totalSessions' => isset($platformStudent->subjects) ? count((array)$platformStudent->subjects) * 5 : 15,
                'watchedSessions' => $platformStudent->totalSessionsViewed ?? 0,
                'totalWatchTimeFormatted' => formatWatchTime($platformStudent->totalWatchTime ?? 0)
            ];

            echo json_encode(['success' => true, 'student' => $studentArray]);
            return;
        }

        echo json_encode(['success' => false, 'message' => "Student not found with phone: " . implode(', ', $phonesToTry)]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getStudentByParentPhone() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $parentPhone = $_GET['parentPhone'] ?? '';
        if (!$parentPhone) {
            echo json_encode(['success' => false, 'message' => 'Parent phone number required']);
            return;
        }

        // Normalize phone formats
        $phonesToTry = [$parentPhone];
        $cleanPhone = preg_replace('/[^0-9]/', '', $parentPhone);
        
        // Handle different phone formats
        if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '20') {
            // 201060416120 -> 01060416120
            $phonesToTry[] = '0' . substr($cleanPhone, 2);
            $phonesToTry[] = '+' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        } elseif (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
            // 01060416120 -> 201060416120
            $phonesToTry[] = '20' . substr($cleanPhone, 1);
            $phonesToTry[] = '+20' . substr($cleanPhone, 1);
            $phonesToTry[] = $cleanPhone;
        }

        // PRIMARY: Search all_students_view collection (management system)
        $filter = ['parentPhone' => ['$in' => $phonesToTry]];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.all_students_view", $query);
        $matches = $cursor->toArray();

        if (!empty($matches)) {
            // Return one record per subject enrollment - DO NOT GROUP
            $students = [];
            
            foreach ($matches as $studentData) {
                $phone = $studentData->phone ?? $studentData->studentPhone ?? '';
                if (empty($phone)) continue;
                
                // Extract grade from subject if present
                $grade = 'senior1';
                if (isset($studentData->subject)) {
                    if (strpos($studentData->subject, 'S1') !== false) {
                        $grade = 'senior1';
                    } elseif (strpos($studentData->subject, 'S2') !== false) {
                        $grade = 'senior2';
                    } elseif (strpos($studentData->subject, 'S3') !== false) {
                        $grade = 'senior3';
                    }
                }
                
                // Extract subject slug
                $subjectSlug = 'mathematics';
                if (isset($studentData->subject)) {
                    $rawSubject = strtolower($studentData->subject);
                    $cleanedSubject = preg_replace('/^\\s*S[123]\\s*-?\\s*/i', '', $rawSubject);
                    
                    if (stripos($cleanedSubject, 'pure') !== false || stripos($cleanedSubject, 'math') !== false) {
                        $subjectSlug = 'mathematics';
                    } elseif (stripos($cleanedSubject, 'physics') !== false) {
                        $subjectSlug = 'physics';
                    } elseif (stripos($cleanedSubject, 'mechanics') !== false) {
                        $subjectSlug = 'mechanics';
                    } elseif (stripos($cleanedSubject, 'stat') !== false) {
                        $subjectSlug = 'statistics';
                    }
                }
                
                // Create a separate record for each enrollment
                $students[] = [
                    'name' => $studentData->studentName ?? $studentData->name ?? 'Student',
                    'phone' => $phone,
                    'parentPhone' => $studentData->parentPhone ?? $parentPhone,
                    'grade' => $grade,
                    'subject' => $studentData->subject ?? '',
                    'subjects' => [$subjectSlug],
                    'balance' => $studentData->balance ?? 0,
                    'bookletBalance' => $studentData->bookletBalance ?? 0,
                    'isActive' => $studentData->isActive ?? true
                ];
            }
            
            if (!empty($students)) {
                echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
                return;
            }
        }

        // FALLBACK: Search in users collection
        $filter = ['parentPhone' => ['$in' => $phonesToTry]];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $studentsArray = $cursor->toArray();

        if (!empty($studentsArray)) {
            $students = [];
            foreach ($studentsArray as $student) {
                // Get the actual subject value from database
                $subjectValue = '';
                if (isset($student->subject)) {
                    $subjectValue = $student->subject;
                }
                
                // Get unique subjects - handle both array and string formats
                $subjectsArray = [];
                if (isset($student->subjects) && is_array($student->subjects)) {
                    $subjectsArray = array_values((array)$student->subjects);
                } elseif (isset($student->subjects) && is_object($student->subjects)) {
                    // Handle BSON array objects
                    $subjectsArray = array_values((array)$student->subjects);
                } elseif (!empty($subjectValue)) {
                    $subjectsArray = [$subjectValue];
                } else {
                    $subjectsArray = ['mathematics'];
                }
                
                // Remove duplicates
                $subjectsArray = array_values(array_unique($subjectsArray));
                
                $students[] = [
                    'name' => $student->name ?? 'Student',
                    'phone' => $student->phone ?? '',
                    'parentPhone' => $parentPhone,
                    'grade' => $student->grade ?? 'senior1',
                    'subject' => $subjectValue,
                    'subjects' => $subjectsArray,
                    'balance' => $student->balance ?? 0,
                    'bookletBalance' => $student->bookletBalance ?? 0,
                    'isActive' => $student->isActive ?? true
                ];
            }

            echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'No student found with parent phone: ' . $parentPhone]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAllStudents() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $filter = ['isActive' => true];
        $options = [
            'sort' => ['joinDate' => -1]
        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.users", $query);

        $students = [];
        foreach ($cursor as $student) {
            $students[] = [
                'name' => $student->name,
                'phone' => $student->phone,
                'grade' => $student->grade,
                'subjects' => $student->subjects,
                'joinDate' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : null,
                'lastLogin' => $student->lastLogin ? $student->lastLogin->toDateTime()->format('c') : null,
                'isActive' => $student->isActive,
                'totalSessionsViewed' => $student->totalSessionsViewed ?? 0,
                'totalWatchTime' => $student->totalWatchTime ?? 0
            ];
        }

        echo json_encode([
            'success' => true,
            'documents' => $students
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching students: ' . $e->getMessage()]);
    }
}

function updateStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['phone'])) {
            echo json_encode(['success' => false, 'message' => 'Phone number required']);
            return;
        }

        $phone = $data['phone'];
        unset($data['phone']); // Remove phone from update data

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['phone' => $phone],
            ['$set' => $data]
        );

        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Student updated successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update error: ' . $e->getMessage()]);
    }
}

function formatWatchTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return "{$hours}h {$mins}m";
}
?>