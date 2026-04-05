<?php
/**
 * Comprehensive Instapay Validation Test Suite
 * Tests validators against various scenarios and edge cases
 */

require_once 'vendor/autoload.php';

// ========== VALIDATION FUNCTIONS ==========
function validateInstapayEmail($email) {
    if (!$email) return ['valid' => false, 'reason' => 'Email is empty'];
    if (preg_match('/^[a-z0-9._\-]+@instapay$/', strtolower($email))) {
        return [
            'valid' => true,
            'email' => $email,
            'domain' => 'instapay',
            'bank' => 'Instapay',
            'format' => 'instapay_username'
        ];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'reason' => 'Invalid email format'];
    }
    $domain = strtolower(substr($email, strrpos($email, '@') + 1));
    $validDomains = [
        'instapay.eg' => 'Instapay',
        'instapay' => 'Instapay',
        'cib.eg' => 'CIB',
        'alahli.eg' => 'Ahly Bank',
        'banquemisr.eg' => 'Banque Misr',
    ];
    $isValid = isset($validDomains[$domain]);
    return [
        'valid' => $isValid,
        'email' => $email,
        'domain' => $domain,
        'reason' => $isValid ? 'Valid domain' : 'Unknown domain'
    ];
}

function validateEgyptianPhone($phone) {
    if (!$phone) return ['valid' => false, 'reason' => 'Empty'];
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) == '20') {
        $cleaned = '0' . substr($cleaned, 2);
    }
    if (!preg_match('/^0[1][0-6][0-9]{8}$/', $cleaned)) {
        return ['valid' => false, 'reason' => 'Invalid format'];
    }
    $prefix = substr($cleaned, 0, 3);
    $providers = ['010' => 'Vodafone', '011' => 'Etisalat', '012' => 'Telecom', '016' => 'Mobinil'];
    return [
        'valid' => true,
        'formatted' => '+20' . substr($cleaned, 1),
        'provider' => $providers[$prefix] ?? 'Unknown'
    ];
}

function validateAmount($amount) {
    $cleaned = preg_replace('/[^\d.]/', '', $amount);
    if (!is_numeric($cleaned)) return ['valid' => false, 'reason' => 'Invalid'];
    $value = (float)$cleaned;
    if ($value < 50) return ['valid' => false, 'reason' => 'Below minimum'];
    if ($value > 1000) return ['valid' => false, 'reason' => 'Above maximum'];
    return ['valid' => true, 'amount' => $value];
}

function validateReference($ref) {
    if (!$ref || $ref === 'N/A') return ['valid' => true, 'status' => 'not_required'];
    if (preg_match('/^[A-Z0-9]{10,20}$/i', trim($ref))) {
        if (!preg_match('/[A-Z]/', $ref)) {
            return ['valid' => true, 'status' => 'numeric'];
        }
        return ['valid' => false, 'reason' => 'Contains letters'];
    }
    return ['valid' => false, 'reason' => 'Invalid format'];
}

