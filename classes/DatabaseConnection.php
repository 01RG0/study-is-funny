<?php
/**
 * Database Connection Wrapper
 * Safely initializes MongoDB with fallback error handling
 */

class DatabaseConnection {
    private static $instance = null;
    private static $client = null;
    private static $databaseName = null;
    private static $mongoAvailable = false;
    private static $error = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::initialize();
        }
        return self::$instance;
    }

    private static function initialize() {
        try {
            // Check extension
            if (!extension_loaded('mongodb')) {
                self::$error = 'MongoDB extension not loaded';
                error_log('⚠️ MongoDB extension not available');
                return false;
            }

            // Check class
            if (!class_exists('MongoDB\\Driver\\Manager')) {
                self::$error = 'MongoDB\\Driver\\Manager class not found';
                error_log('⚠️ MongoDB class not available');
                return false;
            }

            // Try to create connection
            $mongoUri = defined('MONGO_URI') ? MONGO_URI : 'mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0';
            $dbName = defined('DB_NAME') ? DB_NAME : 'attendance_system';

            self::$client = new MongoDB\Driver\Manager($mongoUri);
            self::$databaseName = $dbName;

            // Test connection
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            self::$client->executeCommand('admin', $command);

            self::$mongoAvailable = true;
            error_log('✅ MongoDB connection established successfully');
            return true;

        } catch (Error $e) {
            self::$error = 'Class error: ' . $e->getMessage();
            error_log('❌ MongoDB class error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            self::$error = 'Connection error: ' . $e->getMessage();
            error_log('❌ MongoDB connection error: ' . $e->getMessage());
            return false;
        }
    }

    public static function getClient() {
        self::getInstance();
        return self::$client;
    }

    public static function getDatabaseName() {
        self::getInstance();
        return self::$databaseName;
    }

    public static function isAvailable() {
        self::getInstance();
        return self::$mongoAvailable;
    }

    public static function getError() {
        return self::$error;
    }
}
?>
