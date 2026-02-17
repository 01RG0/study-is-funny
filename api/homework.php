<?php
/**
 * Homework Management API Endpoint
 * Handles homework creation, submission, and grading
 */

// Prevent HTML errors from being output - output JSON only
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

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

require_once dirname(__DIR__) . '/includes/session_check.php';
require_once dirname(__DIR__) . '/config/config.php';

// Check if MongoDB is available
if (!$GLOBALS['mongoClient']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: MongoDB not available'
    ]);
    exit;
}

try {
    $db = new DatabaseMongo();
    $homeworkManager = new Homework($db);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error initializing database: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($homeworkManager);
            break;
            
        case 'POST':
            requireLogin();
            handlePost($homeworkManager);
            break;
            
        case 'PUT':
            requireLogin();
            handlePut($homeworkManager);
            break;
            
        case 'DELETE':
            requireTeacher();
            handleDelete($homeworkManager);
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
function handleGet($homeworkManager) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // List homework (active only by default)
            $status = $_GET['status'] ?? 'active';
            $subjectId = $_GET['subject_id'] ?? null;
            
            if ($subjectId) {
                $homeworks = $homeworkManager->getBySubject($subjectId, $status);
            } else {
                $filters = ['status' => $status];
                if (getCurrentUserRole() === 'teacher' || getCurrentUserRole() === 'admin') {
                    $filters['created_by'] = getCurrentUserId();
                }
                $homeworks = $homeworkManager->getAll($filters);
            }
            
            $result = array_map('formatHomeworkForJSON', $homeworks);
            
            echo json_encode([
                'success' => true,
                'homework' => $result
            ]);
            break;
            
        case 'get':
            $homeworkId = $_GET['id'] ?? '';
            if (empty($homeworkId)) {
                throw new Exception('Homework ID required');
            }
            
            $homework = $homeworkManager->getById($homeworkId);
            if (!$homework) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Homework not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'homework' => formatHomeworkForJSON($homework)
            ]);
            break;
            
        case 'submissions':
            requireTeacher();
            $homeworkId = $_GET['homework_id'] ?? '';
            if (empty($homeworkId)) {
                throw new Exception('Homework ID required');
            }
            
            $submissions = $homeworkManager->getSubmissions($homeworkId);
            $result = array_map('formatSubmissionForJSON', $submissions);
            
            echo json_encode([
                'success' => true,
                'submissions' => $result
            ]);
            break;
            
        case 'my_submissions':
            $submissions = $homeworkManager->getStudentSubmissions(getCurrentUserId());
            $result = array_map('formatSubmissionForJSON', $submissions);
            
            echo json_encode([
                'success' => true,
                'submissions' => $result
            ]);
            break;
            
        case 'statistics':
            requireTeacher();
            $homeworkId = $_GET['homework_id'] ?? '';
            if (empty($homeworkId)) {
                throw new Exception('Homework ID required');
            }
            
            $stats = $homeworkManager->getStatistics($homeworkId);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle POST requests
 */
