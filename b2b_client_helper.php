<?php
/**
 * HOI LoungePass B2B API Client Helper
 * ============================================================
 * Industry Standard OAuth 2.0 Client Credentials Handler
 * Automatic Token Caching, Proactive Refresh & 401 Retry Logic
 * ============================================================
 */

class HoiLoungeB2bClient {
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $tokenCacheFile;

    public function __construct(
        string $baseUrl = 'https://hoi-mock-lounge-api.onrender.com',
        string $clientId = 'client_hoi_prod',
        string $clientSecret = 'secret_hoi_lounge_2024_key'
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenCacheFile = __DIR__ . '/storage/b2b_token_cache.json';
    }

    /**
     * Get a valid Access Token (From Cache or Auto-Fetched)
     */
    public function getValidToken(): string {
        // 1. Check Cache
        if (file_exists($this->tokenCacheFile)) {
            $cached = json_decode(file_get_contents($this->tokenCacheFile), true);
            // Proactive Refresh: Check if token is valid for at least 5 more minutes (300 secs)
            if ($cached && isset($cached['access_token'], $cached['expires_at']) && time() < ($cached['expires_at'] - 300)) {
                return $cached['access_token'];
            }
        }

        // 2. Proactive Auto-Refresh via OAuth 2.0
        return $this->fetchNewToken();
    }

    /**
     * Fetch New Token from Server via OAuth 2.0 Client Credentials
     */
    private function fetchNewToken(): string {
        $ch = curl_init($this->baseUrl . '/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ])
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['access_token'])) {
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 3600;

            // Save to Cache
            if (!is_dir(dirname($this->tokenCacheFile))) {
                @mkdir(dirname($this->tokenCacheFile), 0775, true);
            }
            file_put_contents($this->tokenCacheFile, json_encode([
                'access_token' => $token,
                'expires_at' => time() + $expiresIn
            ], JSON_PRETTY_PRINT));

            return $token;
        }

        throw new Exception("OAuth 2.0 Auth Failed [HTTP $httpCode]: " . ($data['error_description'] ?? 'Unknown Error'));
    }

    /**
     * Make Authenticated API Request with Automatic 401 Retry
     */
    public function makeApiCall(string $endpoint, array $payload = []): array {
        $token = $this->getValidToken();

        $ch = curl_init($this->baseUrl . '/' . ltrim($endpoint, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer $token"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Fallback: If 401 Unauthorized occurs, force token refresh and retry once!
        if ($httpCode === 401) {
            echo "⚠️ [401 Unauthorized] Token expired unexpectedly. Forcing auto-refresh...\n";
            $newToken = $this->fetchNewToken();

            $ch = curl_init($this->baseUrl . '/' . ltrim($endpoint, '/'));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "Authorization: Bearer $newToken"
                ],
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        return json_decode($response, true) ?? [];
    }
}

// ============================================================
// Demonstration / Test Usage
// ============================================================
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "🚀 B2B OAuth 2.0 Auto-Token Refresh Client Helper Test\n\n";

    try {
        $client = new HoiLoungeB2bClient('http://localhost/mock_lounge_server');
        
        // 1. Get Token (Cached or Auto-Renewed)
        $token = $client->getValidToken();
        echo "✅ Valid Token Obtained: " . substr($token, 0, 35) . "...\n\n";

        // 2. Generate QR
        echo "📲 Generating QR Code...\n";
        $qrResult = $client->makeApiCall('/qr/generate', [
            'passenger_name' => 'B2B Partner Pax',
            'lounge_name' => 'Encalm Lounge T3',
            'airport' => 'DEL - Delhi'
        ]);
        echo "Response: " . json_encode($qrResult, JSON_PRETTY_PRINT) . "\n\n";

        if (isset($qrResult['data']['enquiry_id'])) {
            $enquiryId = $qrResult['data']['enquiry_id'];
            echo "✅ Validating Enquiry ID: $enquiryId...\n";
            $valResult = $client->makeApiCall("/lounge-visits/enquiries/$enquiryId", [
                'status' => 'COMPLETED'
            ]);
            echo "Response: " . json_encode($valResult, JSON_PRETTY_PRINT) . "\n";
        }

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
