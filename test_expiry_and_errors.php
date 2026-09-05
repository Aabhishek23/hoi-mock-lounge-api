<?php
$baseUrl = 'http://localhost/mock_lounge_server';

echo "🚀 Testing QR Expiry & Galat Token Error Messages...\n\n";

// Helper function for CURL POST
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

// 2. GENERATE QR WITH SHORT EXPIRY (3 SECONDS)
echo "2️⃣ Generating QR Enquiry with 3-second Expiry...\n";
$qr = callApi("$baseUrl/qr/generate", [
    'passenger_name' => 'Expiry Tester',
    'lounge_name' => 'Plaza Premium',
    'validity_seconds' => 3
], $token);

$enquiryId = $qr['data']['data']['enquiry_id'] ?? '';
echo "Generated Enquiry ID: $enquiryId [Expires at: {$qr['data']['data']['expires_at']}]\n\n";

// 3. IMMEDIATE VALIDATE (BEFORE EXPIRY)
echo "3️⃣ Validating Enquiry $enquiryId IMMEDIATELY (Before Expiry)...\n";
$val1 = callApi("$baseUrl/lounge-visits/enquiries/$enquiryId", ['status' => 'COMPLETED'], $token);
echo "HTTP Code: {$val1['code']}\n";
echo "Response: " . json_encode($val1['data'], JSON_PRETTY_PRINT) . "\n\n";

// 4. TEST GALAT / INVALID TOKEN
echo "4️⃣ Testing Galat / Invalid Token...\n";
$valInvalid = callApi("$baseUrl/lounge-visits/enquiries/$enquiryId", ['status' => 'COMPLETED'], 'GALAT_TAMPERED_TOKEN_XYZ');
echo "HTTP Code: {$valInvalid['code']}\n";
echo "Response: " . json_encode($valInvalid['data'], JSON_PRETTY_PRINT) . "\n\n";

// 5. GENERATE ANOTHER INSTANT EXPIRE QR (1 SEC) AND WAIT 2 SECS
echo "5️⃣ Generating 1-second Expiry QR for Expired Test...\n";
$qrShort = callApi("$baseUrl/qr/generate", [
    'passenger_name' => 'Instant Expire Pax',
    'validity_seconds' => 1
], $token);
$shortId = $qrShort['data']['data']['enquiry_id'];
echo "Generated Short-lived ID: $shortId\n";
echo "Sleeping 2 seconds for QR to expire...\n";
sleep(2);

echo "Validating Expired QR Enquiry $shortId...\n";
$valExpired = callApi("$baseUrl/lounge-visits/enquiries/$shortId", ['status' => 'COMPLETED'], $token);
echo "HTTP Code: {$valExpired['code']}\n";
echo "Response: " . json_encode($valExpired['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "🎉 ALL LOCAL EXPIRY & GALAT TOKEN TESTS FINISHED!\n";
