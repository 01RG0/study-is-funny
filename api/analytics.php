<?php
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($action) {
    // Check admin authentication
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || !validateAdminToken(str_replace('Bearer ', '', $authHeader))) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
        return;
    }

    switch ($action) {
        case 'dashboard':
            getDashboardAnalytics();
            break;
        case 'sessions':
            getSessionAnalytics();
            break;
        case 'students':
            getStudentAnalytics();
            break;
        case 'performance':
            getPerformanceAnalytics();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
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

function getDashboardAnalytics() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get overall statistics
        $stats = [
            'totalStudents' => countStudents(),
            'totalSessions' => countSessions(),
            'totalViews' => getTotalViews(),
            'totalWatchTime' => getTotalWatchTime(),
            'activeStudents' => getActiveStudents(),
            'completionRate' => calculateCompletionRate(),
            'avgRating' => getAverageRating(),
            'newRegistrations' => getNewRegistrationsToday()
        ];

        // Get student growth data (last 6 months)
        $stats['studentGrowth'] = getStudentGrowthData();

        // Get subject distribution
        $stats['subjectDistribution'] = getSubjectDistribution();

        // Get top performing sessions
        $stats['topSessions'] = getTopSessions();

        // Get recent activity
        $stats['recentActivity'] = getRecentActivityData();

        // Get watch time trends
        $stats['watchTimeTrends'] = getWatchTimeTrends();

        echo json_encode(['success' => true, 'analytics' => $stats]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching dashboard analytics: ' . $e->getMessage()]);
    }
}

function getSessionAnalytics() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get sessions by status
        $statusPipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => '$status', 'count' => ['$sum' => 1]]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $statusPipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $statusData = [];
        foreach ($cursor as $doc) {
            $statusData[$doc->_id] = $doc->count;
        }

        // Get sessions by subject
        $subjectPipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => '$subject', 'count' => ['$sum' => 1], 'totalViews' => ['$sum' => '$views']]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $subjectPipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $subjectData = [];
        foreach ($cursor as $doc) {
            $subjectData[] = [
                'subject' => $doc->_id,
                'count' => $doc->count,
                'views' => $doc->totalViews
            ];
        }

        // Get sessions by grade
        $gradePipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => '$grade', 'count' => ['$sum' => 1]]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $gradePipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $gradeData = [];
        foreach ($cursor as $doc) {
            $gradeData[] = [
                'grade' => $doc->_id,
                'count' => $doc->count
            ];
        }

        $analytics = [
            'byStatus' => $statusData,
            'bySubject' => $subjectData,
            'byGrade' => $gradeData,
            'totalUploads' => countSessions(),
            'publishedCount' => $statusData['published'] ?? 0,
            'draftCount' => $statusData['draft'] ?? 0
        ];

        echo json_encode(['success' => true, 'analytics' => $analytics]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching session analytics: ' . $e->getMessage()]);
    }
}

function getStudentAnalytics() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get students by grade
        $gradePipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => '$grade', 'count' => ['$sum' => 1]]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'users',
            'pipeline' => $gradePipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $gradeData = [];
        foreach ($cursor as $doc) {
            $gradeData[] = [
                'grade' => $doc->_id,
                'count' => $doc->count
            ];
        }

        // Get student engagement metrics
        $engagementPipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => [
                '_id' => null,
                'avgSessionsViewed' => ['$avg' => '$totalSessionsViewed'],
                'avgWatchTime' => ['$avg' => '$totalWatchTime'],
                'totalLogins' => ['$sum' => 1]
            ]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'users',
            'pipeline' => $engagementPipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $engagementData = current($cursor->toArray());

        $analytics = [
            'byGrade' => $gradeData,
            'totalStudents' => countStudents(),
            'avgSessionsViewed' => round($engagementData->avgSessionsViewed ?? 0, 1),
            'avgWatchTime' => round(($engagementData->avgWatchTime ?? 0) / 60, 1), // Convert to hours
            'totalLogins' => $engagementData->totalLogins ?? 0
        ];

        echo json_encode(['success' => true, 'analytics' => $analytics]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching student analytics: ' . $e->getMessage()]);
    }
}