function handlePost($homeworkManager) {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            // Only teachers can create homework
            requireTeacher();
            
            $homeworkData = [
                'title' => sanitizeInput($_POST['title'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'instructions' => sanitizeInput($_POST['instructions'] ?? ''),
                'subject_id' => $_POST['subject_id'] ?? null,
                'lesson_id' => $_POST['lesson_id'] ?? null,
                'due_date' => $_POST['due_date'] ?? null,
                'max_score' => (int) ($_POST['max_score'] ?? 100),
                'created_by' => getCurrentUserId(),
                'status' => 'active'
            ];
            
            $homeworkId = $homeworkManager->create($homeworkData);
            
            logActivity('CREATE_HOMEWORK', 'homework', (string) $homeworkId, 'Created: ' . $homeworkData['title']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Homework created successfully',
                'homework_id' => (string) $homeworkId
            ]);
            break;
            
        case 'submit':
            // Students submit homework
            $homeworkId = $_POST['homework_id'] ?? '';
            if (empty($homeworkId)) {
                throw new Exception('Homework ID required');
            }
            
            $submissionData = [
                'submission_text' => sanitizeInput($_POST['submission_text'] ?? ''),
                'submission_file_path' => $_POST['submission_file_path'] ?? null
            ];
            
            $submissionId = $homeworkManager->submit(
                $homeworkId,
                getCurrentUserId(),
                $submissionData
            );
            
            logActivity('SUBMIT_HOMEWORK', 'homework_submission', (string) $submissionId, 'Submitted homework');
            
            echo json_encode([
                'success' => true,
                'message' => 'Homework submitted successfully',
                'submission_id' => (string) $submissionId
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle PUT requests
 */
function handlePut($homeworkManager) {
    parse_str(file_get_contents("php://input"), $data);
    
    $action = $data['action'] ?? 'update';
    
    switch ($action) {
        case 'update':
            requireTeacher();
            
            $homeworkId = $data['homework_id'] ?? '';
            if (empty($homeworkId)) {
                throw new Exception('Homework ID required');
            }
            
            $updateData = [];
            if (isset($data['title'])) {
                $updateData['title'] = sanitizeInput($data['title']);
            }
            if (isset($data['description'])) {
                $updateData['description'] = sanitizeInput($data['description']);
            }
            if (isset($data['due_date'])) {
                $updateData['due_date'] = $data['due_date'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            
            $result = $homeworkManager->update($homeworkId, $updateData);
            
            echo json_encode([
                'success' => $result > 0,
                'message' => $result > 0 ? 'Homework updated' : 'No changes made'
            ]);
            break;
            
        case 'grade':
            requireTeacher();
            
            $homeworkId = $data['homework_id'] ?? '';
            $studentId = $data['student_id'] ?? '';
            $score = $data['score'] ?? 0;
            $feedback = sanitizeInput($data['feedback'] ?? '');
            
            if (empty($homeworkId) || empty($studentId)) {
                throw new Exception('Homework ID and Student ID required');
            }
            
            $result = $homeworkManager->grade(
                $homeworkId,
                $studentId,
                $score,
                $feedback,
                getCurrentUserId()
            );
            
            logActivity('GRADE_HOMEWORK', 'homework_submission', $studentId, "Graded: $score");
            
            echo json_encode([
                'success' => $result > 0,
                'message' => 'Homework graded successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Handle DELETE requests
 */
function handleDelete($homeworkManager) {
    parse_str(file_get_contents("php://input"), $data);
    
    $homeworkId = $data['homework_id'] ?? $_GET['id'] ?? '';
    if (empty($homeworkId)) {
        throw new Exception('Homework ID required');
    }
    
    $result = $homeworkManager->delete($homeworkId);
    
    logActivity('DELETE_HOMEWORK', 'homework', $homeworkId, 'Deleted homework');
    
    echo json_encode([
        'success' => $result > 0,
        'message' => $result > 0 ? 'Homework deleted' : 'Failed to delete'
    ]);
}

/**
 * Format homework for JSON
 */
function formatHomeworkForJSON($homework) {
    return [
        'id' => objectIdToString($homework->_id),
        'title' => $homework->title ?? '',
        'description' => $homework->description ?? '',
        'instructions' => $homework->instructions ?? '',
        'subject_id' => objectIdToString($homework->subject_id ?? ''),
        'lesson_id' => objectIdToString($homework->lesson_id ?? ''),
        'due_date' => formatMongoDate($homework->due_date),
        'max_score' => $homework->max_score ?? 100,
        'status' => $homework->status ?? 'active',
        'created_by' => objectIdToString($homework->created_by ?? ''),
        'created_at' => formatMongoDate($homework->createdAt),
        'updated_at' => formatMongoDate($homework->updatedAt)
    ];
}

/**
 * Format submission for JSON
 */
function formatSubmissionForJSON($submission) {
    return [
        'id' => objectIdToString($submission->_id),
        'homework_id' => objectIdToString($submission->homework_id),
        'student_id' => objectIdToString($submission->student_id),
        'submission_text' => $submission->submission_text ?? '',
        'submission_file_path' => $submission->submission_file_path ?? null,
        'submitted_at' => formatMongoDate($submission->submitted_at),
        'score' => $submission->score ?? null,
        'feedback' => $submission->feedback ?? '',
        'status' => $submission->status ?? 'submitted',
        'graded_by' => objectIdToString($submission->graded_by ?? ''),
        'graded_at' => formatMongoDate($submission->graded_at ?? null)
    ];
}
?>
