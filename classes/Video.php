<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * Video Management Class for MongoDB
 * Handles video upload, storage, and streaming
 */
class Video {
    private $db;
    private $collection = 'videos';
    private $uploadDir;
    private $allowedTypes = ['video/mp4', 'video/webm', 'video/avi', 'video/quicktime', 'video/x-msvideo'];
    private $maxFileSize = 524288000; // 500MB in bytes
    
    public function __construct(DatabaseMongo $database, $uploadDir = null) {
        $this->db = $database;
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../uploads/videos/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Upload a video file
     * @param array $fileData $_FILES array element
     * @param array $metadata Video metadata
     * @return array ['success' => bool, 'video_id' => string, 'message' => string]
     */
    public function upload($fileData, $metadata) {
        // Validate file
        $validation = $this->validateFile($fileData);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Generate unique filename
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $filename = uniqid('video_') . '_' . time() . '.' . $extension;
        
        // Create subject/lesson directory structure
        $subjectDir = $this->uploadDir;
        if (isset($metadata['subject_id'])) {
            $subjectDir .= 'subject_' . $metadata['subject_id'] . '/';
            if (!is_dir($subjectDir)) {
                mkdir($subjectDir, 0755, true);
            }
        }
        
        if (isset($metadata['lesson_id'])) {
            $subjectDir .= 'lesson_' . $metadata['lesson_id'] . '/';
            if (!is_dir($subjectDir)) {
                mkdir($subjectDir, 0755, true);
            }
        }
        
        $filepath = $subjectDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'message' => 'Failed to save video file'
            ];
        }
        
        // Get file size in MB
        $fileSizeMB = round(filesize($filepath) / (1024 * 1024), 2);
        
        // Check if string is a valid MongoDB ObjectId (24 char hex)
        $isValidObjectId = function($id) {
            return is_string($id) && preg_match('/^[a-f\d]{24}$/i', $id);
        };