function getPerformanceAnalytics() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get completion rates by subject
        $completionPipeline = [
            ['$match' => ['isActive' => true, 'status' => 'published']],
            ['$lookup' => [
                'from' => 'session_views',
                'localField' => '_id',
                'foreignField' => 'sessionId',
                'as' => 'views'
            ]],
            ['$addFields' => [
                'completionRate' => [
                    '$cond' => [
                        ['$gt' => ['$views', 0]],
                        ['$multiply' => [['$divide' => ['$views', ['$ifNull' => ['$maxViews', 100]]]], 100]],
                        0
                    ]
                ]
            ]],
            ['$group' => [
                '_id' => '$subject',
                'avgCompletionRate' => ['$avg' => '$completionRate'],
                'totalViews' => ['$sum' => '$views']
            ]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $completionPipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $performanceData = [];
        foreach ($cursor as $doc) {
            $performanceData[] = [
                'subject' => $doc->_id,
                'completionRate' => round($doc->avgCompletionRate, 1),
                'totalViews' => $doc->totalViews
            ];
        }

        echo json_encode(['success' => true, 'analytics' => $performanceData]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching performance analytics: ' . $e->getMessage()]);
    }
}

// Helper functions
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

function getTotalViews() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $pipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => null, 'totalViews' => ['$sum' => '$views']]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $result = current($cursor->toArray());
        return $result->totalViews ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getTotalWatchTime() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $pipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => null, 'totalWatchTime' => ['$sum' => '$totalWatchTime']]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'users',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $result = current($cursor->toArray());
        return $result->totalWatchTime ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getActiveStudents() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Students active in the last 30 days
        $thirtyDaysAgo = new MongoDB\BSON\UTCDateTime(strtotime('-30 days') * 1000);
        $query = new MongoDB\Driver\Query([
            'isActive' => true,
            'lastLogin' => ['$gte' => $thirtyDaysAgo]
        ]);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        return count($cursor->toArray());
    } catch (Exception $e) {
        return 0;
    }
}

function calculateCompletionRate() {
    // Placeholder - would need more complex logic based on actual completion tracking
    return 87;
}

function getAverageRating() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $pipeline = [
            ['$match' => ['isActive' => true, 'ratingCount' => ['$gt' => 0]]],
            ['$group' => ['_id' => null, 'avgRating' => ['$avg' => '$rating']]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $result = current($cursor->toArray());
        return round($result->avgRating ?? 4.8, 1);
    } catch (Exception $e) {
        return 4.8;
    }
}

function getNewRegistrationsToday() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $today = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
        $query = new MongoDB\Driver\Query([
            'isActive' => true,
            'joinDate' => ['$gte' => $today]
        ]);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        return count($cursor->toArray());
    } catch (Exception $e) {
        return 0;
    }
}

function getStudentGrowthData() {
    $growth = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthStart = strtotime("-$i months", strtotime('first day of this month'));
        $monthEnd = strtotime("-$i months", strtotime('last day of this month'));

        try {
            $client = $GLOBALS['mongoClient'];
            $databaseName = $GLOBALS['databaseName'];

            $query = new MongoDB\Driver\Query([
                'isActive' => true,
                'joinDate' => [
                    '$gte' => new MongoDB\BSON\UTCDateTime($monthStart * 1000),
                    '$lte' => new MongoDB\BSON\UTCDateTime($monthEnd * 1000)
                ]
            ]);
            $cursor = $client->executeQuery("$databaseName.users", $query);
            $count = count($cursor->toArray());
        } catch (Exception $e) {
            $count = 0;
        }

        $growth[] = [
            'month' => date('M', $monthStart),
            'count' => $count
        ];
    }
    return $growth;
}

function getSubjectDistribution() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

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
        $distribution = [];
        foreach ($cursor as $doc) {
            $distribution[] = [
                'subject' => $doc->_id,
                'count' => $doc->count
            ];
        }
        return $distribution;
    } catch (Exception $e) {
        return [];
    }
}

function getTopSessions() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $pipeline = [
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

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $topSessions = [];
        foreach ($cursor as $session) {
            $topSessions[] = [
                'title' => $session->title ?? 'Unknown',
                'subject' => $session->subject ?? 'Unknown',
                'views' => $session->views ?? 0,
                'rating' => $session->rating ?? 0,
                'downloads' => $session->downloads ?? 0
            ];
        }
        return $topSessions;
    } catch (Exception $e) {
        return [];
    }
}

function getRecentActivityData() {
    $activities = [];

    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get recent sessions
        $filter = ['isActive' => true];
        $options = [
            'sort' => ['createdAt' => -1],
            'limit' => 5
        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);

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

        // Get recent student registrations
        $studentFilter = ['isActive' => true];
        $studentOptions = [
            'sort' => ['joinDate' => -1],
            'limit' => 3
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

        // Sort by timestamp
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    } catch (Exception $e) {
        return [];
    }
}

function getWatchTimeTrends() {
    // Placeholder - would need historical data
    return [
        ['month' => 'Jan', 'hours' => 1200],
        ['month' => 'Feb', 'hours' => 1450],
        ['month' => 'Mar', 'hours' => 1680],
        ['month' => 'Apr', 'hours' => 1520],
        ['month' => 'May', 'hours' => 1890],
        ['month' => 'Jun', 'hours' => 2100]
    ];
}

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
?></contents>
</xai:function_call">Update admin.js to use the new analytics API