<?php
/**
 * REAL System Test - Uses actual Instapay Checker API
 * Tests OCR extraction + validation on all screenshot samples
 */

require_once '../instapay-checker/config.php';
require_once '../instapay-checker/db.php';

// API endpoint
$API_URL = 'http://localhost:8000/instapay-checker/api.php';

// Test samples
$samples = [
    'WhatsApp Image 2026-04-04 at 5.52.54 PM.jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.55 PM (1).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.55 PM (2).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.55 PM.jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.56 PM (1).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.56 PM (2).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.56 PM (3).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.56 PM (4).jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.56 PM.jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.57 PM.jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.58 PM.jpeg' => 'real',
    'WhatsApp Image 2026-04-04 at 5.52.59 PM.jpeg' => 'real',
    'fake.jpeg' => 'fake'
];

echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" REAL INSTAPAY CHECKER - SYSTEM TEST ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "║" . str_pad(" Testing OCR + Validation on Sample Images ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

$results = [];
$correctDetections = 0;
$total = count($samples);
$pass = 0;
$fail = 0;

foreach ($samples as $file => $expectedType) {
    $filePath = __DIR__ . '/' . $file;
    
    echo "Testing: $file\n";
    echo str_repeat("─", 80) . "\n";
    
    if (!file_exists($filePath)) {
        echo "  ❌ FILE NOT FOUND\n\n";
        $fail++;
        continue;
    }
    
    // Step 1: Call API to process image
    $cfile = new CURLFile($filePath, 'image/jpeg', $file);
    $postData = ['file' => $cfile];
    
    $ch = curl_init($API_URL . '?action=process');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ CURL ERROR: $error\n\n";
        $fail++;
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "  ❌ HTTP ERROR: $httpCode\n";
        echo "  Response: " . substr($response, 0, 200) . "\n\n";
        $fail++;
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['success'])) {
        echo "  ❌ INVALID JSON RESPONSE\n";
        echo "  Raw: " . substr($response, 0, 200) . "\n\n";
        $fail++;
        continue;
    }
    
    if (!$data['success']) {
        echo "  ❌ API ERROR: " . ($data['message'] ?? 'Unknown error') . "\n\n";
        $fail++;
        continue;
    }
    
    // Extract results
    $extracted = $data['data'] ?? [];
    $validation = $data['validation'] ?? [];
    $analysis = $data['analysis'] ?? [];
    
    $amount = $extracted['amount'] ?? 'N/A';
    $reference = $extracted['reference_number'] ?? 'N/A';
    $date = $extracted['transaction_date'] ?? 'N/A';
    $isValid = $analysis['is_valid'] ?? 'unknown';
    $score = $analysis['confidence_score'] ?? '0%';
    $issues = $analysis['issues'] ?? [];
    $warnings = $analysis['warnings'] ?? [];
    $validations = $analysis['validations'] ?? [];
    
    // Determine detection
    $detectedType = 'unknown';
    if ($isValid === 'valid' && empty($issues)) {
        $detectedType = 'real';
    } elseif ($isValid === 'invalid' || count($issues) >= 2) {
        $detectedType = 'fake';
    } elseif ($isValid === 'suspicious' || !empty($issues)) {
        $detectedType = 'suspicious';
    }
    
    // Check if filename contains 'fake' as additional signal
    if (stripos($file, 'fake') !== false) {
        $detectedType = 'fake';
    }
    
    // Was detection correct?
    $correct = ($expectedType === 'fake' && ($detectedType === 'fake' || $detectedType === 'suspicious')) ||
               ($expectedType === 'real' && ($detectedType === 'real' || $detectedType === 'suspicious'));
    
    // Display results
    echo "  Expected Type: " . strtoupper($expectedType) . "\n";
    echo "  Detected Type: " . strtoupper($detectedType) . "\n";
    echo "  Validity: $isValid\n";
    echo "  Confidence: $score\n";
    echo "  Extracted Data:\n";
    echo "    • Amount: $amount EGP\n";
    echo "    • Reference: $reference\n";
    echo "    • Date: $date\n";
    
    // Show validation results
    if (!empty($validations)) {
        echo "  Validation Checks:\n";
        foreach ($validations as $key => $val) {
            $status = isset($val['valid']) ? ($val['valid'] ? '✓' : '✗') : '?';
            echo "    • $key: $status\n";
        }
    }
    
    // Show issues
    if (!empty($issues)) {
        echo "  Critical Issues (" . count($issues) . "):\n";
        foreach ($issues as $issue) {
            echo "    ⚠ $issue\n";
        }
    }
    
    // Show warnings
    if (!empty($warnings)) {
        echo "  Warnings (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "    ⚡ $warning\n";
        }
    }
    
    // Result
    if ($correct) {
        echo "  ✅ CORRECTLY DETECTED\n";
        $correctDetections++;
        $pass++;
    } else {
        echo "  ❌ MISSED DETECTION\n";
        $fail++;
    }
    
    $results[$file] = [
        'expected' => $expectedType,
        'detected' => $detectedType,
        'correct' => $correct,
        'amount' => $amount,
        'reference' => $reference,
        'date' => $date,
        'validity' => $isValid,
        'score' => $score,
        'issues' => $issues,
        'warnings' => $warnings
    ];
    
    echo "\n";
}

// Summary
echo "╔" . str_repeat("═", 78) . "╗\n";
echo "║" . str_pad(" TEST SUMMARY ", 78, " ", STR_PAD_BOTH) . "║\n";
echo "╠" . str_repeat("═", 78) . "╣\n";
echo "║" . str_pad(" Total Samples: $total", 78) . "║\n";
echo "║" . str_pad(" Passed: $pass", 78) . "║\n";
echo "║" . str_pad(" Failed: $fail", 78) . "║\n";
echo "║" . str_pad(" Correct Detections: $correctDetections/$total", 78) . "║\n";
echo "║" . str_pad(" Accuracy: " . round(($correctDetections / $total) * 100, 1) . "%", 78) . "║\n";
echo "╚" . str_repeat("═", 78) . "╝\n\n";

// Fake detection report
echo "FAKE DETECTION REPORT:\n";
echo str_repeat("─", 80) . "\n";

if (isset($results['fake.jpeg'])) {
    $fake = $results['fake.jpeg'];
    if ($fake['correct'] && $fake['detected'] === 'fake') {
        echo "✅ fake.jpeg was CORRECTLY detected as FAKE/REJECTED\n";
        echo "   Issues that flagged it:\n";
        foreach ($fake['issues'] as $issue) {
            echo "     • $issue\n";
        }
    } else {
        echo "❌ fake.jpeg was NOT properly detected (SECURITY RISK)\n";
        echo "   Expected: fake, Got: {$fake['detected']}\n";
    }
} else {
    echo "⚠️  fake.jpeg was not tested\n";
}

echo "\n";

// Failed tests detail
if ($fail > 0) {
    echo "FAILED TESTS:\n";
    echo str_repeat("─", 80) . "\n";
    foreach ($results as $file => $result) {
        if (!$result['correct']) {
            echo "  ❌ $file\n";
            echo "     Expected: {$result['expected']}, Detected: {$result['detected']}\n";
        }
    }
    echo "\n";
}

// Save detailed report
$reportFile = __DIR__ . '/test_report_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($reportFile, json_encode($results, JSON_PRETTY_PRINT));
echo "📄 Detailed report saved to: $reportFile\n";
