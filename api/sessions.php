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
require_once __DIR__ . '/../classes/DatabaseMongo.php';
require_once __DIR__ . '/../classes/Video.php';
require_once __DIR__ . '/../classes/SessionManager.php';

// Check if MongoDB is available
if (!$GLOBALS['mongoClient']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: MongoDB not available'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Subject name mapping for backwards compatibility
function normalizeSubject($subjectName) {
    $subjectMap = [
        // New names (from upload form)
        'mathematics' => 'mathematics',
        'physics' => 'physics',
        'statistics' => 'statistics',
        'mechanics' => 'mechanics',
        // Senior 1 - Mathematics only
        's1 mathematics' => 'mathematics',
        's1 math' => 'mathematics',
        's1 pure math' => 'mathematics',
        // Senior 2 - All subjects
        's2 pure math' => 'mathematics',
        's2 pure mathematics' => 'mathematics',
        's2 mathematics' => 'mathematics',
        's2 math' => 'mathematics',
        's2 physics' => 'physics',
        's2 mechanics' => 'mechanics',
        's2 statistics' => 'statistics',
        // Generic versions
        'pure math' => 'mathematics',
        'pure mathematics' => 'mathematics',
        'math' => 'mathematics',
    ];
    
    $normalized = strtolower(trim($subjectName));
    return $subjectMap[$normalized] ?? $normalized;
}

// Normalize phone numbers
function normalizePhoneNumber($phone) {
    // Remove all non-digit characters except leading +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // If starts with +20, convert to 01...
    if (strpos($phone, '+20') === 0) {
        return '0' . substr($phone, 3);
    }
    
    // If starts with +, remove it
    if (strpos($phone, '+') === 0) {
        return substr($phone, 1);
    }
    
    // Ensure it starts with 0 (Egyptian phone format)
    if (strpos($phone, '0') !== 0 && strpos($phone, '20') === 0) {
        return '0' . substr($phone, 2);
    }
    
    return $phone;
}

// Convert phone to +20 format for database lookup
function convertTo20Format($phone) {
    // First normalize to 01... format
    $normalized = normalizePhoneNumber($phone);
    
    // If it starts with 0, replace with +20
    if (strpos($normalized, '0') === 0) {
        return '+20' . substr($normalized, 1);
    }
    
    // Otherwise, just add +20
    return '+20' . $normalized;
}

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

function handleGet($action) {
    switch ($action) {
        case 'get':
            getSession();
            break;
        case 'all':
        case 'list':
        case 'getAllSessions':  // Added alias for manage-sessions page
            getAllSessions();
            break;
        case 'stats':
            getSessionStats();
            break;
        case 'check-access':
            checkStudentSessionAccess();
            break;
        case 'purchase-session':
            purchaseStudentSession();
            break;
        case 'diagnose':
            runDiagnostics();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($action) {
    // For upload action, don't JSON decode (it uses multipart/form-data with files)
    if ($action === 'upload') {
        uploadSession($_POST);
        return;
    }
    
    // For other actions, decode JSON
    $data = json_decode(file_get_contents('php://input'), true);

    // Add id from query parameter if present
    if (isset($_GET['id'])) {
        $data['id'] = $_GET['id'];
    }

    switch ($action) {
        case 'create':
            createSession($data);
            break;
        case 'updateSession':
        case 'update':
            updateSession($data);
            break;
        case 'deleteSession':
        case 'delete':
            deleteSession($data);
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
            updateSession($data);
            break;
        case 'publish':
            publishSession($data);
            break;
        case 'unpublish':
            unpublishSession($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDelete($action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'delete':
            deleteSession($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function validateSessionData($data) {
    $errors = [];

    // Required field validation
    if (!isset($data['title']) || empty(trim($data['title']))) {
        $errors[] = 'Session title is required';
    } elseif (strlen(trim($data['title'])) < 3) {
        $errors[] = 'Session title must be at least 3 characters long';
    } elseif (strlen(trim($data['title'])) > 200) {
        $errors[] = 'Session title must be less than 200 characters';
    }

    if (!isset($data['subject']) || empty($data['subject'])) {
        $errors[] = 'Subject is required';
    } elseif (!in_array($data['subject'], ['physics', 'mathematics', 'statistics', 'mechanics'])) {
        $errors[] = 'Invalid subject selected';
    }

    if (!isset($data['grade']) || empty($data['grade'])) {
        $errors[] = 'Grade level is required';
    } elseif (!in_array($data['grade'], ['senior1', 'senior2', 'senior3'])) {
        $errors[] = 'Invalid grade level selected';
    }

    if (!isset($data['teacher']) || empty($data['teacher'])) {
        $errors[] = 'Teacher is required';
    }

    // Validate session number
    if (isset($data['sessionNumber'])) {
        $sessionNumber = (int)$data['sessionNumber'];
        if ($sessionNumber < 1 || $sessionNumber > 100) {
            $errors[] = 'Session number must be between 1-100';
        }
    }

    // Validate videos
    if (!isset($data['videos']) || empty($data['videos'])) {
        $errors[] = 'At least one video is required';
    } else {
        foreach ($data['videos'] as $index => $video) {
            if (!isset($video['title']) || empty(trim($video['title']))) {
                $errors[] = "Video " . ($index + 1) . ": Title is required";
            } elseif (strlen(trim($video['title'])) < 2) {
                $errors[] = "Video " . ($index + 1) . ": Title must be at least 2 characters long";
            }

            if (!isset($video['type']) || empty($video['type'])) {
                $errors[] = "Video " . ($index + 1) . ": Type is required";
            } elseif (!in_array($video['type'], ['lecture', 'questions', 'summary', 'exercise', 'homework'])) {
                $errors[] = "Video " . ($index + 1) . ": Invalid video type";
            }

            if (isset($video['duration']) && $video['duration'] !== null) {
                $duration = (int)$video['duration'];
                if ($duration < 1 || $duration > 480) {
                    $errors[] = "Video " . ($index + 1) . ": Duration must be between 1-480 minutes";
                }
            }
        }
    }

    // Validate description
    if (isset($data['description']) && strlen($data['description']) > 1000) {
        $errors[] = 'Description must be less than 1000 characters';
    }

    // Validate access control
    if (!isset($data['allowedStudentTypes']) || empty($data['allowedStudentTypes'])) {
        $errors[] = 'At least one student type must be allowed';
    } else {
        $validTypes = ['all', 'registered', 'senior1', 'senior2'];
        foreach ($data['allowedStudentTypes'] as $type) {
            if (!in_array($type, $validTypes)) {
                $errors[] = 'Invalid student type: ' . $type;
                break;
            }
        }
    }

    // Validate max views
    if (isset($data['maxViews']) && $data['maxViews'] !== null) {
        $maxViews = (int)$data['maxViews'];
        if ($maxViews < 1 || $maxViews > 1000) {
            $errors[] = 'Maximum views must be between 1-1000';
        }
    }

    // Validate dates
    if (isset($data['publishDate']) && !empty($data['publishDate'])) {
        $publishTimestamp = strtotime($data['publishDate']);
        if (!$publishTimestamp) {
            $errors[] = 'Invalid publish date format';
        }
    }

    if (isset($data['expiryDate']) && !empty($data['expiryDate'])) {
        $expiryTimestamp = strtotime($data['expiryDate']);
        if (!$expiryTimestamp) {
            $errors[] = 'Invalid expiry date format';
        } elseif (isset($publishTimestamp) && $expiryTimestamp <= $publishTimestamp) {
            $errors[] = 'Expiry date must be after publish date';
        }
    }

    // Validate difficulty
    if (isset($data['difficulty']) && !in_array($data['difficulty'], ['beginner', 'intermediate', 'advanced'])) {
        $errors[] = 'Invalid difficulty level';
    }

    // Validate tags
    if (isset($data['tags']) && is_array($data['tags'])) {
        if (count($data['tags']) > 10) {
            $errors[] = 'Maximum 10 tags allowed';
        }
        foreach ($data['tags'] as $tag) {
            if (strlen(trim($tag)) > 50) {
                $errors[] = 'Each tag must be less than 50 characters';
                break;
            }
        }
    }

    return $errors;
}

function createSession($data) {
    try {
        error_log('createSession called with title: ' . ($data['title'] ?? 'NO TITLE'));
        
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Validate session data
        $validationErrors = validateSessionData($data);
        if (!empty($validationErrors)) {
            error_log('createSession validation errors: ' . json_encode($validationErrors));
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ]);
            return;
        }

        // Sanitize and prepare session data
        $sessionId = new MongoDB\BSON\ObjectId();
        $sessionData = [
            '_id' => $sessionId,
            'title' => trim($data['title']),
            'subject' => $data['subject'],
            'grade' => $data['grade'],
            'teacher' => $data['teacher'],
            'description' => isset($data['description']) ? trim($data['description']) : '',
            'sessionNumber' => isset($data['sessionNumber']) ? (int)$data['sessionNumber'] : null,
            'accessControl' => $data['accessControl'] ?? 'free',
            'videos' => $data['videos'] ?? [],
            'pdfFiles' => $data['pdfFiles'] ?? [],
            'tags' => $data['tags'] ?? [],
            'difficulty' => $data['difficulty'] ?? 'intermediate',
            'status' => $data['status'] ?? 'draft',
            'isPublished' => $data['isPublished'] ?? false,
            'isFeatured' => $data['isFeatured'] ?? false,
            'publishDate' => isset($data['publishDate']) && !empty($data['publishDate']) ?
                new MongoDB\BSON\UTCDateTime(strtotime($data['publishDate']) * 1000) : null,
            'expiryDate' => isset($data['expiryDate']) && !empty($data['expiryDate']) ?
                new MongoDB\BSON\UTCDateTime(strtotime($data['expiryDate']) * 1000) : null,
            'maxViews' => isset($data['maxViews']) && $data['maxViews'] !== null ? (int)$data['maxViews'] : null,
            'downloadable' => $data['downloadable'] ?? true,
            'allowedStudentTypes' => $data['allowedStudentTypes'] ?? ['all'],
            'views' => 0,
            'downloads' => 0,
            'rating' => 0,
            'ratingCount' => 0,
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime(),
            'createdBy' => $data['createdBy'] ?? 'admin',
            'isActive' => true
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($sessionData);
        $result = $client->executeBulkWrite("$databaseName.online_sessions", $bulk);

        if ($result->getInsertedCount() > 0) {
            error_log('createSession SUCCESS: Inserted session with ID: ' . (string)$sessionId);
            echo json_encode([
                'success' => true,
                'message' => 'Session created successfully!',
                'sessionId' => (string)$sessionId
            ]);
        } else {
            error_log('createSession FAILED: getInsertedCount returned 0');
            echo json_encode(['success' => false, 'message' => 'Session creation failed']);
        }

    } catch (Exception $e) {
        error_log('createSession Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Session creation error: ' . $e->getMessage()]);
    }
}

function uploadSession($data) {
    // Handle file uploads for videos, thumbnails, and PDFs
    try {
        $db = new DatabaseMongo();
        $videoManager = new Video($db);
        
        $uploadDir = __DIR__ . '/../uploads/sessions/';

        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Parse gradeSubject field (e.g., "senior2-mathematics" -> grade="senior2", subject="mathematics")
        $gradeSubject = $_POST['gradeSubject'] ?? '';
        $gradeParts = explode('-', $gradeSubject);
        $grade = $gradeParts[0] ?? '';
        $subject = $gradeParts[1] ?? '';

        // Collect session data from POST
        $sessionData = [
            'title' => $_POST['sessionTitle'] ?? '',
            'subject' => $subject,
            'grade' => $grade,
            'teacher' => $_POST['teacher'] ?? '',
            'description' => $_POST['description'] ?? '',
            'sessionNumber' => !empty($_POST['sessionNumber']) ? (int)$_POST['sessionNumber'] : null,
            'accessControl' => $_POST['accessControl'] ?? 'free',
            'maxViews' => !empty($_POST['maxViews']) ? (int)$_POST['maxViews'] : null,
            'publishDate' => $_POST['publishDate'] ?? null,
            'expiryDate' => $_POST['expiryDate'] ?? null,
            'status' => $_POST['isPublished'] ?? 'draft',
            'isPublished' => ($_POST['isPublished'] ?? 'draft') === 'published',
            'allowedStudentTypes' => ['all'],  // Allow all student types (access controlled by sessionNumber)
            'videos' => [],
            'createdBy' => 'admin', // TODO: Get from session
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
        ];
        
        // Determine year from grade
        $yearMap = ['senior1' => 1, 'senior2' => 2, 'senior3' => 3];
        $sessionData['year'] = $yearMap[$sessionData['grade']] ?? null;

        // Validate required fields
        $errors = [];
        if (empty($sessionData['title']) || strlen($sessionData['title']) < 3) {
            $errors[] = 'Session title must be at least 3 characters long';
        }
        if (empty($sessionData['subject'])) {
            $errors[] = 'Grade & Subject is required';
        }
        if (empty($sessionData['grade'])) {
            $errors[] = 'Grade & Subject is required';
        }
        if (empty($sessionData['teacher'])) {
            $errors[] = 'Teacher is required';
        }
        if ($sessionData['sessionNumber'] === null) {
            $errors[] = 'Session Number is required';
        }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
            return;
        }

        // Get contentType from form (session-level, not per-video)
        $contentType = $_POST['contentType'] ?? 'lecture';
        
        // Set status based on contentType
        if ($contentType === 'homework') {
            $sessionData['status'] = 'homework';
        } else {
            $sessionData['status'] = $_POST['isPublished'] ?? 'draft';
        }
        
        // Get video form data
        $videoTitles = $_POST['videoTitle'] ?? [];
        $videoDescriptions = $_POST['videoDescription'] ?? [];
        $videoDurations = $_POST['duration'] ?? [];
        $videoSources = $_POST['videoSource'] ?? [];
        $videoLinks = $_POST['videoLink'] ?? [];
        
        // Determine number of videos - use the longest array or files count
        $fileCount = (isset($_FILES['videoFile']) && is_array($_FILES['videoFile']['name'])) ? count($_FILES['videoFile']['name']) : 0;
        $videoCount = max($fileCount, count($videoTitles), count($videoSources), count($videoLinks));
        
        // Process each video entry
        for ($index = 0; $index < $videoCount; $index++) {
            $isUpload = ($videoSources[$index] ?? 'upload') === 'upload';
            $fileName = $_FILES['videoFile']['name'][$index] ?? '';
            $videoLink = $videoLinks[$index] ?? '';
            
            // Skip empty entries
            if (empty($fileName) && empty($videoLink)) {
                continue;
            }
            
            if ($isUpload && !empty($fileName) && isset($_FILES['videoFile']['error'][$index]) && $_FILES['videoFile']['error'][$index] === UPLOAD_ERR_OK) {
                // Handle file upload using Video class
                $videoFile = [
                    'name' => $_FILES['videoFile']['name'][$index],
                    'type' => $_FILES['videoFile']['type'][$index],
                    'tmp_name' => $_FILES['videoFile']['tmp_name'][$index],
                    'error' => $_FILES['videoFile']['error'][$index],
                    'size' => $_FILES['videoFile']['size'][$index]
                ];

                $videoMetadata = [
                    'title' => $videoTitles[$index] ?? 'Video ' . ($index + 1),
                    'description' => $videoDescriptions[$index] ?? '',
                    'video_type' => $contentType,
                    'duration_seconds' => !empty($videoDurations[$index]) ? (int)$videoDurations[$index] * 60 : null,
                    'subject_id' => $sessionData['subject'],
                    'uploaded_by' => $sessionData['createdBy']
                ];

                $uploadResult = $videoManager->upload($videoFile, $videoMetadata);

                if (!$uploadResult['success']) {
                    echo json_encode(['success' => false, 'message' => 'Video upload failed: ' . $uploadResult['message']]);
                    return;
                }

                $sessionData['videos'][] = [
                    'video_id' => $uploadResult['video_id'],
                    'title' => $videoMetadata['title'],
                    'type' => $contentType,
                    'description' => $videoMetadata['description'],
                    'duration' => $videoMetadata['duration_seconds'],
                    'source' => 'upload',
                    'file_path' => $uploadResult['file_path']
                ];

            } elseif (!$isUpload && !empty($videoLink)) {
                // Handle video link
                $sessionData['videos'][] = [
                    'video_id' => null,
                    'title' => $videoTitles[$index] ?? 'Video ' . ($index + 1),
                    'type' => $contentType,
                    'description' => $videoDescriptions[$index] ?? '',
                    'duration' => !empty($videoDurations[$index]) ? (int)$videoDurations[$index] * 60 : null,
                    'url' => $videoLink,
                    'source' => 'link'
                ];
            }
        }

        // Validate at least one video
        if (empty($sessionData['videos'])) {
            echo json_encode(['success' => false, 'message' => 'At least one video is required']);
            return;
        }

        // Create the session using createSession function
        error_log('uploadSession: About to call createSession with data: ' . json_encode([
            'title' => $sessionData['title'],
            'subject' => $sessionData['subject'],
            'grade' => $sessionData['grade'],
            'sessionNumber' => $sessionData['sessionNumber'],
            'video_count' => count($sessionData['videos'])
        ]));
        
        createSession($sessionData);

    } catch (Exception $e) {
        error_log('uploadSession Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $e->getMessage()]);
    }
}

function getSession() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $sessionId = $_GET['id'] ?? '';
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Session ID required']);
            return;
        }

        $filter = ['_id' => new MongoDB\BSON\ObjectId($sessionId), 'isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.online_sessions", $query);
        $session = current($cursor->toArray());

        if ($session) {
            $sessionArray = convertSessionToArray($session);
            echo json_encode(['success' => true, 'session' => $sessionArray]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Session not found']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching session: ' . $e->getMessage()]);
    }
}

function getAllSessions() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Build filter based on query parameters
        // Allow fetching inactive sessions for testing, but default to active only
        $filter = [];
        
        if (!isset($_GET['includeInactive']) || $_GET['includeInactive'] !== 'true') {
            $filter['isActive'] = true;
            error_log('getAllSessions: Filtering by isActive=true');
        } else {
            error_log('getAllSessions: Including inactive sessions');
        }

        if (isset($_GET['subject']) && $_GET['subject'] !== '') {
            $normalizedSubject = normalizeSubject($_GET['subject']);
            $filter['subject'] = $normalizedSubject;
            error_log('getAllSessions: Filtering by subject=' . $normalizedSubject . ' (original: ' . $_GET['subject'] . ')');
        }

        if (isset($_GET['grade']) && $_GET['grade'] !== '') {
            $filter['grade'] = $_GET['grade'];
        }

        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filter['status'] = $_GET['status'];
        }

        if (isset($_GET['teacher']) && $_GET['teacher'] !== '') {
            $filter['teacher'] = $_GET['teacher'];
        }

        $options = [
            'sort' => ['createdAt' => -1]
        ];

        // Add pagination
        if (isset($_GET['limit'])) {
            $options['limit'] = (int)$_GET['limit'];
        }

        if (isset($_GET['skip'])) {
            $options['skip'] = (int)$_GET['skip'];
        }

        error_log('getAllSessions: Filter = ' . json_encode($filter));
        
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.online_sessions", $query);

        $sessions = [];
        foreach ($cursor as $session) {
            $sessions[] = convertSessionToArray($session);
        }

        error_log('getAllSessions: Found ' . count($sessions) . ' sessions');
        
        echo json_encode([
            'success' => true,
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);

    } catch (Exception $e) {
        error_log('getAllSessions Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching sessions: ' . $e->getMessage()]);
    }
}

function getSessionStats() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Get total sessions count
        $filter = ['isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.online_sessions", $query);
        $totalSessions = count($cursor->toArray());

        // Get published sessions count
        $filter['status'] = 'published';
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.online_sessions", $query);
        $publishedSessions = count($cursor->toArray());

        // Get total views
        $pipeline = [
            ['$match' => ['isActive' => true]],
            ['$group' => ['_id' => null, 'totalViews' => ['$sum' => '$views'], 'totalDownloads' => ['$sum' => '$downloads']]]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'sessions',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $client->executeCommand($databaseName, $command);
        $statsResult = current($cursor->toArray());

        $stats = [
            'totalSessions' => $totalSessions,
            'publishedSessions' => $publishedSessions,
            'draftSessions' => $totalSessions - $publishedSessions,
            'totalViews' => $statsResult->totalViews ?? 0,
            'totalDownloads' => $statsResult->totalDownloads ?? 0
        ];

        echo json_encode(['success' => true, 'stats' => $stats]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching stats: ' . $e->getMessage()]);
    }
}

function updateSession($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID is required']);
            return;
        }

        $sessionId = $data['id'];
        
        // Validate ObjectId format
        if (!preg_match('/^[a-f0-9]{24}$/i', $sessionId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID format']);
            return;
        }
        
        unset($data['id']);

        // Add updated timestamp
        $data['updatedAt'] = new MongoDB\BSON\UTCDateTime();

        // Handle boolean fields
        if (isset($data['isPublished'])) {
            $data['isPublished'] = (bool) $data['isPublished'];
        }

        // Convert date strings to UTCDateTime if present
        if (isset($data['publishDate'])) {
            $data['publishDate'] = new MongoDB\BSON\UTCDateTime(strtotime($data['publishDate']) * 1000);
        }

        if (isset($data['expiryDate'])) {
            $data['expiryDate'] = new MongoDB\BSON\UTCDateTime(strtotime($data['expiryDate']) * 1000);
        }

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($sessionId)],
            ['$set' => $data]
        );

        $result = $client->executeBulkWrite("$databaseName.online_sessions", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Session updated successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update error: ' . $e->getMessage()]);
    }
}

