# Study is Funny - MongoDB Implementation Guide

**Last Updated:** January 20, 2026  
**Database:** MongoDB Atlas  
**Technology:** PHP 8.1+, MongoDB Driver, HTML5, CSS3, JavaScript  

---

## 📋 PROJECT STRUCTURE

```
study-is-funny/
├── classes/              # PHP Classes (NEW)
│   ├── DatabaseMongo.php      # MongoDB connection & operations
│   ├── User.php              # User management
│   ├── SessionManager.php    # Session management
│   ├── Video.php             # Video upload & streaming
│   └── Homework.php          # Homework & submissions
│
├── config/               # Configuration (NEW)
│   └── config.php            # MongoDB & app configuration
│
├── includes/             # Shared includes (NEW)
│   └── session_check.php     # Authentication & security
│
├── api/                  # Existing API endpoints
│   ├── config.php
│   ├── students.php
│   ├── sessions.php
│   ├── admin.php
│   └── analytics.php
│
├── admin/                # Admin dashboard
│   ├── dashboard.html
│   ├── manage-sessions.html
│   ├── manage-students.html
│   └── analytics.html
│
├── student/              # Student portal
│   ├── index.html
│   └── sessions.html
│
├── uploads/              # File uploads (NEW)
│   ├── videos/
│   ├── homework/
│   ├── resources/
│   └── thumbnails/
│
└── logs/                 # Application logs (NEW)
```

---

## 🗄️ MONGODB COLLECTIONS

### Existing Collections (Your Current Database)
- `users` - Platform users (admin, students, assistants)
- `sessions` - Teaching sessions
- `all_students_view` - Student management with attendance
- `centers` - Teaching centers
- `attendances` - Attendance records
- `activitylogs` - Activity tracking
- `auditlogs` - Audit trail
- `errorlogs` - Error tracking

### New Collections (To be created)
- `videos` - Video content management
- `homework` - Homework assignments
- `homework_submissions` - Student homework submissions
- `session_registrations` - Session registration tracking
- `resources` - Downloadable resources

---

## 🚀 QUICK START GUIDE

### 1. Test Database Connection

```php
<?php
require_once 'config/config.php';

try {
    $db = new DatabaseMongo();
    echo "✓ MongoDB Connected Successfully!\n";
    echo "Database: " . $db->getDatabaseName() . "\n";
} catch (Exception $e) {
    echo "✗ Connection Failed: " . $e->getMessage() . "\n";
}
?>
```

### 2. User Registration Example

```php
<?php
require_once 'config/config.php';

$db = new DatabaseMongo();
$userManager = new User($db);

try {
    // Register a new student
    $userId = $userManager->register(
        'Ahmed Mohamed',           // name
        'ahmed@example.com',       // email
        '123456',                  // password
        '+201234567890',          // phone
        'student',                // role
        [
            'grade' => 'senior2',
            'subjects' => ['mathematics', 'physics']
        ]
    );
    
    echo "✓ Student registered! ID: " . $userId;
} catch (Exception $e) {
    echo "✗ Registration failed: " . $e->getMessage();
}
?>
```

### 3. User Login Example

```php
<?php
session_start();
require_once 'config/config.php';

$db = new DatabaseMongo();
$userManager = new User($db);

$identifier = $_POST['email'] ?? '';  // Email or phone
$password = $_POST['password'] ?? '';

$user = $userManager->login($identifier, $password);

if ($user) {
    // Login successful
    $_SESSION['user_id'] = (string) $user->_id;
    $_SESSION['user_name'] = $user->name;
    $_SESSION['role'] = $user->role;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Redirect based on role
    if ($user->role === 'admin') {
        header('Location: /admin/dashboard.html');
    } else {
        header('Location: /student/index.html');
    }
} else {
    echo "Invalid credentials";
}
?>
```

### 4. Create Teaching Session

```php
<?php
require_once 'includes/session_check.php';
requireTeacher(); // Ensure user is teacher/admin

$db = new DatabaseMongo();
$sessionManager = new SessionManager($db);

$sessionData = [
    'title' => 'Physics - Motion Chapter Review',
    'description' => 'Review of motion equations',
    'subject' => 'S2 Physics',
    'instructor_id' => getCurrentUserId(),
    'center_id' => '6925d421d4303bf0294bace8',
    'session_type' => 'live_class',
    'start_time' => '2026-01-25 16:00:00',
    'end_time' => '2026-01-25 18:00:00',
    'meeting_link' => 'https://meet.google.com/abc-defg-hij',
    'max_participants' => 50,
    'recurrence_type' => 'weekly',
    'day_of_week' => 6  // Saturday
];

try {
    $sessionId = $sessionManager->create($sessionData);
    echo "✓ Session created! ID: " . $sessionId;
    
    // Log activity
    logActivity('CREATE', 'session', (string) $sessionId, 'Created new session');
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage();
}
?>
```

### 5. Upload Video

```php
<?php
require_once 'includes/session_check.php';
requireTeacher();

$db = new DatabaseMongo();
$videoManager = new Video($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $metadata = [
        'title' => $_POST['title'] ?? 'Untitled Video',
        'description' => $_POST['description'] ?? '',
        'subject_id' => $_POST['subject_id'] ?? null,
        'lesson_id' => $_POST['lesson_id'] ?? null,
        'uploaded_by' => getCurrentUserId()
    ];
    
    $result = $videoManager->upload($_FILES['video'], $metadata);
    
    if ($result['success']) {
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
?>
```

### 6. Create & Submit Homework

