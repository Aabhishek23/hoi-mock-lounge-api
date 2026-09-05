<?php
$base = 'https://hoi-mock-lounge-api.onrender.com';

echo "Testing: $base\n\n";

// Test 1: Login
echo "=== TEST 1: LOGIN ===\n";
$ch = curl_init($base . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['email' => 'test@hoi.in', 'password' => 'test123']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "HTTP Code: $code\n";
if ($err) echo "cURL Error: $err\n";
echo "Response: $res\n\n";

$data = json_decode($res, true);
$token = $data['data']['access_token'] ?? null;

if (!$token) {
    echo "Token not received. Stopping.\n";
    exit;
}
echo "Token received: " . substr($token, 0, 40) . "...\n\n";

// Test 2: QR Generate
echo "=== TEST 2: QR GENERATE ===\n";
$ch = curl_init($base . '/qr/generate');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['passenger_name' => 'Amit Kumar', 'lounge_name' => 'Plaza T3']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $code\n";
echo "Response: $res\n\n";

$qrData = json_decode($res, true);
$enquiryId = $qrData['data']['enquiry_id'] ?? null;

if ($enquiryId) {
    // Test 3: Validate Token
    echo "=== TEST 3: VALIDATE TOKEN (ID: $enquiryId) ===\n";
    $ch = curl_init($base . '/lounge-visits/enquiries/' . $enquiryId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['status' => 'COMPLETED']),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'x-ui-source: OPSDASH'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP Code: $code\n";
    echo "Response: $res\n";
}
?>
