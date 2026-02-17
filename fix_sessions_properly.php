<?php
// Read the entire file
$filePath = __DIR__ . '/api/sessions.php';
$content = file_get_contents($filePath);

// The problematic section is:
// if ($student) {
//     $sessionKey = 'session_' . $sessionNumber;
//     // Session check moved below - always found
//     $foundInCollection = $collection;
//     error_log('  Found student with session in: ' . $collection);
//     break 2;
// }

// We need to replace it with just:
// if ($student) {
//     $foundInCollection = $collection;
//     break 2;
// }

// And move the session key check OUTSIDE the loop

// First, let's find and fix the problematic if block
// The key is to find the pattern where we have "if ($student) {" inside the loop,
// then ensure proper logic

// Strategy: Replace the entire problematic loop section with a corrected version

// Find the start: "foreach ($phoneVariations as $phoneVariation) {"
// Find the end: "}" (closing the foreach)

// Look for the problematic pattern:
$search = <<<'EOD'
                    try {
                        $cursor = $client->executeQuery("$databaseName." . $collection, $query);
                        $student = current($cursor->toArray());
                        
                        if ($student) {
                            $sessionKey = 'session_' . $sessionNumber;
                            // Session check moved below - always found
                                $foundInCollection = $collection;
                                error_log('  âœ" Found student with session in: ' . $collection);
                                break 2; 
                            
                        }
                    } catch (Exception $e) {
                        error_log('  Error querying ' . $collection . ': ' . $e->getMessage());
                    }
EOD;

$replace = <<<'EOD'
                    try {
                        $cursor = $client->executeQuery("$databaseName." . $collection, $query);
                        $student = current($cursor->toArray());
                        
                        if ($student) {
                            $foundInCollection = $collection;
                            error_log('Found student in: ' . $collection);
                            break 2;
                        }
                    } catch (Exception $e) {
                        error_log('Error querying ' . $collection . ': ' . $e->getMessage());
                    }
EOD;

if (strpos($content, $search) !== false) {
    echo "Found problematic pattern!\n";
    $content = str_replace($search, $replace, $content);
    echo "Replaced it!\n";
} else {
    echo "Pattern not found with exact match, trying with flexible whitespace...\n";
    
    // Try with more flexible matching
    $search2 = 'if ($student) {
                            $sessionKey = \'session_\' . $sessionNumber;
                            // Session check moved below - always found
                                $foundInCollection = $collection;
                                error_log(\'  Found student with session in: \' . $collection);
                                break 2;';
    
    if (strpos($content, $search2) !== false) {
        $replace2 = 'if ($student) {
                            $foundInCollection = $collection;
                            error_log(\'Found student in: \' . $collection);
                            break 2;';
        
        $content = str_replace($search2, $replace2, $content);
        echo "Replaced with flexible matching!\n";
    } else {
        echo "Could not find pattern with flexible matching.\n";
        
        // Last resort: use regex to find and replace
        $pattern = '/if\s*\(\$student\)\s*{[^}]*\$sessionKey\s*=\s*\'session_\'\s*\.\s*\$sessionNumber[^}]*break\s+2[^}]*}/';
        if (preg_match($pattern, $content)) {
            echo "Found with regex!\n";
            $content = preg_replace($pattern, 'if ($student) {
                            $foundInCollection = $collection;
                            error_log(\'Found student in: \' . $collection);
                            break 2;
                        }', $content);
            echo "Replaced with regex!\n";
        }
    }
}

// Now let's also clean up any emoji characters that might be causing issues
$content = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $content);  // Remove emojis
$content = str_replace('âœ"', '', $content);  // Remove encoding artifacts
$content = str_replace('âœ—', '', $content);
$content = str_replace('ðŸ"„', '', $content);

file_put_contents($filePath, $content);

echo "✅ File updated successfully!\n";

// Verify
shell_exec('php -l ' . escapeshellarg($filePath));
?>
