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
            getAllSessions();
            break;
        case 'stats':
            getSessionStats();
            break;
        case 'check-access':
            checkStudentSessionAccess();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'create':
            createSession($data);
            break;
        case 'upload':
            uploadSession($data);
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
    } elseif (!in_array($data['grade'], ['senior1', 'senior2'])) {
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
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Validate session data
        $validationErrors = validateSessionData($data);
        if (!empty($validationErrors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ]);
            return;
        }

        // Sanitize and prepare session data
        $sessionData = [
            'title' => trim($data['title']),
            'subject' => $data['subject'],
            'grade' => $data['grade'],
            'teacher' => $data['teacher'],
            'description' => isset($data['description']) ? trim($data['description']) : '',
            'sessionNumber' => isset($data['sessionNumber']) ? (int)$data['sessionNumber'] : null,
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
        $result = $client->executeBulkWrite("$databaseName.sessions", $bulk);

        if ($result->getInsertedCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Session created successfully!',
                'sessionId' => (string)$result->getInsertedIds()[0]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Session creation failed']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Session creation error: ' . $e->getMessage()]);
    }
}

function uploadSession($data) {
    // Handle file uploads for videos, thumbnails, and PDFs
    try {
        $uploadDir = '../uploads/sessions/';

        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedFiles = [];

        // Handle video files
        if (isset($_FILES['videos'])) {
            $videos = $_FILES['videos'];
            for ($i = 0; $i < count($videos['name']); $i++) {
                if ($videos['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . basename($videos['name'][$i]);
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($videos['tmp_name'][$i], $filepath)) {
                        $uploadedFiles['videos'][] = [
                            'filename' => $filename,
                            'originalName' => $videos['name'][$i],
                            'size' => $videos['size'][$i],
                            'type' => $videos['type'][$i]
                        ];
                    }
                }
            }
        }

        // Handle thumbnail files
        if (isset($_FILES['thumbnails'])) {
            $thumbnails = $_FILES['thumbnails'];
            for ($i = 0; $i < count($thumbnails['name']); $i++) {
                if ($thumbnails['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_thumb_' . basename($thumbnails['name'][$i]);
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($thumbnails['tmp_name'][$i], $filepath)) {
                        $uploadedFiles['thumbnails'][] = [
                            'filename' => $filename,
                            'originalName' => $thumbnails['name'][$i],
                            'size' => $thumbnails['size'][$i]
                        ];
                    }
                }
            }
        }

        // Handle PDF files
        if (isset($_FILES['pdfFiles'])) {
            $pdfs = $_FILES['pdfFiles'];
            for ($i = 0; $i < count($pdfs['name']); $i++) {
                if ($pdfs['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_pdf_' . basename($pdfs['name'][$i]);
                    $filepath = $uploadDir . $filename;

                    if (move_uploaded_file($pdfs['tmp_name'][$i], $filepath)) {
                        $uploadedFiles['pdfFiles'][] = [
                            'filename' => $filename,
                            'originalName' => $pdfs['name'][$i],
                            'size' => $pdfs['size'][$i]
                        ];
                    }
                }
            }
        }

        // Now create the session with file information
        $sessionData = json_decode($_POST['sessionData'], true);
        if ($sessionData) {
            // Add uploaded files to session data
            if (isset($uploadedFiles['videos'])) {
                foreach ($uploadedFiles['videos'] as $index => $video) {
                    if (isset($sessionData['videos'][$index])) {
                        $sessionData['videos'][$index]['file'] = $video;
                    }
                }
            }

            if (isset($uploadedFiles['thumbnails'])) {
                foreach ($uploadedFiles['thumbnails'] as $index => $thumbnail) {
                    if (isset($sessionData['videos'][$index])) {
                        $sessionData['videos'][$index]['thumbnail'] = $thumbnail;
                    }
                }
            }

            if (isset($uploadedFiles['pdfFiles'])) {
                $sessionData['pdfFiles'] = $uploadedFiles['pdfFiles'];
            }

            createSession($sessionData);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid session data']);
        }

    } catch (Exception $e) {
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
        $cursor = $client->executeQuery("$databaseName.sessions", $query);
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
        $filter = ['isActive' => true];

        if (isset($_GET['subject']) && $_GET['subject'] !== '') {
            $filter['subject'] = $_GET['subject'];
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

        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);

        $sessions = [];
        foreach ($cursor as $session) {
            $sessions[] = convertSessionToArray($session);
        }

        echo json_encode([
            'success' => true,
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);

    } catch (Exception $e) {
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
        $cursor = $client->executeQuery("$databaseName.sessions", $query);
        $totalSessions = count($cursor->toArray());

        // Get published sessions count
        $filter['status'] = 'published';
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.sessions", $query);
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

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID required']);
            return;
        }

        $sessionId = $data['id'];
        unset($data['id']);

        // Add updated timestamp
        $data['updatedAt'] = new MongoDB\BSON\UTCDateTime();

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

        $result = $client->executeBulkWrite("$databaseName.sessions", $bulk);

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

        $result = $client->executeBulkWrite("$databaseName.sessions", $bulk);

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

        $result = $client->executeBulkWrite("$databaseName.sessions", $bulk);

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

        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'Session ID required']);
            return;
        }

        // Soft delete - mark as inactive
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
            ['$set' => [
                'isActive' => false,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $result = $client->executeBulkWrite("$databaseName.sessions", $bulk);

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

        $studentId = $_GET['studentId'] ?? '';
        $sessionNumber = (int)($_GET['sessionNumber'] ?? 0);
        $subject = $_GET['subject'] ?? '';
        $grade = $_GET['grade'] ?? '';

        if (!$studentId || !$sessionNumber || !$subject || !$grade) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters: studentId, sessionNumber, subject, grade']);
            return;
        }

        // Check if student exists and has access to this session
        // Try both studentId and phone number
        $studentFilter = [
            '$or' => [
                ['studentId' => (int)$studentId],
                ['phone' => $studentId]
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
        if ($hasAccess && !$isExpired) {
            $sessionFilter = [
                'subject' => $subject,
                'grade' => $grade,
                'sessionNumber' => $sessionNumber,
                'isActive' => true,
                'isPublished' => true
            ];
            $sessionQuery = new MongoDB\Driver\Query($sessionFilter);
            $sessionCursor = $client->executeQuery("$databaseName.sessions", $sessionQuery);
            $session = current($sessionCursor->toArray());

            if ($session) {
                $sessionContent = convertSessionToArray($session);
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
                'grade' => $student->grade ?? ''
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Access check error: ' . $e->getMessage()]);
    }
}

function convertSessionToArray($session) {
    return [
        'id' => (string)$session->_id,
        'title' => $session->title ?? '',
        'subject' => $session->subject ?? '',
        'grade' => $session->grade ?? '',
        'teacher' => $session->teacher ?? '',
        'description' => $session->description ?? '',
        'sessionNumber' => $session->sessionNumber ?? null,
        'videos' => $session->videos ?? [],
        'pdfFiles' => $session->pdfFiles ?? [],
        'tags' => $session->tags ?? [],
        'difficulty' => $session->difficulty ?? 'intermediate',
        'status' => $session->status ?? 'draft',
        'isPublished' => $session->isPublished ?? false,
        'isFeatured' => $session->isFeatured ?? false,
        'publishDate' => $session->publishDate ? $session->publishDate->toDateTime()->format('c') : null,
        'expiryDate' => $session->expiryDate ? $session->expiryDate->toDateTime()->format('c') : null,
        'maxViews' => $session->maxViews ?? null,
        'downloadable' => $session->downloadable ?? true,
        'allowedStudentTypes' => $session->allowedStudentTypes ?? ['all'],
        'views' => $session->views ?? 0,
        'downloads' => $session->downloads ?? 0,
        'rating' => $session->rating ?? 0,
        'ratingCount' => $session->ratingCount ?? 0,
        'createdAt' => $session->createdAt ? $session->createdAt->toDateTime()->format('c') : null,
        'updatedAt' => $session->updatedAt ? $session->updatedAt->toDateTime()->format('c') : null,
        'createdBy' => $session->createdBy ?? '',
        'isActive' => $session->isActive ?? true
    ];
}
?>