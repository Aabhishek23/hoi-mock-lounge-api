# HOI Mock Client API Server

A fully functional **Mock Client API Server** that simulates the HOI LoungePass backend for testing the Smart QR Scanner system.

## 🔗 Live Endpoints

| Method | Endpoint | Auth Required | Description |
|--------|----------|:---:|-------------|
| `GET`  | `/` | ❌ | Server status & endpoint list |
| `POST` | `/auth/register` | ❌ | Register a new user |
| `POST` | `/auth/login` | ❌ | Login & receive Bearer JWT Token |
| `POST` | `/qr/generate` | ✅ | Generate a new Enquiry/QR ID |
| `POST` | `/lounge-visits/enquiries/{id}` | ✅ | Validate token & complete lounge visit |

---

## ⚙️ How It Works

### 1. Register
```bash
curl -X POST https://your-app.onrender.com/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Rahul","email":"rahul@test.com","password":"pass123"}'
```

### 2. Login → Get Token
```bash
curl -X POST https://your-app.onrender.com/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"rahul@test.com","password":"pass123"}'
```
**Response:** Returns `access_token` (Bearer JWT)

### 3. Generate QR Enquiry
```bash
curl -X POST https://your-app.onrender.com/qr/generate \
  -H "Authorization: Bearer <your_token>" \
  -H "Content-Type: application/json" \
  -d '{"passenger_name":"Amit Kumar","lounge_name":"Plaza Premium T3"}'
```
**Response:** Returns `enquiry_id` to encode in QR code.

### 4. Validate Token (Your QR Scanner calls this!)
```bash
curl -X POST https://your-app.onrender.com/lounge-visits/enquiries/81605 \
  -H "Authorization: Bearer <your_token>" \
  -H "Content-Type: application/json" \
  -d '{"status":"COMPLETED"}'
```
**Success Response (`HTTP 200`):** Token valid → ESP32 gate opens!  
**Failure Response (`HTTP 401`):** Token expired/invalid → Gate stays closed.

---

## 🚀 Deploy to Render.com (Free)

1. Push this repo to GitHub
2. Go to [dashboard.render.com](https://dashboard.render.com)
3. Click **New → Web Service** → Connect GitHub repo
4. Runtime: **PHP**, Start Command: `php -S 0.0.0.0:$PORT -t .`
5. Click **Create Web Service**

Your live URL will be: `https://hoi-mock-lounge-api.onrender.com`
"# hoi-mock-lounge-api" 
