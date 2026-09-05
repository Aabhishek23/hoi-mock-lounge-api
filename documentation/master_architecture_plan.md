# HOI Multi-Provider Lounge Access System
## Master System Architecture & Integration Plan

---

## 1. Executive Summary

This document provides the complete, authoritative specification and implementation blueprint for the **HOI Multi-Provider Lounge Access Control System**.

The system bridges IoT hardware scanners (**ESP32 Turnstile Controllers**) with multiple independent 3rd-party provider backends (e.g., HOI LoungePass, DreamFolks, Encalm) via a centralized, high-performance **Middleware Proxy Relay Server**.

### Key Highlights:
- **Hardware-agnostic 3rd Party Routing**: Seamless integration of 8-character alphanumeric QR tokens without modifying scanner hardware code.
- **Dual-Layer Security Model**: Hardware-level HMAC-SHA256 device authentication combined with Provider-level Bearer JWT Validation.
- **Granular Access Control**: Built-in support for QR Expiry Limits, Usage Count Restrictions (Single-use vs Multi-use), and real-time audit logging.
- **Zero-Downtime Provider Migration**: Database-driven device mapping that allows remote provider switching without re-flashing ESP32 firmware.

---

## 2. High-Level System Architecture

The architecture consists of three decoupled operational tiers:

1. **Tier 1: ESP32 Hardware Edge Scanners (Gate Controllers)**: Scans 8-character QR tokens at gate turnstiles and transmits cryptographically signed payloads to the Middleware Server.
2. **Tier 2: Main Middleware Relay Server (The Proxy Router)**: Verifies hardware integrity via HMAC, queries device-to-provider mappings from the relational database, and proxies HTTP requests to the target provider.
3. **Tier 3: 3rd Party Provider Servers (HOI / Partners)**: Validates QR tokens against business rules (Expiry, Max Usage, Registration Status) and returns approval or rejection codes.

```
[ ESP32 Gate Scanner ]
         │
         ├── 1. Scans 8-char QR ("Z5NUFKDY")
         ├── 2. Signs payload with HMAC-SHA256 (deviceId|timestamp|token)
         └── 3. POST request to Middleware Server (/api/esp32/scan)
         │
[ YOUR MAIN MIDDLEWARE SERVER ]  (The Proxy Router)
         │
         ├── 4. Authenticate ESP32 HMAC Signature (Hardware Auth)
         ├── 5. Query DB `devices` table for `device_id`
         │      Returns: Provider="HOI", Target_URL="https://hoi-api.com"
         ├── 6. Forward / Relay QR Token to Provider API Endpoint:
         │      POST https://hoi-api.com/lounge-visits/enquiries/Z5NUFKDY
         │      Header: Authorization: Bearer <HOI_BEARER_TOKEN>
         v
[ HOI 3rd PARTY SERVER ]
         │
         ├── 7. Validates Bearer Token, Expiry & Max Usage Count
         └── 8. Updates status to "COMPLETED" & Returns HTTP Response
         v
[ YOUR MAIN MIDDLEWARE SERVER ]
         │
         ├── 9. Log transaction in DB `scan_logs`
         └── 10. Transmit final action to ESP32 (GATE_OPEN / GATE_DENIED)
         v
[ ESP32 Gate Scanner ]
         └── 11. Triggers Relay (Turnstile Opens! Green LED Bleeps!)
```

---

## 3. Component Specifications

### Component A: ESP32 Hardware Edge Scanner
The ESP32 microcontroller is connected to an 8-character QR optical scanner module and a turnstile relay switch.

#### Request Payload Format:
```json
{
    "deviceId": "ESP_DEL_T3_01",
    "timestamp": 1788624000,
    "scannedToken": "Z5NUFKDY",
    "signature": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
}
```

#### Signature Formula:
$$\text{Payload} = \text{deviceId} \parallel \text{"|"} \parallel \text{timestamp} \parallel \text{"|"} \parallel \text{scannedToken}$$
$$\text{Signature} = \text{HMAC-SHA256}(\text{Payload}, \text{secretKey})$$

---

### Component B: Main Middleware Server (Proxy Router)
The Middleware Server is responsible for zero-latency routing and auditing:
1. **Hardware Integrity**: Re-computes the HMAC-SHA256 signature to guarantee the request originated from an authorized ESP32 hardware device.
2. **Device Lookup**: Queries the `devices` database table using the `deviceId` to retrieve the target Provider's API URL and pre-configured Bearer Token.
3. **HTTP Proxying**: Dispatches an asynchronous HTTP POST request to the Provider's endpoint.
4. **Gate Command Translation**: Converts Provider HTTP status codes into binary hardware commands (`GATE_OPEN` vs `GATE_DENIED`).