// ========== TEST DATA ==========
$testSets = [
    // SET 1: Valid Transactions (User's Screenshots)
    'valid_transactions' => [
        [
            'name' => 'Normal Transfer 1',
            'amount' => 320,
            'from' => 'shereen12121@instapay',
            'to' => 'Shady M',
            'phone' => '01010796944',
            'reference' => '825399630642',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Normal Transfer 2',
            'amount' => 640,
            'from' => 'retajbelal1@instapay',
            'to' => 'Shady M',
            'phone' => '01010796944',
            'reference' => 'N/A',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Transfer with Different Provider',
            'amount' => 80,
            'from' => 'mezo.moz.123@instapay',
            'to' => 'IOHAMED',
            'phone' => '01061923748',
            'reference' => '413792125912',
            'expected' => 'VALID'
        ],
    ],
    
    // SET 2: Edge Cases - Amount Boundaries
    'amount_boundaries' => [
        [
            'name' => 'Minimum Valid Amount',
            'amount' => 50,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Just Below Minimum',
            'amount' => 49,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'Maximum Valid Amount',
            'amount' => 1000,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Just Above Maximum',
            'amount' => 1001,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'Decimal Amount',
            'amount' => 100.50,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
    ],
    
    // SET 3: Phone Number Variations
    'phone_variations' => [
        [
            'name' => 'Vodafone Standard',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Etisalat Standard',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01112345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Telecom Egypt',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01212345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Mobinil Standard',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01612345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'International Format',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '+201012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'With Spaces and Hyphens',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '0101 234-5678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Invalid Prefix',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '02012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
    ],
    
    // SET 4: Email Format Variations
    'email_variations' => [
        [
            'name' => 'Simple Instapay',
            'amount' => 200,
            'from' => 'user123@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Instapay with Dots',
            'amount' => 200,
            'from' => 'user.name@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Instapay with Underscore',
            'amount' => 200,
            'from' => 'user_name@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Instapay.eg Domain',
            'amount' => 200,
            'from' => 'user@instapay.eg',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Ahly Bank Partner',
            'amount' => 200,
            'from' => 'user@alahli.eg',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'VALID'
        ],
    ],
    
    // SET 5: Reference Variations
    'reference_variations' => [
        [
            'name' => 'Numeric Reference (12 digits)',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '123456789012',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Reference with Letters (INVALID)',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '12345678901A',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'No Reference',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => 'N/A',
            'expected' => 'VALID'
        ],
        [
            'name' => 'Short Reference (Too Short)',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '123456789',
            'expected' => 'INVALID'
        ],
    ],
    
    // SET 6: Fraud Patterns
    'fraud_patterns' => [
        [
            'name' => 'Invalid Email Format',
            'amount' => 200,
            'from' => 'not-an-email',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'Unknown Domain Email',
            'amount' => 200,
            'from' => 'user@fakebbank.com',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'SUSPICIOUS'
        ],
        [
            'name' => 'Reference with Special Chars',
            'amount' => 200,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234-5678-9012',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'Extremely Low Amount',
            'amount' => 1,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
        [
            'name' => 'Extremely High Amount',
            'amount' => 5000,
            'from' => 'test@instapay',
            'to' => 'User',
            'phone' => '01012345678',
            'reference' => '1234567890',
            'expected' => 'INVALID'
        ],
    ]
];

// ========== RUN TESTS ==========
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  COMPREHENSIVE INSTAPAY VALIDATION TEST SUITE                  ║\n";
echo "║  Testing Email, Phone, Amount, and Reference Validators       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$categoryResults = [];

foreach ($testSets as $categoryName => $tests) {
    $categoryResults[$categoryName] = [
        'passed' => 0,
        'failed' => 0,
        'total' => count($tests)
    ];
    
    echo "📋 TEST CATEGORY: " . strtoupper(str_replace('_', ' ', $categoryName)) . "\n";
    echo str_repeat("─", 70) . "\n";
    
    foreach ($tests as $idx => $test) {
        $totalTests++;
        $testNum = $idx + 1;
        
        // Validate all fields
        $emailResult = validateInstapayEmail($test['from']);
        $phoneResult = validateEgyptianPhone($test['phone']);
        $amountResult = validateAmount($test['amount']);
        $refResult = validateReference($test['reference']);
        
        // Determine overall test result
        $allValid = $emailResult['valid'] && $phoneResult['valid'] && $amountResult['valid'] && $refResult['valid'];
        $testPassed = false;
        
        if ($test['expected'] === 'VALID' && $allValid) {
            $testPassed = true;
            $status = "✅ PASS";
        } elseif ($test['expected'] === 'INVALID' && !$allValid) {
            $testPassed = true;
            $status = "✅ PASS";
        } elseif ($test['expected'] === 'SUSPICIOUS' && !$emailResult['valid']) {
            $testPassed = true;
            $status = "✅ PASS";
        } else {
            $status = "❌ FAIL";
        }
        
        if ($testPassed) {
            $passedTests++;
            $categoryResults[$categoryName]['passed']++;
        } else {
            $failedTests++;
            $categoryResults[$categoryName]['failed']++;
        }
        
        echo "{$status} | {$test['name']}\n";
        
        if (!$testPassed) {
            echo "   Expected: {$test['expected']}\n";
            echo "   Email: " . ($emailResult['valid'] ? '✓' : '✗') . " | Phone: " . ($phoneResult['valid'] ? '✓' : '✗') . " | Amount: " . ($amountResult['valid'] ? '✓' : '✗') . " | Ref: " . ($refResult['valid'] ? '✓' : '✗') . "\n";
        }
    }
    
    echo "\n";
}

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                       TEST SUMMARY                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "CATEGORY BREAKDOWN:\n";
foreach ($categoryResults as $cat => $result) {
    $percentage = round(($result['passed'] / $result['total']) * 100);
    echo "  • " . str_pad(ucwords(str_replace('_', ' ', $cat)), 30) . ": {$result['passed']}/{$result['total']} ({$percentage}%)\n";
}

echo "\n" . str_repeat("─", 70) . "\n";
echo "OVERALL RESULTS:\n";
echo "  Total Tests: {$totalTests}\n";
echo "  ✅ Passed: {$passedTests}\n";
echo "  ❌ Failed: {$failedTests}\n";
echo "  Success Rate: " . round(($passedTests / $totalTests) * 100) . "%\n";

if ($failedTests === 0) {
    echo "\n🎉 ALL TESTS PASSED! Validators are perfect!\n";
} else {
    echo "\n⚠️  Some tests failed. Review above for details.\n";
}

echo "\n# Validator Coverage Summary:\n";
echo "✅ Email Format Validation: " . count($testSets['email_variations']) . " tests\n";
echo "✅ Phone Number Validation: " . count($testSets['phone_variations']) . " tests\n";
echo "✅ Amount Range Validation: " . count($testSets['amount_boundaries']) . " tests\n";
echo "✅ Reference Format Validation: " . count($testSets['reference_variations']) . " tests\n";
echo "✅ Fraud Pattern Detection: " . count($testSets['fraud_patterns']) . " tests\n";
echo "✅ Real Transaction Validation: " . count($testSets['valid_transactions']) . " tests\n";

echo "\n✨ Comprehensive validation suite complete!\n";
?>
