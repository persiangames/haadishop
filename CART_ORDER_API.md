# Cart, Order & Payment API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication

تمام endpointهای زیر نیاز به Authentication دارند (Bearer Token).

---

## Cart Endpoints

### Get Cart
**GET** `/api/cart?currency=IRR`

**Response:**
```json
{
  "cart": {
    "id": 1,
    "currency_code": "IRR",
    "items_count": 2,
    "items": [
      {
        "id": 1,
        "variant": {
          "id": 1,
          "sku": "DELL-XPS-001-8GB",
          "option_values": {
            "memory": "8GB",
            "storage": "256GB"
          }
        },
        "product": {
          "id": 1,
          "slug": "laptop-dell-xps",
          "title": "لپ تاپ Dell XPS"
        },
        "quantity": 2,
        "unit_price": 50000000,
        "line_total": 100000000
      }
    ],
    "subtotal": 100000000
  }
}
```

---

### Add Item to Cart
**POST** `/api/cart/items`

**Request Body:**
```json
{
  "variant_id": 1,
  "quantity": 2,
  "currency": "IRR"
}
```

**Response:**
```json
{
  "message": "Item added to cart successfully.",
  "item": {
    "id": 1,
    "variant_id": 1,
    "quantity": 2,
    "unit_price": 50000000,
    "line_total": 100000000
  }
}
```

**Errors:**
- `422` - Insufficient stock available
- `422` - Product variant is not available
- `422` - Price not available for this currency

---

### Update Cart Item
**PUT** `/api/cart/items/{itemId}`

**Request Body:**
```json
{
  "quantity": 3
}
```

**Note:** اگر quantity = 0 باشد، آیتم حذف می‌شود.

---

### Remove Item from Cart
**DELETE** `/api/cart/items/{itemId}`

---

### Clear Cart
**DELETE** `/api/cart`

---

## Checkout & Orders

### Create Order from Cart
**POST** `/api/checkout`

**Request Body:**
```json
{
  "billing_address": {
    "name": "John Doe",
    "phone": "09123456789",
    "country": "Iran",
    "province": "Tehran",
    "city": "Tehran",
    "address_line": "123 Main St",
    "postal_code": "1234567890"
  },
  "shipping_address": {
    "name": "John Doe",
    "phone": "09123456789",
    "country": "Iran",
    "province": "Tehran",
    "city": "Tehran",
    "address_line": "123 Main St",
    "postal_code": "1234567890"
  },
  "coupon_code": "DISCOUNT10",
  "currency": "IRR"
}
```

**Note:** `shipping_address` اختیاری است. اگر داده نشود، از `billing_address` استفاده می‌شود.

**Response:**
```json
{
  "message": "Order created successfully.",
  "order": {
    "id": 1,
    "order_number": "ORD-ABC123",
    "status": "pending",
    "grand_total": 100000000,
    "currency_code": "IRR"
  }
}
```

**Process:**
1. ایجاد سفارش
2. رزرو موجودی (reserve inventory)
3. افزودن آیتم‌های سفارش
4. پاک کردن سبد خرید

---

### Get User Orders
**GET** `/api/orders?page=1&per_page=15`

**Response:**
```json
{
  "orders": {
    "data": [
      {
        "id": 1,
        "order_number": "ORD-ABC123",
        "status": "pending",
        "grand_total": 100000000,
        "placed_at": "2024-01-01 12:00:00"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 10
  }
}
```

---

### Get Order Details
**GET** `/api/orders/{id}`

**Response:**
```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-ABC123",
    "status": "pending",
    "currency_code": "IRR",
    "subtotal": 100000000,
    "discount_total": 0,
    "tax_total": 0,
    "shipping_total": 0,
    "grand_total": 100000000,
    "paid_total": 0,
    "due_total": 100000000,
    "placed_at": "2024-01-01 12:00:00",
    "items": [
      {
        "id": 1,
        "variant": {
          "id": 1,
          "sku": "DELL-XPS-001-8GB",
          "option_values": {...}
        },
        "product": {
          "id": 1,
          "slug": "laptop-dell-xps",
          "title": "لپ تاپ Dell XPS"
        },
        "quantity": 2,
        "unit_price": 50000000,
        "line_total": 100000000
      }
    ],
    "addresses": [
      {
        "type": "billing",
        "name": "John Doe",
        "phone": "09123456789",
        "country": "Iran",
        "province": "Tehran",
        "city": "Tehran",
        "address_line": "123 Main St",
        "postal_code": "1234567890"
      },
      {
        "type": "shipping",
        ...
      }
    ],
    "payments": [...]
  }
}
```

---

### Cancel Order
**POST** `/api/orders/{id}/cancel`

**Response:**
```json
{
  "message": "Order cancelled successfully.",
  "order": {...}
}
```

**Note:** 
- سفارش‌های fulfilled را نمی‌توان لغو کرد
- موجودی رزرو شده به موجودی برمی‌گردد

---

## Payment Endpoints

### Initiate Payment
**POST** `/api/orders/{orderId}/payments/initiate?provider=zarinpal`

**Query Parameters:**
- `provider`: `zarinpal` یا `stripe` (default: zarinpal)

**Response (Zarinpal):**
```json
{
  "message": "Payment initiated successfully.",
  "payment_id": 1,
  "authority": "A000000000000000000000000000000000000000",
  "payment_url": "https://www.zarinpal.com/pg/StartPay/A000000000000000000000000000000000000000"
}
```

**Response (Stripe):**
```json
{
  "message": "Payment initiated successfully.",
  "payment_id": 1,
  "client_secret": "pi_xxx_secret_xxx",
  "payment_intent_id": "pi_xxx"
}
```

**Process:**
1. ایجاد رکورد Payment
2. ارسال درخواست به درگاه پرداخت
3. بازگرداندن URL پرداخت یا client_secret

---

### Verify Payment
**POST** `/api/payments/{paymentId}/verify`

**Response:**
```json
{
  "success": true,
  "payment": {
    "id": 1,
    "status": "succeeded",
    "amount": 100000000
  },
  "order": {
    "id": 1,
    "status": "paid",
    "paid_total": 100000000,
    "due_total": 0
  }
}
```

---

### Payment Callback (Zarinpal)
**GET** `/api/payments/zarinpal/callback?Authority=xxx&Status=OK`

**Note:** این endpoint عمومی است و توسط Zarinpal فراخوانی می‌شود.

**Process:**
1. بررسی Status
2. Verify payment با Zarinpal
3. به‌روزرسانی Payment و Order
4. Redirect به صفحه موفقیت/خطا

---

## Order Status Flow

```
pending → paid → fulfilled
  ↓
cancelled
```

- **pending**: سفارش ایجاد شده، منتظر پرداخت
- **paid**: پرداخت انجام شده
- **fulfilled**: سفارش تحویل داده شده
- **cancelled**: سفارش لغو شده

---

## Payment Status Flow

```
init → succeeded
  ↓
failed
```

- **init**: پرداخت آغاز شده
- **succeeded**: پرداخت موفق
- **failed**: پرداخت ناموفق
- **refunded**: پرداخت برگشت شده

---

## Inventory Management

- هنگام ایجاد سفارش، موجودی رزرو می‌شود (`quantity_reserved` افزایش می‌یابد)
- هنگام لغو سفارش، موجودی بازگردانده می‌شود
- موجودی قابل دسترس = `quantity_on_hand - quantity_reserved`

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error / Business Logic Error
- `500` - Server Error

