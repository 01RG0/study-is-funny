<?php
require_once __DIR__ . '/DatabaseMongo.php';

/**
 * User Management Class for MongoDB
 * Handles user authentication, registration, and profile management
 */
class User {
    private $db;
    private $collection = 'users';
    
    public function __construct(DatabaseMongo $database) {
        $this->db = $database;
    }
    
    /**
     * Register a new user
     * @param string $name Full name
     * @param string $email Email address
     * @param string $password Password
     * @param string $phone Phone number
     * @param string $role User role (admin|student|assistant)
     * @param array $additionalData Additional user data
     * @return mixed User ID
     */
    public function register($name, $email, $password, $phone = null, $role = 'student', $additionalData = []) {
        // Check if email already exists
        if ($email && $this->getByEmail($email)) {
            throw new Exception('Email already exists');
        }
        
        // Check if phone already exists
        if ($phone && $this->getByPhone($phone)) {
            throw new Exception('Phone number already exists');
        }
        
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'isActive' => true,
            'createdAt' => DatabaseMongo::createUTCDateTime(),
            'updatedAt' => DatabaseMongo::createUTCDateTime()
        ];
        
        // Handle password based on role
        if ($role === 'admin' || $role === 'assistant') {
            $userData['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        } else {
            // Students use plain password (as per your current schema)
            $userData['password'] = $password;
        }
        
        // Merge additional data
        $userData = array_merge($userData, $additionalData);
        
        return $this->db->insert($this->collection, $userData);
    }
    
    /**
     * User login
     * @param string $identifier Email, phone, or username
     * @param string $password Password
     * @return object|false User object or false
     */
    public function login($identifier, $password) {
        // Find user by email or phone
        $user = $this->getByEmail($identifier) ?: $this->getByPhone($identifier);
        
        if (!$user) {
            return false;
        }
        
        // Check if active
        if (!isset($user->isActive) || !$user->isActive) {
            return false;
        }
        
        // Verify password
        $isValid = false;
        if (isset($user->password_hash)) {
            // Admin/Assistant - hashed password
            $isValid = password_verify($password, $user->password_hash);
        } elseif (isset($user->password)) {
            // Student - plain password
            $isValid = ($password === $user->password);
        }
        
        if (!$isValid) {
            return false;
        }
        
        // Update last login
        $this->updateLastLogin($user->_id);
        
        return $user;
    }
    
    /**
     * Get user by ID
     * @param string $userId User ID
     * @return object|null
     */
    public function getById($userId) {
        try {
            $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
            return $this->db->findOne($this->collection, $filter);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user by email
     * @param string $email Email address
     * @return object|null
     */
    public function getByEmail($email) {
        if (empty($email)) {
            return null;
        }
        $filter = ['email' => $email];
        return $this->db->findOne($this->collection, $filter);
    }
    
    /**
     * Get user by phone
     * @param string $phone Phone number
     * @return object|null
     */
    public function getByPhone($phone) {
        if (empty($phone)) {
            return null;
        }
        $filter = ['phone' => $phone];
        return $this->db->findOne($this->collection, $filter);
    }
    
    /**
     * Get all users
     * @param string|null $role Filter by role
     * @param bool $activeOnly Only active users
     * @return array
     */
    public function getAll($role = null, $activeOnly = true) {
        $filter = [];
        
        if ($role) {
            $filter['role'] = $role;
        }
        
        if ($activeOnly) {
            $filter['isActive'] = true;
        }
        
        $options = ['sort' => ['createdAt' => -1]];
        return $this->db->find($this->collection, $filter, $options);
    }
    
    /**
     * Update user profile
     * @param string $userId User ID
     * @param array $data Update data
     * @return int Modified count
     */
    public function updateProfile($userId, $data) {
        $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
        
        // Add update timestamp
        $data['updatedAt'] = DatabaseMongo::createUTCDateTime();
        
        $update = ['$set' => $data];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Change password
     * @param string $userId User ID
     * @param string $newPassword New password
     * @param string $role User role
     * @return int Modified count
     */
    public function changePassword($userId, $newPassword, $role) {
        $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
        
        $updateData = [];
        if ($role === 'admin' || $role === 'assistant') {
            $updateData['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        } else {
            $updateData['password'] = $newPassword;
        }
        $updateData['updatedAt'] = DatabaseMongo::createUTCDateTime();
        
        $update = ['$set' => $updateData];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Update last login timestamp
     * @param MongoDB\BSON\ObjectId $userId User ID
     * @return int Modified count
     */
    private function updateLastLogin($userId) {
        $filter = ['_id' => $userId];
        $update = ['$set' => ['lastLogin' => DatabaseMongo::createUTCDateTime()]];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Deactivate user
     * @param string $userId User ID
     * @return int Modified count
     */
    public function deactivate($userId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
        $update = [
            '$set' => [
                'isActive' => false,
                'updatedAt' => DatabaseMongo::createUTCDateTime()
            ]
        ];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Activate user
     * @param string $userId User ID
     * @return int Modified count
     */
    public function activate($userId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
        $update = [
            '$set' => [
                'isActive' => true,
                'updatedAt' => DatabaseMongo::createUTCDateTime()
            ]
        ];
        return $this->db->update($this->collection, $filter, $update);
    }
    
    /**
     * Delete user
     * @param string $userId User ID
     * @return int Deleted count
     */
    public function delete($userId) {
        $filter = ['_id' => DatabaseMongo::createObjectId($userId)];
        return $this->db->delete($this->collection, $filter);
    }
    
    /**
     * Get user statistics
     * @param string $userId User ID
     * @return array
     */
    public function getStatistics($userId) {
        $user = $this->getById($userId);
        
        if (!$user) {
            return null;
        }
        
        return [
            'totalSessionsViewed' => $user->totalSessionsViewed ?? 0,
            'totalWatchTime' => $user->totalWatchTime ?? 0,
            'activityLog' => $user->activityLog ?? []
        ];
    }
}
?>
