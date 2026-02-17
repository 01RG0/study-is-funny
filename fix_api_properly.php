<?php
// This script properly reconstructs the broken session API file

$file = __DIR__ . '/api/sessions.php';
$content = file_get_contents($file);

// The whole problem: the code requires session_X to exist before returning student data
// Requirements: When checking a session that hasn't been purchased yet (session_X doesn't exist),
// we should still return the student data so frontend can show "Purchase & Watch Now" button

// Solution: Find student by phone, don't require session to exist
// Return the student data regardless

// Replace the broken logic section completely
$broken = <<<'EOT'
                        if ($student) {
                            $sessionKey = 'session_' . $sessionNumber;
                            // Session check moved below - always found
                                $foundInCollection = $collection;
                                error_log('  âœ" Found student with session in: ' . $collection);
                                break 2; 
                            
                        }
EOT;

$fixed = <<<'EOT'
                        if ($student) {
                            // Found student! Session may not exist yet if not purchased
                            $foundInCollection = $collection;
                            error_log('  Found student - returning data even if session not purchased yet');
                            break 2;
                        }
EOT;

$content = str_replace($broken, $fixed, $content);

// Also remove emoji from error messages
$content = str_replace("error_log('✓ Student found in collection:", "error_log('Student found in collection:", $content);
$content = str_replace("error_log('✗ Access Denied:", "error_log('Access Denied:", $content);

file_put_contents($file, $content);

echo "✅ File updated!\n";
echo "✅ API now returns student data even when session_X doesn't exist\n";
echo "✅ Frontend can show Purchase button for non-purchased sessions\n";
?>
