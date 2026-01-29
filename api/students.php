<?php
// Prevent HTML errors from being output - output JSON only
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

header('Content-Type: application/json');

// Catch any fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignore deprecated warnings
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return true;
    }
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

// Initialize MongoDB directly if not available from config
if (!$GLOBALS['mongoClient']) {
    try {
        if (class_exists('MongoDB\\Driver\\Manager')) {
            $mongoUri = defined('MONGO_URI') ? MONGO_URI : 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
            $dbName = defined('DB_NAME') ? DB_NAME : 'attendance_system';
            
            $GLOBALS['mongoClient'] = new MongoDB\Driver\Manager($mongoUri);
            $GLOBALS['databaseName'] = $dbName;
            
            // Test connection
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $GLOBALS['mongoClient']->executeCommand('admin', $command);
        }
    } catch (Exception $e) {
        // Still not available
    }
}

// Final check
if (!$GLOBALS['mongoClient']) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection error: MongoDB extension not available. Please enable MongoDB in hosting control panel.'
    ]);
    exit;
}

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
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGet($action) {
    switch ($action) {
        case 'get':
            getStudent();
            break;
        case 'getByParentPhone':
            getStudentByParentPhone();
            break;
        case 'all':
            getAllStudents();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'register':
            registerStudent($data);
            break;
        case 'login':
            loginStudent($data);
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
            updateStudent($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function registerStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        // Validate required fields
        if (!isset($data['name']) || !isset($data['phone']) || !isset($data['password']) || !isset($data['grade'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $phone = $data['phone'];

        // Check if student already exists
        $filter = ['phone' => $phone];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $existingStudent = current($cursor->toArray());

        if ($existingStudent) {
            echo json_encode(['success' => false, 'message' => 'Student already exists']);
            return;
        }

        // Create new student
        $studentData = [
            'name' => $data['name'],
            'phone' => $phone,
            'password' => $data['password'],
            'grade' => $data['grade'],
            'subjects' => $data['grade'] === 'senior1' ? ["mathematics"] : ["physics", "mathematics", "mechanics"],
            'joinDate' => new MongoDB\BSON\UTCDateTime(),
            'lastLogin' => new MongoDB\BSON\UTCDateTime(),
            'isActive' => true,
            'totalSessionsViewed' => 0,
            'totalWatchTime' => 0,
            'activityLog' => []
        ];

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($studentData);
        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        if ($result->getInsertedCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Student registered successfully!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Registration error: ' . $e->getMessage()]);
    }
}

function loginStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['phone']) || !isset($data['password'])) {
            echo json_encode(['success' => false, 'message' => 'Missing phone or password']);
            return;
        }

        $phone = $data['phone'];
        $password = $data['password'];

        // Find student
        $filter = ['phone' => $phone, 'password' => $password, 'isActive' => true];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $student = current($cursor->toArray());

        if ($student) {
            // Update last login
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(
                ['phone' => $phone],
                ['$set' => ['lastLogin' => new MongoDB\BSON\UTCDateTime()]]
            );
            $client->executeBulkWrite("$databaseName.users", $bulk);

            // Convert BSON to array for JSON response
            $studentArray = [
                'name' => $student->name,
                'phone' => $student->phone,
                'grade' => $student->grade,
                'subjects' => $student->subjects,
                'joinDate' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : null,
                'lastLogin' => date('c'),
                'isActive' => $student->isActive,
                'totalSessionsViewed' => $student->totalSessionsViewed ?? 0,
                'totalWatchTime' => $student->totalWatchTime ?? 0
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Login successful!',
                'user' => $studentArray
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid phone or password']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
    }
}

function getStudent() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $phone = $_GET['phone'] ?? '';
        $subjectFilter = $_GET['subject'] ?? '';
        if (!$phone) {
            echo json_encode(['success' => false, 'message' => 'Phone number required']);
            return;
        }

        // Normalize phone formats
        $phonesToTry = [$phone];
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
            $phonesToTry[] = '+2' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        }

        // 1. PRIMARY: Search 'all_students_view' (management system - MOST ACCURATE)
        $viewQuery = new MongoDB\Driver\Query(['phone' => ['$in' => $phonesToTry]]);
        $viewCursor = $client->executeQuery("$databaseName.all_students_view", $viewQuery);
        $allMatches = $viewCursor->toArray();

        if (!empty($allMatches)) {
            $subjectMapping = [
                'math' => 'mathematics',
                'pure math' => 'pure_math',
                'pure' => 'pure_math',
                'physics' => 'physics',
                'mechanics' => 'mechanics',
                'stat' => 'statistics',
                'statistics' => 'statistics'
            ];

            $normalizeSubject = function ($rawSubject) use ($subjectMapping) {
                $cleanedSubject = preg_replace('/^\s*S[123]\s*-?\s*/i', '', strtolower($rawSubject));
                foreach ($subjectMapping as $key => $slug) {
                    if (stripos($cleanedSubject, $key) !== false) {
                        return $slug;
                    }
                }
                if (stripos($cleanedSubject, 'mathematics') !== false || stripos($cleanedSubject, 'math') !== false) {
                    return 'mathematics';
                }
                if (stripos($cleanedSubject, 'pure math') !== false || stripos($cleanedSubject, 'pure') !== false) {
                    return 'pure_math';
                }
                if (stripos($cleanedSubject, 'physics') !== false) {
                    return 'physics';
                }
                if (stripos($cleanedSubject, 'mechanics') !== false) {
                    return 'mechanics';
                }
                if (stripos($cleanedSubject, 'statistics') !== false || stripos($cleanedSubject, 'stat') !== false) {
                    return 'statistics';
                }
                return null;
            };

            if (!empty($subjectFilter)) {
                $allMatches = array_values(array_filter($allMatches, function ($match) use ($normalizeSubject, $subjectFilter) {
                    $rawSubject = $match->subject ?? '';
                    return $normalizeSubject($rawSubject) === $subjectFilter;
                }));
            }

            if (empty($allMatches)) {
                echo json_encode(['success' => false, 'message' => 'No subject data found for this student']);
                return;
            }

            // Use the first match as the base student
            $base = (array)$allMatches[0];
            $merged = [
                'name' => $base['studentName'] ?? $base['name'] ?? 'Student',
                'phone' => $phone,
                'subjects' => [],
                'subjectIds' => [],
                'grade' => 'senior1',
                'isActive' => true
            ];
            foreach ($allMatches as $match) {
                if (isset($match->subject)) {
                    if (strpos($match->subject, 'S1') !== false) $merged['grade'] = 'senior1';
                    elseif (strpos($match->subject, 'S2') !== false) $merged['grade'] = 'senior2';
                    elseif (strpos($match->subject, 'S3') !== false) $merged['grade'] = 'senior3';
                }

                $foundSlug = $normalizeSubject($match->subject ?? '');

                if ($foundSlug && !in_array($foundSlug, $merged['subjects'])) {
                    $merged['subjects'][] = $foundSlug;
                    $merged['subjectIds'][$foundSlug] = $match->studentId ?? null;
                }
            }

            if (empty($merged['subjects'])) {
                $merged['subjects'] = ($merged['grade'] === 'senior1') ? ['mathematics'] : ['physics', 'mathematics', 'mechanics'];
            }
            // Merge session fields - only from matching subject if filter is applied
            $sessionFields = [];
            foreach ($allMatches as $match) {
                foreach ((array)$match as $k => $v) {
                    if (strpos($k, 'session_') === 0) {
                        // If subject filter is applied, only include sessions from matching subjects
                        if (!empty($subjectFilter)) {
                            $matchSubject = $normalizeSubject($match->subject ?? '');
                            if ($matchSubject === $subjectFilter) {
                                $sessionFields[$k] = $v;
                            }
                        } else {
                            // No filter - include all sessions
                            $sessionFields[$k] = $v;
                        }
                    }
                }
            }
            $studentArray = array_merge([
                'name' => $merged['name'],
                'phone' => $phone,
                'grade' => $merged['grade'],
                'subjects' => array_values($merged['subjects']),
                'subjectIds' => $merged['subjectIds'],
                'subject' => $subjectFilter ?: null,
                'isActive' => true,
                'totalSessionsViewed' => 0,
                'totalWatchTime' => 0,
                'totalSessions' => count($merged['subjects']) * 5,
                'watchedSessions' => 0,
                'totalWatchTimeFormatted' => '0h 0m'
            ], $sessionFields);

            echo json_encode(['success' => true, 'student' => $studentArray]);
            return;
        }

        // 2. FALLBACK: Try 'users' collection (platform accounts)
        $filter = ['phone' => ['$in' => $phonesToTry]];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $platformStudent = current($cursor->toArray());

        if ($platformStudent) {
            $studentArray = [
                'name' => $platformStudent->name ?? 'Student',
                'phone' => $phone,
                'grade' => $platformStudent->grade ?? 'senior1',
                'subjects' => isset($platformStudent->subjects) ? array_values((array)$platformStudent->subjects) : (($platformStudent->grade ?? 'senior1') === 'senior1' ? ['mathematics'] : ['physics', 'mathematics', 'mechanics']),
                'isActive' => $platformStudent->isActive ?? true,
                'totalSessionsViewed' => $platformStudent->totalSessionsViewed ?? 0,
                'totalWatchTime' => $platformStudent->totalWatchTime ?? 0,
                'totalSessions' => isset($platformStudent->subjects) ? count((array)$platformStudent->subjects) * 5 : 15,
                'watchedSessions' => $platformStudent->totalSessionsViewed ?? 0,
                'totalWatchTimeFormatted' => formatWatchTime($platformStudent->totalWatchTime ?? 0)
            ];

            echo json_encode(['success' => true, 'student' => $studentArray]);
            return;
        }

        echo json_encode(['success' => false, 'message' => "Student not found with phone: " . implode(', ', $phonesToTry)]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getStudentByParentPhone() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $parentPhone = $_GET['parentPhone'] ?? '';
        if (!$parentPhone) {
            echo json_encode(['success' => false, 'message' => 'Parent phone number required']);
            return;
        }

        // Normalize phone formats
        $phonesToTry = [$parentPhone];
        $cleanPhone = preg_replace('/[^0-9]/', '', $parentPhone);
        
        // Handle different phone formats
        if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '20') {
            // 201060416120 -> 01060416120
            $phonesToTry[] = '0' . substr($cleanPhone, 2);
            $phonesToTry[] = '+' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        } elseif (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
            // 01060416120 -> 201060416120
            $phonesToTry[] = '+2' . $cleanPhone;
            $phonesToTry[] = '2' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        } elseif (strlen($cleanPhone) === 10) {
            // 1060416120 -> 01060416120 and 201060416120
            $phonesToTry[] = '0' . $cleanPhone;
            $phonesToTry[] = '+20' . $cleanPhone;
            $phonesToTry[] = '20' . $cleanPhone;
        }

        // Search in all_students_view for parentPhone
        $viewQuery = new MongoDB\Driver\Query(['parentPhone' => ['$in' => $phonesToTry]]);
        $viewCursor = $client->executeQuery("$databaseName.all_students_view", $viewQuery);
        $matches = $viewCursor->toArray();

        if (!empty($matches)) {
            $students = [];
            $studentsByPhone = [];
            
            // Group all rows by student phone to aggregate subjects
            foreach ($matches as $studentData) {
                $studentPhone = $studentData->phone ?? $studentData->studentPhone ?? null;
                
                if ($studentPhone) {
                    if (!isset($studentsByPhone[$studentPhone])) {
                        $studentsByPhone[$studentPhone] = [
                            'name' => $studentData->studentName ?? $studentData->name ?? 'Student',
                            'phone' => $studentPhone,
                            'grade' => $studentData->grade ?? 'senior1',
                            'subjects' => [],
                            'subject' => ''
                        ];
                    }
                    
                    // Collect all subjects from all rows for this student
                    if (isset($studentData->subject) && $studentData->subject) {
                        $subjectValue = $studentData->subject;
                        // Extract subject name (remove grade prefix like S1, S2, S3)
                        $cleanSubject = preg_replace('/^\s*S[123]\s*-?\s*/i', '', $subjectValue);
                        $cleanSubject = trim(strtolower($cleanSubject));
                        
                        // Map to standard subject slugs
                        $subjectMapping = [
                            // Senior 1
                            'math' => 'mathematics',
                            'mathematics' => 'mathematics',
                            // Senior 2
                            'pure math' => 'pure_math',
                            'pure' => 'pure_math',
                            'mechanics' => 'mechanics',
                            'physics' => 'physics',
                            // Senior 3
                            'statistics' => 'statistics',
                            'stat' => 'statistics'
                        ];
                        
                        $slug = null;
                        // Check in order of specificity
                        foreach ($subjectMapping as $key => $value) {
                            if (stripos($cleanSubject, $key) !== false) {
                                $slug = $value;
                                break;
                            }
                        }
                        
                        // Fallback: direct comparison
                        if (!$slug) {
                            if (stripos($cleanSubject, 'mathematics') !== false || stripos($cleanSubject, 'math') !== false) {
                                $slug = 'mathematics';
                            } elseif (stripos($cleanSubject, 'pure math') !== false || stripos($cleanSubject, 'pure') !== false) {
                                $slug = 'pure_math';
                            } elseif (stripos($cleanSubject, 'physics') !== false) {
                                $slug = 'physics';
                            } elseif (stripos($cleanSubject, 'mechanics') !== false) {
                                $slug = 'mechanics';
                            } elseif (stripos($cleanSubject, 'statistics') !== false || stripos($cleanSubject, 'stat') !== false) {
                                $slug = 'statistics';
                            } else {
                                $slug = 'mathematics'; // default
                            }
                        }
                        
                        if (!in_array($slug, $studentsByPhone[$studentPhone]['subjects'])) {
                            $studentsByPhone[$studentPhone]['subjects'][] = $slug;
                        }
                        
                        // Keep the first subject for backward compatibility
                        if (!$studentsByPhone[$studentPhone]['subject']) {
                            $studentsByPhone[$studentPhone]['subject'] = $subjectValue;
                        }
                    }
                    
                    // Update grade from all rows
                    if (isset($studentData->grade)) {
                        $studentsByPhone[$studentPhone]['grade'] = $studentData->grade;
                    }
                }
            }
            
            // Convert to final array
            foreach ($studentsByPhone as $phone => $studentData) {
                if (empty($studentData['subjects'])) {
                    $studentData['subjects'] = ['mathematics'];
                }
                $students[] = [
                    'name' => $studentData['name'],
                    'phone' => $phone,
                    'parentPhone' => $parentPhone,
                    'grade' => $studentData['grade'],
                    'subject' => $studentData['subject'],
                    'subjects' => array_values(array_unique($studentData['subjects'])),
                    'isActive' => true
                ];
            }
            
            if (!empty($students)) {
                echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
                return;
            }
        }

        // Fallback: Search in users collection
        $filter = ['parentPhone' => ['$in' => $phonesToTry]];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $client->executeQuery("$databaseName.users", $query);
        $studentsArray = $cursor->toArray();

        if (!empty($studentsArray)) {
            $students = [];
            foreach ($studentsArray as $student) {
                // Get the actual subject value from database
                $subjectValue = '';
                if (isset($student->subject)) {
                    $subjectValue = $student->subject;
                }
                
                // Get unique subjects - handle both array and string formats
                $subjectsArray = [];
                if (isset($student->subjects) && is_array($student->subjects)) {
                    $subjectsArray = array_values((array)$student->subjects);
                } elseif (isset($student->subjects) && is_object($student->subjects)) {
                    // Handle BSON array objects
                    $subjectsArray = array_values((array)$student->subjects);
                } elseif (!empty($subjectValue)) {
                    $subjectsArray = [$subjectValue];
                } else {
                    $subjectsArray = ['mathematics'];
                }
                
                // Remove duplicates
                $subjectsArray = array_values(array_unique($subjectsArray));
                
                $students[] = [
                    'name' => $student->name ?? 'Student',
                    'phone' => $student->phone ?? '',
                    'parentPhone' => $parentPhone,
                    'grade' => $student->grade ?? 'senior1',
                    'subject' => $subjectValue,
                    'subjects' => $subjectsArray,
                    'isActive' => $student->isActive ?? true
                ];
            }

            echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'No student found with parent phone: ' . $parentPhone]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAllStudents() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        $filter = ['isActive' => true];
        $options = [
            'sort' => ['joinDate' => -1]
        ];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $client->executeQuery("$databaseName.users", $query);

        $students = [];
        foreach ($cursor as $student) {
            $students[] = [
                'name' => $student->name,
                'phone' => $student->phone,
                'grade' => $student->grade,
                'subjects' => $student->subjects,
                'joinDate' => $student->joinDate ? $student->joinDate->toDateTime()->format('c') : null,
                'lastLogin' => $student->lastLogin ? $student->lastLogin->toDateTime()->format('c') : null,
                'isActive' => $student->isActive,
                'totalSessionsViewed' => $student->totalSessionsViewed ?? 0,
                'totalWatchTime' => $student->totalWatchTime ?? 0
            ];
        }

        echo json_encode([
            'success' => true,
            'documents' => $students
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching students: ' . $e->getMessage()]);
    }
}

function updateStudent($data) {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];

        if (!isset($data['phone'])) {
            echo json_encode(['success' => false, 'message' => 'Phone number required']);
            return;
        }

        $phone = $data['phone'];
        unset($data['phone']); // Remove phone from update data

        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['phone' => $phone],
            ['$set' => $data]
        );

        $result = $client->executeBulkWrite("$databaseName.users", $bulk);

        echo json_encode([
            'success' => true,
            'modifiedCount' => $result->getModifiedCount(),
            'message' => 'Student updated successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Update error: ' . $e->getMessage()]);
    }
}

function formatWatchTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return "{$hours}h {$mins}m";
}
?>