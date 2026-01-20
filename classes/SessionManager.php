<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * Session Management Class for MongoDB
 * Handles teaching sessions, registrations, and attendance
 */
class SessionManager {
    private $db;
    private $collection = 'sessions';
    private $registrationCollection = 'session_registrations';
    
    public function __construct(DatabaseMongo $database) {
        $this->db = $database;
    }
    
    /**
     * Create a new session
     * @param array $sessionData Session data
     * @return mixed Session ID
     */
    public function create($sessionData) {
        $session = [
            'subject' => $sessionData['subject'] ?? '',
            'session_title' => $sessionData['title'] ?? '',
            'session_description' => $sessionData['description'] ?? '',
            'instructor_id' => isset($sessionData['instructor_id']) 
                ? DatabaseMongo::createObjectId($sessionData['instructor_id']) 
                : null,
            'assistant_id' => isset($sessionData['assistant_id']) 
                ? DatabaseMongo::createObjectId($sessionData['assistant_id']) 
                : null,
            'center_id' => isset($sessionData['center_id']) 
                ? DatabaseMongo::createObjectId($sessionData['center_id']) 
                : null,
            'session_type' => $sessionData['session_type'] ?? 'general_study',
            'start_time' => isset($sessionData['start_time']) 
                ? DatabaseMongo::createUTCDateTime(strtotime($sessionData['start_time']) * 1000) 
                : null,
            'end_time' => isset($sessionData['end_time']) 
                ? DatabaseMongo::createUTCDateTime(strtotime($sessionData['end_time']) * 1000) 
                : null,
            'recurrence_type' => $sessionData['recurrence_type'] ?? 'once',
            'day_of_week' => $sessionData['day_of_week'] ?? null,
            'max_participants' => $sessionData['max_participants'] ?? null,
            'meeting_link' => $sessionData['meeting_link'] ?? null,
            'is_active' => $sessionData['is_active'] ?? true,
            'session_status' => 'scheduled',
            'createdAt' => DatabaseMongo::createUTCDateTime(),
            'updatedAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        // Add homework_id if this is a homework session
        if (isset($sessionData['homework_id'])) {
            $session['homework_id'] = DatabaseMongo::createObjectId($sessionData['homework_id']);
        }
        
        // Add video content if applicable
        if (isset($sessionData['video_url'])) {
            $session['video_url'] = $sessionData['video_url'];
            $session['video_duration'] = $sessionData['video_duration'] ?? null;
        }
        
        return $this->db->insert($this->collection, $session);
    }
    
    /**
     * Get session by ID
     * @param string $sessionId Session ID
     * @return object|null
     */
    public function getById($sessionId) {
        try {
            $filter = ['_id' => DatabaseMongo::createObjectId($sessionId)];
            return $this->db->findOne($this->collection, $filter);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get all sessions
     * @param array $filters Filters (subject, center_id, status, etc.)
     * @param int $limit Limit results
     * @return array
     */
    public function getAll($filters = [], $limit = 100) {
        $filter = ['is_active' => true];
        
        if (isset($filters['subject'])) {
            $filter['subject'] = $filters['subject'];
        }
        
        if (isset($filters['center_id'])) {
            $filter['center_id'] = DatabaseMongo::createObjectId($filters['center_id']);
        }
        
        if (isset($filters['session_status'])) {
            $filter['session_status'] = $filters['session_status'];
        }
        
        if (isset($filters['session_type'])) {
            $filter['session_type'] = $filters['session_type'];
        }
        
        $options = [
            'sort' => ['start_time' => -1],
            'limit' => $limit
        ];
        
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get upcoming sessions
     * @param int $limit Limit results
     * @return array
     */
    public function getUpcoming($limit = 10) {
        $now = DatabaseMongo::createUTCDateTime();
        
        $filter = [
            'is_active' => true,
            'session_status' => 'scheduled',
            'start_time' => ['$gt' => $now]
        ];
        
        $options = [
            'sort' => ['start_time' => 1],
            'limit' => $limit
        ];
        
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Update session
     * @param string $sessionId Session ID
     * @param array $data Update data
     * @return int Modified count
     */
    public function update($sessionId, $data) {
        $filter = ['_id' => DatabaseMongo::createObjectId($sessionId)];
        
        // Convert datetime fields if present
        if (isset($data['start_time']) && is_string($data['start_time'])) {
            $data['start_time'] = DatabaseMongo::createUTCDateTime(strtotime($data['start_time']) * 1000);
        }
        
        if (isset($data['end_time']) && is_string($data['end_time'])) {
            $data['end_time'] = DatabaseMongo::createUTCDateTime(strtotime($data['end_time']) * 1000);
        }
        
        $data['updatedAt'] = DatabaseMongo::createUTCDateTime();
        
        $update = ['$set' => $data];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Start session
     * @param string $sessionId Session ID
     * @return int Modified count
     */
    public function startSession($sessionId) {
        return $this->update($sessionId, [
            'session_status' => 'in_progress',
            'actual_start_time' => DatabaseMongo::createUTCDateTime()
        ]);
    }
    
    /**
     * End session
     * @param string $sessionId Session ID
     * @return int Modified count
     */
    public function endSession($sessionId) {
        return $this->update($sessionId, [
            'session_status' => 'completed',
            'actual_end_time' => DatabaseMongo::createUTCDateTime()
        ]);
    }
    
    /**
     * Cancel session
     * @param string $sessionId Session ID
     * @return int Modified count
     */
    public function cancelSession($sessionId) {
        return $this->update($sessionId, [
            'session_status' => 'cancelled'
        ]);
    }
    
    /**
     * Delete session
     * @param string $sessionId Session ID
     * @return int Deleted count
     */
    public function delete($sessionId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($sessionId)];
        return $this->db->delete($this->collection, $filter);
    }
    
    /**
     * Register student for session
     * @param string $sessionId Session ID
     * @param string $studentId Student ID
     * @return mixed Registration ID
     */
    public function registerStudent($sessionId, $studentId) {
        // Check if already registered
        $existing = $this->db->findOne($this->registrationCollection, [
            'session_id' => DatabaseMongo::createObjectId($sessionId),
            'student_id' => DatabaseMongo::createObjectId($studentId)
        ]);
        
        if ($existing) {
            throw new Exception('Student already registered for this session');
        }
        
        // Check session capacity
        $session = $this->getById($sessionId);
        if ($session && isset($session->max_participants)) {
            $currentCount = $this->getRegistrationCount($sessionId);
            if ($currentCount >= $session->max_participants) {
                throw new Exception('Session is full');
            }
        }
        
        $registration = [
            'session_id' => DatabaseMongo::createObjectId($sessionId),
            'student_id' => DatabaseMongo::createObjectId($studentId),
            'attendance_status' => 'registered',
            'registration_time' => DatabaseMongo::createUTCDateTime(),
            'createdAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        return $this->db->insert($this->registrationCollection, $registration);
    }
    
    /**
     * Get session registrations
     * @param string $sessionId Session ID
     * @return array
     */
    public function getRegistrations($sessionId) {
        $filter = ['session_id' => DatabaseMongo::createObjectId($sessionId)];
        return $this->db->find($this->registrationCollection, $filter);
    }
    
    /**
     * Get registration count
     * @param string $sessionId Session ID
     * @return int
     */
    public function getRegistrationCount($sessionId) {
        $filter = ['session_id' => DatabaseMongo::createObjectId($sessionId)];
        return $this->db->count($this->registrationCollection, $filter);
    }
    
    /**
     * Mark attendance (check-in)
     * @param string $sessionId Session ID
     * @param string $studentId Student ID
     * @return int Modified count
     */
    public function checkIn($sessionId, $studentId) {
        $filter = [
            'session_id' => DatabaseMongo::createObjectId($sessionId),
            'student_id' => DatabaseMongo::createObjectId($studentId)
        ];
        
        $update = [
            '$set' => [
                'attendance_status' => 'attended',
                'check_in_time' => DatabaseMongo::createUTCDateTime()
            ]
        ];
        
        return $this->db->update($this->registrationCollection, $filter, $update);
    }
    
    /**
     * Mark check-out
     * @param string $sessionId Session ID
     * @param string $studentId Student ID
     * @return int Modified count
     */
    public function checkOut($sessionId, $studentId) {
        $filter = [
            'session_id' => DatabaseMongo::createObjectId($sessionId),
            'student_id' => DatabaseMongo::createObjectId($studentId)
        ];
        
        $update = [
            '$set' => [
                'check_out_time' => DatabaseMongo::createUTCDateTime()
            ]
        ];
        
        return $this->db->update($this->registrationCollection, $filter, $update);
    }
}
?>
