<?php
require_once '../includes/ai_context.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? $input['message'] : '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// --- CONFIGURATION ---
$apiKey = 'AIzaSyDkKD_4RiBXyCaldHM5zFdtpTGlErImUQw'; 
// ---------------------

if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
    echo json_encode(['error' => 'API Key not configured. Please add your Gemini API Key in api/chat.php']);
    exit;
}

// 1. Get Context
$aiContext = new AIContext();
$systemPrompt = $aiContext->getContext();

// 2. Prepare Payload for Gemini
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemPrompt . "\n\nUser Question: " . $userMessage]
            ]
        ]
    ]
];

// 3. Send Request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
// FIX: Disable SSL verification for local XAMPP environments
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    $errorMsg = "API Request failed (HTTP $httpCode). ";
    if ($curlError) $errorMsg .= "cURL: $curlError. ";
    if ($response) {
        $resp = json_decode($response, true);
        if (isset($resp['error']['message'])) {
            $errorMsg .= "Gemini: " . $resp['error']['message'];
        } else {
            $errorMsg .= "Details: " . substr($response, 0, 100);
        }
    }
    
    echo json_encode([
        'error' => $errorMsg
    ]);
    exit;
}

// 4. Parse Response
$responseData = json_decode($response, true);
$aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not understand that.';

echo json_encode(['reply' => $aiReply]);
?>