        // Create database record
        $videoData = [
            'video_title' => $metadata['title'] ?? 'Untitled Video',
            'video_description' => $metadata['description'] ?? '',
            'video_file_path' => str_replace($this->uploadDir, '', $filepath),
            'file_size_mb' => $fileSizeMB,
            'uploaded_by' => (isset($metadata['uploaded_by']) && $isValidObjectId($metadata['uploaded_by']))
                ? DatabaseMongo::createObjectId($metadata['uploaded_by']) 
                : ($metadata['uploaded_by'] ?? null),
            'status' => 'completed',
            'view_count' => 0,
            'createdAt' => DatabaseMongo::createUTCDateTime(),
            'updatedAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        // Add subject and lesson references if provided
        if (isset($metadata['subject_id'])) {
            $videoData['subject_id'] = $isValidObjectId($metadata['subject_id'])
                ? DatabaseMongo::createObjectId($metadata['subject_id'])
                : $metadata['subject_id'];
        }
        
        if (isset($metadata['lesson_id'])) {
            $videoData['lesson_id'] = $isValidObjectId($metadata['lesson_id'])
                ? DatabaseMongo::createObjectId($metadata['lesson_id'])
                : $metadata['lesson_id'];
        }
        
        // Add thumbnail if provided
        if (isset($metadata['thumbnail_path'])) {
            $videoData['thumbnail_path'] = $metadata['thumbnail_path'];
        }
        
        // Add duration if provided
        if (isset($metadata['duration_seconds'])) {
            $videoData['duration_seconds'] = (int) $metadata['duration_seconds'];
        }
        
        try {
            $videoId = $this->db->insert($this->collection, $videoData);
            
            return [
                'success' => true,
                'video_id' => (string) $videoId,
                'message' => 'Video uploaded successfully',
                'file_path' => $videoData['video_file_path']
            ];
        } catch (Exception $e) {
            // Delete file if database insert fails
            unlink($filepath);
            
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate uploaded file
     * @param array $fileData $_FILES array element
     * @return array
     */
    private function validateFile($fileData) {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Upload error: ' . $this->getUploadError($fileData['error'])
            ];
        }
        
        // Check file size
        if ($fileData['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File too large. Maximum size is 500MB'
            ];
        }
        
        // Check MIME type
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileData['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($fileData['tmp_name']);
        } else {
            // Last fallback: use the type provided by the browser (less secure but prevents crash)
            $mimeType = $fileData['type'];
        }
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only video files are allowed. (Detected: ' . $mimeType . ')'
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Get upload error message
     * @param int $errorCode Error code
     * @return string
     */
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Get video by ID
     * @param string $videoId Video ID
     * @return object|null
     */
    public function getById($videoId) {
        try {
            $filter = ['_id' => DatabaseMongo::createObjectId($videoId)];
            return $this->db->findOne($this->collection, $filter);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get videos by lesson
     * @param string $lessonId Lesson ID
     * @return array
     */
    public function getByLesson($lessonId) {
        $filter = [
            'lesson_id' => DatabaseMongo::createObjectId($lessonId),
            'status' => 'completed'
        ];
        $options = ['sort' => ['createdAt' => -1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get videos by subject
     * @param string $subjectId Subject ID
     * @return array
     */
    public function getBySubject($subjectId) {
        $filter = [
            'subject_id' => DatabaseMongo::createObjectId($subjectId),
            'status' => 'completed'
        ];
        $options = ['sort' => ['createdAt' => -1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Get all videos
     * @param array $filters Additional filters
     * @param int $limit Limit results
     * @return array
     */
    public function getAll($filters = [], $limit = 100) {
        $filter = ['status' => 'completed'];
        
        if (isset($filters['subject_id'])) {
            $filter['subject_id'] = DatabaseMongo::createObjectId($filters['subject_id']);
        }
        
        if (isset($filters['uploaded_by'])) {
            $filter['uploaded_by'] = DatabaseMongo::createObjectId($filters['uploaded_by']);
        }
        
        $options = [
            'sort' => ['createdAt' => -1],
            'limit' => $limit
        ];
        
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Update video metadata
     * @param string $videoId Video ID
     * @param array $data Update data
     * @return int Modified count
     */
    public function update($videoId, $data) {
        $filter = ['_id' => DatabaseMongo::createObjectId($videoId)];
        $data['updatedAt'] = DatabaseMongo::createUTCDateTime();
        
        $update = ['$set' => $data];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Increment view count
     * @param string $videoId Video ID
     * @return int Modified count
     */
    public function incrementViewCount($videoId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($videoId)];
        $update = ['$inc' => ['view_count' => 1]];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Delete video
     * @param string $videoId Video ID
     * @param bool $deleteFile Also delete physical file
     * @return array
     */
    public function delete($videoId, $deleteFile = true) {
        $video = $this->getById($videoId);
        
        if (!$video) {
            return [
                'success' => false,
                'message' => 'Video not found'
            ];
        }
        
        // Delete physical file if requested
        if ($deleteFile && isset($video->video_file_path)) {
            $filepath = $this->uploadDir . $video->video_file_path;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Delete database record
        $filter = ['_id' => DatabaseMongo::createObjectId($videoId)];
        $result = $this->db->delete($this->collection, $filter);
        
        return [
            'success' => $result > 0,
            'message' => $result > 0 ? 'Video deleted successfully' : 'Failed to delete video'
        ];
    }
    
    /**
     * Get video file path
     * @param string $videoId Video ID
     * @return string|null
     */
    public function getFilePath($videoId) {
        $video = $this->getById($videoId);
        
        if (!$video || !isset($video->video_file_path)) {
            return null;
        }
        
        return $this->uploadDir . $video->video_file_path;
    }
    
    /**
     * Stream video
     * @param string $videoId Video ID
     * @param bool $incrementViews Increment view count
     * @return void
     */
    public function stream($videoId, $incrementViews = true) {
        $filepath = $this->getFilePath($videoId);
        
        if (!$filepath || !file_exists($filepath)) {
            http_response_code(404);
            die('Video not found');
        }
        
        // Increment view count
        if ($incrementViews) {
            $this->incrementViewCount($videoId);
        }
        
        // Set headers for video streaming
        $filesize = filesize($filepath);
        $mimeType = mime_content_type($filepath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $filesize);
        header('Accept-Ranges: bytes');
        
        // Handle range requests for seeking
        if (isset($_SERVER['HTTP_RANGE'])) {
            $this->streamRange($filepath, $filesize);
        } else {
            readfile($filepath);
        }
        
        exit;
    }
    
    /**
     * Stream video with range support
     * @param string $filepath File path
     * @param int $filesize File size
     * @return void
     */
    private function streamRange($filepath, $filesize) {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        $range = explode('-', $range);
        
        $start = (int) $range[0];
        $end = isset($range[1]) && $range[1] !== '' ? (int) $range[1] : $filesize - 1;
        
        $length = $end - $start + 1;
        
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$filesize");
        header("Content-Length: $length");
        
        $fp = fopen($filepath, 'rb');
        fseek($fp, $start);
        
        $buffer = 8192;
        $remaining = $length;
        
        while ($remaining > 0 && !feof($fp)) {
            $read = min($buffer, $remaining);
            echo fread($fp, $read);
            $remaining -= $read;
            flush();
        }
        
        fclose($fp);
    }
}
?>
