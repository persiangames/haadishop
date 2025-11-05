# Admin Panel API Documentation

## Base URL
```
http://localhost:8000/api/admin
```

## Authentication

تمام endpointهای زیر نیاز به Authentication + Admin Role دارند.

---

## Dashboard Analytics

### Get Dashboard Stats
**GET** `/api/admin/dashboard/stats?period=30days`

**Query Parameters:**
- `period` (optional): `today`, `7days`, `30days`, `90days`, `1year` (default: `30days`)

**Response:**
```json
{
  "stats": {
    "total_revenue": 5000000000,
    "total_orders": 150,
    "total_users": 500,
    "total_products": 100,
    "average_order_value": 33333333.33,
    "conversion_rate": 15.5,
    "new_users": 50,
    "pending_orders": 10,
    "low_stock_products": 5
  },
  "period": "30days"
}
```

---

### Get Sales Chart
**GET** `/api/admin/dashboard/sales-chart?period=30days&group_by=day`

**Query Parameters:**
- `period` (optional): `today`, `7days`, `30days`, `90days`, `1year`
- `group_by` (optional): `hour`, `day`, `week`, `month` (default: `day`)

**Response:**
```json
{
  "chart": [
    {
      "period": "2024-01-01",
      "revenue": 50000000,
      "orders": 5
    },
    {
      "period": "2024-01-02",
      "revenue": 75000000,
      "orders": 8
    }
  ],
  "period": "30days",
  "group_by": "day"
}
```

---

### Get Top Selling Products
**GET** `/api/admin/dashboard/top-products?limit=10&period=30days`

**Query Parameters:**
- `limit` (optional): تعداد محصولات (default: 10)
- `period` (optional): دوره زمانی

**Response:**
```json
{
  "products": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "total_sold": 50,
      "total_revenue": 2500000000
    }
  ]
}
```

---

### Get Users Chart
**GET** `/api/admin/dashboard/users-chart?period=30days`

**Response:**
```json
{
  "chart": [
    {
      "date": "2024-01-01",
      "count": 10
    },
    {
      "date": "2024-01-02",
      "count": 15
    }
  ],
  "period": "30days"
}
```

---

### Get Category Stats
**GET** `/api/admin/dashboard/category-stats?period=30days`

**Response:**
```json
{
  "categories": [
    {
      "id": 1,
      "slug": "electronics",
      "total_sold": 100,
      "total_revenue": 5000000000
    }
  ],
  "period": "30days"
}
```

---

### Get Low Stock Products
**GET** `/api/admin/dashboard/low-stock?threshold=10`

**Query Parameters:**
- `threshold` (optional): حداقل موجودی (default: از config)

**Response:**
```json
{
  "products": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "sku": "DELL-XPS-001",
      "quantity_on_hand": 5,
      "quantity_reserved": 2,
      "available": 3
    }
  ],
  "count": 5
}
```

---

### Check Inventory Alerts
**POST** `/api/admin/dashboard/check-inventory-alerts`

**Response:**
```json
{
  "message": "Inventory alerts checked.",
  "low_stock_count": 5,
  "out_of_stock_count": 2
}
```

---

## User Management

### Get Users List
**GET** `/api/admin/users?status=active&search=john&sort_by=created_at&sort_order=desc&per_page=15`

**Query Parameters:**
- `status` (optional): `active`, `inactive`, `banned`
- `search` (optional): جستجو در نام، ایمیل، شماره تلفن
- `sort_by` (optional): فیلد مرتب‌سازی
- `sort_order` (optional): `asc` یا `desc`
- `per_page` (optional): تعداد در هر صفحه

**Response:**
```json
{
  "users": {
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "09123456789",
        "status": "active",
        "orders_count": 10,
        "roles": [...]
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

---

### Get User Details
**GET** `/api/admin/users/{id}`

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "09123456789",
    "status": "active",
    "affiliate_code": "ABC12345",
    "orders": [...],
    "addresses": [...],
    "roles": [...],
    "loyalty_points": [...]
  }
}
```

---

### Update User Status
**PUT** `/api/admin/users/{id}/status`

**Request Body:**
```json
{
  "status": "banned"
}
```

**Response:**
```json
{
  "message": "User status updated successfully.",
  "user": {...}
}
```

---

### Assign Role to User
**POST** `/api/admin/users/{id}/roles`

**Request Body:**
```json
{
  "role_id": 1
}
```

---

### Remove Role from User
**DELETE** `/api/admin/users/{id}/roles/{roleId}`

---

## Order Management

### Get Orders List
**GET** `/api/admin/orders?status=paid&from_date=2024-01-01&to_date=2024-01-31&search=ORD-123&sort_by=created_at&sort_order=desc&per_page=15`

**Query Parameters:**
- `status` (optional): `pending`, `paid`, `fulfilled`, `cancelled`, `refunded`
- `from_date` (optional): تاریخ شروع
- `to_date` (optional): تاریخ پایان
- `search` (optional): جستجو در شماره سفارش یا اطلاعات کاربر
- `sort_by` (optional): فیلد مرتب‌سازی
- `sort_order` (optional): `asc` یا `desc`
- `per_page` (optional): تعداد در هر صفحه

**Response:**
```json
{
  "orders": {
    "data": [
      {
        "id": 1,
        "order_number": "ORD-ABC123",
        "status": "paid",
        "grand_total": 100000000,
        "user": {...},
        "items": [...],
        "addresses": [...],
        "payments": [...]
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

---

### Get Order Details
**GET** `/api/admin/orders/{id}`

---

### Update Order Status
**PUT** `/api/admin/orders/{id}/status`

**Request Body:**
```json
{
  "status": "fulfilled"
}
```

**Note:** 
- اگر status = `fulfilled` و سفارش `paid` باشد، OrderService.fulfillOrder فراخوانی می‌شود
- اگر status = `cancelled` باشد، OrderService.cancelOrder فراخوانی می‌شود

---

## A/B Testing

### Get Variant
**GET** `/api/ab-test/{testKey}/variant`

**Response:**
```json
{
  "variant": "variant_a",
  "test_key": "homepage_hero"
}
```

---

### Track Metric
**POST** `/api/ab-test/{testKey}/track`

**Request Body:**
```json
{
  "event_key": "button_click",
  "value": 1
}
```

**Response:**
```json
{
  "message": "Metric tracked successfully."
}
```

---

## Scheduled Commands

### Check Lottery Draws
```bash
php artisan lottery:check-draws
```
**Schedule:** هر 5 دقیقه

### Check Inventory Alerts
```bash
php artisan inventory:check-alerts
```
**Schedule:** هر ساعت

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (Admin access required)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

