# Study is Funny - Database & Code Templates

---

## COMPLETE DATABASE SETUP SQL

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS study_is_funny;
USE study_is_funny;

-- 1. Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150),
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    profile_picture VARCHAR(255),
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- 2. Subjects Table
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(150) NOT NULL,
    description TEXT,
    subject_code VARCHAR(50) UNIQUE,
    instructor_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id),
    INDEX idx_subject_name (subject_name)
);

-- 3. Lessons Table
CREATE TABLE lessons (
    lesson_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    lesson_title VARCHAR(200) NOT NULL,
    lesson_description TEXT,
    lesson_order INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    INDEX idx_subject_id (subject_id),
    INDEX idx_lesson_order (lesson_order)
);

-- 4. Videos Table
CREATE TABLE videos (
    video_id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    video_title VARCHAR(200) NOT NULL,
    video_description TEXT,
    video_file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    duration_seconds INT,
    file_size_mb DECIMAL(10, 2),
    uploaded_by INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_status (status)
);

-- 5. Homework Table
CREATE TABLE homework (
    homework_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    lesson_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    instructions TEXT,
    due_date DATE NOT NULL,
    due_time TIME,
    max_score INT DEFAULT 100,
    created_by INT NOT NULL,
    status ENUM('active', 'closed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_due_date (due_date)
);

-- 6. Sessions Table
CREATE TABLE sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT,
    homework_id INT NULL,
    session_title VARCHAR(200) NOT NULL,
    session_description TEXT,
    instructor_id INT NOT NULL,
    session_type ENUM('homework', 'general_study', 'live_class', 'review') DEFAULT 'general_study',
    scheduled_start_datetime DATETIME NOT NULL,
    scheduled_end_datetime DATETIME NOT NULL,
    actual_start_datetime DATETIME NULL,
    actual_end_datetime DATETIME NULL,
    session_status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    max_participants INT DEFAULT NULL,
    meeting_link VARCHAR(255) NULL,
    session_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
    FOREIGN KEY (homework_id) REFERENCES homework(homework_id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_scheduled_start (scheduled_start_datetime),
    INDEX idx_session_status (session_status)
);

-- 7. Session Registrations Table
CREATE TABLE session_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    registration_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    notes TEXT,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (session_id, student_id),
    INDEX idx_student_id (student_id),
    INDEX idx_attendance_status (attendance_status)
);

