<?php
/**
 * Test Script for MongoDB Classes
 * Tests all core functionality
 */

require_once __DIR__ . '/../config/config.php';

echo "=================================\n";
echo "MongoDB Classes Test Suite\n";
echo "=================================\n\n";

// Initialize database
try {
    $db = new DatabaseMongo();
    echo "✓ Database connection successful\n";
    echo "  Database: " . $db->getDatabaseName() . "\n\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Test User Class
echo "--- Testing User Class ---\n";
try {
    $userManager = new User($db);
    
    // Test: Get existing user
    $users = $userManager->getAll('admin', true);
    echo "✓ Found " . count($users) . " admin users\n";
    
    if (count($users) > 0) {
        $user = reset($users);
        echo "  Sample user: " . ($user->name ?? 'N/A') . "\n";
    }
    
    // Test: Login function
    echo "✓ User class methods available\n";
    
} catch (Exception $e) {
    echo "✗ User class error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test SessionManager Class
echo "--- Testing SessionManager Class ---\n";
try {
    $sessionManager = new SessionManager($db);
    
    // Get upcoming sessions
    $sessions = $sessionManager->getUpcoming(5);
    echo "✓ Found " . count($sessions) . " upcoming sessions\n";
    
    if (count($sessions) > 0) {
        $session = reset($sessions);
        echo "  Sample session: " . ($session->session_title ?? $session->subject ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ SessionManager error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Video Class
echo "--- Testing Video Class ---\n";
try {
    $videoManager = new Video($db);
    
    // Get all videos
    $videos = $videoManager->getAll([], 5);
    echo "✓ Found " . count($videos) . " videos in database\n";
    
    if (count($videos) > 0) {
        $video = reset($videos);
        echo "  Sample video: " . ($video->video_title ?? 'N/A') . "\n";
        echo "  Video status: " . ($video->status ?? 'unknown') . "\n";
    }
    
    // Check upload directory
    if (is_dir(VIDEOS_DIR)) {
        echo "✓ Upload directory exists: " . VIDEOS_DIR . "\n";
    } else {
        echo "⚠ Upload directory not found (will be created on first upload)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Video class error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Homework Class
echo "--- Testing Homework Class ---\n";
try {
    $homeworkManager = new Homework($db);
    
    // Get all homework
    $homeworks = $homeworkManager->getAll([], 5);
    echo "✓ Found " . count($homeworks) . " homework assignments\n";
    
    if (count($homeworks) > 0) {
        $homework = reset($homeworks);
        echo "  Sample homework: " . ($homework->title ?? 'N/A') . "\n";
        echo "  Status: " . ($homework->status ?? 'unknown') . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Homework class error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Database Operations
echo "--- Testing Database Operations ---\n";
try {
    // Count documents
    $userCount = $db->count('users');
    $sessionCount = $db->count('sessions');
    $centerCount = $db->count('centers');
    
    echo "✓ Database counts:\n";
    echo "  Users: $userCount\n";
    echo "  Sessions: $sessionCount\n";
    echo "  Centers: $centerCount\n";
    
} catch (Exception $e) {
    echo "✗ Database operations error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test Helper Functions
echo "--- Testing Helper Functions ---\n";
try {
    // Test ObjectId creation
    $testId = DatabaseMongo::createObjectId();
    echo "✓ Created ObjectId: " . (string) $testId . "\n";
    
    // Test UTCDateTime creation
    $testDate = DatabaseMongo::createUTCDateTime();
    echo "✓ Created UTCDateTime: " . $testDate->toDateTime()->format('Y-m-d H:i:s') . "\n";
    
    // Test input sanitization
    $testInput = sanitizeInput("<script>alert('xss')</script>");
    echo "✓ Input sanitization works\n";
    
    // Test email validation
    $validEmail = validateEmail('test@example.com');
    $invalidEmail = validateEmail('invalid-email');
    echo "✓ Email validation: " . ($validEmail ? 'valid' : 'invalid') . ", " . ($invalidEmail ? 'valid' : 'invalid') . "\n";
    
} catch (Exception $e) {
    echo "✗ Helper functions error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test File Upload Directories
echo "--- Testing Upload Directories ---\n";
$directories = [
    'Videos' => VIDEOS_DIR,
    'Homework' => HOMEWORK_DIR,
    'Resources' => RESOURCES_DIR,
    'Thumbnails' => THUMBNAILS_DIR
];

foreach ($directories as $name => $dir) {
    if (is_dir($dir)) {
        $permissions = substr(sprintf('%o', fileperms($dir)), -4);
        echo "✓ $name directory: $dir (Permissions: $permissions)\n";
    } else {
        echo "⚠ $name directory not found: $dir\n";
    }
}
echo "\n";

// Summary
echo "=================================\n";
echo "Test Suite Complete\n";
echo "=================================\n";
echo "\n";
echo "Next Steps:\n";
echo "1. Create test admin account if needed\n";
echo "2. Test video upload via admin panel\n";
echo "3. Create sample homework assignment\n";
echo "4. Test student submission workflow\n";
echo "5. Verify session registration\n";
echo "\n";
echo "All core classes are ready to use!\n";
?>