---

### Component C: 3rd Party Provider Server (HOI Mock API)
The 3rd Party Provider validates the business logic:
- **JWT Bearer Authorization**: Validates caller token.
- **Record Existence**: Ensures the 8-character Enquiry ID exists in storage (Returns `HTTP 404 Not Found` if invalid).
- **Token Expiry**: Checks `expires_at` timestamp (Returns `HTTP 410 Gone` if expired).
- **Usage Count Control**: Increments `used_count` against `max_usage` limit (Returns `HTTP 409 Conflict` if limit exceeded).

---

## 4. Database Schema Design (DDL)

### Table 1: `devices` (Hardware-to-Provider Mapping)
```sql
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL UNIQUE,
    device_name VARCHAR(100) NOT NULL,
    location_name VARCHAR(150) NOT NULL,
    provider_name VARCHAR(50) NOT NULL,
    provider_api_url VARCHAR(255) NOT NULL,
    provider_bearer_token TEXT NOT NULL,
    secret_key VARCHAR(128) NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table 2: `scan_logs` (Audit History & Analytics)
```sql
CREATE TABLE scan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    scanned_token VARCHAR(20) NOT NULL,
    provider_name VARCHAR(50) NOT NULL,
    http_code INT NOT NULL,
    gate_action ENUM('GATE_OPEN', 'GATE_DENIED') NOT NULL,
    response_msg TEXT,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device (device_id),
    INDEX idx_scanned_at (scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Complete Gate Action Matrix

| Provider Status | Error Code | Middleware Action | ESP32 Hardware Action |
|---|---|---|---|
| **HTTP 200 OK** | `COMPLETED` | Logs success; Returns `GATE_OPEN` | 🚪 Turnstile Opens + Green LED + Chime |
| **HTTP 401** | `unauthorized` | Logs auth failure; Returns `GATE_DENIED` | 🔴 Red LED + Display "Auth Error" |
| **HTTP 404** | `enquiry_not_found` | Logs invalid ID attempt; Returns `GATE_DENIED` | 🔴 Red LED + Display "Invalid QR ID" |
| **HTTP 409** | `limit_exceeded` | Logs limit exceeded; Returns `GATE_DENIED` | 🔴 Red LED + Display "Already Used" |
| **HTTP 410** | `qr_token_expired` | Logs QR expiry; Returns `GATE_DENIED` | 🔴 Red LED + Display "QR Expired" |
| **HTTP 500 / 504** | `server_error` | Logs timeout; Returns `GATE_DENIED` | 🔴 Red LED + Display "Network Timeout" |

---

## 6. API Specifications

### 1. Obtain OAuth 2.0 Access Token
- **Method**: `POST`
- **Endpoint**: `/oauth/token`
- **Content-Type**: `application/json`

```json
// Request Body
{
    "grant_type": "client_credentials",
    "client_id": "client_hoi_prod",
    "client_secret": "secret_hoi_lounge_2024_key"
}

// Response Body (HTTP 200 OK)
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 2592000,
    "scope": "lounge_read_write"
}
```

### 2. Validate Lounge Visit / Enquiry ID
- **Method**: `POST`
- **Endpoint**: `/lounge-visits/enquiries/{ENQUIRY_ID}`
- **Header**: `Authorization: Bearer <ACCESS_TOKEN>`

```json
// Success Response (HTTP 200 OK)
{
    "statusCode": 200,
    "success": true,
    "message": "Lounge visit processed & completed successfully!",
    "data": {
        "enquiry_id": "Z5NUFKDY",
        "passenger_name": "Rahul Sharma",
        "status": "COMPLETED",
        "max_usage": 1,
        "used_count": 1,
        "remaining_uses": 0,
        "expires_at": "2026-10-05 23:59:59"
    }
}
```

---

## 7. Security Audit & Compliance Standards
1. **RFC 6749 Compliance**: Implements standard OAuth 2.0 Client Credentials Grant for B2B API integrations.
2. **FIPS 198-1 HMAC Compliance**: Uses SHA-256 keyed-hash algorithm to prevent hardware spoofing and replay attacks.
3. **Timezone Standardization**: All logs, timestamps (`created_at`, `expires_at`), and audit trails are synchronized to **Indian Standard Time (IST - Asia/Kolkata, UTC+5:30)**.
