<?php
/**
 * Standalone test script for Mistral AI Vision API
 */
require_once __DIR__ . '/config.php';

function testMistralOCR($imagePath) {
    if (!file_exists($imagePath)) {
        die("❌ Image not found: $imagePath\n");
    }

    echo "🔍 Testing Mistral AI OCR with image: " . basename($imagePath) . "\n";
    echo "🤖 Model: " . MISTRAL_MODEL . "\n";
    echo "🌐 Endpoint: " . MISTRAL_API_URL . "\n";
    
    $imageData = file_get_contents($imagePath);
    $base64Image = base64_encode($imageData);
    $imageInfo = getimagesize($imagePath);
    $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

    $requestBody = [
        'model' => MISTRAL_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'أنت متخصص في تحليل لقطات شاشة معاملات إنستاباي. حلل الصورة وأخرج جميع المعلومات بصيغة JSON تحتوي على: amount (المبلغ), currency (العملة), sender_account (بريد المرسل @instapay), sender_name (اسم المرسل), receiver_name (اسم المستقبل), receiver_phone (رقم الهاتف), reference_number (الرقم المرجع), transaction_date (التاريخ والوقت), bank_name (اسم البنك), transaction_type (نوع المعاملة). يجب أن تكون جميع القيم نصية. إذا لم تجد معلومة ما، ضع null. أعد فقط JSON بدون شرح.'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:' . $mimeType . ';base64,' . $base64Image
                        ]
                    ]
                ]
            ]
        ],
        'temperature' => 0.1,
        'response_format' => ['type' => 'json_object']
    ];

    $startTime = microtime(true);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => MISTRAL_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MISTRAL_API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    curl_close($curl);

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    if ($err) {
        die("❌ Curl Error: " . $err . "\n");
    }

    echo "📊 HTTP Code: $httpCode (Time: {$duration}s)\n";
    
    $decoded = json_decode($response, true);
    if (isset($decoded['choices'][0]['message']['content'])) {
        echo "✅ Success! Extracted Content:\n";
        echo "----------------------------------------\n";
        echo $decoded['choices'][0]['message']['content'] . "\n";
        echo "----------------------------------------\n";
    } else {
        echo "❌ Error: Failed to extract content.\n";
        echo "Response: " . substr($response, 0, 1000) . "...\n";
    }
}

// Find the first available image in uploads
$uploadsDir = __DIR__ . '/uploads/';
$images = glob($uploadsDir . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);

if (empty($images)) {
    die("❌ No sample images found in $uploadsDir\n");
}

$sampleImage = $images[0];
testMistralOCR($sampleImage);
