<?php
// This script fixes the sessions API to properly return student data even when session doesn't exist

$filePath = __DIR__ . '/api/sessions.php';
$content = file_get_contents($filePath);

// The problem: Lines 986-992 require session_X key to exist before returning student
// The fix: Return student whenever found, regardless of session_X existence

// Split into lines for easier manipulation
$lines = file($filePath, FILE_IGNORE_NEW_LINES);

$output = [];
$inProblematicBlock = false;
$skipLines = 0;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    // Detect the problematic if ($student) block around line 985-986
    // It starts with "if ($student) {" after "current($cursor->toArray());"
    if (strpos($line, 'if ($student) {') !== false && 
        $i > 980 && 
        $i < 990 &&
        strpos($lines[$i-1], 'current($cursor->toArray())') !== false &&
        strpos($lines[$i-2], 'executeQuery') !== false) {
        
        // Found it! Now reconstruct this block properly
        $output[] = $line;  // Keep "if ($student) {"
        
        // Skip the broken lines inside this if block
        $i++;  // Move to next line
        $braceCount = 1;
        
        while ($i < count($lines) && $braceCount > 0) {
            $testLine = $lines[$i];
            
            // Count braces
            $braceCount += substr_count($testLine, '{') - substr_count($testLine, '}');
            
            // Check if this is the break 2 line (end of our statement)
            if (strpos($testLine, 'break 2') !== false && $braceCount == 1) {
                // Add our corrected version
                $output[] = '                            $foundInCollection = $collection;';
                $output[] = '                            error_log(\'  Found student in: \' . $collection);';
                $output[] = '                            break 2;';
                // Next line should be the closing brace
                $i++;
                if (isset($lines[$i]) && trim($lines[$i]) === '}') {
                    $output[] = $lines[$i];  // Add the closing brace
                }
                break;
            }
            
            $i++;
        }
        continue;
    }
    
    // Clean up emoji characters in error_log messages
    $line = str_replace('âœ"', '', $line);
    $line = str_replace('âœ—', '', $line);
    $line = str_replace('ðŸ"„', '', $line);
    $line = str_replace('âŒ', '', $line);
    
    $output[] = $line;
}

$result = implode("\n", $output);
file_put_contents($filePath, $result);

echo "✅ Fixed API sessions.php!\n";
echo "✅ Now returns student data even when session doesn't exist yet\n";
echo "✅ Frontend can show 'Purchase & Watch Now' button\n";
?>
