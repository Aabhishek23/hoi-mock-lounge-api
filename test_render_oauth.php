<?php
require_once __DIR__ . '/b2b_client_helper.php';

echo "Testing Live Render B2B OAuth 2.0 Integration...\n\n";

try {
    $client = new HoiLoungeB2bClient('https://hoi-mock-lounge-api.onrender.com');

    // 1. Fetch OAuth 2.0 Token
    $token = $client->getValidToken();
    echo "✅ OAuth 2.0 Token Issued: " . substr($token, 0, 40) . "...\n\n";

    // 2. Generate QR
    echo "📲 Generating QR Enquiry...\n";
    $qrRes = $client->makeApiCall('/qr/generate', [
        'passenger_name' => 'Live OAuth Partner Pax',
        'lounge_name' => 'Encalm Lounge T3',
        'airport' => 'DEL - Delhi International Airport'
    ]);
    echo "Response: " . json_encode($qrRes, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($qrRes['data']['enquiry_id'])) {
        $enquiryId = $qrRes['data']['enquiry_id'];
        echo "✅ Validating Enquiry ID: $enquiryId...\n";
        $valRes = $client->makeApiCall("/lounge-visits/enquiries/$enquiryId", [
            'status' => 'COMPLETED'
        ]);
        echo "Response: " . json_encode($valRes, JSON_PRETTY_PRINT) . "\n\n";
        echo "🎉 ALL LIVE RENDER OAUTH 2.0 TESTS PASSED!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
