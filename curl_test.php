<?php
// Test API endpoint directly through HTTP GET
$testUrl = "http://localhost:8000/api/sessions.php?action=check-access&session_number=2&phone=01000733148&grade=senior2&subject=mathematics";

echo "Testing URL: " . $testUrl . "\n\n";

// Use curl instead of file_get_contents for better error handling  
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: " . $error . "\n";
} else {
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "\nParsed JSON:\n";
        print_r($data);
    } else {
        echo "\nFailed to parse as JSON. Raw response:\n";
        echo $response;
    }
}
?>