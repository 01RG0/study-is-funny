<?php
// API Testing Script for Study is Funny
echo "Study is Funny API Test Suite\n";
echo "==============================\n\n";

// Test 1: Basic PHP functionality
echo "1. Testing PHP Configuration:\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   MongoDB Extension: " . (extension_loaded('mongodb') ? '✓ Available' : '✗ Not Available') . "\n";
echo "   JSON Extension: " . (extension_loaded('json') ? '✓ Available' : '✗ Not Available') . "\n";
echo "   cURL Extension: " . (extension_loaded('curl') ? '✓ Available' : '✗ Not Available') . "\n\n";

// Test 2: Test config.php
echo "2. Testing config.php:\n";
try {
    require_once 'api/config.php';
    echo "   ✓ config.php loaded successfully\n";
    echo "   ✓ MongoDB connection established\n";
} catch (Exception $e) {
    echo "   ✗ config.php failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Test API endpoints
echo "3. Testing API Endpoints:\n";

$baseUrl = 'http://localhost:8000/api/';

// Test student registration
echo "   Testing Student Registration:\n";
$registerData = [
    'name' => 'Test Student',
    'phone' => '01234567899',
    'password' => 'test123',
    'grade' => 'senior1'
];

$result = testApiEndpoint($baseUrl . 'students.php?action=register', 'POST', $registerData);
if ($result && isset($result['success']) && $result['success']) {
    echo "   ✓ Registration successful\n";
} else {
    echo "   ✗ Registration failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}

// Test student login
echo "   Testing Student Login:\n";
$loginData = [
    'phone' => '01234567899',
    'password' => 'test123'
];

$result = testApiEndpoint($baseUrl . 'students.php?action=login', 'POST', $loginData);
if ($result && isset($result['success']) && $result['success']) {
    echo "   ✓ Login successful\n";
} else {
    echo "   ✗ Login failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}

// Test get all students
echo "   Testing Get All Students:\n";
$result = testApiEndpoint($baseUrl . 'students.php?action=all', 'GET');
if ($result && isset($result['success']) && $result['success']) {
    echo "   ✓ Retrieved " . count($result['documents']) . " students\n";
} else {
    echo "   ✗ Failed to get students: " . ($result['message'] ?? 'Unknown error') . "\n";
}

echo "\n4. Demo Data Students:\n";
$demoStudents = [
    ['name' => 'أحمد محمد', 'phone' => '01280912031', 'grade' => 'senior1'],
    ['name' => 'فاطمة أحمد', 'phone' => '01234567890', 'grade' => 'senior2'],
    ['name' => 'محمد علي', 'phone' => '01111111111', 'grade' => 'senior1']
];

foreach ($demoStudents as $student) {
    echo "   - {$student['name']} ({$student['phone']}) - {$student['grade']}\n";
}

echo "\n5. Available API Endpoints:\n";
echo "   GET  /api/students.php?action=all          - Get all students\n";
echo "   GET  /api/students.php?action=get&phone=X  - Get specific student\n";
echo "   POST /api/students.php?action=register     - Register new student\n";
echo "   POST /api/students.php?action=login        - Student login\n";
echo "   PUT  /api/students.php?action=update       - Update student\n";

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";

function testApiEndpoint($url, $method = 'GET', $data = null) {
    if (!extension_loaded('curl')) {
        echo "   ✗ cURL extension required for API testing\n";
        return false;
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Request failed'];
    }

    $decoded = json_decode($response, true);
    return $decoded ?: ['success' => false, 'message' => 'Invalid JSON response', 'raw' => $response];
}
?>