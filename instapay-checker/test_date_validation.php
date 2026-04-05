<?php
/**
 * Date Validation Test Suite
 * Tests transactions within 7-day window
 */

// Load config without database initialization
require_once 'config.php';

// Define the validateTransactionDate function directly
function validateTransactionDate($dateStr) {
    if (!$dateStr) {
        return [
            'valid' => false,
            'reason' => 'Transaction date is empty',
            'days_old' => null
        ];
    }

    // Try to parse various date formats
    $parsedDate = null;
    
    // Try parsing: "1/2/2024 14:30", "01-02-2024 14:30", "1 Feb 2024 14:30", etc.
    $formats = [
        'd/m/Y H:i',   // 1/2/2024 14:30
        'm/d/Y H:i',   // 2/1/2024 14:30
        'd-m-Y H:i',   // 1-2-2024 14:30
        'm-d-Y H:i',   // 2-1-2024 14:30
        'd/m/Y',       // 1/2/2024
        'm/d/Y',       // 2/1/2024
        'd-m-Y',       // 1-2-2024
        'm-d-Y',       // 2-1-2024
        'd M Y H:i',   // 1 Feb 2024 14:30
        'd M Y',       // 1 Feb 2024
        'Y-m-d H:i:s', // 2024-02-01 14:30:00
        'Y-m-d',       // 2024-02-01
    ];

    foreach ($formats as $format) {
        $dateObj = \DateTime::createFromFormat($format, trim($dateStr));
        if ($dateObj !== false && $dateObj->format($format) === trim($dateStr)) {
            $parsedDate = $dateObj;
            break;
        }
    }

    // If parsing failed, try a more lenient approach
    if (!$parsedDate) {
        // Extract year, month, day with regex
        if (preg_match('/(\d{1,4})[\/-](\d{1,2})[\/-](\d{1,4})/', $dateStr, $matches)) {
            $part1 = (int)$matches[1];
            $part2 = (int)$matches[2];
            $part3 = (int)$matches[3];
            
            // Determine which is year, month, day
            if ($part3 > 1000) { // part3 is year (YYYY format)
                $year = $part3;
                // Decide if part1 is month or day
                $month = ($part1 <= 12) ? $part1 : $part2;
                $day = ($part1 <= 12) ? $part2 : $part1;
            } elseif ($part1 > 1000) { // part1 is year (YYYY format)
                $year = $part1;
                $month = $part2;
                $day = $part3;
            } else { // 2-digit or 4-digit year at end
                $year = ($part3 < 100) ? 2000 + $part3 : $part3;
                $month = ($part1 <= 12) ? $part1 : $part2;
                $day = ($part1 <= 12) ? $part2 : $part1;
            }
            
            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                $parsedDate = new \DateTime("$year-$month-$day");
            }
        }
    }

    if (!$parsedDate) {
        return [
            'valid' => false,
            'reason' => 'Could not parse transaction date format',
            'days_old' => null
        ];
    }

    // Check if date is in the future (invalid)
    $now = new \DateTime('now');
    if ($parsedDate > $now) {
        return [
            'valid' => false,
            'reason' => 'Transaction date is in the future',
            'days_old' => 'Future'
        ];
    }

    // Calculate days difference
    $interval = $now->diff($parsedDate);
    $daysOld = $interval->days;

    // Check if within allowed timeframe (7 days by default)
    $maxDays = defined('MAX_TRANSACTION_AGE_DAYS') ? MAX_TRANSACTION_AGE_DAYS : 7;
    
    if ($daysOld > $maxDays) {
        return [
            'valid' => false,
            'reason' => "Transaction is $daysOld days old (max allowed: $maxDays days)",
            'days_old' => $daysOld
        ];
    }

    return [
        'valid' => true,
        'reason' => "Valid - Transaction is $daysOld days old",
        'days_old' => $daysOld
    ];
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           DATE VALIDATION TEST (7-DAY WINDOW)                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$now = new DateTime('now');
$tests = [];

// Test 1: Today's date (valid)
$today = $now->format('d/m/Y H:i');
$tests[] = [
    'name' => 'Today (0 days old)',
    'date' => $today,
    'expected' => 'VALID'
];

// Test 2: 1 day old (valid)
$yesterday = clone $now;
$yesterday->sub(new DateInterval('P1D'));
$tests[] = [
    'name' => 'Yesterday (1 day old)',
    'date' => $yesterday->format('d/m/Y H:i'),
    'expected' => 'VALID'
];

