# Study is Funny - Complete Project Plan & Architecture
## Backend Development with PHP, MySQL, HTML5, CSS3, JavaScript

**Date Created:** January 20, 2026  
**Project Version:** 1.0  
**Technology Stack:** PHP 8.1+, MySQL 5.7+, HTML5, CSS3, JavaScript (Vanilla/jQuery)  
**Note:** NO Node.js - Pure PHP Backend

---

## TABLE OF CONTENTS
1. [Project Overview](#project-overview)
2. [Architecture & Structure](#architecture--structure)
3. [Database Design](#database-design)
4. [Backend Development Roadmap](#backend-development-roadmap)
5. [Admin Panel Features](#admin-panel-features)
6. [Student Dashboard](#student-dashboard)
7. [Session Management System](#session-management-system)
8. [Video Upload & Streaming](#video-upload--streaming)
9. [File Structure Organization](#file-structure-organization)
10. [Security Guidelines](#security-guidelines)
11. [Implementation Timeline](#implementation-timeline)

---

## PROJECT OVERVIEW

### Purpose
A comprehensive learning management system for managing subjects, homework, real-time sessions, and video content with:
- **Admin Panel:** Full control over subjects, lessons, videos, sessions, and user management
- **Student Dashboard:** View sessions, homework, download resources, track progress
- **Real-time Sessions:** Live class/study sessions with registration tracking
- **Video Management:** Upload, manage, and stream educational videos
- **Session Creation:** Both scheduled homework sessions and general study sessions

### Key Requirements
✅ PHP Backend (no Node.js)  
✅ Admin Panel with full functionality  
✅ Video upload with streaming  
✅ Real-time session creation and management  
✅ Database-connected pages  
✅ Student UI from previous website  
✅ Sessions without homework (flexible sessions)  

---

## ARCHITECTURE & STRUCTURE

### MVC Architecture
```
Model (Database Layer)
├── Data access objects
├── Database queries
└── Business logic

View (Presentation Layer)
├── Admin pages
├── Student pages
└── Shared templates

Controller (Application Layer)
├── Session handling
├── Request routing
├── Data validation
└── Response generation
```

### Key Principles
- **Separation of Concerns:** Models handle data, Controllers handle logic, Views handle presentation
- **Reusability:** Common functions in includes and libraries
- **Security:** Input validation, prepared statements, session validation
- **Scalability:** Database-driven, modular code

---

## DATABASE DESIGN

### Core Tables

#### 1. **users**
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150),
    role ENUM('admin', 'student', 'teacher') NOT NULL,
    profile_picture VARCHAR(255),
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status)
);
```

#### 2. **subjects**
```sql
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(150) NOT NULL,
    description TEXT,
    subject_code VARCHAR(50) UNIQUE,
    instructor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id),
    INDEX idx_subject_name (subject_name)
);
```

#### 3. **lessons** (Topics/Chapters)
```sql
CREATE TABLE lessons (
    lesson_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    lesson_title VARCHAR(200) NOT NULL,
    lesson_description TEXT,
    lesson_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    INDEX idx_subject_id (subject_id),
    INDEX idx_lesson_order (lesson_order)
);
```

#### 4. **videos**
```sql
CREATE TABLE videos (
    video_id INT PRIMARY KEY AUTO_INCREMENT,
    lesson_id INT NOT NULL,
    video_title VARCHAR(200) NOT NULL,
    video_description TEXT,
    video_file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    duration_seconds INT,
    file_size_mb DECIMAL(10, 2),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_status (status)
);
```

#### 5. **sessions** (Study/Class Sessions)
```sql
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
```

#### 6. **session_registrations**
```sql
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
```

#### 7. **homework**
```sql
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_due_date (due_date)
);
```

#### 8. **homework_submissions**
```sql
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
    status ENUM('submitted', 'graded', 'late') DEFAULT 'submitted',
    FOREIGN KEY (homework_id) REFERENCES homework(homework_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(user_id),
    UNIQUE KEY unique_submission (homework_id, student_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);
```

#### 9. **session_history** (For auditing)
```sql
CREATE TABLE session_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    action VARCHAR(100),
    performed_by INT,
    old_values JSON,
    new_values JSON,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_performed_at (performed_at)
);
```

#### 10. **resources**
```sql
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
```

---

## BACKEND DEVELOPMENT ROADMAP

### Phase 1: Core Infrastructure (Week 1)
- [ ] Set up project directory structure
- [ ] Create database with all tables
- [ ] Build database connection class (mysqli with prepared statements)
- [ ] Create configuration files (config.php, constants.php)
- [ ] Implement basic error handling
- [ ] Create session management class
- [ ] Build user authentication (login/register)

### Phase 2: Admin Panel Foundation (Week 2-3)
- [ ] Create admin layout template
- [ ] Build admin navigation/menu
- [ ] Implement admin dashboard with statistics
- [ ] Create user management CRUD
- [ ] Create subject management CRUD
- [ ] Create lesson management CRUD
- [ ] Implement role-based access control

### Phase 3: Video Management (Week 3-4)
- [ ] Create video upload form
- [ ] Implement secure file upload handling
- [ ] Create video storage system
- [ ] Build video management CRUD
- [ ] Implement video streaming functionality
- [ ] Add video thumbnail generation
- [ ] Create video listing page

### Phase 4: Session Management (Week 4-5)
- [ ] Create session creation form
- [ ] Implement session scheduling
- [ ] Build session registration system
- [ ] Create real-time attendance tracking
- [ ] Build session listing pages
- [ ] Implement session status updates
- [ ] Create session history/audit log

### Phase 5: Homework System (Week 5-6)
- [ ] Create homework CRUD
- [ ] Build homework submission system
- [ ] Create grading interface
- [ ] Implement submission tracking
- [ ] Build homework analytics

### Phase 6: Student Dashboard (Week 6-7)
- [ ] Create student dashboard layout
- [ ] Display enrolled sessions
- [ ] Show homework assignments
- [ ] Display available videos
- [ ] Create submission interface
- [ ] Build progress tracking

### Phase 7: Integration & Testing (Week 7-8)
- [ ] Integrate all components
- [ ] Performance optimization
- [ ] Security audit
- [ ] User acceptance testing
- [ ] Bug fixes and refinement

---

## ADMIN PANEL FEATURES

### 1. Admin Dashboard
```
├─ Overview Statistics
│  ├─ Total Students
│  ├─ Total Sessions
│  ├─ Total Videos
│  ├─ Pending Homework
│  └─ Recent Activities
├─ Quick Actions
├─ Recent Session List
└─ System Notifications
```

### 2. User Management
```
├─ Users List (with search, filter, pagination)
├─ Add New User
├─ Edit User Details
├─ Delete User
├─ Role Assignment (Admin/Teacher/Student)
├─ User Status Management
└─ User Activity Log
```

### 3. Subject Management
```
├─ Subjects List
├─ Create Subject
├─ Edit Subject
├─ Delete Subject
├─ Assign Instructors
└─ Subject Statistics
```

### 4. Lesson Management
```
├─ Lessons by Subject
├─ Create/Edit Lesson
├─ Delete Lesson
├─ Lesson Ordering
└─ Lesson Resources
```

### 5. Video Management
```
├─ Video Upload Interface
│  ├─ Single Video Upload
│  ├─ Bulk Upload
│  └─ Video Preview
├─ Video List
├─ Edit Video Metadata
├─ Delete Video
├─ Video Status Tracking
└─ Video Statistics (Views, etc)
```

### 6. Session Management
```
├─ Create Session
│  ├─ Homework Session
│  └─ General Study Session
├─ Session Schedule Calendar
├─ Session List with Filters
├─ Edit Session
├─ Start/End Session
├─ Session Attendance
│  ├─ Check-in/Out
│  ├─ Attendance Report
│  └─ No-show Management
├─ Delete Session
└─ Session History
```

### 7. Homework Management
```
├─ Create Homework
├─ Homework List
├─ Edit Homework
├─ Delete Homework
├─ Student Submissions View
├─ Grading Interface
├─ Feedback System
└─ Homework Statistics
```

### 8. Reports & Analytics
```
├─ Student Progress Report
├─ Session Attendance Report
├─ Video View Statistics
├─ Homework Submission Rate
├─ System Performance Report
└─ Export to PDF/Excel
```

---

## STUDENT DASHBOARD

### 1. Dashboard Home
```
├─ Welcome Message
├─ My Courses/Subjects
├─ Upcoming Sessions
├─ Pending Homework
├─ Recent Videos
└─ My Progress
```

### 2. Sessions View
```
├─ Upcoming Sessions (Filterable)
├─ Session Details
├─ Register for Session
├─ View Attendance Status
└─ Session Recording (if available)
```

### 3. Homework View
```
├─ Active Assignments
├─ Past Submissions
├─ Homework Details
├─ Submit Homework
├─ View Feedback
└─ Grades
```

### 4. Videos Library
```
├─ Video List by Subject
├─ Search Videos
├─ Watch Video
├─ Download Materials
└─ Track Progress
```

### 5. Profile
```
├─ View Profile
├─ Edit Profile
├─ Change Password
└─ My Submissions History
```

---

## SESSION MANAGEMENT SYSTEM

### Session Types

#### 1. **Homework Sessions** (Fixed to Homework Deadline)
- Created when homework is created
- Automatically scheduled before homework due date
- Purpose: Review/Q&A for homework
- Can have multiple sessions per homework

#### 2. **General Study Sessions** (Not tied to Homework)
- Created independently
- For revision, doubt clearing, or general topics
- Flexible scheduling
- Can be for any subject

### Session Workflow

```
1. Admin Creates Session
   ├─ Select Type (Homework/General)
   ├─ If Homework: Link to Homework
   ├─ Set Schedule
   └─ Save

2. System Notifications
   ├─ Email to Students
   └─ Dashboard Alert

3. Student Registration
   ├─ View Session
   ├─ Register (Limited/Unlimited capacity)
   └─ Confirm Registration

4. Session Execution
   ├─ Check-in (Automatic timestamp)
   ├─ Live Session (Link/Meet)
   └─ Check-out (Automatic/Manual)

5. Post Session
   ├─ Mark Attendance
   ├─ Store Recording
   ├─ Generate Report
   └─ Archive
```

### Real-time Tracking
```php
// JavaScript (Client Side)
- Auto-update session status every 30 seconds
- Live participant count
- Real-time notifications

// PHP (Server Side)
- Update session_registrations
- Track timestamps
- Generate real-time reports
```

---

## VIDEO UPLOAD & STREAMING

### Upload Process

```php
1. File Validation
   ├─ Check file type (mp4, webm, avi)
   ├─ Check file size (max 500MB)
   └─ Scan for malware

2. Storage
   ├─ Store in secure directory (/videos/uploads/)
   ├─ Create organized folders (subject_id/lesson_id/)
   └─ Generate unique filename

3. Thumbnail Generation
   ├─ Extract frame at 5 seconds
   └─ Save as thumbnail

4. Database Entry
   ├─ Record file path
   ├─ Store metadata
   └─ Set status = 'completed'

5. Streaming
   ├─ Use HTML5 video tag
   ├─ Support adaptive bitrate
   └─ Track view count
```

### Streaming Code Example
```php
<?php
// stream_video.php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}

$video_id = $_GET['id'];
// Validate student has access
// Stream video with security

header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
?>
```

---

## FILE STRUCTURE ORGANIZATION

```
study-is-funny/
│
├── index.php                    # Entry point
├── config/
│   ├── config.php              # Database credentials
│   ├── constants.php           # Application constants
│   └── database.php            # Database connection class
│
├── includes/
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   ├── navigation.php          # Navigation menu
│   ├── functions.php           # Common functions
│   ├── auth.php                # Authentication functions
│   ├── session_check.php       # Session validation
│   └── error_handler.php       # Error handling
│
├── classes/
│   ├── Database.php            # Database class
│   ├── User.php                # User management
│   ├── Subject.php             # Subject management
│   ├── Lesson.php              # Lesson management
│   ├── Video.php               # Video management
│   ├── Session.php             # Session management
│   ├── Homework.php            # Homework management
│   ├── SessionRegistration.php # Session registration
│   └── Utils.php               # Utility functions
│
├── admin/
│   ├── index.php               # Admin dashboard
│   ├── users/
│   │   ├── index.php           # Users list
│   │   ├── add.php             # Add user
│   │   ├── edit.php            # Edit user
│   │   ├── delete.php          # Delete user
│   │   └── process.php         # Process actions
│   ├── subjects/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── process.php
│   ├── lessons/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── process.php
│   ├── videos/
│   │   ├── index.php           # Video list
│   │   ├── upload.php          # Upload form
│   │   ├── edit.php            # Edit metadata
│   │   ├── delete.php          # Delete video
│   │   ├── upload_handler.php  # Handle upload
│   │   └── stream.php          # Stream video
│   ├── sessions/
│   │   ├── index.php           # Sessions list
│   │   ├── create.php          # Create session
│   │   ├── edit.php            # Edit session
│   │   ├── delete.php          # Delete
│   │   ├── attendance.php      # Attendance tracking
│   │   ├── process.php         # Process actions
│   │   └── api/
│   │       ├── get_sessions.php
│   │       ├── update_session.php
│   │       └── get_registrations.php
│   ├── homework/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── submissions.php
│   │   ├── grade.php
│   │   └── process.php
│   ├── reports/
│   │   ├── attendance.php
│   │   ├── sessions.php
│   │   ├── homework.php
│   │   └── export.php
│   └── css/
│       ├── admin.css
│       └── responsive.css
│
├── student/
│   ├── index.php               # Student dashboard
│   ├── sessions/
│   │   ├── index.php           # View sessions
│   │   ├── detail.php          # Session details
│   │   ├── register.php        # Register for session
│   │   └── api/
│   │       └── register.php    # AJAX registration
│   ├── homework/
│   │   ├── index.php           # Homework list
│   │   ├── submit.php          # Submit homework
│   │   ├── view_submission.php # View submission
│   │   └── api/
│   │       └── submit.php      # AJAX submit
│   ├── videos/
│   │   ├── index.php           # Video list
│   │   ├── watch.php           # Watch video
│   │   └── stream.php          # Secure streaming
│   ├── profile.php
│   ├── my_submissions.php
│   └── css/
│       ├── student.css
│       └── responsive.css
│
├── assets/
│   ├── images/
│   │   ├── logo.png
│   │   ├── icons/
│   │   └── thumbnails/
│   ├── css/
│   │   ├── main.css            # Global styles
│   │   ├── bootstrap.min.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js             # Global scripts
│   │   ├── jquery.min.js
│   │   ├── bootstrap.min.js
│   │   ├── session_tracker.js  # Real-time session tracker
│   │   ├── form_validator.js   # Form validation
│   │   └── api_handler.js      # AJAX requests
│   └── fonts/
│
├── uploads/
│   ├── videos/
│   │   ├── subject_1/
│   │   └── subject_2/
│   ├── homework/
│   ├── resources/
│   └── thumbnails/
│
├── logs/
│   ├── error.log
│   ├── activity.log
│   └── session.log
│
├── api/
│   ├── auth_api.php
│   ├── session_api.php
│   ├── video_api.php
│   ├── homework_api.php
│   └── user_api.php
│
└── docs/
    ├── README.md
    ├── INSTALLATION.md
    ├── API_DOCUMENTATION.md
    └── DATABASE_SCHEMA.md
```

---

## SECURITY GUIDELINES

### 1. **Authentication & Authorization**
```php
// Always validate user session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Check role-based access
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Unauthorized Access');
}
```

### 2. **Input Validation**
```php
// Sanitize all inputs
$input = trim(htmlspecialchars($_POST['input'], ENT_QUOTES, 'UTF-8'));

// Validate email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    die('Invalid email');
}

// Validate integers
$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
```

### 3. **Prepared Statements**
```php
// ALWAYS use prepared statements
$stmt = $mysqli->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
```

### 4. **File Upload Security**
```php
// Validate file type by MIME
$allowed_types = ['video/mp4', 'video/avi', 'video/webm'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['video']['tmp_name']);

if (!in_array($mime_type, $allowed_types)) {
    die('Invalid file type');
}

// Check file size
if ($_FILES['video']['size'] > 500 * 1024 * 1024) {
    die('File too large');
}

// Generate unique filename
$filename = md5(time() . $_FILES['video']['name']) . '.mp4';
```

### 5. **Password Security**
```php
// Use password_hash()
$hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Verify with password_verify()
if (password_verify($_POST['password'], $stored_hash)) {
    // Login success
}
```

### 6. **Session Security**
```php
// Regenerate session ID after login
session_regenerate_id(true);

// Set secure session options
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
```

### 7. **CSRF Protection**
```php
// Generate token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate in form
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Verify on submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token invalid');
}
```

### 8. **Logging & Monitoring**
```php
// Log important actions
function log_activity($user_id, $action, $details) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
}
```

---

## IMPLEMENTATION TIMELINE

### Week 1: Foundation
- [ ] Project setup and configuration
- [ ] Database creation and population
- [ ] Core PHP classes (Database, User, Session)
- [ ] Authentication system (login/logout/register)

### Week 2-3: Admin Core
- [ ] Admin dashboard layout
- [ ] User management CRUD
- [ ] Subject management CRUD
- [ ] Lesson management CRUD

### Week 4: Video System
- [ ] Video upload functionality
- [ ] Thumbnail generation
- [ ] Video management CRUD
- [ ] Video streaming implementation

### Week 5: Sessions
- [ ] Session creation and scheduling
- [ ] Registration system
- [ ] Real-time attendance tracking
- [ ] Session management UI

### Week 6: Homework
- [ ] Homework creation and assignment
- [ ] Submission system
- [ ] Grading interface
- [ ] Analytics/reports

### Week 7: Student Dashboard
- [ ] Student UI implementation
- [ ] Session viewing and registration
- [ ] Homework viewing and submission
- [ ] Video watching

### Week 8: Testing & Deployment
- [ ] Complete testing
- [ ] Bug fixes
- [ ] Performance optimization
- [ ] Security audit
- [ ] Deployment

---

## CRITICAL NEXT STEPS

1. **Import this plan into your GitHub repository as a WIKI or README**
2. **Create a GitHub Project board with milestones**
3. **Set up your development environment (XAMPP/WAMP)**
4. **Create the database using the provided SQL scripts**
5. **Start with Week 1 Foundation phase**
6. **Create feature branches for each component**

---

## NOTES FOR GOOGLE GRAVITY OPTIMIZATION

- **URL-friendly structure:** All URLs follow `/admin/sessions/` pattern
- **API endpoints:** Separate API files for real-time updates
- **JavaScript location:** Minimized, in assets/js folder
- **Database indexing:** All frequently queried columns are indexed
- **Cache strategy:** Implement query caching for dashboard statistics
- **Mobile optimization:** Responsive Bootstrap 5 framework
- **SEO:** Semantic HTML5 throughout

---

**Document prepared for comprehensive backend development.**  
**Ready for implementation with structured team workflow.**