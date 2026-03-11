<?php
// Test for free student without sessions
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['phone'] = '+201299887744';  // team shady
$_GET['sessionNumber'] = '1';
$_GET['grade'] = 'senior2';
$_GET['subject'] = 'physics';

include 'api/sessions.php';
?>
