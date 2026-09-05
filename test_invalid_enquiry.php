<?php
$base = 'http://localhost/mock_lounge_server';

// Login
$ch = curl_init($base . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['email' => 'test@hoi.in', 'password' => 'test123'])
]);
$res = json_decode(curl_exec($ch), true);
$token = $res['data']['access_token'];

// Test Invalid Enquiry ID
$ch2 = curl_init($base . '/lounge-visits/enquiries/hkhzkhkh');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_POSTFIELDS => json_encode(['status' => 'COMPLETED'])
]);
$res2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP Code: $code2\n";
echo "Response: $res2\n";
