<?php
$base = 'https://hoi-mock-lounge-api.onrender.com';

echo "🚀 Testing Live Render QR Usage Limit & Count Features...\n\n";

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

// LOGIN
$login = callApi("$base/auth/login", ['email' => 'test@hoi.in', 'password' => 'test123']);
$token = $login['data']['data']['access_token'];

// TEST: ONE-TIME QR (max_usage = 1)
echo "1️⃣ Generating ONE-TIME QR (max_usage = 1)...\n";
$qr1 = callApi("$base/qr/generate", ['passenger_name' => 'Live OneTime Pax', 'max_usage' => 1], $token);
$id1 = $qr1['data']['data']['enquiry_id'] ?? '';
echo "Generated One-Time Enquiry ID: $id1\n";

// Scan 1
$scan1 = callApi("$base/lounge-visits/enquiries/$id1", ['status' => 'COMPLETED'], $token);
echo "Scan 1 Result [HTTP {$scan1['code']}]: " . json_encode($scan1['data'], JSON_PRETTY_PRINT) . "\n\n";

// Scan 2 (Should Fail with 409 Limit Exceeded)
$scan2 = callApi("$base/lounge-visits/enquiries/$id1", ['status' => 'COMPLETED'], $token);
echo "Scan 2 Result (Limit Exceeded) [HTTP {$scan2['code']}]: " . json_encode($scan2['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "🎉 LIVE RENDER USAGE LIMIT TEST FINISHED!\n";
