<?php
require_once 'config.php';

echo "Testing Pollinations Unified API...\n";
echo "Endpoint: " . POLLINATIONS_API_URL . "\n";
define('POLLINATIONS_MODEL_TEST', 'qwen-vision');
echo "Model: " . POLLINATIONS_MODEL_TEST . "\n";

$imagePath = 'test-receipt.png';
if (!file_exists($imagePath)) {
    die("Error: test-receipt.png not found. Please place a sample image in the directory.\n");
}

$imageData = file_get_contents($imagePath);
$base64Image = base64_encode($imageData);
$mimeType = 'image/png';

$requestBody = [
    'model' => POLLINATIONS_MODEL_TEST,
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Analyze this receipt and return JSON with amount, date, and reference_number.'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mimeType . ';base64,' . $base64Image]]
            ]
        ]
    ],
    'temperature' => 0.1
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => POLLINATIONS_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . POLLINATIONS_API_KEY
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "CURL Error: $err\n";
} else {
    echo "HTTP Status: $httpCode\n";
    echo "Response:\n$response\n";
}
