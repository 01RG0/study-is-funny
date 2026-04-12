<?php
/**
 * Test Gemini API connection
 */
require_once __DIR__ . '/../load_env.php';

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (empty($apiKey)) {
    die("Error: GEMINI_API_KEY not found in environment\n");
}

echo "Testing Gemini API with key: " . substr($apiKey, 0, 8) . "...\n";

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$requestBody = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Respond with "GEMINI_OK" and nothing else.']
            ]
        ]
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
