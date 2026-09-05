<?php
/**
 * ============================================================
 * HOI Mock Client API Server
 * ============================================================
 * Simulates the real HOI LoungePass Client Backend.
 * Endpoints:
 *   POST /auth/register          -> Register user
 *   POST /auth/login             -> Login & get JWT Token
 *   POST /qr/generate            -> Generate Enquiry/QR
 *   POST /lounge-visits/enquiries/{id} -> Validate Token & Complete Visit
 * ============================================================
 */

// ---- CORS Headers (Allow all origins for testing) ----
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-UI-Source, ngrok-skip-browser-warning');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PATCH, PUT');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---- Simple Config ----
define('JWT_SECRET', 'hoi_mock_secret_2024_##$$%%_key');
define('TOKEN_EXPIRY_SECONDS', 3600); // 1 hour
define('STORAGE_DIR', __DIR__ . '/storage');
define('USERS_FILE', STORAGE_DIR . '/users.json');
define('ENQUIRIES_FILE', STORAGE_DIR . '/enquiries.json');
define('LOGS_FILE', STORAGE_DIR . '/logs.json');

// ---- Initialize Storage Files if not exist ----
if (!file_exists(USERS_FILE)) {
    // Pre-seed a default test user
    $defaultUsers = [
        'usr_default' => [
            'id' => 'usr_default',
            'name' => 'Test User',
            'email' => 'test@hoi.in',
            'password_hash' => md5('test123'),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(USERS_FILE, json_encode($defaultUsers));
}
if (!file_exists(ENQUIRIES_FILE)) {
    // Seed with a default enquiry
    $defaultEnquiries = [
        '81605' => [
            'enquiry_id' => '81605',
            'passenger_name' => 'Demo Passenger',
            'lounge_name' => 'Plaza Premium Lounge T3',
            'airport' => 'DEL - Indira Gandhi International Airport',
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(ENQUIRIES_FILE, json_encode($defaultEnquiries));
}
if (!file_exists(LOGS_FILE)) {
    file_put_contents(LOGS_FILE, json_encode([]));
}

// ---- Helper Functions ----

function readJSON($file) {
    return json_decode(file_get_contents($file), true) ?? [];
}

function writeJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function getBody() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if ($json !== null) return $json;
    return $_POST;
}

function generateToken($userId, $email) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'sub' => $userId,
        'email' => $email,
        'iat' => time(),
        'exp' => time() + TOKEN_EXPIRY_SECONDS
    ]));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

function validateToken() {
    $headers = getallheaders();
    $authHeader = '';
    foreach ($headers as $k => $v) {
        if (strtolower($k) === 'authorization') {
            $authHeader = $v;
            break;
        }
    }

    if (empty($authHeader)) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'Authorization header missing',
            'error' => 'Unauthorized'
        ]);
    }

    if (!str_starts_with($authHeader, 'Bearer ')) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'User not authorized: jwt malformed',
            'error' => 'Unauthorized'
        ]);
    }

    $token = substr($authHeader, 7);
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'User not authorized: jwt malformed',
            'error' => 'Unauthorized'
        ]);
    }

    $payload = json_decode(base64_decode($parts[1]), true);

    if (!$payload) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'User not authorized: jwt malformed',
            'error' => 'Unauthorized'
        ]);
    }

    if ($payload['exp'] < time()) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'User not authorized: jwt expired',
            'error' => 'Unauthorized'
        ]);
    }

    return $payload;
}

function logRequest($action, $data) {
    $logs = readJSON(LOGS_FILE);
    array_unshift($logs, [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'data' => $data
    ]);
    // Keep last 100 logs only
    $logs = array_slice($logs, 0, 100);
    writeJSON(LOGS_FILE, $logs);
}

// ---- Router ----
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Handle: POST /auth/register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'auth/register') {
    $body = getBody();
    $name = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        respond(400, [
            'statusCode' => 400,
            'message' => 'Name, email and password are required',
            'error' => 'Bad Request'
        ]);
    }

    $users = readJSON(USERS_FILE);
    foreach ($users as $u) {
        if ($u['email'] === $email) {
            respond(409, [
                'statusCode' => 409,
                'message' => 'User with this email already exists',
                'error' => 'Conflict'
            ]);
        }
    }

    $userId = 'usr_' . rand(1000, 9999);
    $users[$userId] = [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'password_hash' => md5($password),
        'created_at' => date('Y-m-d H:i:s')
    ];
    writeJSON(USERS_FILE, $users);

    logRequest('REGISTER', ['user_id' => $userId, 'email' => $email]);

    respond(201, [
        'statusCode' => 201,
        'success' => true,
        'message' => 'User registered successfully',
        'data' => ['id' => $userId, 'name' => $name, 'email' => $email]
    ]);
}

