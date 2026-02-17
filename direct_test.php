<?php
// Simple direct API test
echo "Testing API Direct Call...\n";

// Include the sessions API directly
$_GET['action'] = 'check-access';
$_GET['session_number'] = '2'; 
$_GET['phone'] = '01000733148';
$_GET['grade'] = 'senior2';
$_GET['subject'] = 'mathematics';

echo "Parameters set:\n";
print_r($_GET);

echo "\nCalling sessions.php...\n";

// Capture any output
ob_start();
include 'api/sessions.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
?>