# Affiliate & Lottery API Documentation

## Base URL
```
http://localhost:8000/api
```

## Affiliate System

### Get Affiliate Stats
**GET** `/api/affiliate/stats` (Requires Auth)

**Response:**
```json
{
  "stats": {
    "affiliate_code": "ABC12345",
    "affiliate_link": "https://site.com/?ref=ABC12345",
    "total_clicks": 150,
    "total_referrals": 25,
    "approved_referrals": 20,
    "total_commission": 5000000,
    "pending_commission": 1000000
  }
}
```

---

### Generate Share Link
**GET** `/api/affiliate/share/{productSlug}?lottery_code=XXX` (Requires Auth)

**Query Parameters:**
- `lottery_code` (optional): کد قرعه‌کشی برای لینک

**Response:**
```json
{
  "share_link": "https://site.com/product/laptop-dell-xps?ref=ABC12345&lottery=XXX",
  "affiliate_code": "ABC12345"
}
```

**Format:**
```
https://site.com/product/{product_slug}?ref={affiliate_code}&lottery={lottery_code}
```

---

### Track Affiliate Click
**POST** `/api/affiliate/track` (Public)

**Request Body:**
```json
{
  "ref": "ABC12345",
  "product_id": 1
}
```

**Response:**
```json
{
  "message": "Click tracked successfully.",
  "click_id": 1
}
```

---

## Lottery System

### Get Lottery by Product
**GET** `/api/lotteries/product/{productSlug}`

**Response:**
```json
{
  "lottery": {
    "id": 1,
    "product": {
      "id": 1,
      "slug": "laptop-dell-xps",
      "title": "لپ تاپ Dell XPS"
    },
    "target_pool_amount": 1000000000,
    "current_pool_amount": 750000000,
    "completion_percent": 75,
    "currency_code": "IRR",
    "is_active": true,
    "total_entries": 150,
    "total_draws": 2,
    "winners": [
      {
        "draw_number": 1,
        "user": {
          "id": 10,
          "name": "John Doe"
        },
        "is_claimed": true,
        "claimed_at": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

---

### Create Lottery Entry
**POST** `/api/orders/{orderId}/lottery-entry` (Requires Auth)

**Request Body:**
```json
{
  "lottery_id": 1,
  "affiliate_code": "ABC12345"
}
```

**Response:**
```json
{
  "message": "Lottery entry created successfully.",
  "entry": {
    "id": 1,
    "lottery_code": "LOT123456789",
    "weight": 3
  }
}
```

**Process:**
1. بررسی اینکه سفارش پرداخت شده است
2. ایجاد referral اگر affiliate_code وجود دارد
3. محاسبه weight بر اساس تعداد خریدهای قبلی
4. ایجاد lottery entry
5. به‌روزرسانی صندوق قرعه‌کشی
6. بررسی قرعه‌کشی خودکار (اگر به 100% رسید)

---

### Get My Lottery Entries
**GET** `/api/lotteries/my-entries` (Requires Auth)

**Response:**
```json
{
  "entries": [
    {
      "id": 1,
      "lottery_code": "LOT123456789",
      "weight": 3,
      "lottery": {
        "id": 1,
        "product": {
          "id": 1,
          "slug": "laptop-dell-xps",
          "title": "لپ تاپ Dell XPS"
        },
        "completion_percent": 75
      },
      "is_winner": true,
      "won_at": "2024-01-01 12:00:00"
    }
  ]
}
```

---

### Get Lottery Stats
**GET** `/api/lotteries/{lotteryId}/stats`

**Response:** همانند Get Lottery by Product

---

### Draw Lottery (Admin)
**POST** `/api/admin/lotteries/{lotteryId}/draw` (Requires Auth + Admin)

**Response:**
```json
{
  "message": "Lottery drawn successfully.",
  "draw": {
    "id": 1,
    "draw_number": 3,
    "winner": {
      "user_id": 25,
      "name": "Jane Doe"
    }
  }
}
```

---

## Lottery Weight System

### محاسبه Weight

**Base Weight:** 1

**افزایش Weight بر اساس:**
- هر خرید قبلی از طریق لینک معرفی = +1 weight
- هر خرید مستقیم = +0.5 weight

**مثال:**
- خرید اول از طریق لینک معرفی = weight 1
- خرید دوم از طریق همان لینک = weight 2
- خرید سوم = weight 3
- و...

---

## Auto-Draw System

### قرعه‌کشی خودکار

وقتی `current_pool_amount >= target_pool_amount * (auto_draw_threshold_percent / 100)`:
1. Job `AutoDrawLottery` اجرا می‌شود
2. قرعه‌کشی انجام می‌شود
3. برنده انتخاب می‌شود
4. صندوق قرعه‌کشی ریست می‌شود (برای قرعه‌کشی بعدی)

### Command: Check Lottery Draws

```bash
php artisan lottery:check-draws
```

این command به صورت خودکار هر 5 دقیقه اجرا می‌شود (در `app/Console/Kernel.php`).

---

## Winner Exclusion System

### حذف برندگان قبلی

برندگان قرعه‌کشی‌های قبلی از قرعه‌کشی‌های بعدی حذف می‌شوند، **مگر اینکه:**
- مجدداً خرید کنند
- یک lottery entry جدید ایجاد کنند

**منطق:**
```php
// دریافت لیست برندگان قبلی
$previousWinners = LotteryWinner::whereHas('draw', function ($q) use ($lottery) {
    $q->where('lottery_id', $lottery->id)
      ->where('draw_number', '<', $currentDrawNumber);
})->pluck('user_id')->toArray();

// فقط ورودی‌های غیر برنده
$eligibleEntries = LotteryEntry::where('lottery_id', $lottery->id)
    ->whereNotIn('buyer_user_id', $previousWinners)
    ->get();
```

---

## Integration Flow

### 1. User clicks affiliate link
```
https://site.com/product/{slug}?ref={affiliate_code}&lottery={lottery_code}
```

### 2. Track click
```
POST /api/affiliate/track
```

### 3. User purchases product
```
POST /api/checkout
```

### 4. Create referral
```
در OrderService، affiliate_code از session یا request گرفته می‌شود
```

### 5. Create lottery entry
```
POST /api/orders/{orderId}/lottery-entry
```

### 6. Auto-draw when threshold reached
```
Job: AutoDrawLottery
```

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (Admin required)
- `404` - Not Found
- `422` - Validation Error / Business Logic Error
- `500` - Server Error

