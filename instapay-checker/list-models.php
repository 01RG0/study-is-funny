<?php
require_once 'config.php';

echo "Listing models for API Key: " . POLLINATIONS_API_KEY . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://gen.pollinations.ai/v1/models",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . POLLINATIONS_API_KEY
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['data'])) {
    echo "Found " . count($data['data']) . " models.\n";
    echo "Vision Models (support image input):\n";
    foreach ($data['data'] as $model) {
        if (isset($model['input_modalities']) && in_array('image', $model['input_modalities'])) {
            echo "- " . $model['id'] . "\n";
        }
    }
} else {
    echo "Error fetching models: " . $response . "\n";
}
