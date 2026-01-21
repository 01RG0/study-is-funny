<?php
require_once 'api/config.php';

try {
    $client = new MongoDB\Client($mongoUri);
    $databaseName = 'study_is_funny_db';
    $database = $client->selectDatabase($databaseName);
    
    // Get sessions collection
    $sessionsCollection = $database->selectCollection('sessions');
    
    // Count total sessions
    $totalCount = $sessionsCollection->countDocuments([]);
    echo "Total Sessions in Database: $totalCount\n\n";
    
    // Count active sessions
    $activeCount = $sessionsCollection->countDocuments(['isActive' => true]);
    echo "Active Sessions (isActive=true): $activeCount\n\n";
    
    // Count inactive sessions
    $inactiveCount = $sessionsCollection->countDocuments(['isActive' => false]);
    echo "Inactive Sessions (isActive=false): $inactiveCount\n\n";
    
    // List all sessions with details
    echo "=== ALL SESSIONS ===\n";
    $cursor = $sessionsCollection->find([], ['sort' => ['createdAt' => -1]]);
    
    foreach ($cursor as $index => $session) {
        echo "\n--- Session " . ($index + 1) . " ---\n";
        echo "ID: " . (string)$session->_id . "\n";
        echo "Title: " . ($session->title ?? 'N/A') . "\n";
        echo "Grade: " . ($session->grade ?? 'N/A') . "\n";
        echo "Subject: " . ($session->subject ?? 'N/A') . "\n";
        echo "Session Number: " . ($session->sessionNumber ?? 'N/A') . "\n";
        echo "Access Control: " . ($session->accessControl ?? 'N/A') . "\n";
        echo "Is Active: " . ($session->isActive ? 'Yes' : 'No') . "\n";
        echo "Videos: " . (count($session->videos ?? []) ?? 0) . "\n";
        echo "Created: " . ($session->createdAt ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
