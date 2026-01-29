<?php
/**
 * Debug script to inspect student data via the existing API
 * Usage: Access via browser or run with PHP CLI
 */

// Include the API configuration
require_once 'api/config.php';

function debugStudentData() {
    try {
        $client = $GLOBALS['mongoClient'];
        $databaseName = $GLOBALS['databaseName'];
        
        $parentPhone = "01202118649";
        
        echo "<h2>Debug: Student Data for Parent Phone: $parentPhone</h2>";
        
        // Normalize phone formats (same as in API)
        $phonesToTry = [$parentPhone];
        $cleanPhone = preg_replace('/[^0-9]/', '', $parentPhone);
        
        if (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
            $phonesToTry[] = '+2' . $cleanPhone;
            $phonesToTry[] = $cleanPhone;
        }
        
        echo "<h3>Phone variants to try:</h3>";
        echo "<pre>" . print_r($phonesToTry, true) . "</pre>";
        
        // Search in all_students_view
        echo "<h3>=== all_students_view collection ===</h3>";
        $viewQuery = new MongoDB\Driver\Query(['parentPhone' => ['$in' => $phonesToTry]]);
        $viewCursor = $client->executeQuery("$databaseName.all_students_view", $viewQuery);
        $matches = $viewCursor->toArray();
        
        echo "<p>Found " . count($matches) . " records</p>";
        
        foreach ($matches as $i => $studentData) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h4>Record " . ($i + 1) . "</h4>";
            echo "<strong>Name:</strong> " . ($studentData->studentName ?? $studentData->name ?? 'N/A') . "<br>";
            echo "<strong>Phone:</strong> " . ($studentData->phone ?? 'N/A') . "<br>";
            echo "<strong>Grade:</strong> " . ($studentData->grade ?? 'N/A') . "<br>";
            echo "<strong>Subject:</strong> " . ($studentData->subject ?? 'N/A') . "<br>";
            
            // Show session fields
            $sessionFields = [];
            foreach ((array)$studentData as $k => $v) {
                if (strpos($k, 'session_') === 0) {
                    $sessionFields[$k] = $v;
                }
            }
            
            if (!empty($sessionFields)) {
                echo "<strong>Session Fields (" . count($sessionFields) . "):</strong><br>";
                echo "<table border='1' style='font-size: 12px;'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                foreach ($sessionFields as $field => $value) {
                    echo "<tr><td>$field</td><td>" . (is_scalar($value) ? $value : json_encode($value)) . "</td></tr>";
                }
                echo "</table>";
            }
            echo "</div>";
        }
        
        // Check for duplicate session data
        echo "<h3>=== Session Data Comparison ===</h3>";
        $subjectSessions = [];
        foreach ($matches as $studentData) {
            $subject = $studentData->subject ?? 'unknown';
            $sessionFields = [];
            foreach ((array)$studentData as $k => $v) {
                if (strpos($k, 'session_') === 0) {
                    $sessionFields[$k] = $v;
                }
            }
            if (!empty($sessionFields)) {
                $subjectSessions[$subject][] = $sessionFields;
            }
        }
        
        foreach ($subjectSessions as $subject => $sessionsList) {
            echo "<h4>Subject: $subject</h4>";
            echo "<p>Found " . count($sessionsList) . " records</p>";
            
            if (count($sessionsList) > 1) {
                $first = $sessionsList[0];
                $allSame = true;
                for ($i = 1; $i < count($sessionsList); $i++) {
                    if ($sessionsList[$i] !== $first) {
                        $allSame = false;
                        break;
                    }
                }
                echo "<p><strong>All records have identical session data: " . ($allSame ? 'YES' : 'NO') . "</strong></p>";
                
                if (!$allSame) {
                    echo "<p>Differences found:</p>";
                    foreach ($sessionsList as $i => $sessions) {
                        echo "<h5>Record " . ($i + 1) . ":</h5>";
                        echo "<pre>" . json_encode($sessions, JSON_PRETTY_PRINT) . "</pre>";
                    }
                }
            }
        }
        
        // Also check users collection
        echo "<h3>=== users collection ===</h3>";
        $userQuery = new MongoDB\Driver\Query(['parentPhone' => ['$in' => $phonesToTry]]);
        $userCursor = $client->executeQuery("$databaseName.users", $userQuery);
        $users = $userCursor->toArray();
        
        echo "<p>Found " . count($users) . " records</p>";
        
        foreach ($users as $i => $user) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h4>User Record " . ($i + 1) . "</h4>";
            echo "<strong>Name:</strong> " . ($user->name ?? 'N/A') . "<br>";
            echo "<strong>Phone:</strong> " . ($user->phone ?? 'N/A') . "<br>";
            echo "<strong>Grade:</strong> " . ($user->grade ?? 'N/A') . "<br>";
            echo "<strong>Subjects:</strong> " . (isset($user->subjects) ? json_encode($user->subjects) : 'N/A') . "<br>";
            echo "<strong>Subject:</strong> " . ($user->subject ?? 'N/A') . "<br>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<h2>Error:</h2>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
}

// Run the debug
debugStudentData();
?>
