<?php
// Set error handling before anything else
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors, log them instead
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Start session
session_start();

// Load configuration
require_once dirname(__DIR__) . '/config/config.php';

// Clean output buffer to remove any stray characters
ob_clean();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Try-catch for entire script
try {
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
        case 'DELETE':
            handleDelete($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Throwable $e) {
    // Catch any error (Exception or Error)
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error' => get_class($e)
    ]);
    error_log('Admin API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

function handleGet($action) {
    // Check admin authentication
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
        return;
    }

    switch ($action) {
        case 'dashboard-stats':
            getDashboardStats();
            break;
        case 'recent-activity':
            getRecentActivity();
            break;
        case 'analytics':
            getAnalyticsData();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($action) {
    switch ($action) {
        case 'login':
            adminLogin();
            break;
        case 'logout':
            adminLogout();
            break;
        default:
            // Check admin authentication for other actions
            if (!isAdminLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
                return;
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePut($action) {
    // Check admin authentication
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
        return;
    }

    switch ($action) {
        case 'update-settings':
            updateSettings();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDelete($action) {
    // Check admin authentication
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
        return;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function isAdminLoggedIn() {
    // Check for admin session/token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader)) {
        return false;
    }

    // Remove "Bearer " prefix if present
    $token = str_replace('Bearer ', '', $authHeader);

    // For demo purposes, we'll use a simple token check
    // In production, you should validate JWT tokens or session tokens
    return $token === 'admin_token_2024' || validateAdminToken($token);
}

function validateAdminToken($token) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $filter = [
            'token' => $token,
            'type' => 'admin',
            'expiresAt' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
        ];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.auth_tokens", $query);

        return count($cursor->toArray()) > 0;
    } catch (Exception $e) {
        return false;
    }
}

function adminLogin() {
    try {
        $inputData = file_get_contents('php://input');
        $data = json_decode($inputData, true);

        if (!isset($data['username']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }

        $username = trim($data['username']);
        $password = $data['password'];

        // Validate credentials (hardcoded for now)
        $validAdmins = [
            'admin' => 'admin123',
            'shady' => 'shady123'
        ];

        if (!isset($validAdmins[$username]) || $validAdmins[$username] !== $password) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + (24 * 60 * 60); // 24 hours

        // Store token in session (primary method)
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_token'] = $token;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();

        // Try to store in database (optional, for persistence across sessions)
        try {
            if (isset($GLOBALS['mongoClient']) && $GLOBALS['mongoClient']) {
                $client = $GLOBALS['mongoClient'];
                $databaseName = $GLOBALS['databaseName'];
                
                if ($databaseName) {
                    $expiresAtTimestamp = new MongoDB\BSON\UTCDateTime($expiresAt * 1000);
                    
                    $tokenData = [
                        'token' => $token,
                        'type' => 'admin',
                        'username' => $username,
                        'createdAt' => new MongoDB\BSON\UTCDateTime(),
                        'expiresAt' => $expiresAtTimestamp,
                        'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255)
                    ];
                    
                    $bulk = new MongoDB\Driver\BulkWrite();
                    $bulk->insert($tokenData);
                    $client->executeBulkWrite("$databaseName.auth_tokens", $bulk);
                }
            }
        } catch (Exception $dbError) {
            // Database error is not critical - session auth will still work
            error_log('Database token storage failed: ' . $dbError->getMessage());
        }

        // Return success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'expiresAt' => date('c', $expiresAt),
            'user' => [
                'username' => $username,
                'role' => 'admin'
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Login error: ' . $e->getMessage()
        ]);
        error_log('Login error: ' . $e->getMessage());
    }
}

function adminLogout() {
    try {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!empty($authHeader)) {
            $token = str_replace('Bearer ', '', $authHeader);
            
            // Remove token from database
            $client = $GLOBALS['mongoClient'];
            $databaseName = $GLOBALS['databaseName'];
            
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete(['token' => $token], ['limit' => 0]);
            $client->executeBulkWrite("$databaseName.auth_tokens", $bulk);
        }
        
        // Clear session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'Logged out']);
    }
}

function getDashboardStats() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get total sessions
        $filter = ['isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);
        $totalSessions = count($cursor->toArray());

        // Get total students (from both collections)
        $studentQuery = new MongoDB\Driver\Query(['isActive' => true]);
        $studentCursor = $client->executeQuery("$databaseName.users", $studentQuery);
        $platformStudents = count($studentCursor->toArray());

        // Get students from all_students_view (management system)
        $allStudentsQuery = new MongoDB\Driver\Query([]);
        $allStudentsCursor = $client->executeQuery("$databaseName.all_students_view", $allStudentsQuery);
        $allStudents = count($allStudentsCursor->toArray());

        $totalStudents = max($platformStudents, $allStudents);

        // Get total views and uploads today
        $pipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => [
                '_id' => null,
                'totalViews' => ['$sum' => '$views'],
                'totalDownloads' => ['$sum' => '$downloads']
            ]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $statsResult = current($cursor->toArray());

        // Get uploads today (sessions created today)
        $today = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
        $tomorrow = new MongoDB\BSON\UTCDateTime(strtotime('tomorrow') * 1000);

        $todayFilter = [
            'isActive' => true,
            'createdAt' => ['$gte' => $today, '$lt' => $tomorrow]
        ];
        $todayQuery = new MongoDB\Driver\Query($todayFilter);
        $todayCursor = $client->executeQuery("$databaseName.sessions", $todayQuery);
        $uploadsToday = count($todayCursor->toArray());

        $stats = [
            'totalSessions' => $totalSessions,
            'totalStudents' => $totalStudents,
            'totalViews' => $statsResult->totalViews ?? 0,
            'uploadsToday' => $uploadsToday
        ];

        echo json_encode(['success' => true, 'stats' => $stats]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching dashboard stats: ' . $e->getMessage()]);
    }
}

function getRecentActivity() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get recent sessions (last 10)
        $filter = ['isActive' => true];
        $options = [
            'sort' => ['createdAt' => -1],
            'limit' => 10
        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);

        $activities = [];
        foreach ($cursor as $session) {
            $activities[] = [
                'type' => 'session_created',
                'title' => $session->title ?? 'Unknown Session',
                'description' => "New session uploaded: " . ($session->title ?? 'Unknown'),
                'timestamp' => $session->createdAt ? $session->createdAt->toDateTime()->format('c') : date('c'),
                'timeAgo' => timeAgo($session->createdAt ? $session->createdAt->toDateTime()->getTimestamp() : time()),
                'icon' => 'fas fa-upload'
            ];
        }

        // Get recent student registrations (last 5)
        $studentFilter = ['isActive' => true];
        $studentOptions = [
            'sort' => ['joinDate' => -1],
            'limit' => 5
        ];
        $studentQuery = new MongoDB\Driver\Query($studentFilter, $studentOptions);
        $studentCursor = $client->executeQuery("$databaseName.users", $studentQuery);

        foreach ($studentCursor as $student) {
            $activities[] = [
                'type' => 'student_registered',
                'title' => $student->name ?? 'New Student',
                'description' => "Student registered: " . ($student->name ?? 'Unknown'),
                'timestamp' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : date('c'),
                'timeAgo' => timeAgo($student->joinDate ? $student->joinDate->toDateTime()->getTimestamp() : time()),
                'icon' => 'fas fa-user-plus'
            ];
        }

        // Sort activities by timestamp (most recent first)
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Take only the most recent 10 activities
        $activities = array_slice($activities, 0, 10);

        echo json_encode(['success' => true, 'activities' => $activities]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching recent activity: ' . $e->getMessage()]);
    }
}

function getAnalyticsData() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get student growth data (last 6 months)
        $studentGrowth = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = strtotime("-$i months", strtotime('first day of this month'));
            $monthEnd = strtotime("-$i months", strtotime('last day of this month'));

            $filter = [
                'isActive' => true,
                'joinDate' => [
                    '$gte' => new MongoDB\BSON\UTCDateTime($monthStart * 1000),
                    '$lte' => new MongoDB\BSON\UTCDateTime($monthEnd * 1000)
                ]
            ];
            $query = new MongoDB\Driver\Query($filter);
            $cursor = $client->executeQuery("$databaseName.users", $query);
            $count = count($cursor->toArray());

            $studentGrowth[] = [
                'month' => date('M', $monthStart),
                'count' => $count
            ];
        }

        // Get subject distribution
        $pipeline = [
            ['$match' => ['isActive' => true]],
            ['$unwind' => '$subjects'],
            ['$group' => ['_id' => '$subjects', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'users',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $subjectDistribution = [];
        foreach ($cursor as $doc) {
            $subjectDistribution[] = [
                'subject' => $doc->_id,
                'count' => $doc->count,
                'percentage' => 0 // Will be calculated on frontend
            ];
        }

        // Get watch time data
        $watchTimePipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => [
                '_id' => null,
                'totalWatchTime' => ['$sum' => '$totalWatchTime'],
                'avgWatchTime' => ['$avg' => '$totalWatchTime']
            ]]
        ];

        $watchTimeCommand = new MongoDB\Driver\Command([
            'aggregate' => 'users',
            'pipeline' => $watchTimePipeline,
            'cursor' => new stdClass()
        ]);

        $watchTimeCursor = $client->executeCommand($databaseName, $watchTimeCommand);
        $watchTimeResult = current($watchTimeCursor->toArray());

        // Get top sessions
        $topSessionsPipeline = [
            ['$match' => ['isActive' => true]],
            ['$sort' => ['views' => -1]],
            ['$limit' => 5],
            ['$project' => [
                'title' => 1,
                'subject' => 1,
                'views' => 1,
                'rating' => 1,
                'downloads' => 1
            ]]
        ];

        $topSessionsCommand = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $topSessionsPipeline,
            'cursor' => new stdClass()
        ]);

        $topSessionsCursor = $client->executeCommand($databaseName, $topSessionsCommand);
        $topSessions = [];
        foreach ($topSessionsCursor as $session) {
            $topSessions[] = [
                'title' => $session->title ?? 'Unknown',
                'subject' => $session->subject ?? 'Unknown',
                'views' => $session->views ?? 0,
                'rating' => $session->rating ?? 0,
                'downloads' => $session->downloads ?? 0
            ];
        }

        $analytics = [
            'studentGrowth' => $studentGrowth,
            'subjectDistribution' => $subjectDistribution,
            'totalWatchTime' => $watchTimeResult->totalWatchTime ?? 0,
            'avgWatchTime' => $watchTimeResult->avgWatchTime ?? 0,
            'topSessions' => $topSessions,
            'totalStudents' => countStudents(),
            'totalSessions' => countSessions(),
            'completionRate' => 87 // Placeholder - would need more complex calculation
        ];

        echo json_encode(['success' => true, 'analytics' => $analytics]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching analytics: ' . $e->getMessage()]);
    }
}

function updateSettings() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        // For now, just acknowledge the update
        // In a real implementation, you'd store settings in the database

        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully',
            'updated' => $data
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Settings update error: ' . $e->getMessage()]);
    }
}

// Helper functions
function timeAgo($timestamp) {
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return floor($diff / 604800) . ' weeks ago';
    }
}

function countStudents() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $query = new MongoDB\Driver\Query(['isActive' => true]);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        return count($cursor->toArray());
    } catch (Exception $e) {
        return 0;
    }
}

function countSessions() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $query = new MongoDB\Driver\Query(['isActive' => true]);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);
        return count($cursor->toArray());
    } catch (Exception $e) {
        return 0;
    }
}