```php
<?php
require_once 'includes/session_check.php';

$db = new DatabaseMongo();
$homeworkManager = new Homework($db);

// TEACHER: Create homework
if (getCurrentUserRole() === 'admin') {
    $homeworkData = [
        'title' => 'Newton\'s Laws Problem Set',
        'description' => 'Solve problems 1-10 from chapter 3',
        'instructions' => 'Show all work and calculations',
        'subject_id' => '6924c3e8ef58be28b5b33ec4',
        'lesson_id' => '6924c3e8ef58be28b5b33ec5',
        'due_date' => '2026-01-30 23:59:59',
        'max_score' => 100,
        'created_by' => getCurrentUserId()
    ];
    
    $homeworkId = $homeworkManager->create($homeworkData);
    echo "Homework created: " . $homeworkId;
}

// STUDENT: Submit homework
if (getCurrentUserRole() === 'student') {
    $submissionData = [
        'submission_text' => 'My solutions to the problems...',
        'submission_file_path' => '/uploads/homework/student123_hw1.pdf'
    ];
    
    try {
        $submissionId = $homeworkManager->submit(
            $homeworkId,
            getCurrentUserId(),
            $submissionData
        );
        echo "Homework submitted: " . $submissionId;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
```

### 7. Stream Video (Protected)

```php
<?php
// File: api/stream_video.php
require_once '../includes/session_check.php';
requireLogin();

$db = new DatabaseMongo();
$videoManager = new Video($db);

$videoId = $_GET['id'] ?? '';

if (empty($videoId)) {
    http_response_code(400);
    die('Video ID required');
}

// Check if user has access to this video
$video = $videoManager->getById($videoId);
if (!$video) {
    http_response_code(404);
    die('Video not found');
}

// Stream video (increments view count)
$videoManager->stream($videoId, true);
?>
```

---

## 🔐 SECURITY IMPLEMENTATION

### Protected Page Template

```php
<?php
// admin/manage-videos.php
require_once '../includes/session_check.php';
requireAdmin(); // Only admins can access

$db = new DatabaseMongo();
$videoManager = new Video($db);

// CSRF token for forms
$csrfToken = generateCSRFToken();

// Get all videos
$videos = $videoManager->getAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Videos</title>
</head>
<body>
    <h1>Video Management</h1>
    
    <!-- CSRF token in forms -->
    <form action="upload_video.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <input type="file" name="video" required>
        <input type="text" name="title" placeholder="Video Title" required>
        <button type="submit">Upload Video</button>
    </form>
    
    <!-- Display videos -->
    <?php foreach ($videos as $video): ?>
        <div class="video-item">
            <h3><?= htmlspecialchars($video->video_title) ?></h3>
            <p>Views: <?= $video->view_count ?></p>
            <p>Size: <?= $video->file_size_mb ?> MB</p>
        </div>
    <?php endforeach; ?>
</body>
</html>
```

### API Endpoint with Authentication

```php
<?php
// api/get_sessions.php
header('Content-Type: application/json');
require_once '../includes/session_check.php';
requireLogin();

$db = new DatabaseMongo();
$sessionManager = new SessionManager($db);

try {
    $filters = [
        'subject' => $_GET['subject'] ?? null,
        'center_id' => $_GET['center_id'] ?? null
    ];
    
    $sessions = $sessionManager->getAll($filters, 50);
    
    // Convert MongoDB objects to arrays for JSON
    $result = [];
    foreach ($sessions as $session) {
        $result[] = [
            'id' => (string) $session->_id,
            'title' => $session->session_title ?? '',
            'subject' => $session->subject ?? '',
            'start_time' => formatMongoDate($session->start_time),
            'status' => $session->session_status ?? 'scheduled'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sessions' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
```

---

## 📊 COMMON QUERIES

### Get Student's Enrolled Sessions

```php
$studentId = getCurrentUserId();

$pipeline = [
    [
        '$lookup' => [
            'from' => 'session_registrations',
            'localField' => '_id',
            'foreignField' => 'session_id',
            'as' => 'registrations'
        ]
    ],
    [
        '$match' => [
            'registrations.student_id' => DatabaseMongo::createObjectId($studentId)
        ]
    ]
];

$sessions = $db->aggregate('sessions', $pipeline);
```

### Get Homework with Submission Status

```php
$homeworkList = $homeworkManager->getActive();
$studentId = getCurrentUserId();

foreach ($homeworkList as $hw) {
    $submission = $homeworkManager->getSubmission((string) $hw->_id, $studentId);
    
    echo "Homework: " . $hw->title . "\n";
    echo "Due: " . formatMongoDate($hw->due_date) . "\n";
    echo "Status: " . ($submission ? $submission->status : 'Not submitted') . "\n";
    
    if ($submission && isset($submission->score)) {
        echo "Score: " . $submission->score . "/" . $hw->max_score . "\n";
    }
}
```

---

## 🎯 NEXT STEPS

1. **Test All Classes**
   - Run test scripts for each class
   - Verify MongoDB connections
   - Test CRUD operations

2. **Create API Endpoints**
   - Video upload API
   - Homework management API
   - Session registration API

3. **Build Admin Pages**
   - Video management page
   - Homework creation page
   - Enhanced session management

4. **Build Student Pages**
   - Video library page
   - Homework submission page
   - My submissions page

5. **Add Features**
   - Email notifications
   - Real-time updates (WebSockets)
   - Analytics dashboards
   - Export functionality

---

## 📝 NOTES

- All classes use MongoDB BSON types (ObjectId, UTCDateTime)
- Password handling differs by role (hashed for admin, plain for students)
- CSRF protection is built into session_check.php
- Activity logging is automatic via helper functions
- Video streaming supports range requests (seeking)
- File uploads are validated for type and size

---

**Ready to implement!** All core classes are in place and ready to use with your existing MongoDB database.
