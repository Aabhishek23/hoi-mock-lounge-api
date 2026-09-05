<?php
// Dynamically detect the base URL for both localhost subdir and Render/cloud root
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$scheme = $isHttps ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOI Mock Lounge API — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        :root {
            --bg: #080c14;
            --card: rgba(16, 22, 36, 0.85);
            --border: rgba(56, 70, 100, 0.6);
            --purple: #7c3aed;
            --purple-glow: rgba(124, 58, 237, 0.3);
            --green: #10b981;
            --blue: #3b82f6;
            --red: #ef4444;
            --gold: #f59e0b;
            --text: #e2e8f0;
            --muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            background-image:
                radial-gradient(at 0% 0%, rgba(124, 58, 237, 0.15) 0px, transparent 55%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 55%);
            color: var(--text);
            min-height: 100vh;
        }

        /* ─── Top Nav ─── */
        nav {
            padding: 16px 32px;
            border-bottom: 1px solid var(--border);
            background: rgba(8, 12, 20, 0.9);
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a78bfa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--purple), var(--green));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .status-live {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--green);
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
        }

        .dot-live {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 8px var(--green);
            animation: blink 1.4s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; } 50% { opacity: 0.3; }
        }

        /* ─── Layout ─── */
        .wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 24px;
            display: grid;
            gap: 24px;
        }

        .grid-3 { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .grid-3 { grid-template-columns: 1fr; } }

        /* ─── Card ─── */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            backdrop-filter: blur(12px);
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #94a3b8;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ─── Tabs ─── */
        .tabs {
            display: flex;
            background: rgba(0,0,0,0.4);
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 18px;
        }

        .tab-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 7px;
            background: transparent;
            color: var(--muted);
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: var(--purple);
            color: #fff;
            box-shadow: 0 2px 10px var(--purple-glow);
        }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ─── Form ─── */
        .form-group { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 0.82rem;
            color: var(--muted);
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px 13px;
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            transition: border 0.2s;
        }

        input:focus { outline: none; border-color: var(--purple); }

        .btn {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.btn-green { background: linear-gradient(135deg, #059669, var(--green)); }

        /* ─── Alert ─── */
        .alert {
            padding: 10px 14px;
            border-radius: 9px;
            font-size: 0.85rem;
            margin-bottom: 14px;
            display: none;
        }

        .alert.success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: var(--green); }
        .alert.error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: var(--red); }
        .alert.info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: var(--blue); }

        /* ─── Token Box ─── */
        .token-box {
            background: rgba(0,0,0,0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: #a78bfa;
            word-break: break-all;
            max-height: 80px;
            overflow-y: auto;
            margin-bottom: 10px;
            display: none;
        }

        .copy-btn {
            width: 100%;
            padding: 7px;
            background: transparent;
            border: 1px solid var(--purple);
            color: var(--purple);
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s;
            display: none;
        }

        .copy-btn:hover { background: var(--purple); color: #fff; }

        /* ─── QR Box ─── */
        .qr-display {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
        }

        #qrOutput { width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; }
        #qrOutput svg, #qrOutput img { width: 100%; height: 100%; }

        .qr-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #0f172a;
            font-weight: 600;
            text-align: center;
        }

        .enquiry-id-display {
            background: rgba(124,58,237,0.1);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 8px;
            padding: 8px 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #a78bfa;
            text-align: center;
            display: none;
            margin-top: 10px;
        }

        /* ─── Log List ─── */
        .log-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 380px;
            overflow-y: auto;
        }

        .log-item {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 13px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .log-action {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--blue);
        }

        .log-detail {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.73rem;
            color: var(--muted);
        }

        .log-time {
            font-size: 0.7rem;
            color: #374151;
        }

        .empty-state {
            text-align: center;
            color: var(--muted);
            font-size: 0.85rem;
            padding: 30px 10px;
        }

        /* ─── Endpoint Docs ─── */
        .endpoint-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(56,70,100,0.3);
            font-size: 0.83rem;
        }

        .method-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 5px;
            min-width: 50px;
            text-align: center;
        }

        .method-post { background: rgba(16,185,129,0.2); color: var(--green); }
        .method-get { background: rgba(59,130,246,0.2); color: var(--blue); }

        .endpoint-path {
            font-family: 'JetBrains Mono', monospace;
            color: #94a3b8;
            font-size: 0.78rem;
        }

        .badge-auth {
            margin-left: auto;
            font-size: 0.65rem;
            padding: 2px 7px;
            border-radius: 20px;
            background: rgba(245,158,11,0.15);
            color: var(--gold);
            border: 1px solid rgba(245,158,11,0.3);
        }

        .refresh-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            border-radius: 7px;
            padding: 4px 10px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .refresh-btn:hover { border-color: var(--blue); color: var(--blue); }

        /* Spinner */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.7s linear infinite;
            display: inline-block;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<nav>
    <div class="brand">
        <div class="brand-icon">🏨</div>
        HOI Mock Lounge API
    </div>
    <div class="status-live">
        <div class="dot-live"></div>
        SERVER LIVE
    </div>
