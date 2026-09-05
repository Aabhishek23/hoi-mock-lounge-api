<?php
$base = 'http://localhost/mock_lounge_server';

// Test 1: Register
echo "=== TEST 1: REGISTER ===\n";
$ch = curl_init($base . '/auth/register');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['name' => 'Test User', 'email' => 'newuser@hoi.in', 'password' => 'test123']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $code\n";
echo "Response: $res\n\n";

// Test 2: Login with pre-seeded user
echo "=== TEST 2: LOGIN (pre-seeded user) ===\n";
$ch = curl_init($base . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['email' => 'test@hoi.in', 'password' => 'test123']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Code: $code\n";
echo "Response: $res\n\n";

$data = json_decode($res, true);
$token = $data['data']['access_token'] ?? null;

// Test 3: QR Generate with token
if ($token) {
    echo "=== TEST 3: QR GENERATE ===\n";
    $ch = curl_init($base . '/qr/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['passenger_name' => 'Amit Kumar', 'lounge_name' => 'Plaza T3']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token]
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP Code: $code\n";
    echo "Response: $res\n\n";

    $qrData = json_decode($res, true);
    $enquiryId = $qrData['data']['enquiry_id'] ?? '81605';

    // Test 4: Validate Token (Lounge Visit Endpoint)
    echo "=== TEST 4: VALIDATE TOKEN (Enquiry ID: $enquiryId) ===\n";
    $ch = curl_init($base . '/lounge-visits/enquiries/' . $enquiryId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['status' => 'COMPLETED']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token, 'x-ui-source: OPSDASH']
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP Code: $code\n";
    echo "Response: $res\n\n";
} else {
    echo "Login failed - token not received. Cannot test QR generate.\n";
}
?>
