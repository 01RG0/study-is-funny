<?php
// Simulate a browser calling the API 
// Set up the query parameters
$_GET['phone'] = '+201013044079';
$_GET['sessionNumber'] = '1';
$_GET['subject'] = 'S1 Math';
$_GET['grade'] = 'senior1';

// Also set REQUEST_METHOD to avoid the warning
$_SERVER['REQUEST_METHOD'] = 'GET';

// Include the API
include 'api/sessions.php';
?>
