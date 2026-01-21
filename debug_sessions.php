<?php
require_once 'api/config.php';
require_once 'classes/DatabaseMongo.php';

try {
    $db = new DatabaseMongo();
    $sessions = $db->find('sessions', []);
    echo "Total Sessions: " . count($sessions) . "\n\n";
    foreach ($sessions as $s) {
        echo "ID: " . (string)$s->_id . "\n";
        echo "Title: " . ($s->title ?? 'N/A') . "\n";
        echo "Subject: " . ($s->subject ?? 'N/A') . "\n";
        echo "Grade: " . ($s->grade ?? 'N/A') . "\n";
        echo "Status: " . ($s->status ?? 'N/A') . "\n";
        echo "isActive: " . ($s->isActive ? 'true' : 'false') . "\n";
        echo "------------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
