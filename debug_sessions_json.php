<?php
require_once 'config/config.php';
require_once 'classes/DatabaseMongo.php';

try {
    $db = new DatabaseMongo();
    $sessions = $db->find('sessions', []);
    foreach ($sessions as $s) {
        echo json_encode($s) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