// Handle: POST /auth/login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'auth/login') {
    $body = getBody();
    $email = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');

    if (empty($email) || empty($password)) {
        respond(400, [
            'statusCode' => 400,
            'message' => 'Email and password are required',
            'error' => 'Bad Request'
        ]);
    }

    $users = readJSON(USERS_FILE);
    $found = null;
    foreach ($users as $u) {
        if ($u['email'] === $email && $u['password_hash'] === md5($password)) {
            $found = $u;
            break;
        }
    }

    if (!$found) {
        respond(401, [
            'statusCode' => 401,
            'message' => 'Invalid email or password',
            'error' => 'Unauthorized'
        ]);
    }

    $token = generateToken($found['id'], $found['email']);
    logRequest('LOGIN', ['email' => $email]);

    respond(200, [
        'statusCode' => 200,
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => TOKEN_EXPIRY_SECONDS,
            'user' => ['id' => $found['id'], 'name' => $found['name'], 'email' => $found['email']]
        ]
    ]);
}

// Handle: POST /qr/generate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'qr/generate') {
    $tokenPayload = validateToken(); // Must be logged in

    $body = getBody();
    $passengerName = trim($body['passenger_name'] ?? $tokenPayload['email']);
    $loungeName = trim($body['lounge_name'] ?? 'Plaza Premium Lounge T3');
    $airport = trim($body['airport'] ?? 'DEL - Indira Gandhi International Airport');

    $enquiryId = (string)rand(10000, 99999);
    $enquiries = readJSON(ENQUIRIES_FILE);

    $enquiries[$enquiryId] = [
        'enquiry_id' => $enquiryId,
        'passenger_name' => $passengerName,
        'lounge_name' => $loungeName,
        'airport' => $airport,
        'status' => 'PENDING',
        'created_by' => $tokenPayload['sub'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    writeJSON(ENQUIRIES_FILE, $enquiries);

    logRequest('QR_GENERATE', ['enquiry_id' => $enquiryId, 'passenger' => $passengerName]);

    respond(201, [
        'statusCode' => 201,
        'success' => true,
        'message' => 'QR Code enquiry created successfully',
        'data' => [
            'enquiry_id' => $enquiryId,
            'qr_value' => $enquiryId,
            'passenger_name' => $passengerName,
            'lounge_name' => $loungeName,
            'airport' => $airport,
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

// Handle: POST /lounge-visits/enquiries/{id}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^lounge-visits/enquiries/([^/]+)$#', $path, $matches)) {
    $tokenPayload = validateToken();

    $enquiryId = $matches[1];
    $body = getBody();
    $newStatus = trim($body['status'] ?? 'COMPLETED');

    $enquiries = readJSON(ENQUIRIES_FILE);

    // Auto-create enquiry if not exists
    if (!isset($enquiries[$enquiryId])) {
        $enquiries[$enquiryId] = [
            'enquiry_id' => $enquiryId,
            'passenger_name' => 'Auto-Created Passenger',
            'lounge_name' => 'Airport Executive Lounge',
            'airport' => 'Unknown Airport',
            'status' => 'PENDING',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    $enquiries[$enquiryId]['status'] = $newStatus;
    $enquiries[$enquiryId]['completed_at'] = date('Y-m-d H:i:s');
    $enquiries[$enquiryId]['processed_by'] = $tokenPayload['sub'];
    writeJSON(ENQUIRIES_FILE, $enquiries);

    logRequest('VALIDATE_TOKEN', [
        'enquiry_id' => $enquiryId,
        'status' => $newStatus,
        'user' => $tokenPayload['email'] ?? $tokenPayload['sub']
    ]);

    respond(200, [
        'statusCode' => 200,
        'success' => true,
        'message' => 'Lounge visit enquiry processed and validated successfully!',
        'data' => $enquiries[$enquiryId]
    ]);
}

// Handle: GET /logs (Fetch activity logs)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (str_ends_with($path, 'logs') || $path === 'logs')) {
    $logs = readJSON(LOGS_FILE);
    respond(200, ['success' => true, 'logs' => $logs]);
}

// Handle: GET / -> Redirect to Dashboard for Browser requests
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && str_contains($acceptHeader, 'text/html') && (
    $path === '' || $path === 'index.php'
)) {
    header('Location: /dashboard.php');
    exit;
}

// Handle: GET / (API Info for non-browser requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
    $path === '' || $path === 'index.php' ||
    str_ends_with($path, 'mock_lounge_server') ||
    str_ends_with($path, 'mock_lounge_server/index.php')
)) {
    $logs = readJSON(LOGS_FILE);
    respond(200, [
        'name' => 'HOI Mock Client API Server',
        'version' => '1.0.0',
        'status' => 'RUNNING',
        'dashboard' => '/dashboard.php',
        'default_login' => ['email' => 'test@hoi.in', 'password' => 'test123'],
        'endpoints' => [
            'POST /auth/register' => 'Register new user',
            'POST /auth/login' => 'Login and get Bearer JWT Token',
            'POST /qr/generate' => 'Generate new QR Enquiry (requires token)',
            'POST /lounge-visits/enquiries/{id}' => 'Validate token & complete lounge visit'
        ],
        'server_time' => date('Y-m-d H:i:s')
    ]);
}

// 404 Fallback
respond(404, [
    'statusCode' => 404,
    'message' => 'Endpoint not found. Check /endpoints for available routes.',
    'error' => 'Not Found'
]);
