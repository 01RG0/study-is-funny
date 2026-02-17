<?php
/**
 * Investigation Script for Session ObjectId 6988deea867ada8290052b72
 * Checking phone number matching and session details
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/../classes/DatabaseMongo.php';

echo "=== Investigation Script for Session 6988deea867ada8290052b72 ===\n\n";

// Phone number functions from sessions.php
function normalizePhoneNumber($phone) {
    // Remove all non-digit characters except leading +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    
    // If starts with +20, convert to 01...
    if (strpos($phone, '+20') === 0) {
        return '0' . substr($phone, 3);
    }
    
    // If starts with +, remove it
    if (strpos($phone, '+') === 0) {
        return substr($phone, 1);
    }
    
    // Ensure it starts with 0 (Egyptian phone format)
    if (strpos($phone, '0') !== 0 && strpos($phone, '20') === 0) {
        return '0' . substr($phone, 2);
    }
    
    return $phone;
}

function convertTo20Format($phone) {
    // First normalize to 01... format
    $normalized = normalizePhoneNumber($phone);
    
    // If it starts with 0, replace with +20
    if (strpos($normalized, '0') === 0) {
        return '+20' . substr($normalized, 1);
    }
    
    return $normalized;
}

try {
    $mongo = new DatabaseMongo();
    
    echo "1. SEARCHING FOR SESSION ID 6988deea867ada8290052b72 IN online_sessions\n";
    echo "==================================================================\n";
    
    // Search for this ObjectId in online_sessions collection
    $sessionObjectId = new MongoDB\BSON\ObjectId('6988deea867ada8290052b72');
    $sessionDoc = $mongo->findOne('online_sessions', ['_id' => $sessionObjectId]);
    
    if ($sessionDoc) {
        echo "✓ FOUND SESSION IN online_sessions collection!\n";
        echo "Session Details:\n";
        echo "- _id: " . $sessionDoc->_id . "\n";
        echo "- sessionNumber: " . ($sessionDoc->sessionNumber ?? 'NOT SET') . "\n";
        echo "- title: " . ($sessionDoc->title ?? 'NOT SET') . "\n";
        echo "- subject: " . ($sessionDoc->subject ?? 'NOT SET') . "\n";
        echo "- grade: " . ($sessionDoc->grade ?? 'NOT SET') . "\n";
        echo "- status: " . ($sessionDoc->status ?? 'NOT SET') . "\n";
        echo "- createdAt: " . ($sessionDoc->createdAt ?? 'NOT SET') . "\n";
        echo "- Full Document: " . json_encode($sessionDoc, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "✗ Session NOT found in online_sessions collection\n\n";
    }
    
    echo "2. PHONE NUMBER MATCHING TEST\n";
    echo "==============================\n";
    $testPhone = "01000733148";  // URL parameter format
    $dbPhone = "+201000733148"; // Database format
    
    echo "URL Parameter Phone: '$testPhone'\n";
    echo "Database Phone Format: '$dbPhone'\n\n";
    
    echo "Phone Normalization Results:\n";
    echo "  normalizePhoneNumber('$testPhone'): '" . normalizePhoneNumber($testPhone) . "'\n";
    echo "  normalizePhoneNumber('$dbPhone'): '" . normalizePhoneNumber($dbPhone) . "'\n";
    echo "  convertTo20Format('$testPhone'): '" . convertTo20Format($testPhone) . "'\n";
    echo "  convertTo20Format('$dbPhone'): '" . convertTo20Format($dbPhone) . "'\n\n";
    
    // Generate phone variations like in purchaseStudentSession
    $phoneVariations = [
        $testPhone,
        normalizePhoneNumber($testPhone),
        convertTo20Format($testPhone),
    ];
    $phoneVariations = array_values(array_unique(array_filter($phoneVariations)));
    
    echo "Generated Phone Variations for Lookup: " . json_encode($phoneVariations) . "\n\n";
    
    echo "3. SEARCHING FOR STUDENT WITH PHONE $testPhone\n";
    echo "==============================================\n";
    
    // Check all possible collections for this student
    $collections = [
        'senior1_math',
        'senior2_pure_math', 
        'senior2_mechanics',
        'senior2_physics',
        'senior3_math',
        'senior3_physics', 
        'senior3_statistics'
    ];
    
    foreach ($collections as $collection) {
        echo "Checking collection: $collection\n";
        
        $student = $mongo->findOne($collection, ['phone' => ['$in' => $phoneVariations], 'isActive' => true]);
        
        if ($student) {
            echo "  ✓ FOUND STUDENT in $collection!\n";
            echo "  - Student ID: " . ($student->studentId ?? 'NOT SET') . "\n";
            echo "  - Name: " . ($student->studentName ?? 'NOT SET') . "\n";  
            echo "  - Phone: " . ($student->phone ?? 'NOT SET') . "\n";
            echo "  - Balance: " . ($student->balance ?? 'NOT SET') . "\n";
            echo "  - Payment Amount: " . ($student->paymentAmount ?? 'NOT SET') . "\n";
            
            echo "  - Session Access Status:\n";
            for ($i = 1; $i <= 10; $i++) {
                $sessionKey = 'session_' . $i;
                if (isset($student->$sessionKey)) {
                    $session = $student->$sessionKey;
                    echo "    * Session $i: ";
                    if (isset($session->online_session) && $session->online_session === true) {
                        echo "PURCHASED";
                        if (isset($session->purchased_at)) {
                            echo " (purchased: " . $session->purchased_at . ")";
                        }
                    } else {
                        echo "not purchased";
                    }
                    echo "\n";
                }
            }
            echo "\n";
        } else {
            echo "  ✗ No student found in $collection\n";
        }
    }
    
    echo "4. CHECKING IF SESSION 2 MATCHES THIS OBJECT ID\n";
    echo "===============================================\n";
    
    // If we found the session earlier, check if it's session 2
    if ($sessionDoc && isset($sessionDoc->sessionNumber)) {
        $sessionNum = $sessionDoc->sessionNumber;
        echo "This ObjectId corresponds to Session Number: $sessionNum\n";
        
        if ($sessionNum == 2) {
            echo "✓ YES! This is session_2 that the student may have already purchased\n";
        } else {
            echo "✗ No, this is session_$sessionNum, not session_2\n";
        }
    }
    
    echo "\n=== INVESTIGATION COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>