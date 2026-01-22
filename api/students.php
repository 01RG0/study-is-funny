<?php
// Prevent HTML errors from being output - output JSON only
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');

header('Content-Type: application/json');

// Catch any fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
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
            // Initialize merged student data from first match
            $merged = [
                'name' => $allMatches[0]->studentName ?? $allMatches[0]->name ?? 'Student',
                'phone' => $phone,
                'subjects' => [],
                'subjectIds' => [], // Map of subject => studentId
                'grade' => 'senior1',
                'isActive' => true
            ];

            // Advanced Subject Mapping
            $subjectMapping = [
                'math' => 'mathematics',
                'pure math' => 'mathematics',
                'physics' => 'physics',
                'mechanics' => 'mechanics',
                'stat' => 'mathematics'
            ];

            foreach ($allMatches as $match) {
                $rawSubject = strtolower($match->subject ?? '');
                
                // Determine grade from subject field
                if (isset($match->subject)) {
                    if (strpos($match->subject, 'S1') !== false) $merged['grade'] = 'senior1';
                    elseif (strpos($match->subject, 'S2') !== false) $merged['grade'] = 'senior2';
                    elseif (strpos($match->subject, 'S3') !== false) $merged['grade'] = 'senior3';
                }

                // Map subject names and store studentId for each subject
                foreach ($subjectMapping as $key => $slug) {
                    if (strpos($rawSubject, $key) !== false) {
                        if (!in_array($slug, $merged['subjects'])) {
                            $merged['subjects'][] = $slug;
                            // Store the studentId for this specific subject
                            $merged['subjectIds'][$slug] = $match->studentId ?? null;
                        }
                    }
                }
            }

            // Fallback subjects
            if (empty($merged['subjects'])) {
                $merged['subjects'] = ($merged['grade'] === 'senior1') ? ['mathematics'] : ['physics', 'mathematics', 'mechanics'];
            }

            $studentArray = [
                'name' => $merged['name'],
                'phone' => $phone,
                'grade' => $merged['grade'],
                'subjects' => array_values($merged['subjects']),
                'subjectIds' => $merged['subjectIds'], // Include subject-to-ID mapping
                'isActive' => true,
                'totalSessionsViewed' => 0,
                'totalWatchTime' => 0,
                'totalSessions' => count($merged['subjects']) * 5,
                'watchedSessions' => 0,
                'totalWatchTimeFormatted' => '0h 0m'
            ];

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