</nav>

<div class="wrapper">

    <!-- ── Main 3 Column Grid ── -->
    <div class="grid-3">

        <!-- ─── Column 1: Auth ─── -->
        <div class="card">
            <div class="card-title">🔐 Authentication</div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('login')">User Login</button>
                <button class="tab-btn" onclick="switchTab('oauth')">OAuth 2.0 (B2B)</button>
                <button class="tab-btn" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login -->
            <div class="tab-pane active" id="tab-login">
                <div class="alert" id="loginAlert"></div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="loginEmail" value="test@hoi.in" placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPass" value="test123" placeholder="••••••••">
                </div>
                <button class="btn" onclick="doLogin()">Login & Get Token</button>
            </div>

            <!-- OAuth 2.0 (B2B) -->
            <div class="tab-pane" id="tab-oauth">
                <div class="alert" id="oauthAlert"></div>
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" id="oauthClientId" value="client_hoi_prod" placeholder="client_hoi_prod">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" id="oauthClientSecret" value="secret_hoi_lounge_2024_key" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Grant Type</label>
                    <input type="text" id="oauthGrantType" value="client_credentials" readonly style="opacity:0.7;">
                </div>
                <button class="btn btn-purple" onclick="doOAuthLogin()">Get B2B OAuth 2.0 Token</button>
            </div>

            <!-- Register -->
            <div class="tab-pane" id="tab-register">
                <div class="alert" id="regAlert"></div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="regName" placeholder="Rahul Sharma">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="regEmail" placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="regPass" placeholder="Min 6 characters">
                </div>
                <button class="btn" onclick="doRegister()">Create Account</button>
            </div>

            <!-- Token Display -->
            <div style="margin-top: 18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                    <label style="margin:0;">🔑 Bearer Token (Valid 1 Hour)</label>
                    <span id="autoRefreshBadge" style="font-size:0.75rem; color:var(--green); display:none;">⚡ Auto-Refresh Active</span>
                </div>
                <div class="token-box" id="tokenBox">—</div>
                <div style="display:flex; gap: 8px; margin-top: 8px;">
                    <button class="copy-btn" id="copyBtn" onclick="copyToken()">📋 Copy Token</button>
                    <button class="copy-btn" id="refreshBtn" onclick="doRefreshToken()" style="display:none; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border-color: rgba(59, 130, 246, 0.4);">🔄 Refresh Token Now</button>
                </div>
            </div>
        </div>

        <!-- ─── Column 2: QR Generator ─── -->
        <div class="card">
            <div class="card-title">📱 QR Code Generator</div>

            <div class="alert info" id="qrAuthAlert" style="display:block;">
                ⚠️ Pehle Login karein, Token milne ke baad QR generate hoga.
            </div>

            <div class="form-group">
                <label>Passenger Name</label>
                <input type="text" id="qrPassenger" placeholder="Amit Kumar" value="Amit Kumar">
            </div>
            <div class="form-group">
                <label>Lounge Name</label>
                <input type="text" id="qrLounge" placeholder="Plaza Premium T3" value="Plaza Premium Lounge T3">
            </div>
            <div class="form-group">
                <label>Airport</label>
                <input type="text" id="qrAirport" placeholder="DEL - IGI Airport" value="DEL - Indira Gandhi Int. Airport">
            </div>
            <div class="form-group">
                <label>⏰ QR Token Expiry Time</label>
                <select id="qrExpirySelect" style="width:100%; padding:10px; border-radius:8px; background:rgba(15,23,42,0.8); color:var(--text); border:1px solid var(--border);">
                    <option value="3600" selected>1 Hour (Default)</option>
                    <option value="10">10 Seconds (Instant Expiry Test)</option>
                    <option value="60">1 Minute</option>
                    <option value="300">5 Minutes</option>
                    <option value="86400">24 Hours</option>
                    <option value="2592000">30 Days</option>
                </select>
            </div>
            <button class="btn btn-green" id="qrBtn" onclick="generateQR()" disabled>Generate QR Code</button>

            <div class="qr-display" id="qrDisplay" style="display:none;">
                <div id="qrOutput"></div>
                <div class="qr-label" id="qrLabel">—</div>
            </div>

            <div class="enquiry-id-display" id="enquiryIdBox">
                Enquiry ID: <strong id="enquiryIdVal">—</strong>
                <div style="font-size:0.75rem; color:var(--gold); margin-top:4px;" id="qrExpiryTimeVal">Expires At: —</div>
            </div>
        </div>

        <!-- ─── Column 3: Error & Validation Tester ─── -->
        <div class="card">
            <div class="card-title">🧪 Token & Error Tester</div>
            <div class="form-group">
                <label>Enquiry ID / QR Value</label>
                <input type="text" id="testEnquiryId" placeholder="e.g. Z5NUFKDY">
            </div>
            <div class="form-group">
                <label>Bearer Token to Test</label>
                <select id="testTokenType" onchange="toggleCustomTokenInput()" style="width:100%; padding:8px; border-radius:8px; background:rgba(15,23,42,0.8); color:var(--text); border:1px solid var(--border); margin-bottom:6px;">
                    <option value="valid">✅ Use Valid Current Token</option>
                    <option value="invalid">❌ Use Galat / Tampered Token</option>
                    <option value="expired">⏰ Use Expired Token</option>
                    <option value="missing">🚫 Use Missing Token (No Header)</option>
                    <option value="custom">✏️ Enter Custom Token</option>
                </select>
                <input type="text" id="customTestToken" placeholder="Paste custom token here..." style="display:none;">
            </div>
            
            <div style="display:flex; flex-direction:column; gap:8px;">
                <button class="btn btn-blue" onclick="testValidateVisit()">Validate Visit (POST)</button>
            </div>

            <div id="testResultBox" style="display:none; margin-top:12px; padding:12px; border-radius:8px; font-size:0.8rem; font-family:'JetBrains Mono', monospace; white-space:pre-wrap; word-break:break-all;"></div>
        </div>

        <!-- ─── Column 4: Logs ─── -->
        <div class="card">
            <div class="card-title" style="justify-content: space-between;">
                📋 Activity Logs
                <button class="refresh-btn" onclick="fetchLogs()">↻ Refresh</button>
            </div>
            <div class="log-list" id="logList">
                <div class="empty-state">No activity yet. Login aur QR generate karein!</div>
            </div>
        </div>
    </div>

    <!-- ── API Endpoints Reference ── -->
    <div class="card">
        <div class="card-title">⚡ API Endpoints Reference</div>
        <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="endpoint-path">/</span>
            <span style="font-size:0.82rem; color: var(--muted);">Server status & health check</span>
        </div>
        <div class="endpoint-row">
            <span class="method-badge method-post">POST</span>
            <span class="endpoint-path">/auth/register</span>
            <span style="font-size:0.82rem; color: var(--muted);">Register new user</span>
        </div>
        <div class="endpoint-row">
            <span class="method-badge method-post">POST</span>
            <span class="endpoint-path">/auth/login</span>
            <span style="font-size:0.82rem; color: var(--muted);">User Login & get Bearer JWT Token</span>
        </div>
        <div class="endpoint-row">
            <span class="method-badge method-post" style="background: rgba(124, 58, 237, 0.2); color: #a78bfa;">POST</span>
            <span class="endpoint-path">/oauth/token</span>
            <span style="font-size:0.82rem; color: var(--muted);">OAuth 2.0 Client Credentials Grant (B2B)</span>
        </div>
        <div class="endpoint-row">
            <span class="method-badge method-post" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa;">POST</span>
            <span class="endpoint-path">/auth/refresh</span>
            <span style="font-size:0.82rem; color: var(--muted);">Proactive Token Auto-Refresh / Extension</span>
            <span class="badge-auth">🔒 Token Required</span>
        </div>
        <div class="endpoint-row">
            <span class="method-badge method-post">POST</span>
            <span class="endpoint-path">/qr/generate</span>
            <span style="font-size:0.82rem; color: var(--muted);">Generate QR Enquiry ID</span>
            <span class="badge-auth">🔒 Token Required</span>
        </div>
        <div class="endpoint-row" style="border:none;">
            <span class="method-badge method-post">POST</span>
            <span class="endpoint-path">/lounge-visits/enquiries/{id}</span>
            <span style="font-size:0.82rem; color: var(--muted);">Validate token + complete visit</span>
            <span class="badge-auth">🔒 Token Required</span>
        </div>
    </div>

