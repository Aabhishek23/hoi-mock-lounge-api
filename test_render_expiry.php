<?php
$baseUrl = 'https://hoi-mock-lounge-api.onrender.com';

echo "🚀 Testing Live Render QR Expiry & Galat Token Error Messages...\n\n";

function callApi($url, $payload = [], $token = null) {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token !== null) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($res, true)];
}

// 1. LOGIN
echo "1️⃣ Logging in...\n";
$login = callApi("$baseUrl/auth/login", ['email' => 'test@hoi.in', 'password' => 'test123']);
$token = $login['data']['data']['access_token'] ?? '';
echo "Token Obtained! [HTTP {$login['code']}]\n\n";

// 2. GENERATE INSTANT EXPIRED QR (1 SEC)
echo "2️⃣ Generating 1-second Expiry QR for Expired Test...\n";
$qrShort = callApi("$baseUrl/qr/generate", [
    'passenger_name' => 'Live Instant Expire Pax',
    'validity_seconds' => 1
], $token);
$shortId = $qrShort['data']['data']['enquiry_id'] ?? '';
echo "Generated Short-lived ID: $shortId [Expires at: {$qrShort['data']['data']['expires_at']}]\n";

echo "Sleeping 2 seconds for QR to expire...\n";
sleep(2);

echo "Validating Expired QR Enquiry $shortId...\n";
$valExpired = callApi("$baseUrl/lounge-visits/enquiries/$shortId", ['status' => 'COMPLETED'], $token);
echo "HTTP Code: {$valExpired['code']}\n";
echo "Response: " . json_encode($valExpired['data'], JSON_PRETTY_PRINT) . "\n\n";

// 3. TEST GALAT TOKEN
echo "3️⃣ Testing Galat / Tampered Token...\n";
$valInvalid = callApi("$baseUrl/lounge-visits/enquiries/$shortId", ['status' => 'COMPLETED'], 'GALAT_TAMPERED_TOKEN_XYZ');
echo "HTTP Code: {$valInvalid['code']}\n";
echo "Response: " . json_encode($valInvalid['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "🎉 ALL LIVE RENDER TESTS FINISHED!\n";
