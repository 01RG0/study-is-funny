<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * Analytics Class for MongoDB
 * Generate reports and statistics
 */
class Analytics {
    private $db;
    
    public function __construct(DatabaseMongo $database) {
        $this->db = $database;
    }
    
    /**
     * Get user statistics
     * @param array $filters Filters
     * @return array
     */
    public function getUserStats($filters = []) {
        $filter = [];
        
        if (isset($filters['role'])) {
            $filter['role'] = $filters['role'];
        }
        
        $totalUsers = $this->db->count('users', $filter);
        $activeUsers = $this->db->count('users', array_merge($filter, ['isActive' => true]));
        
        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => $totalUsers - $activeUsers
        ];
    }
    
    /**
     * Get session statistics
     * @param array $filters Filters
     * @return array
     */
    public function getSessionStats($filters = []) {
        $filter = ['is_active' => true];
        
        if (isset($filters['subject'])) {
            $filter['subject'] = $filters['subject'];
        }
        
        $total = $this->db->count('sessions', $filter);
        $scheduled = $this->db->count('sessions', array_merge($filter, ['session_status' => 'scheduled']));
        $completed = $this->db->count('sessions', array_merge($filter, ['session_status' => 'completed']));
        
        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'completed' => $completed,
            'in_progress' => $this->db->count('sessions', array_merge($filter, ['session_status' => 'in_progress']))
        ];
    }
    
    /**
     * Get homework statistics
     * @param array $filters Filters
     * @return array
     */
    public function getHomeworkStats($filters = []) {
        $filter = [];
        
        if (isset($filters['subject_id'])) {
            $filter['subject_id'] = DatabaseMongo::createObjectId($filters['subject_id']);
        }
        
        $total = $this->db->count('homework', $filter);
        $active = $this->db->count('homework', array_merge($filter, ['status' => 'active']));
        $closed = $this->db->count('homework', array_merge($filter, ['status' => 'closed']));
        
        // Get submission stats
        $submissions = $this->db->count('homework_submissions');
        $graded = $this->db->count('homework_submissions', ['status' => 'graded']);
        
        return [
            'total_homework' => $total,
            'active' => $active,
            'closed' => $closed,
            'total_submissions' => $submissions,
            'graded_submissions' => $graded,
            'pending_submissions' => $submissions - $graded
        ];
    }
    
    /**
     * Get video statistics
     * @param array $filters Filters
     * @return array
     */
    public function getVideoStats($filters = []) {
        $filter = ['status' => 'completed'];
        
        if (isset($filters['subject_id'])) {
            $filter['subject_id'] = DatabaseMongo::createObjectId($filters['subject_id']);
        }
        
        $videos = $this->db->find('videos', $filter);
        
        $stats = [
            'total_videos' => count($videos),
            'total_views' => 0,
            'total_size_mb' => 0,
            'average_views' => 0
        ];
        
        foreach ($videos as $video) {
            $stats['total_views'] += $video->view_count ?? 0;
            $stats['total_size_mb'] += $video->file_size_mb ?? 0;
        }
        
        if ($stats['total_videos'] > 0) {
            $stats['average_views'] = round($stats['total_views'] / $stats['total_videos'], 2);
        }
        
        $stats['total_size_mb'] = round($stats['total_size_mb'], 2);
        
        return $stats;
    }
    
    /**
     * Get student statistics
     * @param array $filters Filters
     * @return array
     */
    public function getStudentStats($filters = []) {
        $filter = [];
        
        if (isset($filters['subject'])) {
            $filter['subject'] = $filters['subject'];
        }
        
        if (isset($filters['grade'])) {
            $filter['grade'] = $filters['grade'];
        }
        
        $total = $this->db->count('all_students_view', $filter);
        $active = $this->db->count('all_students_view', array_merge($filter, ['isActive' => true]));
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active
        ];
    }
    
    /**
     * Get attendance report
     * @param string $subject Subject
     * @param int $sessionNumber Session number
     * @return array
     */
    public function getAttendanceReport($subject, $sessionNumber) {
        $students = $this->db->find('all_students_view', ['subject' => $subject]);
        
        $report = [
            'session_number' => $sessionNumber,
            'subject' => $subject,
            'total_students' => count($students),
            'present' => 0,
            'absent' => 0,
            'not_recorded' => 0,
            'attendance_rate' => 0
        ];
        
        $sessionKey = 'session_' . $sessionNumber;
        
        foreach ($students as $student) {
            if (isset($student->$sessionKey) && isset($student->$sessionKey->attendance)) {
                if ($student->$sessionKey->attendance === 'present') {
                    $report['present']++;
                } elseif ($student->$sessionKey->attendance === 'absent') {
                    $report['absent']++;
                }
            } else {
                $report['not_recorded']++;
            }
        }
        
        if ($report['total_students'] > 0) {
            $report['attendance_rate'] = round(($report['present'] / $report['total_students']) * 100, 2);
        }
        
        return $report;
    }
    
    /**
     * Get homework completion report
     * @param string $homeworkId Homework ID
     * @return array
     */
    public function getHomeworkCompletionReport($homeworkId) {
        $submissions = $this->db->find('homework_submissions', [
            'homework_id' => DatabaseMongo::createObjectId($homeworkId)
        ]);
        
        $report = [
            'total_submissions' => count($submissions),
            'graded' => 0,
            'pending' => 0,
            'late' => 0,
            'average_score' => 0,
            'scores' => []
        ];
        
        $totalScore = 0;
        $gradedCount = 0;
        
        foreach ($submissions as $submission) {
            if ($submission->status === 'graded') {
                $report['graded']++;
                if (isset($submission->score)) {
                    $totalScore += $submission->score;
                    $gradedCount++;
                    $report['scores'][] = $submission->score;
                }
            } elseif ($submission->status === 'late') {
                $report['late']++;
            } else {
                $report['pending']++;
            }
        }
        
        if ($gradedCount > 0) {
            $report['average_score'] = round($totalScore / $gradedCount, 2);
        }
        
        return $report;
    }
    
    /**
     * Get dashboard summary
     * @return array
     */
    public function getDashboardSummary() {
        return [
            'users' => $this->getUserStats(),
            'sessions' => $this->getSessionStats(),
            'homework' => $this->getHomeworkStats(),
            'videos' => $this->getVideoStats(),
            'students' => $this->getStudentStats()
        ];
    }
}
?>
