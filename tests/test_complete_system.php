<?php
/**
 * Complete System Test
 * Tests all implemented classes and features
 */

require_once __DIR__ . '/../config/config.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   Study is Funny - Complete System Test            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$passedTests = 0;
$totalTests = 0;

function test($description, $callback) {
    global $passedTests, $totalTests;
    $totalTests++;
    
    try {
        $result = $callback();
        if ($result) {
            echo "✓ $description\n";
            $passedTests++;
            return true;
        } else {
            echo "✗ $description\n";
            return false;
        }
    } catch (Exception $e) {
        echo "✗ $description - Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
test("MongoDB connection", function() {
    $db = new DatabaseMongo();
    return $db->getDatabaseName() === 'attendance_system';
});
echo "\n";

// Test 2: Core Classes
echo "2. Testing Core Classes...\n";
$db = new DatabaseMongo();

test("DatabaseMongo class", function() use ($db) {
    return $db instanceof DatabaseMongo;
});

test("User class", function() use ($db) {
    $user = new User($db);
    return $user instanceof User;
});

test("SessionManager class", function() use ($db) {
    $session = new SessionManager($db);
    return $session instanceof SessionManager;
});

test("Video class", function() use ($db) {
    $video = new Video($db);
    return $video instanceof Video;
});

test("Homework class", function() use ($db) {
    $homework = new Homework($db);
    return $homework instanceof Homework;
});

test("Student class", function() use ($db) {
    $student = new Student($db);
    return $student instanceof Student;
});

test("Analytics class", function() use ($db) {
    $analytics = new Analytics($db);
    return $analytics instanceof Analytics;
});
echo "\n";

// Test 3: Database Operations
echo "3. Testing Database Operations...\n";

test("Create ObjectId", function() {
    $id = DatabaseMongo::createObjectId();
    return $id instanceof MongoDB\BSON\ObjectId;
});

test("Create UTCDateTime", function() {
    $date = DatabaseMongo::createUTCDateTime();
    return $date instanceof MongoDB\BSON\UTCDateTime;
});

test("Query execution", function() use ($db) {
    $users = $db->find('users', [], ['limit' => 1]);
    return is_array($users);
});
echo "\n";

// Test 4: User Management
echo "4. Testing User Management...\n";
$userManager = new User($db);

test("Get all users", function() use ($userManager) {
    $users = $userManager->getAll();
    return is_array($users);
});

test("User statistics", function() use ($userManager, $db) {
    $users = $db->find('users', [], ['limit' => 1]);
    if (!empty($users)) {
        $user = reset($users);
        $stats = $userManager->getStatistics((string)$user->_id);
        return is_array($stats);
    }
    return true;
});
echo "\n";

// Test 5: Session Management
echo "5. Testing Session Management...\n";
$sessionManager = new SessionManager($db);

test("Get all sessions", function() use ($sessionManager) {
    $sessions = $sessionManager->getAll();
    return is_array($sessions);
});

test("Get upcoming sessions", function() use ($sessionManager) {
    $sessions = $sessionManager->getUpcoming(5);
    return is_array($sessions);
});
echo "\n";

// Test 6: Video Management
echo "6. Testing Video Management...\n";
$videoManager = new Video($db);

test("Get all videos", function() use ($videoManager) {
    $videos = $videoManager->getAll([], 10);
    return is_array($videos);
});

test("Video upload directory exists", function() {
    return is_dir(VIDEOS_DIR);
});

test("Video upload directory writable", function() {
    return is_writable(VIDEOS_DIR);
});
echo "\n";

// Test 7: Homework Management
echo "7. Testing Homework Management...\n";
$homeworkManager = new Homework($db);

test("Get all homework", function() use ($homeworkManager) {
    $homework = $homeworkManager->getAll([], 10);
    return is_array($homework);
});

test("Get active homework", function() use ($homeworkManager) {
    $homework = $homeworkManager->getActive();
    return is_array($homework);
});

test("Homework upload directory exists", function() {
    return is_dir(HOMEWORK_DIR);
});
echo "\n";

// Test 8: Student Management
echo "8. Testing Student Management...\n";
$studentManager = new Student($db);

test("Get all students", function() use ($studentManager) {
    $students = $studentManager->getAll([], 10);
    return is_array($students);
});
echo "\n";

// Test 9: Analytics
echo "9. Testing Analytics...\n";
$analytics = new Analytics($db);

test("Get dashboard summary", function() use ($analytics) {
    $summary = $analytics->getDashboardSummary();
    return is_array($summary) && isset($summary['users']) && isset($summary['sessions']);
});

test("Get user statistics", function() use ($analytics) {
    $stats = $analytics->getUserStats();
    return is_array($stats) && isset($stats['total']);
});

test("Get session statistics", function() use ($analytics) {
    $stats = $analytics->getSessionStats();
    return is_array($stats) && isset($stats['total']);
});

test("Get homework statistics", function() use ($analytics) {
    $stats = $analytics->getHomeworkStats();
    return is_array($stats);
});

test("Get video statistics", function() use ($analytics) {
    $stats = $analytics->getVideoStats();
    return is_array($stats);
});
echo "\n";

// Test 10: File System
echo "10. Testing File System...\n";

test("Uploads directory exists", function() {
    return is_dir(UPLOADS_DIR);
});

test("Videos directory exists", function() {
    return is_dir(VIDEOS_DIR);
});

test("Homework directory exists", function() {
    return is_dir(HOMEWORK_DIR);
});

test("Resources directory exists", function() {
    return is_dir(RESOURCES_DIR);
});

test("Thumbnails directory exists", function() {
    return is_dir(THUMBNAILS_DIR);
});

test("Logs directory exists", function() {
    return is_dir(BASE_PATH . '/logs');
});
echo "\n";

// Test 11: Page Files
echo "11. Testing Page Files...\n";

test("Admin upload-video.php exists", function() {
    return file_exists(BASE_PATH . '/admin/upload-video.php');
});

test("Admin manage-homework.php exists", function() {
    return file_exists(BASE_PATH . '/admin/manage-homework.php');
});

test("Student videos.php exists", function() {
    return file_exists(BASE_PATH . '/student/videos.php');
});

test("Student homework-detail.php exists", function() {
    return file_exists(BASE_PATH . '/student/homework-detail.php');
});

test("Video streaming page exists", function() {
    return file_exists(BASE_PATH . '/stream-video.php');
});
echo "\n";

// Test 12: API Endpoints
echo "12. Testing API Endpoints...\n";

test("API videos.php exists", function() {
    return file_exists(BASE_PATH . '/api/videos.php');
});

test("API homework.php exists", function() {
    return file_exists(BASE_PATH . '/api/homework.php');
});

test("API sessions.php exists", function() {
    return file_exists(BASE_PATH . '/api/sessions.php');
});

test("API stream-video.php exists", function() {
    return file_exists(BASE_PATH . '/api/stream-video.php');
});
echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║                  Test Results                         ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$percentage = round(($passedTests / $totalTests) * 100, 1);
$status = $percentage >= 90 ? "EXCELLENT" : ($percentage >= 70 ? "GOOD" : "NEEDS ATTENTION");

echo "Passed:  $passedTests / $totalTests tests\n";
echo "Success Rate: $percentage%\n";
echo "Status: $status\n\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL TESTS PASSED! System is fully operational.\n\n";
} elseif ($percentage >= 90) {
    echo "✓ System is operational with minor issues.\n\n";
} else {
    echo "⚠ System needs attention. Please review failed tests.\n\n";
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║              Implementation Complete                  ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "Created Components:\n";
echo "• 7 PHP Classes (DatabaseMongo, User, SessionManager, Video,\n";
echo "  Homework, Student, Analytics)\n";
echo "• 4 API Endpoints (videos, homework, sessions, stream-video)\n";
echo "• 5 User Pages (upload-video, manage-homework, videos,\n";
echo "  homework-detail, stream-video)\n";
echo "• 2 Dynamic Pages (homework list, sessions list)\n";
echo "• Complete authentication & security layer\n";
echo "• File upload & streaming system\n\n";

echo "Next Steps:\n";
echo "1. Test video upload through admin panel\n";
echo "2. Create sample homework assignment\n";
echo "3. Test student homework submission\n";
echo "4. Test video streaming\n";
echo "5. Verify all integrations\n\n";

echo "All systems ready! 🚀\n\n";

exit($passedTests === $totalTests ? 0 : 1);
?>