function publishSession($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID required']);
            return;
        }

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
            ['$set' => [
                'status' => 'published',
                'isPublished' => true,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $result = $client->executeBulkWrite("$databaseName.online_sessions", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Session published successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Publish error: ' . $e->getMessage()]);
    }
}

function unpublishSession($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID required']);
            return;
        }

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
            ['$set' => [
                'status' => 'draft',
                'isPublished' => false,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $result = $client->executeBulkWrite("$databaseName.online_sessions", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Session unpublished successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Unpublish error: ' . $e->getMessage()]);
    }
}

function deleteSession($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID is required']);
            return;
        }

        $id = $data['id'];
        
        // Validate ObjectId format
        if (!preg_match('/^[a-f0-9]{24}$/i', $id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID format']);
            return;
        }

        // Soft delete - mark as inactive
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => [
                'isActive' => false,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $result = $client->executeBulkWrite("$databaseName.online_sessions", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Session deleted successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Delete error: ' . $e->getMessage()]);
    }
}

function checkStudentSessionAccess() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Support multiple parameter types
        $phone = $_GET['phone'] ?? '';
        $sessionNumber = (int)($_GET['sessionNumber'] ?? $_GET['session_number'] ?? 0);
        $sessionId = $_GET['sessionId'] ?? $_GET['session_id'] ?? '';
        $subject = $_GET['subject'] ?? '';
        $grade = $_GET['grade'] ?? '';

        error_log('=== checkStudentSessionAccess ===');
        error_log('Phone: ' . $phone);
        error_log('Session Number: ' . $sessionNumber);

        // Case 1: Check by session_number (e.g., Session 13)
        if ($sessionNumber && $phone) {
            // Generate phone number variations for searching
            // Student input: 01280912038
            // Database has: +201280912038
            $phoneVariations = [
                $phone,  // Original as provided
                normalizePhoneNumber($phone),  // Normalized to 01... format
                convertTo20Format($phone),  // Converted to +20... format
            ];
            
            // Remove duplicates and empty values
            $phoneVariations = array_values(array_unique(array_filter($phoneVariations)));
            
            error_log('=== checkStudentSessionAccess ===');
            error_log('Phone input: ' . $phone);
            error_log('Phone variations: ' . implode(' | ', $phoneVariations));
            error_log('Session Number: ' . $sessionNumber);
            error_log('Subject: ' . $subject);
            error_log('Grade: ' . $grade);
            
            $student = null;
            $foundInCollection = null;

            // Map subject/grade to physical collection for precise security
            $targetCollection = null;
            $normSubject = normalizeSubject($subject);
            $normGrade = strtolower(trim($grade));
            
            // Define precise mapping
            if ($normGrade === 'senior1') {
                if ($normSubject === 'mathematics') $targetCollection = 'senior1_math';
            } elseif ($normGrade === 'senior2') {
                if ($normSubject === 'mathematics') $targetCollection = 'senior2_pure_math';
                elseif ($normSubject === 'mechanics') $targetCollection = 'senior2_mechanics';
                elseif ($normSubject === 'physics') $targetCollection = 'senior2_physics';
            } elseif ($normGrade === 'senior3') {
                if ($normSubject === 'mathematics') $targetCollection = 'senior3_math';
                elseif ($normSubject === 'physics') $targetCollection = 'senior3_physics';
                elseif ($normSubject === 'statistics') $targetCollection = 'senior3_statistics';
            }

            $collectionsToTry = [];
            if ($targetCollection) {
                $collectionsToTry[] = $targetCollection;
                error_log('Targeting specific collection: ' . $targetCollection);
            } else {
                // Fallback for older links or imprecise parameters
                error_log('No specific target collection found. Falling back to broad search.');
                $collectionsToTry = ['all_students_view', 'senior1_math', 'senior2_pure_math', 'senior2_mechanics', 'senior2_physics', 'senior3_math', 'senior3_physics', 'senior3_statistics'];
            }
            
            // Try each collection and phone variation
            foreach ($collectionsToTry as $collection) {
                foreach ($phoneVariations as $phoneVariation) {
                    $studentFilter = ['phone' => $phoneVariation];
                    
                    // If subject is provided and we are checking a broad collection, add subject filter
                    if ($subject && in_array($collection, ['all_students_view', 'users', 'students'])) {
                        $studentFilter['subject'] = ['$regex' => $subject, '$options' => 'i'];
                    }

                    $query = new MongoDB\Driver\Query($studentFilter);
                    
                    try {
                        $cursor = $client->executeQuery("$databaseName." . $collection, $query);
                        $student = current($cursor->toArray());
                        
                        if ($student) {
                            $sessionKey = 'session_' . $sessionNumber;
                            if (isset($student->$sessionKey)) {
                                $foundInCollection = $collection;
                                error_log('  âœ“ Found student with session in: ' . $collection);
                                break 2; 
                            } else {
                                $student = null; // Continue searching
                            }
                        }
                    } catch (Exception $e) {
                        error_log('  Error querying ' . $collection . ': ' . $e->getMessage());
                    }
                }
            }

            if (!$student) {
                error_log('âœ— Access Denied: Student or Session record not found');
                echo json_encode([
                    'success' => false,
                    'hasAccess' => false,
                    'message' => 'Student not found or not enrolled in this session'
                ]);
                return;
            }

            error_log('âœ“ Student found in collection: ' . $foundInCollection);

            // Check if student has purchased this session number
            // Structure: session_13.online_session = true
            $sessionKey = 'session_' . $sessionNumber;
            $hasAccess = false;

            if (isset($student->$sessionKey)) {
                $studentSession = $student->$sessionKey;
                error_log('  Found session key: ' . $sessionKey);
                error_log('  Session type: ' . gettype($studentSession));
                
                // Session is an object with online_session field
                if (is_object($studentSession) && isset($studentSession->online_session)) {
                    $hasAccess = (bool)$studentSession->online_session === true;
                    error_log('  online_session value: ' . ($studentSession->online_session ? 'true' : 'false'));
                } 
                // Session might be an array
                elseif (is_array($studentSession) && isset($studentSession['online_session'])) {
                    $hasAccess = (bool)$studentSession['online_session'] === true;
                    error_log('  online_session value: ' . ($studentSession['online_session'] ? 'true' : 'false'));
                }
                // Session might be boolean directly
                elseif ($studentSession === true || $studentSession === 1 || $studentSession === 'true') {
                    $hasAccess = true;
                    error_log('  Session value is truthy');
                }
            }

            error_log('Access result: ' . ($hasAccess ? 'âœ“ GRANTED' : 'âœ— DENIED'));

            // Update online_attendance if access is granted and it's currently false
            if ($hasAccess && $foundInCollection) {
                $shouldUpdate = false;
                
                if (isset($student->$sessionKey)) {
                    $studentSession = $student->$sessionKey;
                    $currentOnlineAttendance = false;
                    
                    if (is_object($studentSession) && isset($studentSession->online_attendance)) {
                        $currentOnlineAttendance = (bool)$studentSession->online_attendance;
                    } elseif (is_array($studentSession) && isset($studentSession['online_attendance'])) {
                        $currentOnlineAttendance = (bool)$studentSession['online_attendance'];
                    }
                    
                    error_log('  Current online_attendance: ' . ($currentOnlineAttendance ? 'true' : 'false'));
                    
                    if (!$currentOnlineAttendance) {
                        $shouldUpdate = true;
                    }
                }
                
                if ($shouldUpdate) {
                    error_log('  ðŸ”„ Updating online_attendance to true for session ' . $sessionNumber);
                    
                    $updateFields = [
                        $sessionKey . '.online_attendance' => true,
                        $sessionKey . '.online_attendance_completed_at' => date('Y-m-d\TH:i:s.v\Z')
                    ];
                    
                    try {
                        $bulk = new MongoDB\Driver\BulkWrite();
                        $bulk->update(
                            ['phone' => ['$in' => $phoneVariations]],
                            ['$set' => $updateFields],
                            ['multi' => false]
                        );
                        $client->executeBulkWrite("$databaseName." . $foundInCollection, $bulk);
                        
                        // Also update students collection
                        if ($foundInCollection !== 'students') {
                            $bulk2 = new MongoDB\Driver\BulkWrite();
                            $bulk2->update(
                                ['phone' => ['$in' => $phoneVariations], 'subject' => $student->subject],
                                ['$set' => $updateFields],
                                ['multi' => true]
                            );
                            $client->executeBulkWrite("$databaseName.students", $bulk2);
                        }
                    } catch (Exception $e) {
                        error_log('  âŒ Update error: ' . $e->getMessage());
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'hasAccess' => $hasAccess,
                'message' => $hasAccess ? 'Access granted' : ($foundInCollection ? 'No subscription for this session' : 'Student not found or not enrolled in this subject'),
                'sessionNumber' => $sessionNumber,
                'phone' => $phone,
                'student' => $student ? [
                    'name' => $student->studentName ?? 'Student',
                    'subject' => $student->subject ?? '',
                    'grade' => $student->grade ?? '',
                    'balance' => $student->balance ?? 0,
                    'paymentAmount' => $student->paymentAmount ?? 80
                ] : null
            ]);
            return;
        }

        // Case 2: Check by session_id
        if ($sessionId && $phone) {
            // Find student by phone
            $studentFilter = ['phone' => $phone];
            $query = new MongoDB\Driver\Query($studentFilter);
            $cursor = $client->executeQuery("$databaseName.students", $query);
            $student = current($cursor->toArray());

            if (!$student) {
                echo json_encode(['success' => false, 'hasAccess' => false, 'message' => 'Student not found']);
                return;
            }

            // Get the session by ID
            $sessionFilter = ['_id' => new MongoDB\BSON\ObjectId($sessionId)];
            $sessionQuery = new MongoDB\Driver\Query($sessionFilter);
            $sessionCursor = $client->executeQuery("$databaseName.online_sessions", $sessionQuery);
            $session = current($sessionCursor->toArray());

            if (!$session) {
                echo json_encode(['success' => false, 'hasAccess' => false, 'message' => 'Session not found']);
                return;
            }

            $hasAccess = false;
            
            // Check by session number if available
            if (isset($session->sessionNumber)) {
                $sessionKey = 'session_' . $session->sessionNumber;
                if (isset($student->$sessionKey)) {
                    $studentSession = $student->$sessionKey;
                    if (is_object($studentSession)) {
                        $hasAccess = isset($studentSession->online_session) && $studentSession->online_session === true;
                    } elseif (is_array($studentSession)) {
                        $hasAccess = isset($studentSession['online_session']) && $studentSession['online_session'] === true;
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'hasAccess' => $hasAccess,
                'message' => $hasAccess ? 'Access granted' : 'No subscription for this session',
                'sessionId' => $sessionId,
                'phone' => $phone,
                'student' => [
                    'name' => $student->studentName ?? 'Student',
                    'subject' => $student->subject ?? '',
                    'grade' => $student->grade ?? '',
                    'balance' => $student->balance ?? 0,
                    'paymentAmount' => $student->paymentAmount ?? 80
                ]
            ]);
            return;
        }

        // Original logic for backward compatibility
        if (!$phone && !$sessionNumber && !$subject && !$grade) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        // Try both studentId and phone number
        $studentFilter = [
            '$or' => [
                ['phone' => $phone],
                ['phone' => normalizePhoneNumber($phone)],
                ['phone' => convertTo20Format($phone)]
            ],
            'subject' => $subject,
            'isActive' => true
        ];
        $query = new MongoDB\Driver\Query($studentFilter);
        $cursor = $client->executeQuery("$databaseName.all_students_view", $query);
        $student = current($cursor->toArray());

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found or inactive']);
            return;
        }

        // Check if student has the specific session number
        $sessionKey = 'session_' . $sessionNumber;
        if (!isset($student->$sessionKey)) {
            echo json_encode(['success' => false, 'message' => 'Session not found for this student']);
            return;
        }

        $studentSession = $student->$sessionKey;

        // Check if online_session is true
        $hasAccess = isset($studentSession->online_session) && $studentSession->online_session === true;

        // Check if session is not expired (if there's an expiry date)
        $isExpired = false;
        if (isset($studentSession->date)) {
            $sessionDate = strtotime($studentSession->date);
            $currentDate = time();
            // Allow access up to 30 days after session date
            $isExpired = ($currentDate - $sessionDate) > (30 * 24 * 60 * 60);
        }

        // Get the online session content if available
        $sessionContent = null;
        $sessionQueryDebug = '';
        if ($hasAccess && !$isExpired) {
            $sessionFilter = [
                'subject' => $subject,
                'grade' => $grade,
                'sessionNumber' => $sessionNumber,
                'isActive' => true,
                'isPublished' => true
            ];
            $sessionQueryDebug = 'Filter: ' . json_encode($sessionFilter);
            error_log('Session query: ' . $sessionQueryDebug);
            
            $sessionQuery = new MongoDB\Driver\Query($sessionFilter);
            $sessionCursor = $client->executeQuery("$databaseName.online_sessions", $sessionQuery);
            $session = current($sessionCursor->toArray());

            if ($session) {
                error_log('âœ“ Session found with isPublished=true');
                $sessionContent = convertSessionToArray($session);
            } else {
                error_log('âœ— Session NOT found. Query was: ' . $sessionQueryDebug);
                // Try again without isPublished filter for debugging
                $sessionFilter2 = [
                    'subject' => $subject,
                    'grade' => $grade,
                    'sessionNumber' => $sessionNumber,
                    'isActive' => true
                ];
                $sessionQuery2 = new MongoDB\Driver\Query($sessionFilter2);
                $sessionCursor2 = $client->executeQuery("$databaseName.online_sessions", $sessionQuery2);
                $session2 = current($sessionCursor2->toArray());
                if ($session2) {
                    error_log('  Session found without isPublished filter. isPublished value: ' . var_export($session2->isPublished ?? 'NOT SET', true));
                }
            }
        }

        echo json_encode([
            'success' => true,
            'hasAccess' => $hasAccess && !$isExpired,
            'isExpired' => $isExpired,
            'sessionData' => $studentSession,
            'sessionContent' => $sessionContent,
            'student' => [
                'name' => $student->studentName ?? 'Student',
                'subject' => $student->subject ?? '',
                'grade' => $student->grade ?? '',
                'balance' => $student->balance ?? 0,
                'paymentAmount' => $student->paymentAmount ?? 80
            ]
        ]);

    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Access check error: ' . $e->getMessage()]);
    }
}

function convertSessionToArray($session) {
    // Convert videos array to ensure proper JSON serialization
    $videos = [];
    if (isset($session->videos) && is_array($session->videos)) {
        foreach ($session->videos as $video) {
            $videos[] = convertVideoToArray($video);
        }
    }
    
    return [
        'id' => (string)$session->_id,
        '_id' => (string)$session->_id,
        'title' => $session->title ?? '',
        'subject' => $session->subject ?? '',
        'grade' => $session->grade ?? '',
        'teacher' => $session->teacher ?? '',
        'description' => $session->description ?? '',
        'sessionNumber' => $session->sessionNumber ?? null,
        'accessControl' => $session->accessControl ?? 'free',
        'videos' => $videos,
        'pdfFiles' => $session->pdfFiles ?? [],
        'tags' => $session->tags ?? [],
        'difficulty' => $session->difficulty ?? 'intermediate',
        'status' => $session->status ?? 'draft',
        'isPublished' => $session->isPublished ?? false,
        'isFeatured' => $session->isFeatured ?? false,
        'publishDate' => isset($session->publishDate) ? formatMongoDate($session->publishDate) : null,
        'expiryDate' => isset($session->expiryDate) ? formatMongoDate($session->expiryDate) : null,
        'maxViews' => $session->maxViews ?? null,
        'downloadable' => $session->downloadable ?? true,
        'allowedStudentTypes' => $session->allowedStudentTypes ?? ['all'],
        'views' => $session->views ?? 0,
        'downloads' => $session->downloads ?? 0,
        'rating' => $session->rating ?? 0,
        'ratingCount' => $session->ratingCount ?? 0,
        'createdAt' => isset($session->createdAt) ? formatMongoDate($session->createdAt) : null,
        'updatedAt' => isset($session->updatedAt) ? formatMongoDate($session->updatedAt) : null,
        'createdBy' => $session->createdBy ?? '',
        'isActive' => $session->isActive ?? true
    ];
}

function convertVideoToArray($video) {
    if (is_array($video)) {
        // Ensure array has correct field names
        return [
            'video_id' => $video['video_id'] ?? null,
            'title' => $video['title'] ?? $video['video_title'] ?? '',
            'type' => $video['type'] ?? 'lecture',
            'description' => $video['description'] ?? '',
            'duration' => $video['duration'] ?? null,
            'url' => $video['url'] ?? $video['video_file_path'] ?? null,
            'source' => $video['source'] ?? 'upload'
        ];
    }
    
    // Handle object (from MongoDB)
    return [
        'video_id' => $video->video_id ?? null,
        'title' => $video->title ?? $video->video_title ?? '',
        'type' => $video->type ?? 'lecture',
        'description' => $video->description ?? '',
        'duration' => $video->duration ?? null,
        'url' => $video->url ?? $video->video_file_path ?? null,
        'source' => $video->source ?? 'upload'
    ];
}

function formatMongoDate($mongoDate) {
    try {
        if ($mongoDate instanceof MongoDB\BSON\UTCDateTime) {
            return $mongoDate->toDateTime()->format('c');
        } elseif ($mongoDate instanceof DateTime) {
            return $mongoDate->format('c');
        } else {
            return (string)$mongoDate;
        }
    } catch (Exception $e) {
        error_log('Error formatting date: ' . $e->getMessage());
        return null;
    }
}

function purchaseStudentSession() {

    try {

        $client = $GLOBALS['mongoClient'];

        $databaseName = $GLOBALS['databaseName'];



        $phone = $_GET['phone'] ?? '';

        $sessionNumber = (int)($_GET['sessionNumber'] ?? $_GET['session_number'] ?? 0);

        $subject = $_GET['subject'] ?? '';

        $grade = $_GET['grade'] ?? '';



        if (!$phone || !$sessionNumber || !$subject || !$grade) {

            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);

            return;

        }



        // Map subject/grade to physical collection

        $targetCollection = null;

        $normSubject = normalizeSubject($subject);

        $normGrade = strtolower(trim($grade));

        

        if ($normGrade === 'senior1') {

            if ($normSubject === 'mathematics') $targetCollection = 'senior1_math';

        } elseif ($normGrade === 'senior2') {

            if ($normSubject === 'mathematics') $targetCollection = 'senior2_pure_math';

            elseif ($normSubject === 'mechanics') $targetCollection = 'senior2_mechanics';

            elseif ($normSubject === 'physics') $targetCollection = 'senior2_physics';

        } elseif ($normGrade === 'senior3') {

            if ($normSubject === 'mathematics') $targetCollection = 'senior3_math';

            elseif ($normSubject === 'physics') $targetCollection = 'senior3_physics';

            elseif ($normSubject === 'statistics') $targetCollection = 'senior3_statistics';

        }



        if (!$targetCollection) {

            echo json_encode(['success' => false, 'message' => 'Invalid subject or grade (' . $grade . ' / ' . $subject . ')']);

            return;

        }


        // Generate phone variations
        $phoneVariations = [
            $phone,
            normalizePhoneNumber($phone),
            convertTo20Format($phone),
        ];
        $phoneVariations = array_values(array_unique(array_filter($phoneVariations)));

        // 1. Find the student in the target collection
        $query = new MongoDB\Driver\Query(['phone' => ['$in' => $phoneVariations], 'isActive' => true]);
        $cursor = $client->executeQuery("$databaseName.$targetCollection", $query);

        $student = current($cursor->toArray());



        if (!$student) {

            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this (' . $subject . ') subject for ' . $grade . '. Please contact support.']);

            return;

        }



        // 2. Check balance and cost

        $balance = isset($student->balance) ? (float)$student->balance : 0;

        $cost = isset($student->paymentAmount) ? (float)$student->paymentAmount : 80;



        if ($balance < $cost) {

            echo json_encode([

                'success' => false, 

                'message' => 'Insufficient balance. You need ' . $cost . ' EGP but only have ' . $balance . ' EGP.',

                'balance' => $balance,

                'cost' => $cost

            ]);

            return;

        }



        $sessionKey = 'session_' . $sessionNumber;

        

        // 3. Perform the purchase (Deduct balance and Grant access)

        $bulk = new MongoDB\Driver\BulkWrite();

        $bulk->update(

            ['_id' => $student->_id],

            ['$inc' => ['balance' => -$cost], '$set' => [

                $sessionKey . '.online_session' => true,

                $sessionKey . '.purchased_at' => date('Y-m-d\TH:i:s.v\Z'),

                $sessionKey . '.attendanceStatus' => 'absence'

            ]],

            ['multi' => false]

        );

        $client->executeBulkWrite("$databaseName.$targetCollection", $bulk);



        // 4. Record Transaction

        $transactionBulk = new MongoDB\Driver\BulkWrite();

        $transactionBulk->insert([

            'studentId' => $student->studentId ?? null,

            'studentName' => $student->studentName ?? 'Student',

            'subject' => $subject . ' (' . $grade . ')',

            'type' => 'online_purchase',

            'amount' => $cost,

            'previousBalance' => $balance,

            'newBalance' => $balance - $cost,

            'note' => 'Automatic purchase for Session #' . $sessionNumber . ' (Online)',

            'recordedBy' => 'system_online_purchase',

            'createdAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)

        ]);

        $client->executeBulkWrite("$databaseName.transactions", $transactionBulk);



        // 5. Sync to students collection
        $syncBulk = new MongoDB\Driver\BulkWrite();
        $syncBulk->update(
            ['phone' => ['$in' => $phoneVariations], 'subject' => ['$regex' => $subject, '$options' => 'i']],
            ['$inc' => ['balance' => -$cost], '$set' => [
                $sessionKey . '.online_session' => true,
                $sessionKey . '.purchased_at' => date('Y-m-d\TH:i:s.v\Z'),
                $sessionKey . '.attendanceStatus' => 'absence'
            ]],
            ['multi' => false]
        );

        $client->executeBulkWrite("$databaseName.students", $syncBulk);



        echo json_encode([

            'success' => true,

            'message' => 'Session purchased successfully!',

            'newBalance' => $balance - $cost

        ]);



    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Purchase error: ' . $e->getMessage()]);
    }
}

/**
 * Diagnostic function to check system status
 */
function runDiagnostics() {
    $diagnostics = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'mongodb_extension' => extension_loaded('mongodb') ? 'Loaded' : 'Not Loaded',
        'mongodb_driver_found' => class_exists('MongoDB\\Driver\\Manager') ? 'Yes' : 'No',
        'global_mongo_client' => $GLOBALS['mongoClient'] ? 'Connected' : 'Not Connected',
        'database_name' => $GLOBALS['databaseName'] ?? 'Not Set',
        'config_file_exists' => file_exists(dirname(__DIR__) . '/config/config.php') ? 'Yes' : 'No',
        'classes_exist' => [
            'DatabaseMongo' => class_exists('DatabaseMongo') ? 'Yes' : 'No',
            'Video' => class_exists('Video') ? 'Yes' : 'No',
            'SessionManager' => class_exists('SessionManager') ? 'Yes' : 'No'
        ]
    ];
    
    // Try to test MongoDB connection directly
    if (extension_loaded('mongodb') && class_exists('MongoDB\\Driver\\Manager')) {
        try {
            $testClient = new MongoDB\Driver\Manager(MONGO_URI);
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $testClient->executeCommand('admin', $command);
            $diagnostics['mongodb_direct_test'] = 'Success';
        } catch (Exception $e) {
            $diagnostics['mongodb_direct_test'] = 'Failed: ' . $e->getMessage();
        }
    }
    
    // Try to list sessions to verify database access
    if ($GLOBALS['mongoClient']) {
        try {
            $query = new MongoDB\Driver\Query(['isActive' => true], ['limit' => 1]);
            $cursor = $GLOBALS['mongoClient']->executeQuery($GLOBALS['databaseName'] . '.online_sessions', $query);
            $sessionCount = count($cursor->toArray());
            $diagnostics['database_access'] = "Success (found $sessionCount active sessions)";
        } catch (Exception $e) {
            $diagnostics['database_access'] = 'Failed: ' . $e->getMessage();
        }
    } else {
        $diagnostics['database_access'] = 'MongoDB client not available';
    }
    
    // Check required directories
    $diagnostics['directories'] = [
        'uploads' => is_writable(__DIR__ . '/../uploads') ? 'Writable' : 'Not Writable',
        'logs' => is_writable(__DIR__ . '/../logs') ? 'Writable' : 'Not Writable'
    ];
    
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
}
?>
