<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * Homework Management Class for MongoDB
 * Handles homework assignments, submissions, and grading
 */
class Homework {
    private $db;
    private $collection = 'homework';
    private $submissionCollection = 'homework_submissions';
    
    public function __construct(DatabaseMongo $database) {
        $this->db = $database;
    }
    
    /**
     * Create homework assignment
     * @param array $homeworkData Homework data
     * @return mixed Homework ID
     */
    public function create($homeworkData) {
        $homework = [
            'title' => $homeworkData['title'] ?? 'Untitled Homework',
            'description' => $homeworkData['description'] ?? '',
            'instructions' => $homeworkData['instructions'] ?? '',
            'subject_id' => isset($homeworkData['subject_id']) 
                ? DatabaseMongo::createObjectId($homeworkData['subject_id']) 
                : null,
            'lesson_id' => isset($homeworkData['lesson_id']) 
                ? DatabaseMongo::createObjectId($homeworkData['lesson_id']) 
                : null,
            'due_date' => isset($homeworkData['due_date']) 
                ? DatabaseMongo::createUTCDateTime(strtotime($homeworkData['due_date']) * 1000) 
                : null,
            'max_score' => $homeworkData['max_score'] ?? 100,
            'created_by' => isset($homeworkData['created_by']) 
                ? DatabaseMongo::createObjectId($homeworkData['created_by']) 
                : null,
            'status' => $homeworkData['status'] ?? 'active',
            'createdAt' => DatabaseMongo::createUTCDateTime(),
            'updatedAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        // Add attachments if provided
        if (isset($homeworkData['attachments'])) {
            $homework['attachments'] = $homeworkData['attachments'];
        }
        
        return $this->db->insert($this->collection, $homework);
    }
    
    /**
     * Get homework by ID
     * @param string $homeworkId Homework ID
     * @return object|null
     */
    public function getById($homeworkId) {
        try {
            $filter = ['_id' => DatabaseMongo::createObjectId($homeworkId)];
            return $this->db->findOne($this->collection, $filter);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get homework by subject
     * @param string $subjectId Subject ID
     * @param string $status Filter by status
     * @return array
     */
    public function getBySubject($subjectId, $status = null) {
        $filter = ['subject_id' => DatabaseMongo::createObjectId($subjectId)];
        
        if ($status) {
            $filter['status'] = $status;
        }
        
        $options = ['sort' => ['due_date' => -1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get homework by lesson
     * @param string $lessonId Lesson ID
     * @return array
     */
    public function getByLesson($lessonId) {
        $filter = ['lesson_id' => DatabaseMongo::createObjectId($lessonId)];
        $options = ['sort' => ['due_date' => -1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get all homework
     * @param array $filters Additional filters
     * @param int $limit Limit results
     * @return array
     */
    public function getAll($filters = [], $limit = 100) {
        $filter = [];
        
        if (isset($filters['status'])) {
            $filter['status'] = $filters['status'];
        }
        
        if (isset($filters['created_by'])) {
            $filter['created_by'] = DatabaseMongo::createObjectId($filters['created_by']);
        }
        
        $options = [
            'sort' => ['due_date' => -1],
            'limit' => $limit
        ];
        
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get active homework (due in future)
     * @param string|null $subjectId Filter by subject
     * @return array
     */
    public function getActive($subjectId = null) {
        $now = DatabaseMongo::createUTCDateTime();
        
        $filter = [
            'status' => 'active',
            'due_date' => ['$gt' => $now]
        ];
        
        if ($subjectId) {
            $filter['subject_id'] = DatabaseMongo::createObjectId($subjectId);
        }
        
        $options = ['sort' => ['due_date' => 1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Update homework
     * @param string $homeworkId Homework ID
     * @param array $data Update data
     * @return int Modified count
     */
    public function update($homeworkId, $data) {
        $filter = ['_id' => DatabaseMongo::createObjectId($homeworkId)];
        
        if (isset($data['due_date']) && is_string($data['due_date'])) {
            $data['due_date'] = DatabaseMongo::createUTCDateTime(strtotime($data['due_date']) * 1000);
        }
        
        $data['updatedAt'] = DatabaseMongo::createUTCDateTime();
        
        $update = ['$set' => $data];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Close homework (no more submissions)
     * @param string $homeworkId Homework ID
     * @return int Modified count
     */
    public function close($homeworkId) {
        return $this->update($homeworkId, ['status' => 'closed']);
    }
    
    /**
     * Delete homework
     * @param string $homeworkId Homework ID
     * @return int Deleted count
     */
    public function delete($homeworkId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($homeworkId)];
        return $this->db->delete($this->collection, $filter);
    }
    
    /**
     * Submit homework
     * @param string $homeworkId Homework ID
     * @param string $studentId Student ID
     * @param array $submissionData Submission data
     * @return mixed Submission ID
     */
    public function submit($homeworkId, $studentId, $submissionData) {
        // Check if already submitted
        $existing = $this->getSubmission($homeworkId, $studentId);
        if ($existing) {
            throw new Exception('Homework already submitted');
        }
        
        // Check if homework is still open
        $homework = $this->getById($homeworkId);
        if (!$homework || $homework->status !== 'active') {
            throw new Exception('Homework is closed');
        }
        
        $submission = [
            'homework_id' => DatabaseMongo::createObjectId($homeworkId),
            'student_id' => DatabaseMongo::createObjectId($studentId),
            'submission_text' => $submissionData['submission_text'] ?? '',
            'submission_file_path' => $submissionData['submission_file_path'] ?? null,
            'submitted_at' => DatabaseMongo::createUTCDateTime(),
            'status' => 'submitted',
            'createdAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        // Check if late submission
        if (isset($homework->due_date)) {
            $now = time() * 1000; // Convert to milliseconds
            $dueDate = $homework->due_date->toDateTime()->getTimestamp() * 1000;
            
            if ($now > $dueDate) {
                $submission['status'] = 'late';
            }
        }
        
        return $this->db->insert($this->submissionCollection, $submission);
    }
    
    /**
     * Get submission
     * @param string $homeworkId Homework ID
     * @param string $studentId Student ID
     * @return object|null
     */
    public function getSubmission($homeworkId, $studentId) {
        $filter = [
            'homework_id' => DatabaseMongo::createObjectId($homeworkId),
            'student_id' => DatabaseMongo::createObjectId($studentId)
        ];
        
        return $this->db->findOne($this->submissionCollection, $filter);
    }
    
    /**
     * Get all submissions for homework
     * @param string $homeworkId Homework ID
     * @return array
     */
    public function getSubmissions($homeworkId) {
        $filter = ['homework_id' => DatabaseMongo::createObjectId($homeworkId)];
        $options = ['sort' => ['submitted_at' => -1]];
        return $this->db->find($this->submissionCollection, $filter, $options);
    }
    
    /**
     * Get student submissions
     * @param string $studentId Student ID
     * @return array
     */
    public function getStudentSubmissions($studentId) {
        $filter = ['student_id' => DatabaseMongo::createObjectId($studentId)];
        $options = ['sort' => ['submitted_at' => -1]];
        return $this->db->find($this->submissionCollection, $filter, $options);
    }
    
    /**
     * Grade submission
     * @param string $submissionId Submission ID (as string or use homework_id + student_id)
     * @param string $homeworkId Homework ID
     * @param string $studentId Student ID
     * @param int $score Score
     * @param string $feedback Feedback text
     * @param string $gradedBy Grader user ID
     * @return int Modified count
     */
    public function grade($homeworkId, $studentId, $score, $feedback = '', $gradedBy = null) {
        $filter = [
            'homework_id' => DatabaseMongo::createObjectId($homeworkId),
            'student_id' => DatabaseMongo::createObjectId($studentId)
        ];
        
        $gradeData = [
            'score' => (int) $score,
            'feedback' => $feedback,
            'status' => 'graded',
            'graded_at' => DatabaseMongo::createUTCDateTime()
        ];
        
        if ($gradedBy) {
            $gradeData['graded_by'] = DatabaseMongo::createObjectId($gradedBy);
        }
        
        $update = ['$set' => $gradeData];
        return $this->db->update($this->submissionCollection, $filter, $update);
    }
    
    /**
     * Get homework statistics
     * @param string $homeworkId Homework ID
     * @return array
     */
    public function getStatistics($homeworkId) {
        $submissions = $this->getSubmissions($homeworkId);
        
        $stats = [
            'total_submissions' => count($submissions),
            'graded' => 0,
            'pending' => 0,
            'late' => 0,
            'average_score' => 0
        ];
        
        $totalScore = 0;
        $gradedCount = 0;
        
        foreach ($submissions as $submission) {
            if ($submission->status === 'graded') {
                $stats['graded']++;
                $gradedCount++;
                $totalScore += $submission->score ?? 0;
            } elseif ($submission->status === 'late') {
                $stats['late']++;
            } else {
                $stats['pending']++;
            }
        }
        
        if ($gradedCount > 0) {
            $stats['average_score'] = round($totalScore / $gradedCount, 2);
        }
        
        return $stats;
    }
}
?>
