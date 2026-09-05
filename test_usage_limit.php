<?php
$base = 'http://localhost/mock_lounge_server';

echo "🚀 Testing QR Usage Count & Limit Features...\n\n";

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
$login = callApi("$base/auth/login", ['email' => 'test@hoi.in', 'password' => 'test123']);
$token = $login['data']['data']['access_token'];
echo "1️⃣ Logged in! Token ready.\n\n";

// TEST A: ONE-TIME QR USE (max_usage = 1)
echo "2️⃣ Testing ONE-TIME QR (max_usage = 1)...\n";
$qr1 = callApi("$base/qr/generate", ['passenger_name' => 'OneTime Pax', 'max_usage' => 1], $token);
$id1 = $qr1['data']['data']['enquiry_id'];
echo "Generated One-Time Enquiry ID: $id1\n";

// Scan 1
$scan1 = callApi("$base/lounge-visits/enquiries/$id1", ['status' => 'COMPLETED'], $token);
echo "Scan 1 Result [HTTP {$scan1['code']}]: " . json_encode($scan1['data'], JSON_PRETTY_PRINT) . "\n";

// Scan 2 (Should Fail with 409)
$scan2 = callApi("$base/lounge-visits/enquiries/$id1", ['status' => 'COMPLETED'], $token);
echo "Scan 2 Result (Limit Reached) [HTTP {$scan2['code']}]: " . json_encode($scan2['data'], JSON_PRETTY_PRINT) . "\n\n";

// TEST B: MULTI-USE QR (max_usage = 2)
echo "3️⃣ Testing MULTI-USE QR (max_usage = 2 Pax Entry)...\n";
$qr2 = callApi("$base/qr/generate", ['passenger_name' => 'Two Pax Family', 'max_usage' => 2], $token);
$id2 = $qr2['data']['data']['enquiry_id'];
echo "Generated 2-Use Enquiry ID: $id2\n";

// Scan 1 (Pax 1 Entry)
$multi1 = callApi("$base/lounge-visits/enquiries/$id2", ['status' => 'COMPLETED'], $token);
echo "Pax 1 Entry [HTTP {$multi1['code']}]: " . json_encode($multi1['data'], JSON_PRETTY_PRINT) . "\n";

// Scan 2 (Pax 2 Entry)
$multi2 = callApi("$base/lounge-visits/enquiries/$id2", ['status' => 'COMPLETED'], $token);
echo "Pax 2 Entry [HTTP {$multi2['code']}]: " . json_encode($multi2['data'], JSON_PRETTY_PRINT) . "\n";

// Scan 3 (Extra Entry - Should Fail 409)
$multi3 = callApi("$base/lounge-visits/enquiries/$id2", ['status' => 'COMPLETED'], $token);
echo "Pax 3 Extra Entry [HTTP {$multi3['code']}]: " . json_encode($multi3['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "🎉 ALL QR USAGE COUNT TESTS FINISHED!\n";
