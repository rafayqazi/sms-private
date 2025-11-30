<?php
$apiKey = 'AIzaSyDkKD_4RiBXyCaldHM5zFdtpTGlErImUQw';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
if ($curlError) {
    echo "cURL Error: " . $curlError . "\n";
}

$data = json_decode($response, true);
if (isset($data['models'])) {
    echo "Available Gemini Models for generateContent:\n";
    foreach ($data['models'] as $model) {
        if (strpos($model['name'], 'gemini') !== false && in_array('generateContent', $model['supportedGenerationMethods'])) {
            echo " - " . $model['name'] . "\n";
        }
    }
} else {
    echo "No models found or error parsing JSON.\n";
    echo substr($response, 0, 500); // Print first 500 chars of response for debugging
}
?>
