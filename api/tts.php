<?php
/**
 * Vercel Serverless Function - TTS Proxy
 * File: api/tts.php
 */

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration
define('GOOGLE_TTS_API_KEY', 'AIzaSyAzmnMAqjJnhRTv4XOsjsYkGH6kXb9YirE');
define('ALLOWED_SECRET', 'tsuvoice-4530-LeRt');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify API Key
// ค้นหา Header แบบไม่สนใจตัวพิมพ์เล็ก/ใหญ่
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$apiKey = $headers['x-api-key'] ?? '';

if ($apiKey !== ALLOWED_SECRET) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

$text = $input['text'] ?? '';
$voice = $input['voice'] ?? 'th-TH-Neural2-C';
$speakingRate = floatval($input['speaking_rate'] ?? 1.0);
$pitch = floatval($input['pitch'] ?? 0.0);

if (empty($text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Text is required']);
    exit;
}

if (strlen($text) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Text too long (max 5000 bytes)']);
    exit;
}

// Call Google Cloud TTS API
$url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . GOOGLE_TTS_API_KEY;

$data = json_encode([
    'input' => ['text' => $text],
    'voice' => [
        'languageCode' => 'th-TH',
        'name' => $voice
    ],
    'audioConfig' => [
        'audioEncoding' => 'MP3',
        'speakingRate' => $speakingRate,
        'pitch' => $pitch
    ]
], JSON_UNESCAPED_UNICODE);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ],
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $error]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200) {
    $errorMsg = $result['error']['message'] ?? 'Unknown error';
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => 'Google TTS Error: ' . $errorMsg]);
    exit;
}

if (!isset($result['audioContent'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No audio content']);
    exit;
}

echo json_encode([
    'success' => true,
    'audioContent' => $result['audioContent'],
    'voice' => $voice
]);