-- 8. Homework Submissions Table
CREATE TABLE homework_submissions (
    submission_id INT PRIMARY KEY AUTO_INCREMENT,
    homework_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_file_path VARCHAR(255),
    submission_text TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    score INT,
    feedback TEXT,
    graded_by INT,
    graded_at DATETIME NULL,
    status ENUM('submitted', 'graded', 'late', 'not_submitted') DEFAULT 'submitted',
    FOREIGN KEY (homework_id) REFERENCES homework(homework_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(user_id),
    UNIQUE KEY unique_submission (homework_id, student_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- 9. Resources Table
CREATE TABLE resources (
    resource_id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    resource_title VARCHAR(200),
    resource_type ENUM('pdf', 'document', 'image', 'presentation', 'other') DEFAULT 'pdf',
    file_path VARCHAR(255) NOT NULL,
    file_size_mb DECIMAL(10, 2),
    download_count INT DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_lesson_id (lesson_id)
);

-- 10. Activity Log Table
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);
```

---

## CORE PHP CLASSES & TEMPLATES

### 1. Database Class (classes/Database.php)
```php
<?php
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);
        
        if ($this->conn->connect_error) {
            die('Connection failed: ' . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
        return $this->conn;
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function close() {
        return $this->conn->close();
    }

    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    public function affectedRows() {
        return $this->conn->affected_rows;
    }

    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
}
?>
```

### 2. Configuration File (config/config.php)
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'study_is_funny');

// Application Settings
define('APP_NAME', 'Study is Funny');
define('APP_URL', 'http://localhost/study-is-funny');
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('VIDEOS_DIR', UPLOADS_DIR . '/videos');
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024); // 500MB

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.cookie_samesite', 'Strict');
?>
```

### 3. User Class (classes/User.php)
```php
<?php
class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function register($username, $email, $password, $full_name, $role = 'student') {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT user_id, password_hash, role, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        if ($user['status'] !== 'active') {
            return false;
        }
        
        return $user;
    }

    public function getById($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateProfile($user_id, $full_name, $email, $phone) {
        $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        return $stmt->execute();
    }

    public function changePassword($user_id, $new_password) {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        return $stmt->execute();
    }

    public function getAll($role = null, $status = 'active') {
        if ($role) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? AND status = ? ORDER BY created_at DESC");
            $stmt->bind_param("ss", $role, $status);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE status = ? ORDER BY created_at DESC");
            $stmt->bind_param("s", $status);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
```

### 4. Session Class (classes/Session.php)
```php
<?php
class SessionManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function create($subject_id, $homework_id, $title, $description, $instructor_id, $session_type, $start_datetime, $end_datetime, $meeting_link = null, $max_participants = null) {
        $stmt = $this->db->prepare("
            INSERT INTO sessions 
            (subject_id, homework_id, session_title, session_description, instructor_id, session_type, scheduled_start_datetime, scheduled_end_datetime, meeting_link, max_participants) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iisssssssi",
            $subject_id, $homework_id, $title, $description, $instructor_id, 
            $session_type, $start_datetime, $end_datetime, $meeting_link, $max_participants
        );
        
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    public function getById($session_id) {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUpcoming($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions 
            WHERE scheduled_start_datetime > NOW() AND session_status = 'scheduled'
            ORDER BY scheduled_start_datetime ASC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function startSession($session_id) {
        $status = 'in_progress';
        $stmt = $this->db->prepare("UPDATE sessions SET session_status = ?, actual_start_datetime = NOW() WHERE session_id = ?");
        $stmt->bind_param("si", $status, $session_id);
        return $stmt->execute();
    }

    public function endSession($session_id) {
        $status = 'completed';
        $stmt = $this->db->prepare("UPDATE sessions SET session_status = ?, actual_end_datetime = NOW() WHERE session_id = ?");
        $stmt->bind_param("si", $status, $session_id);
        return $stmt->execute();
    }

    public function registerStudent($session_id, $student_id) {
        $status = 'registered';
        $stmt = $this->db->prepare("
            INSERT INTO session_registrations (session_id, student_id, attendance_status) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE attendance_status = VALUES(attendance_status)
        ");
        $stmt->bind_param("iis", $session_id, $student_id, $status);
        return $stmt->execute();
    }

    public function getRegistrations($session_id) {
        $stmt = $this->db->prepare("
            SELECT sr.*, u.username, u.full_name, u.email 
            FROM session_registrations sr
            JOIN users u ON sr.student_id = u.user_id
            WHERE sr.session_id = ?
        ");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkIn($registration_id) {
        $stmt = $this->db->prepare("
            UPDATE session_registrations 
            SET check_in_time = NOW(), attendance_status = 'attended' 
            WHERE registration_id = ?
        ");
        $stmt->bind_param("i", $registration_id);
        return $stmt->execute();
    }

    public function checkOut($registration_id) {
        $stmt = $this->db->prepare("UPDATE session_registrations SET check_out_time = NOW() WHERE registration_id = ?");
        $stmt->bind_param("i", $registration_id);
        return $stmt->execute();
    }
}
?>
```

### 5. Video Class (classes/Video.php)
```php
<?php
class Video {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function upload($lesson_id, $title, $description, $file_path, $thumbnail_path, $uploaded_by, $duration = 0, $file_size = 0) {
        $status = 'completed';
        $stmt = $this->db->prepare("
            INSERT INTO videos 
            (lesson_id, video_title, video_description, video_file_path, thumbnail_path, uploaded_by, status, duration_seconds, file_size_mb) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isssssidi",
            $lesson_id, $title, $description, $file_path, $thumbnail_path, $uploaded_by, $status, $duration, $file_size
        );
        
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    public function getById($video_id) {
        $stmt = $this->db->prepare("SELECT * FROM videos WHERE video_id = ?");
        $stmt->bind_param("i", $video_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getByLesson($lesson_id) {
        $stmt = $this->db->prepare("SELECT * FROM videos WHERE lesson_id = ? AND status = 'completed' ORDER BY created_at DESC");
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateViewCount($video_id) {
        $stmt = $this->db->prepare("UPDATE videos SET view_count = view_count + 1 WHERE video_id = ?");
        $stmt->bind_param("i", $video_id);
        return $stmt->execute();
    }

    public function delete($video_id) {
        $stmt = $this->db->prepare("DELETE FROM videos WHERE video_id = ?");
        $stmt->bind_param("i", $video_id);
        return $stmt->execute();
    }
}
?>
```

### 6. Login Handler (includes/login.php)
```php
<?php
session_start();
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'));
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Username and password required';
    } else {
        $db = new Database();
        $db->connect();
        $user = new User($db);
        
        $login_result = $user->login($username, $password);
        
        if ($login_result) {
            // Login successful
            session_regenerate_id(true);
            $_SESSION['user_id'] = $login_result['user_id'];
            $_SESSION['role'] = $login_result['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Log activity
            logActivity($db, $login_result['user_id'], 'LOGIN', 'users', $login_result['user_id']);
            
            // Redirect based on role
            if ($login_result['role'] === 'admin' || $login_result['role'] === 'teacher') {
                header('Location: /admin/');
            } else {
                header('Location: /student/');
            }
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    }
}

function logActivity($db, $user_id, $action, $entity_type, $entity_id) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $entity_type, $entity_id, $ip, $user_agent);
    $stmt->execute();
}
?>
```

### 7. Session Check (includes/session_check.php)
```php
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Check session timeout
$timeout_duration = SESSION_TIMEOUT;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_destroy();
    header('Location: /login.php?msg=session_expired');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check CSRF token
function validateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to check admin access
function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('Unauthorized Access');
    }
}

// Function to check teacher/admin access
function requireTeacher() {
    if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
        http_response_code(403);
        die('Unauthorized Access');
    }
}

// Function to get current user info
function getCurrentUser($db) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
```

---

## QUICK START SETUP CHECKLIST

- [ ] Create database using provided SQL
- [ ] Copy all class files to `/classes/` folder
- [ ] Create configuration file at `/config/config.php`
- [ ] Create session check include at `/includes/session_check.php`
- [ ] Set up uploads directory with proper permissions (755)
- [ ] Create `.htaccess` file for URL routing if needed
- [ ] Test database connection
- [ ] Create login page
- [ ] Test user registration
- [ ] Build admin dashboard
- [ ] Implement video upload
- [ ] Create session management
- [ ] Build student dashboard
- [ ] Perform security audit

---

## FILE PERMISSIONS

```bash
# Create directories
mkdir -p uploads/videos
mkdir -p uploads/homework
mkdir -p uploads/resources
mkdir -p uploads/thumbnails
mkdir -p logs

# Set permissions
chmod 755 uploads
chmod 755 uploads/videos
chmod 755 uploads/homework
chmod 755 uploads/resources
chmod 755 uploads/thumbnails
chmod 755 logs
```

---

**Ready to implement! All templates provided for rapid development.**