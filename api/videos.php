<?php
/**
 * Video Management API Endpoint
 * Handles video upload, listing, and management
 */

header('Content-Type: application/json');
require_once '../includes/session_check.php';
require_once '../config/config.php';

$db = new DatabaseMongo();
$videoManager = new Video($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($videoManager);
            break;
            
        case 'POST':
            requireTeacher();
            handlePost($videoManager);
            break;
            
        case 'PUT':
            requireTeacher();
            handlePut($videoManager);
            break;
            
        case 'DELETE':
            requireAdmin();
            handleDelete($videoManager);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGet($videoManager) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all videos or filter by subject/lesson
            $filters = [];
            if (isset($_GET['subject_id'])) {
                $filters['subject_id'] = $_GET['subject_id'];
            }
            if (isset($_GET['uploaded_by'])) {
                $filters['uploaded_by'] = $_GET['uploaded_by'];
            }
            
            $limit = (int) ($_GET['limit'] ?? 100);
            $videos = $videoManager->getAll($filters, $limit);
            
            $result = [];
            foreach ($videos as $video) {
                $result[] = formatVideoForJSON($video);
            }
            
            echo json_encode([
                'success' => true,
                'videos' => $result,
                'count' => count($result)
            ]);
            break;
            
        case 'get':
            // Get specific video
            $videoId = $_GET['id'] ?? '';
            if (empty($videoId)) {
                throw new Exception('Video ID required');
            }
            
            $video = $videoManager->getById($videoId);
            if (!$video) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Video not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'video' => formatVideoForJSON($video)
            ]);
            break;
            
        case 'by_lesson':
            $lessonId = $_GET['lesson_id'] ?? '';
            if (empty($lessonId)) {
                throw new Exception('Lesson ID required');
            }
            
            $videos = $videoManager->getByLesson($lessonId);
            $result = array_map('formatVideoForJSON', $videos);
            
            echo json_encode([
                'success' => true,
                'videos' => $result
            ]);
            break;
            
        case 'by_subject':
            $subjectId = $_GET['subject_id'] ?? '';
            if (empty($subjectId)) {
                throw new Exception('Subject ID required');
            }
            
            $videos = $videoManager->getBySubject($subjectId);
            $result = array_map('formatVideoForJSON', $videos);
            
            echo json_encode([
                'success' => true,
                'videos' => $result
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests (upload)
 */
function handlePost($videoManager) {
    requireCSRFToken();
    
    if (!isset($_FILES['video'])) {
        throw new Exception('No video file provided');
    }
    
    $metadata = [
        'title' => $_POST['title'] ?? 'Untitled Video',
        'description' => $_POST['description'] ?? '',
        'subject_id' => $_POST['subject_id'] ?? null,
        'lesson_id' => $_POST['lesson_id'] ?? null,
        'uploaded_by' => getCurrentUserId(),
        'duration_seconds' => $_POST['duration'] ?? null
    ];
    
    $result = $videoManager->upload($_FILES['video'], $metadata);
    
    if ($result['success']) {
        logActivity('UPLOAD_VIDEO', 'video', $result['video_id'], 'Uploaded: ' . $metadata['title']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'video_id' => $result['video_id']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

/**
 * Handle PUT requests (update)
 */
function handlePut($videoManager) {
    parse_str(file_get_contents("php://input"), $data);
    
    $videoId = $data['video_id'] ?? '';
    if (empty($videoId)) {
        throw new Exception('Video ID required');
    }
    
    $updateData = [];
    if (isset($data['title'])) {
        $updateData['video_title'] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $updateData['video_description'] = sanitizeInput($data['description']);
    }
    
    $result = $videoManager->update($videoId, $updateData);
    
    echo json_encode([
        'success' => $result > 0,
        'message' => $result > 0 ? 'Video updated' : 'No changes made'
    ]);
}

/**
 * Handle DELETE requests
 */
function handleDelete($videoManager) {
    parse_str(file_get_contents("php://input"), $data);
    
    $videoId = $data['video_id'] ?? $_GET['id'] ?? '';
    if (empty($videoId)) {
        throw new Exception('Video ID required');
    }
    
    $deleteFile = ($data['delete_file'] ?? 'true') === 'true';
    $result = $videoManager->delete($videoId, $deleteFile);
    
    if ($result['success']) {
        logActivity('DELETE_VIDEO', 'video', $videoId, 'Deleted video');
    }
    
    echo json_encode($result);
}

/**
 * Format video object for JSON response
 */
function formatVideoForJSON($video) {
    return [
        'id' => objectIdToString($video->_id),
        'title' => $video->video_title ?? '',
        'description' => $video->video_description ?? '',
        'file_path' => $video->video_file_path ?? '',
        'file_size_mb' => $video->file_size_mb ?? 0,
        'duration_seconds' => $video->duration_seconds ?? 0,
        'view_count' => $video->view_count ?? 0,
        'status' => $video->status ?? 'completed',
        'uploaded_by' => objectIdToString($video->uploaded_by ?? ''),
        'subject_id' => objectIdToString($video->subject_id ?? ''),
        'lesson_id' => objectIdToString($video->lesson_id ?? ''),
        'created_at' => formatMongoDate($video->createdAt),
        'updated_at' => formatMongoDate($video->updatedAt)
    ];
}
?>
