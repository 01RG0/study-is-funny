<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * Student Management Class for MongoDB
 * Handles student data from all_students_view and subject collections
 */
class Student {
    private $db;
    private $viewCollection = 'all_students_view';
    
    public function __construct(DatabaseMongo $database) {
        $this->db = $database;
    }
    
    /**
     * Get student by ID
     * @param string $studentId Student ID or phone
     * @param string $subject Subject filter
     * @return object|null
     */
    public function getById($studentId, $subject = null) {
        try {
            $filter = [
                '$or' => [
                    ['studentId' => (int)$studentId],
                    ['phone' => $studentId]
                ]
            ];
            
            if ($subject) {
                $filter['subject'] = $subject;
            }
            
            return $this->db->findOne($this->viewCollection, $filter);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get student by phone
     * @param string $phone Phone number
     * @return object|null
     */
    public function getByPhone($phone) {
        $filter = ['phone' => $phone];
        return $this->db->findOne($this->viewCollection, $filter);
    }
    
    /**
     * Get all students
     * @param array $filters Filters (subject, grade, center, active status)
     * @param int $limit Limit results
     * @return array
     */
    public function getAll($filters = [], $limit = 100) {
        $filter = [];
        
        if (isset($filters['subject'])) {
            $filter['subject'] = $filters['subject'];
        }
        
        if (isset($filters['grade'])) {
            $filter['grade'] = $filters['grade'];
        }
        
        if (isset($filters['center'])) {
            $filter['center'] = $filters['center'];
        }
        
        if (isset($filters['isActive'])) {
            $filter['isActive'] = $filters['isActive'];
        }
        
        $options = [
            'sort' => ['studentName' => 1],
            'limit' => $limit
        ];
        
        return $this->db->find($this->viewCollection, $filter, $options);
    }
    
    /**
     * Get session data for student
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @return object|null
     */
    public function getSessionData($studentId, $sessionNumber, $subject) {
        $student = $this->getById($studentId, $subject);
        
        if (!$student) {
            return null;
        }
        
        $sessionKey = 'session_' . $sessionNumber;
        return $student->$sessionKey ?? null;
    }
    
    /**
     * Update session data
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @param array $data Session data to update
     * @return int Modified count
     */
    public function updateSessionData($studentId, $sessionNumber, $subject, $data) {
        $filter = [
            '$or' => [
                ['studentId' => (int)$studentId],
                ['phone' => $studentId]
            ],
            'subject' => $subject
        ];
        
        $sessionKey = 'session_' . $sessionNumber;
        $update = ['$set' => [$sessionKey => $data]];
        
        return $this->db->update($this->viewCollection, $filter, $update);
    }
    
    /**
     * Record attendance
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @param string $status Attendance status
     * @return int Modified count
     */
    public function recordAttendance($studentId, $sessionNumber, $subject, $status = 'present') {
        $sessionData = $this->getSessionData($studentId, $sessionNumber, $subject);
        
        if (!$sessionData) {
            $sessionData = new stdClass();
        }
        
        $sessionData->attendance = $status;
        $sessionData->attendance_date = new MongoDB\BSON\UTCDateTime();
        
        return $this->updateSessionData($studentId, $sessionNumber, $subject, (array)$sessionData);
    }
    
    /**
     * Record homework submission
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @param string $status Homework status
     * @return int Modified count
     */
    public function recordHomework($studentId, $sessionNumber, $subject, $status = 'done') {
        $sessionData = $this->getSessionData($studentId, $sessionNumber, $subject);
        
        if (!$sessionData) {
            $sessionData = new stdClass();
        }
        
        $sessionData->homework = $status;
        $sessionData->homework_date = new MongoDB\BSON\UTCDateTime();
        
        return $this->updateSessionData($studentId, $sessionNumber, $subject, (array)$sessionData);
    }
    
    /**
     * Record payment
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @param float $amount Payment amount
     * @return int Modified count
     */
    public function recordPayment($studentId, $sessionNumber, $subject, $amount) {
        $sessionData = $this->getSessionData($studentId, $sessionNumber, $subject);
        
        if (!$sessionData) {
            $sessionData = new stdClass();
        }
        
        $sessionData->payment = $amount;
        $sessionData->payment_date = new MongoDB\BSON\UTCDateTime();
        
        return $this->updateSessionData($studentId, $sessionNumber, $subject, (array)$sessionData);
    }
    
    /**
     * Get student statistics
     * @param string $studentId Student ID or phone
     * @param string $subject Subject
     * @return array
     */
    public function getStatistics($studentId, $subject) {
        $student = $this->getById($studentId, $subject);
        
        if (!$student) {
            return [];
        }
        
        $stats = [
            'total_sessions' => 0,
            'attended' => 0,
            'absent' => 0,
            'homework_done' => 0,
            'homework_pending' => 0,
            'total_paid' => 0,
            'attendance_rate' => 0,
            'homework_rate' => 0
        ];
        
        // Count sessions dynamically
        foreach ($student as $key => $value) {
            if (strpos($key, 'session_') === 0 && is_object($value)) {
                $stats['total_sessions']++;
                
                if (isset($value->attendance)) {
                    if ($value->attendance === 'present') {
                        $stats['attended']++;
                    } elseif ($value->attendance === 'absent') {
                        $stats['absent']++;
                    }
                }
                
                if (isset($value->homework)) {
                    if ($value->homework === 'done') {
                        $stats['homework_done']++;
                    } else {
                        $stats['homework_pending']++;
                    }
                }
                
                if (isset($value->payment)) {
                    $stats['total_paid'] += (float)$value->payment;
                }
            }
        }
        
        // Calculate rates
        if ($stats['total_sessions'] > 0) {
            $stats['attendance_rate'] = round(($stats['attended'] / $stats['total_sessions']) * 100, 2);
            $stats['homework_rate'] = round(($stats['homework_done'] / $stats['total_sessions']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Check session access
     * @param string $studentId Student ID or phone
     * @param int $sessionNumber Session number
     * @param string $subject Subject
     * @return bool
     */
    public function hasSessionAccess($studentId, $sessionNumber, $subject) {
        $sessionData = $this->getSessionData($studentId, $sessionNumber, $subject);
        
        if (!$sessionData) {
            return false;
        }
        
        return isset($sessionData->online_session) && $sessionData->online_session === true;
    }
}
?>
