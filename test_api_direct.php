<?php
// Direct MongoDB check endpoint
require_once 'api/config.php';

try {
    // Get sessions from API
    $_GET['action'] = 'all';
    $_GET['includeInactive'] = 'true';
    
    ob_start();
    include 'api/sessions.php';
    $output = ob_get_clean();
    
    echo $output;
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
