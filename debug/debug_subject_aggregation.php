<?php
/**
 * Debug script to test subject collection for students with multiple subjects
 * This helps identify why only one subject appears when there should be multiple
 */

// Test data structure - simulating what comes from all_students_view
$testRows = [
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

echo "=== Testing Subject Aggregation ===\n\n";

// Simulate grouping by phone
$studentsByPhone = [];

foreach ($testRows as $studentData) {
    $studentPhone = $studentData->phone ?? $studentData->studentPhone ?? null;
    
    echo "Processing row:\n";
    echo "  Phone: $studentPhone\n";
    echo "  Raw Subject: {$studentData->subject}\n";
    
    if ($studentPhone) {
        if (!isset($studentsByPhone[$studentPhone])) {
            $studentsByPhone[$studentPhone] = [
                'name' => $studentData->studentName ?? 'Student',
                'phone' => $studentPhone,
                'grade' => $studentData->grade ?? 'senior1',
                'subjects' => [],
                'subject' => ''
            ];
        }
        
        // Collect subjects
        if (isset($studentData->subject) && $studentData->subject) {
            $subjectValue = $studentData->subject;
            // Remove grade prefix
            $cleanSubject = preg_replace('/^\s*S[123]\s*-?\s*/i', '', $subjectValue);
            $cleanSubject = trim(strtolower($cleanSubject));
            
            echo "  Cleaned Subject: $cleanSubject\n";
            
            // Subject mapping
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
                echo "    Checking if '$cleanSubject' contains '$key': ";
                if (stripos($cleanSubject, $key) !== false) {
                    $slug = $value;
                    echo "YES → mapped to '$slug'\n";
                    break;
                } else {
                    echo "NO\n";
                }
            }
            
            if (!$slug) {
                echo "    No mapping found, trying fallback...\n";
                if (stripos($cleanSubject, 'mathematics') !== false) {
                    $slug = 'mathematics';
                    echo "    Fallback matched: 'mathematics'\n";
                } elseif (stripos($cleanSubject, 'physics') !== false) {
                    $slug = 'physics';
                    echo "    Fallback matched: 'physics'\n";
                } elseif (stripos($cleanSubject, 'mechanics') !== false) {
                    $slug = 'mechanics';
                    echo "    Fallback matched: 'mechanics'\n";
                } else {
                    $slug = 'mathematics';
                    echo "    Using default: 'mathematics'\n";
                }
            }
            
            echo "  Final Slug: $slug\n";
            
            if (!in_array($slug, $studentsByPhone[$studentPhone]['subjects'])) {
                $studentsByPhone[$studentPhone]['subjects'][] = $slug;
                echo "  ✓ Added to subjects array\n";
            } else {
                echo "  ! Already in array, skipping\n";
            }
        }
        echo "\n";
    }
}

echo "=== Final Result ===\n";
foreach ($studentsByPhone as $phone => $data) {
    echo "Phone: $phone\n";
    echo "Name: {$data['name']}\n";
    echo "Subjects: " . implode(', ', $data['subjects']) . "\n";
    echo "Expected: mathematics, mechanics\n";
    echo "Match: " . (count($data['subjects']) === 2 && in_array('mathematics', $data['subjects']) && in_array('mechanics', $data['subjects']) ? '✓ YES' : '✗ NO') . "\n";
}
?>
