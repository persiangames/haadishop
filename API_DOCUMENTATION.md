# HaadiShop API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication

برای دسترسی به APIهای محافظت‌شده، باید توکن Sanctum را در header ارسال کنید:
```
Authorization: Bearer {token}
```

---

## Auth Endpoints

### Register
**POST** `/api/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "09123456789",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "message": "User registered successfully.",
  "user": {...},
  "token": "...",
  "affiliate_code": "ABC12345"
}
```

---

### Login
**POST** `/api/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
یا
```json
{
  "phone": "09123456789",
  "password": "password123"
}
```

**Response (بدون 2FA):**
```json
{
  "message": "Login successful.",
  "user": {...},
  "token": "..."
}
```

**Response (با 2FA):**
```json
{
  "requires_2fa": true,
  "user_id": 1,
  "message": "Two-factor authentication required."
}
```

---

### 2FA Verify (برای ورود)
**POST** `/api/2fa/verify`

**Request Body:**
```json
{
  "user_id": 1,
  "code": "123456"
}
```

**Response:**
```json
{
  "message": "Two-factor authentication verified.",
  "user": {...},
  "token": "..."
}
```

---

### 2FA Setup
**GET** `/api/2fa/setup` (Requires Auth)

**Response:**
```json
{
  "secret": "...",
  "qr_code_svg": "base64_encoded_svg",
  "message": "Scan this QR code with your authenticator app."
}
```

---

### 2FA Enable
**POST** `/api/2fa/enable` (Requires Auth)

**Request Body:**
```json
{
  "secret": "...",
  "code": "123456"
}
```

**Response:**
```json
{
  "message": "Two-factor authentication enabled successfully.",
  "recovery_codes": ["CODE1-CODE2", ...],
  "warning": "Save these recovery codes in a safe place..."
}
```

---

### 2FA Disable
**POST** `/api/2fa/disable` (Requires Auth)

**Request Body:**
```json
{
  "password": "current_password"
}
```

---

### Password Reset Request
**POST** `/api/password/reset-link`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```
یا
```json
{
  "phone": "09123456789"
}
```

---

### Password Reset
**POST** `/api/password/reset`

**Request Body:**
```json
{
  "token": "...",
  "email": "john@example.com",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```

---

### Change Password
**POST** `/api/user/password/change` (Requires Auth)

**Request Body:**
```json
{
  "current_password": "old_password",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```

---

## Profile Endpoints

### Get Profile
**GET** `/api/user` (Requires Auth)

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "09123456789",
    "affiliate_code": "ABC12345",
    "addresses": [...],
    "roles": [...]
  }
}
```

---

### Update Profile
**PUT** `/api/user` (Requires Auth)

**Request Body:**
```json
{
  "name": "John Updated",
  "email": "newemail@example.com"
}
```

---

## Address Endpoints

### Get Addresses
**GET** `/api/user/addresses` (Requires Auth)

### Add Address
**POST** `/api/user/addresses` (Requires Auth)

**Request Body:**
```json
{
  "title": "Home",
  "name": "John Doe",
  "phone": "09123456789",
  "country": "Iran",
  "province": "Tehran",
  "city": "Tehran",
  "address_line": "123 Main St",
  "postal_code": "1234567890",
  "is_default": true
}
```

### Update Address
**PUT** `/api/user/addresses/{id}` (Requires Auth)

### Delete Address
**DELETE** `/api/user/addresses/{id}` (Requires Auth)

---

## Logout

**POST** `/api/logout` (Requires Auth)

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

