<?php
// Simple debug logger: writes POSTed JSON to a file and echoes it
header('Content-Type: application/json');
$data = file_get_contents('php://input');
$logFile = __DIR__ . '/../logs/debug_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $data . "\n\n", FILE_APPEND);
echo json_encode(['success' => true, 'logged' => true]);