</div>

<script>
    const SCRIPT_DIR = '<?php echo $scriptDir; ?>';
    const BASE = window.location.origin + SCRIPT_DIR;
    let bearerToken = '';

    let refreshTimer = null;

    // ─── Tab Switch ───
    function switchTab(tab) {
        const tabs = ['login', 'oauth', 'register'];
        document.querySelectorAll('.tab-btn').forEach((b, i) => {
            b.classList.toggle('active', tabs[i] === tab);
        });
        tabs.forEach(t => {
            const el = document.getElementById('tab-' + t);
            if (el) el.classList.toggle('active', t === tab);
        });
    }

    // ─── Show Alert ───
    function showAlert(id, msg, type) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.className = `alert ${type}`;
        el.style.display = 'block';
        setTimeout(() => el.style.display = 'none', 4000);
    }

    // ─── Token Setup Helper ───
    function onTokenReceived(token, sourceMsg) {
        bearerToken = token;

        // Display Token
        const tokenBox = document.getElementById('tokenBox');
        tokenBox.style.display = 'block';
        tokenBox.textContent = bearerToken;
        document.getElementById('copyBtn').style.display = 'block';
        document.getElementById('refreshBtn').style.display = 'block';
        document.getElementById('autoRefreshBadge').style.display = 'inline-block';

        // Enable QR
        document.getElementById('qrBtn').disabled = false;
        document.getElementById('qrAuthAlert').style.display = 'none';

        // Start Proactive Auto-Refresh (55 minutes = 3300 seconds)
        startProactiveAutoRefresh();

        fetchLogs();
    }

    // ─── Proactive Auto-Refresh Timer (Every 55 mins) ───
    function startProactiveAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        // Auto refresh every 55 minutes (3300000 ms) before 1h expiry
        refreshTimer = setInterval(() => {
            console.log("⚡ [Auto-Refresh] Proactively renewing token before expiry...");
            doRefreshToken(true);
        }, 55 * 60 * 1000);
    }

    // ─── Manual or Auto Refresh Token ───
    async function doRefreshToken(isAuto = false) {
        if (!bearerToken) return;
        try {
            const res = await fetch(`${BASE}/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${bearerToken}`
                }
            });
            const data = await res.json();
            if (data.success && data.data && data.data.access_token) {
                bearerToken = data.data.access_token;
                document.getElementById('tokenBox').textContent = bearerToken;
                document.getElementById('autoRefreshBadge').textContent = '⚡ Token Refreshed at ' + new Date().toLocaleTimeString();
                if (!isAuto) showAlert('loginAlert', '🔄 Token refreshed successfully!', 'success');
            } else {
                if (!isAuto) showAlert('loginAlert', 'Refresh failed. Please login again.', 'error');
            }
        } catch (e) {
            console.error("Refresh error:", e);
        }
    }

    // ─── B2B OAuth 2.0 Login ───
    async function doOAuthLogin() {
        const clientId = document.getElementById('oauthClientId').value.trim();
        const clientSecret = document.getElementById('oauthClientSecret').value.trim();
        const grantType = document.getElementById('oauthGrantType').value.trim();

        if (!clientId || !clientSecret) return showAlert('oauthAlert', 'Client ID aur Client Secret daalen!', 'error');

        try {
            const res = await fetch(`${BASE}/oauth/token`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    grant_type: grantType,
                    client_id: clientId,
                    client_secret: clientSecret
                })
            });
            const data = await res.json();
            if (data.access_token) {
                onTokenReceived(data.access_token);
                showAlert('oauthAlert', `✅ B2B OAuth 2.0 Token Issued!`, 'success');
            } else {
                showAlert('oauthAlert', data.error_description || 'OAuth Failed!', 'error');
            }
        } catch (e) {
            showAlert('oauthAlert', 'Network Error: ' + e.message, 'error');
        }
    }

    // ─── Register ───
    async function doRegister() {
        const name = document.getElementById('regName').value.trim();
        const email = document.getElementById('regEmail').value.trim();
        const pass = document.getElementById('regPass').value.trim();

        if (!name || !email || !pass) return showAlert('regAlert', 'Sabhi fields fill karein!', 'error');

        try {
            const res = await fetch(`${BASE}/auth/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password: pass })
            });
            const data = await res.json();
            if (data.success) {
                showAlert('regAlert', '✅ Account created! Ab Login karein.', 'success');
                document.getElementById('loginEmail').value = email;
                document.getElementById('loginPass').value = pass;
                switchTab('login');
            } else {
                showAlert('regAlert', data.message || 'Error!', 'error');
            }
        } catch (e) {
            showAlert('regAlert', 'Network Error: ' + e.message, 'error');
        }
    }

    // ─── Login ───
    async function doLogin() {
        const email = document.getElementById('loginEmail').value.trim();
        const pass = document.getElementById('loginPass').value.trim();

        if (!email || !pass) return showAlert('loginAlert', 'Email aur password daalen!', 'error');

        try {
            const res = await fetch(`${BASE}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password: pass })
            });
            const data = await res.json();
            if (data.success) {
                onTokenReceived(data.data.access_token);
                showAlert('loginAlert', `✅ Login successful! Token ready.`, 'success');
            } else {
                showAlert('loginAlert', data.message || 'Login failed!', 'error');
            }
        } catch (e) {
            showAlert('loginAlert', 'Network Error: ' + e.message, 'error');
        }
    }

    // ─── Copy Token ───
    function copyToken() {
        navigator.clipboard.writeText(bearerToken).then(() => {
            document.getElementById('copyBtn').textContent = '✅ Copied!';
            setTimeout(() => document.getElementById('copyBtn').textContent = '📋 Copy Token', 2000);
        });
    }

    function toggleCustomTokenInput() {
        const type = document.getElementById('testTokenType').value;
        document.getElementById('customTestToken').style.display = type === 'custom' ? 'block' : 'none';
    }

    // ─── Generate QR ───
    async function generateQR() {
        if (!bearerToken) return;

        const passenger = document.getElementById('qrPassenger').value.trim();
        const lounge = document.getElementById('qrLounge').value.trim();
        const airport = document.getElementById('qrAirport').value.trim();
        const validitySecs = parseInt(document.getElementById('qrExpirySelect').value || '3600');

        const qrBtn = document.getElementById('qrBtn');
        qrBtn.disabled = true;
        qrBtn.innerHTML = '<span class="spinner"></span>';

        try {
            const res = await fetch(`${BASE}/qr/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${bearerToken}`
                },
                body: JSON.stringify({
                    passenger_name: passenger,
                    lounge_name: lounge,
                    airport,
                    validity_seconds: validitySecs
                })
            });
            const data = await res.json();

            if (data.success) {
                const enquiryId = data.data.enquiry_id;

                // Generate QR SVG
                const qr = qrcode(0, 'M');
                qr.addData(enquiryId);
                qr.make();
                const output = document.getElementById('qrOutput');
                output.innerHTML = qr.createSvgTag(5, 0);

                document.getElementById('qrDisplay').style.display = 'flex';
                document.getElementById('qrLabel').textContent = passenger + ' | ' + lounge;
                document.getElementById('enquiryIdBox').style.display = 'block';
                document.getElementById('enquiryIdVal').textContent = enquiryId;
                document.getElementById('qrExpiryTimeVal').textContent = 'Expires At: ' + (data.data.expires_at || '—');

                // Auto-populate Tester input
                document.getElementById('testEnquiryId').value = enquiryId;

                fetchLogs();
            } else {
                alert('QR Generate failed: ' + data.message);
            }
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            qrBtn.disabled = false;
            qrBtn.innerHTML = 'Generate QR Code';
        }
    }

    // ─── Error & Validation Tester ───
    async function testValidateVisit() {
        const enquiryId = document.getElementById('testEnquiryId').value.trim();
        if (!enquiryId) return alert('Enquiry ID daalen!');

        const tokenType = document.getElementById('testTokenType').value;
        let headers = { 'Content-Type': 'application/json' };

        if (tokenType === 'valid') {
            if (!bearerToken) return alert('Pehle Login karke Valid Token lein!');
            headers['Authorization'] = `Bearer ${bearerToken}`;
        } else if (tokenType === 'invalid') {
            headers['Authorization'] = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.GALAT_INVALID_TAMPERED_TOKEN_SIGNATURE';
        } else if (tokenType === 'expired') {
            headers['Authorization'] = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c3JfZGVmYXVsdCIsImVtYWlsIjoidGVzdEBob2kuaW4iLCJpYXQiOjE1MDAwMDAwMDAsImV4cCI6MTUwMDAwMDAwMH0=.SIG';
        } else if (tokenType === 'custom') {
            const customToken = document.getElementById('customTestToken').value.trim();
            if (!customToken) return alert('Custom Token input field me token daalen!');
            headers['Authorization'] = customToken.startsWith('Bearer ') ? customToken : `Bearer ${customToken}`;
        }

        const box = document.getElementById('testResultBox');
        box.style.display = 'block';
        box.style.background = '#0f172a';
        box.style.border = '1px solid var(--border)';
        box.style.color = '#e2e8f0';
        box.textContent = '⏳ Sending POST request to validate...';

        try {
            const res = await fetch(`${BASE}/lounge-visits/enquiries/${enquiryId}`, {
                method: 'POST',
                headers,
                body: JSON.stringify({ status: 'COMPLETED' })
            });
            const data = await res.json();
            
            if (res.status === 200) {
                box.style.background = 'rgba(16, 185, 129, 0.15)';
                box.style.border = '1px solid #10b981';
                box.style.color = '#34d399';
            } else {
                box.style.background = 'rgba(239, 68, 68, 0.15)';
                box.style.border = '1px solid #ef4444';
                box.style.color = '#fca5a5';
            }

            box.textContent = `HTTP Code: ${res.status} (${res.statusText || ''})\nResponse:\n` + JSON.stringify(data, null, 2);
            fetchLogs();
        } catch (e) {
            box.style.background = 'rgba(239, 68, 68, 0.15)';
            box.style.color = '#fca5a5';
            box.textContent = '❌ Network Error: ' + e.message;
        }
    }

    // ─── Fetch Logs ───
    async function fetchLogs() {
        try {
            const res = await fetch(`${BASE}/logs`);
            const data = await res.json();
            const list = document.getElementById('logList');

            if (!data.logs || data.logs.length === 0) {
                list.innerHTML = '<div class="empty-state">No activity yet.</div>';
                return;
            }

            list.innerHTML = data.logs.map(log => `
                <div class="log-item">
                    <span class="log-action">📌 ${log.action}</span>
                    <span class="log-detail">${JSON.stringify(log.data)}</span>
                    <span class="log-time">${log.timestamp}</span>
                </div>
            `).join('');
        } catch (e) {}
    }

    // Load logs on start
    fetchLogs();
</script>
</body>
</html>