// Test 3: 7 days old (valid - at boundary)
$sevenDaysAgo = clone $now;
$sevenDaysAgo->sub(new DateInterval('P7D'));
$tests[] = [
    'name' => 'Exactly 7 days old (boundary)',
    'date' => $sevenDaysAgo->format('d/m/Y H:i'),
    'expected' => 'VALID'
];

// Test 4: 8 days old (invalid - over limit)
$eightDaysAgo = clone $now;
$eightDaysAgo->sub(new DateInterval('P8D'));
$tests[] = [
    'name' => 'Eight days old (over limit)',
    'date' => $eightDaysAgo->format('d/m/Y H:i'),
    'expected' => 'INVALID'
];

// Test 5: 30 days old (invalid - way over limit)
$thirtyDaysAgo = clone $now;
$thirtyDaysAgo->sub(new DateInterval('P30D'));
$tests[] = [
    'name' => '30 days old (way over limit)',
    'date' => $thirtyDaysAgo->format('d/m/Y H:i'),
    'expected' => 'INVALID'
];

// Test 6: Future date (invalid)
$tomorrow = clone $now;
$tomorrow->add(new DateInterval('P1D'));
$tests[] = [
    'name' => 'Tomorrow (future)',
    'date' => $tomorrow->format('d/m/Y H:i'),
    'expected' => 'INVALID'
];

// Test 7: Various date formats
$sevenDaysAgoFormatted = $sevenDaysAgo->format('m-d-Y');
$tests[] = [
    'name' => 'Format: m-d-Y (7 days ago)',
    'date' => $sevenDaysAgoFormatted,
    'expected' => 'VALID'
];

// Test 8: ISO format
$tests[] = [
    'name' => 'Format: Y-m-d H:i:s',
    'date' => $now->format('Y-m-d H:i:s'),
    'expected' => 'VALID'
];

// Test 9: 5 days old (valid)
$fiveDaysAgo = clone $now;
$fiveDaysAgo->sub(new DateInterval('P5D'));
$tests[] = [
    'name' => '5 days old (within limit)',
    'date' => $fiveDaysAgo->format('d/m/Y H:i'),
    'expected' => 'VALID'
];

// Test 10: Empty date
$tests[] = [
    'name' => 'Empty date',
    'date' => '',
    'expected' => 'INVALID'
];

// ========== RUN TESTS ==========
$passed = 0;
$failed = 0;

echo "📋 TEST RESULTS:\n";
echo "──────────────────────────────────────────────────────────────────────\n";

foreach ($tests as $i => $test) {
    $result = validateTransactionDate($test['date']);
    $isValid = $result['valid'] ? 'VALID' : 'INVALID';
    $isCorrect = ($isValid === $test['expected']);
    
    $status = $isCorrect ? '✅ PASS' : '❌ FAIL';
    $passed += $isCorrect ? 1 : 0;
    $failed += $isCorrect ? 0 : 1;
    
    echo "$status | Test " . ($i + 1) . ": " . $test['name'] . "\n";
    echo "   Date: {$test['date']}\n";
    
    if ($result['valid']) {
        echo "   Result: ✓ Valid ({$result['days_old']} days old)\n";
    } else {
        echo "   Result: ✗ {$result['reason']}\n";
    }
    
    if (!$isCorrect) {
        echo "   Expected: {$test['expected']}, Got: $isValid\n";
    }
    echo "\n";
}

// ========== SUMMARY ==========
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                       TEST SUMMARY                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Configuration:\n";
echo "  • Max Transaction Age: " . MAX_TRANSACTION_AGE_DAYS . " days\n";
echo "  • Min Amount: " . MIN_TRANSACTION_AMOUNT . " EGP\n";
echo "  • Max Amount: " . MAX_TRANSACTION_AMOUNT . " EGP\n\n";

$total = count($tests);
$percentage = round(($passed / $total) * 100);

echo "Results:\n";
echo "  Total Tests: $total\n";
echo "  ✅ Passed: $passed\n";
echo "  ❌ Failed: $failed\n";
echo "  Success Rate: $percentage%\n\n";

if ($failed === 0) {
    echo "✨ All date validation tests passed! ✨\n";
} else {
    echo "⚠️  Some tests failed. Review above for details.\n";
}
?>
