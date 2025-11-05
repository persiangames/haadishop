# Internationalization & Notifications API Documentation

## Base URL
```
http://localhost:8000/api
```

---

## Localization (i18n)

### Get Current Locale
**GET** `/api/locale`

**Response:**
```json
{
  "locale": "fa",
  "available_locales": ["fa", "en"],
  "default_locale": "fa"
}
```

---

### Set Locale
**POST** `/api/locale`

**Request Body:**
```json
{
  "locale": "en"
}
```

**Response:**
```json
{
  "message": "Locale changed successfully.",
  "locale": "en"
}
```

**Headers (Alternative):**
```
Accept-Language: en
```

---

## Currency (Multi-Currency)

### Get Active Currencies
**GET** `/api/currencies`

**Response:**
```json
{
  "currencies": [
    {
      "code": "IRR",
      "name": "Iranian Rial",
      "symbol": "﷼",
      "precision": 0,
      "is_default": true,
      "is_active": true
    },
    {
      "code": "USD",
      "name": "US Dollar",
      "symbol": "$",
      "precision": 2,
      "is_default": false,
      "is_active": true
    }
  ],
  "default": {
    "code": "IRR",
    "name": "Iranian Rial",
    "symbol": "﷼"
  }
}
```

---

### Convert Currency
**GET** `/api/currencies/convert?amount=1000000&from=IRR&to=USD`

**Query Parameters:**
- `amount` (required): مبلغ
- `from` (required): ارز مبدا (3 حرف)
- `to` (required): ارز مقصد (3 حرف)

**Response:**
```json
{
  "amount": 1000000,
  "from": "IRR",
  "to": "USD",
  "rate": 0.000024,
  "converted": 24.00
}
```

---

### Get Exchange Rate
**GET** `/api/currencies/rate?from=IRR&to=USD`

**Query Parameters:**
- `from` (required): ارز مبدا
- `to` (required): ارز مقصد

**Response:**
```json
{
  "from": "IRR",
  "to": "USD",
  "rate": 0.000024
}
```

**Note:** نرخ‌های ارز به صورت خودکار هر 6 ساعت به‌روزرسانی می‌شوند.

---

## Notifications

### Order Notifications

سیستم اعلان‌رسانی به صورت خودکار برای رویدادهای زیر فعال است:

1. **Order Placed** - هنگام ثبت سفارش
2. **Order Paid** - هنگام پرداخت موفق
3. **Order Fulfilled** - هنگام ارسال سفارش
4. **Order Cancelled** - هنگام لغو سفارش

**کانال‌های اعلان:**
- ✅ Email
- ✅ SMS (اگر شماره تلفن کاربر موجود باشد)
- ✅ Push Notification
- ✅ Database Notification

---

### Cart Abandonment Recovery

سیستم به صورت خودکار برای سبد خریدهای رها شده، یادآوری ارسال می‌کند.

**منطق:**
- اگر کاربر سبد خرید را پر کرده اما خرید نکرده
- بعد از مدت زمان مشخص (مثلاً 24 ساعت)
- ارسال ایمیل یادآوری

---

## Reports API (Admin)

### Full Report
**GET** `/api/admin/reports/full?from_date=2024-01-01&to_date=2024-01-31` (Requires Auth + Admin)

**Query Parameters:**
- `from_date` (required): تاریخ شروع
- `to_date` (required): تاریخ پایان

**Response:**
```json
{
  "report": {
    "period": {
      "from": "2024-01-01",
      "to": "2024-01-31"
    },
    "summary": {
      "total_revenue": 5000000000,
      "total_orders": 150,
      "paid_orders": 120,
      "total_users": 50,
      "average_order_value": 41666666.67
    },
    "sales": [...],
    "products": [...],
    "users": [...],
    "payments": [...]
  }
}
```

---

### Sales Report
**GET** `/api/admin/reports/sales?from_date=2024-01-01&to_date=2024-01-31&group_by=day`

**Query Parameters:**
- `from_date` (required): تاریخ شروع
- `to_date` (required): تاریخ پایان
- `group_by` (optional): `hour`, `day`, `week`, `month` (default: `day`)

**Response:**
```json
{
  "report": [
    {
      "period": "2024-01-01",
      "total_revenue": 50000000,
      "total_orders": 5,
      "average_order_value": 10000000
    }
  ]
}
```

---

### Products Report
**GET** `/api/admin/reports/products?from_date=2024-01-01&to_date=2024-01-31`

**Response:**
```json
{
  "report": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "total_sold": 50,
      "total_revenue": 2500000000,
      "average_price": 50000000
    }
  ]
}
```

---

### Users Report
**GET** `/api/admin/reports/users?from_date=2024-01-01&to_date=2024-01-31`

**Response:**
```json
{
  "report": [
    {
      "date": "2024-01-01",
      "new_users": 10
    }
  ]
}
```

---

### Payments Report
**GET** `/api/admin/reports/payments?from_date=2024-01-01&to_date=2024-01-31`

**Response:**
```json
{
  "report": [
    {
      "provider": "zarinpal",
      "total_payments": 100,
      "total_amount": 5000000000,
      "average_amount": 50000000
    },
    {
      "provider": "stripe",
      "total_payments": 20,
      "total_amount": 1000000000,
      "average_amount": 50000000
    }
  ]
}
```

---

## Scheduled Tasks

### Update Exchange Rates
```bash
php artisan currency:update-rates
```
**Schedule:** هر 6 ساعت

---

## Middleware

### SetLocale Middleware

این middleware به صورت خودکار زبان را از header یا session تنظیم می‌کند.

**Priority:**
1. `Accept-Language` header
2. `locale` query parameter
3. Session
4. Default locale

---

### SetCurrency Middleware

این middleware به صورت خودکار ارز را از request یا session تنظیم می‌کند.

**Priority:**
1. `currency` query parameter
2. Session
3. Default currency

---

## Status Codes

- `200` - Success
- `401` - Unauthorized
- `403` - Forbidden (Admin required)
- `422` - Validation Error
- `500` - Server Error

