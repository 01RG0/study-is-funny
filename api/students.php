<?php
require_once 'config.php';

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
            'subjects' => $data['grade'] === 'senior1' ? ["physics", "mathematics", "statistics"] : ["physics", "mathematics", "statistics"],
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

        $filter = ['phone' => $phone, 'isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $student = current($cursor->toArray());

        if ($student) {
            $studentArray = [
                'name' => $student->name,
                'phone' => $student->phone,
                'grade' => $student->grade,
                'subjects' => $student->subjects,
                'joinDate' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : null,
                'lastLogin' => $student->lastLogin ? $student->lastLogin->toDateTime()->format('c') : null,
                'isActive' => $student->isActive,
                'totalSessionsViewed' => $student->totalSessionsViewed ?? 0,
                'totalWatchTime' => $student->totalWatchTime ?? 0,
                'totalSessions' => isset($student->subjects) ? count($student->subjects) * 5 : 0,
                'watchedSessions' => $student->totalSessionsViewed ?? 0,
                'totalWatchTimeFormatted' => formatWatchTime($student->totalWatchTime ?? 0)
            ];

            echo json_encode([
                'success' => true,
                'student' => $studentArray
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching student: ' . $e->getMessage()]);
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