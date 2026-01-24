<?php
/**
 * Test API endpoint to debug subject retrieval
 * Usage: Add debug=1 to the query string
 * Example: /api/students.php?action=getByParentPhone&parentPhone=+201280912038&debug=1
 */

$parentPhone = '+201280912038'; // Example phone
$debugMode = true;

if ($debugMode) {
    error_log("\n=== DEBUG: getStudentByParentPhone ===");
    error_log("Parent Phone: " . $parentPhone);
}

// Normalize phone formats
$phonesToTry = [$parentPhone];
$cleanPhone = preg_replace('/[^0-9]/', '', $parentPhone);

if ($debugMode) {
    error_log("Clean Phone: " . $cleanPhone);
    error_log("Clean Phone Length: " . strlen($cleanPhone));
}

// Handle different phone formats
if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '20') {
    $phonesToTry[] = '0' . substr($cleanPhone, 2);
    $phonesToTry[] = '+' . $cleanPhone;
    $phonesToTry[] = $cleanPhone;
} elseif (strlen($cleanPhone) === 11 && substr($cleanPhone, 0, 1) === '0') {
    $phonesToTry[] = '+2' . $cleanPhone;
    $phonesToTry[] = '2' . $cleanPhone;
    $phonesToTry[] = $cleanPhone;
} elseif (strlen($cleanPhone) === 10) {
    $phonesToTry[] = '0' . $cleanPhone;
    $phonesToTry[] = '+20' . $cleanPhone;
    $phonesToTry[] = '20' . $cleanPhone;
}

if ($debugMode) {
    error_log("Phones to Try:");
    foreach ($phonesToTry as $i => $p) {
        error_log("  [$i] = $p");
    }
}

// Example database rows
$matches = [
    (object)[
        'phone' => '01280912038',
        'studentName' => 'محمد أحمد',
        'subject' => 'S2 - Pure Math',
        'grade' => 'senior2'
    ],
    (object)[
        'phone' => '01280912038',
        'studentName' => 'محمد أحمد',
        'subject' => 'S2 - Mechanics',
        'grade' => 'senior2'
    ]
];

if ($debugMode) {
    error_log("Database Matches Found: " . count($matches));
    foreach ($matches as $i => $match) {
        error_log("  [$i] phone={$match->phone}, subject={$match->subject}");
    }
}

// Group by phone
$studentsByPhone = [];

foreach ($matches as $studentData) {
    $studentPhone = $studentData->phone ?? $studentData->studentPhone ?? null;
    
    if ($debugMode) {
        error_log("Processing studentPhone: $studentPhone");
    }
    
    if ($studentPhone) {
        if (!isset($studentsByPhone[$studentPhone])) {
            $studentsByPhone[$studentPhone] = [
                'name' => $studentData->studentName ?? $studentData->name ?? 'Student',
                'phone' => $studentPhone,
                'grade' => $studentData->grade ?? 'senior1',
                'subjects' => [],
                'subject' => ''
            ];
            if ($debugMode) {
                error_log("  Created new entry for phone: $studentPhone");
            }
        }
        
        // Collect subjects
        if (isset($studentData->subject) && $studentData->subject) {
            $subjectValue = $studentData->subject;
            $cleanSubject = preg_replace('/^\s*S[123]\s*-?\s*/i', '', $subjectValue);
            $cleanSubject = trim(strtolower($cleanSubject));
            
            if ($debugMode) {
                error_log("  Raw Subject: '$subjectValue' → Cleaned: '$cleanSubject'");
            }
            
            $subjectMapping = [
                'pure math' => 'mathematics',
                'pure' => 'mathematics',
                'math' => 'mathematics',
                'physics' => 'physics',
                'mechanics' => 'mechanics',
                'statistics' => 'mathematics',
                'stat' => 'mathematics'
            ];
            
            $slug = null;
            foreach ($subjectMapping as $key => $value) {
                if (stripos($cleanSubject, $key) !== false) {
                    $slug = $value;
                    if ($debugMode) {
                        error_log("  Mapped via '$key' to: '$slug'");
                    }
                    break;
                }
            }
            
            if (!$slug) {
                if (stripos($cleanSubject, 'mathematics') !== false) {
                    $slug = 'mathematics';
                } elseif (stripos($cleanSubject, 'physics') !== false) {
                    $slug = 'physics';
                } elseif (stripos($cleanSubject, 'mechanics') !== false) {
                    $slug = 'mechanics';
                } else {
                    $slug = 'mathematics';
                }
                if ($debugMode) {
                    error_log("  Used fallback mapping: '$slug'");
                }
            }
            
            if (!in_array($slug, $studentsByPhone[$studentPhone]['subjects'])) {
                $studentsByPhone[$studentPhone]['subjects'][] = $slug;
                if ($debugMode) {
                    error_log("  Added subject: '$slug' (total now: " . count($studentsByPhone[$studentPhone]['subjects']) . ")");
                }
            } else {
                if ($debugMode) {
                    error_log("  Subject already exists: '$slug'");
                }
            }
            
            if (!$studentsByPhone[$studentPhone]['subject']) {
                $studentsByPhone[$studentPhone]['subject'] = $subjectValue;
            }
        }
    }
}

if ($debugMode) {
    error_log("\n=== Final Aggregation Result ===");
    foreach ($studentsByPhone as $phone => $data) {
        error_log("Phone: $phone");
        error_log("  Subjects: " . json_encode($data['subjects']));
        error_log("  Subject Count: " . count($data['subjects']));
    }
}

?>
