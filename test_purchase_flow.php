<?php
// Test complete purchase flow

echo "=== Testing Complete Purchase Flow ===\n\n";

// Test 1: Check access for session 1 (should need purchase)
echo "1. Checking access for Session 1 (should need purchase):\n";
$url1 = 'http://localhost:8000/api/sessions.php?action=check-access&session_number=1&phone=01000733148&grade=senior2&subject=mathematics';
echo "URL: $url1\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode1\n";
echo "Response: " . trim($response1) . "\n\n";

// Test 2: Purchase session 1
echo "2. Purchasing Session 1:\n";
$url2 = 'http://localhost:8000/api/sessions.php?action=purchase&session_number=1&phone=01000733148&grade=senior2&subject=mathematics';
echo "URL: $url2\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode2\n";
echo "Response: " . trim($response2) . "\n\n";

// Test 3: Check access again for session 1 (should have access now)
echo "3. Re-checking access for Session 1 (should have access now):\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode3\n";
echo "Response: " . trim($response3) . "\n\n";

// Test 4: Check access for session 2 (should still have access)
echo "4. Checking access for Session 2 (should still have access):\n";
$url4 = 'http://localhost:8000/api/sessions.php?action=check-access&session_number=2&phone=01000733148&grade=senior2&subject=mathematics';
echo "URL: $url4\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url4);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response4 = curl_exec($ch);
$httpCode4 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode4\n";
echo "Response: " . trim($response4) . "\n";
?>