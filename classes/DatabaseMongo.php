<?php
/**
 * MongoDB Database Connection Class
 * Handles all MongoDB connections and operations
 */
class DatabaseMongo {
    private $client;
    private $database;
    private $databaseName;
    private $mongoUri;
    
    /**
     * Constructor - Initialize MongoDB connection
     */
    public function __construct($uri = null, $dbName = null) {
        $this->mongoUri = $uri ?? 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
        $this->databaseName = $dbName ?? 'attendance_system';
        $this->connect();
    }
    
    /**
     * Connect to MongoDB
     */
    public function connect() {
        try {
            $this->client = new MongoDB\Driver\Manager($this->mongoUri);
            
            // Test connection
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $this->client->executeCommand('admin', $command);
            
            return true;
        } catch (Exception $e) {
            throw new Exception('MongoDB Connection Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get MongoDB Client
     */
    public function getClient() {
        return $this->client;
    }
    
    /**
     * Get Database Name
     */
    public function getDatabaseName() {
        return $this->databaseName;
    }
    
    /**
     * Execute a query
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @param array $options Query options
     * @return MongoDB\Driver\Cursor
     */
    public function query($collection, $filter = [], $options = []) {
        try {
            $query = new MongoDB\Driver\Query($filter, $options);
            $namespace = $this->databaseName . '.' . $collection;
            return $this->client->executeQuery($namespace, $query);
        } catch (Exception $e) {
            throw new Exception('Query Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Find documents
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @param array $options Query options
     * @return array
     */
    public function find($collection, $filter = [], $options = []) {
        try {
            $cursor = $this->query($collection, $filter, $options);
            return iterator_to_array($cursor);
        } catch (Exception $e) {
            throw new Exception('Find Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Find one document
     * @param string $collection Collection name
     * @param array $filter Query filter
     * @return object|null
     */
    public function findOne($collection, $filter = []) {
        try {
            $options = ['limit' => 1];
            $cursor = $this->query($collection, $filter, $options);
            $result = iterator_to_array($cursor);
            return !empty($result) ? reset($result) : null;
        } catch (Exception $e) {
            throw new Exception('FindOne Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Insert a document
     * @param string $collection Collection name
     * @param array $document Document to insert
     * @return mixed Insert ID
     */
    public function insert($collection, $document) {
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $insertId = $bulk->insert($document);
            
            $namespace = $this->databaseName . '.' . $collection;
            $result = $this->client->executeBulkWrite($namespace, $bulk);
            
            return $insertId;
        } catch (Exception $e) {
            throw new Exception('Insert Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update documents
     * @param string $collection Collection name
     * @param array $filter Update filter
     * @param array $update Update data
     * @param array $options Update options
     * @return int Modified count
     */
    public function update($collection, $filter, $update, $options = ['multi' => false]) {
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update($filter, $update, $options);
            
            $namespace = $this->databaseName . '.' . $collection;
            $result = $this->client->executeBulkWrite($namespace, $bulk);
            
            return $result->getModifiedCount();
        } catch (Exception $e) {
            throw new Exception('Update Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete documents
     * @param string $collection Collection name
     * @param array $filter Delete filter
     * @param array $options Delete options
     * @return int Deleted count
     */
    public function delete($collection, $filter, $options = ['limit' => 1]) {
        try {
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete($filter, $options);
            
            $namespace = $this->databaseName . '.' . $collection;
            $result = $this->client->executeBulkWrite($namespace, $bulk);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new Exception('Delete Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Count documents
     * @param string $collection Collection name
     * @param array $filter Count filter
     * @return int
     */
    public function count($collection, $filter = []) {
        try {
            // Use countDocuments aggregation instead of deprecated count command
            $pipeline = [
                ['$match' => empty($filter) ? new stdClass() : $filter],
                ['$count' => 'total']
            ];
            
            $command = new MongoDB\Driver\Command([
                'aggregate' => $collection,
                'pipeline' => $pipeline,
                'cursor' => new stdClass()
            ]);
            
            $cursor = $this->client->executeCommand($this->databaseName, $command);
            $result = iterator_to_array($cursor);
            
            // Check if we have results
            if (!empty($result) && isset($result[0]->firstBatch) && !empty($result[0]->firstBatch)) {
                return $result[0]->firstBatch[0]->total ?? 0;
            }
            
            return 0;
        } catch (Exception $e) {
            // Fallback: use find and count manually
            try {
                $cursor = $this->query($collection, $filter);
                return iterator_count($cursor);
            } catch (Exception $e2) {
                throw new Exception('Count Failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Execute aggregation pipeline
     * @param string $collection Collection name
     * @param array $pipeline Aggregation pipeline
     * @return array
     */
    public function aggregate($collection, $pipeline) {
        try {
            $command = new MongoDB\Driver\Command([
                'aggregate' => $collection,
                'pipeline' => $pipeline,
                'cursor' => new stdClass()
            ]);
            
            $cursor = $this->client->executeCommand($this->databaseName, $command);
            return iterator_to_array($cursor);
        } catch (Exception $e) {
            throw new Exception('Aggregation Failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create ObjectId from string
     * @param string|null $id ID string
     * @return MongoDB\BSON\ObjectId
     */
    public static function createObjectId($id = null) {
        try {
            return $id ? new MongoDB\BSON\ObjectId($id) : new MongoDB\BSON\ObjectId();
        } catch (Exception $e) {
            throw new Exception('Invalid ObjectId: ' . $e->getMessage());
        }
    }
    
    /**
     * Create UTCDateTime
     * @param int|null $timestamp Unix timestamp (milliseconds)
     * @return MongoDB\BSON\UTCDateTime
     */
    public static function createUTCDateTime($timestamp = null) {
        return new MongoDB\BSON\UTCDateTime($timestamp);
    }
    
    /**
     * Convert ObjectId to string
     * @param MongoDB\BSON\ObjectId $objectId
     * @return string
     */
    public static function objectIdToString($objectId) {
        return (string) $objectId;
    }
}
?>
