<?php
// Fix the session API to return student data even when session doesn't exist yet

$filePath = __DIR__ . '/api/sessions.php';
$content = file_get_contents($filePath);

// Problem: The API checks if session_X key exists before returning student data
// Solution: Return student data regardless of whether session exists
// This allows frontend to show "Purchase & Watch Now" button even for new sessions

// Find and replace the problematic loop logic
// OLD: Loops through collections and break 2 only if session_X exists
// NEW: Loops through collections and break 2 once student is found (session may not exist yet)

$patterns = [
    // Pattern 1: Remove the isset check for session_X
    '/if \(isset\(\$student->\$sessionKey\)\)\s+\{/' => '// Session check moved below - always found',
    
    // Pattern 2: Fix the broken else clause
    '/\} else \{\s+\$student = null;.*?\/\/ Session doesn.*?exist yet.*?continue searching.*?remove this\s+\}/' => ''
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content, 1);
}

// More direct approach: replace entire problematic block
$oldBlock = <<<'EOT'
                        if ($student) {
                            $sessionKey = 'session_' . $sessionNumber;
                            if (isset($student->$sessionKey)) {
                                $foundInCollection = $collection;
                                error_log('  Found student with session in: ' . $collection);
                                break 2; 
                            } else {
                                $student = null; // Session doesn't exist yet, continue searching - remove this
                            }
                        }
EOT;

$newBlock = <<<'EOT'
                        if ($student) {
                            // Found student! Session may not exist yet if not purchased
                            $foundInCollection = $collection;
                            error_log('  Found student in: ' . $collection);
                            break 2;
                        }
EOT;

$content = str_replace($oldBlock, $newBlock, $content);

file_put_contents($filePath, $content);
echo "✓ API file updated successfully!\n";
echo "✓ Now returns student data even when session doesn't exist yet\n";
echo "✓ Frontend can show 'Purchase & Watch Now' button for all sessions\n";
?>
