# Catalog API Documentation

## Base URL
```
http://localhost:8000/api
```

## Public Endpoints (نیازی به Authentication نیست)

### Categories

#### Get All Categories
**GET** `/api/categories?locale=fa`

**Query Parameters:**
- `locale` (optional): Language code (default: app locale)

**Response:**
```json
{
  "categories": [
    {
      "id": 1,
      "slug": "electronics",
      "name": "الکترونیک",
      "description": "...",
      "parent_id": null,
      "parent": null,
      "children": [...]
    }
  ]
}
```

#### Get Category by ID
**GET** `/api/categories/{id}?locale=fa`

---

### Brands

#### Get All Brands
**GET** `/api/brands?locale=fa`

**Response:**
```json
{
  "brands": [
    {
      "id": 1,
      "slug": "samsung",
      "name": "سامسونگ",
      "description": "...",
      "products_count": 15
    }
  ]
}
```

#### Get Brand by ID
**GET** `/api/brands/{id}?locale=fa`

---

### Products

#### Get All Products
**GET** `/api/products?locale=fa&currency=IRR&category_id=1&brand_id=1&search=laptop&sort_by=created_at&sort_order=desc&per_page=15`

**Query Parameters:**
- `locale` (optional): Language code
- `currency` (optional): Currency code (default: IRR)
- `category_id` (optional): Filter by category
- `brand_id` (optional): Filter by brand
- `search` (optional): Search in title and description
- `sort_by` (optional): Field to sort by (default: created_at)
- `sort_order` (optional): asc or desc (default: desc)
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "sku": "DELL-XPS-001",
      "title": "لپ تاپ Dell XPS",
      "short_desc": "...",
      "brand": {...},
      "categories": [...],
      "variants": [...],
      "min_price": 50000000,
      "max_price": 60000000
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 100
}
```

#### Get Product by Slug
**GET** `/api/products/{slug}?locale=fa&currency=IRR`

**Note:** این endpoint به صورت خودکار بازدید محصول را ثبت می‌کند.

**Response:**
```json
{
  "product": {
    "id": 1,
    "slug": "laptop-dell-xps",
    "sku": "DELL-XPS-001",
    "title": "لپ تاپ Dell XPS",
    "short_desc": "...",
    "long_desc": "...",
    "meta_title": "...",
    "meta_desc": "...",
    "brand": {...},
    "categories": [...],
    "variants": [
      {
        "id": 1,
        "sku": "DELL-XPS-001-8GB",
        "barcode": "...",
        "option_values": {"memory": "8GB", "storage": "256GB"},
        "is_active": true,
        "price": {
          "amount": 50000000,
          "compare_at_amount": 55000000,
          "currency": "IRR"
        },
        "inventory": {
          "quantity_on_hand": 10,
          "quantity_reserved": 2,
          "available": 8,
          "in_stock": true,
          "low_stock": false
        }
      }
    ],
    "weight": 1.5,
    "width": 30,
    "height": 20,
    "length": 40,
    "warranty_months": 24
  }
}
```

---

## Admin Endpoints (نیاز به Authentication + Admin Role)

### Categories

#### Create Category
**POST** `/api/admin/categories`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "slug": "electronics",
  "parent_id": null,
  "is_active": true,
  "translations": [
    {
      "locale": "fa",
      "name": "الکترونیک",
      "description": "دسته‌بندی محصولات الکترونیکی"
    },
    {
      "locale": "en",
      "name": "Electronics",
      "description": "Electronic products category"
    }
  ]
}
```

#### Update Category
**PUT** `/api/admin/categories/{id}`

#### Delete Category
**DELETE** `/api/admin/categories/{id}`

**Note:** نمی‌توان دسته‌بندی که محصول دارد را حذف کرد.

---

### Brands

#### Create Brand
**POST** `/api/admin/brands`

**Request Body:**
```json
{
  "slug": "samsung",
  "is_active": true,
  "translations": [
    {
      "locale": "fa",
      "name": "سامسونگ",
      "description": "برند کره‌ای"
    },
    {
      "locale": "en",
      "name": "Samsung",
      "description": "Korean brand"
    }
  ]
}
```

#### Update Brand
**PUT** `/api/admin/brands/{id}`

#### Delete Brand
**DELETE** `/api/admin/brands/{id}`

---

### Products

#### Create Product
**POST** `/api/admin/products`

**Request Body:**
```json
{
  "slug": "laptop-dell-xps",
  "sku": "DELL-XPS-001",
  "brand_id": 1,
  "status": "published",
  "is_published": true,
  "weight": 1.5,
  "width": 30,
  "height": 20,
  "length": 40,
  "warranty_months": 24,
  "category_ids": [1, 2],
  "translations": [
    {
      "locale": "fa",
      "title": "لپ تاپ Dell XPS",
      "short_desc": "لپ تاپ قدرتمند",
      "long_desc": "توضیحات کامل...",
      "meta_title": "...",
      "meta_desc": "..."
    }
  ]
}
```

#### Update Product
**PUT** `/api/admin/products/{id}`

#### Delete Product
**DELETE** `/api/admin/products/{id}`

**Note:** نمی‌توان محصولی که سفارش دارد را حذف کرد.

---

### Product Variants

#### Create Variant
**POST** `/api/admin/products/{productId}/variants`

**Request Body:**
```json
{
  "sku": "DELL-XPS-001-8GB",
  "option_values": {
    "memory": "8GB",
    "storage": "256GB",
    "color": "Black"
  },
  "barcode": "1234567890123",
  "is_active": true,
  "prices": [
    {
      "currency_code": "IRR",
      "amount": 50000000,
      "compare_at_amount": 55000000,
      "start_at": null,
      "end_at": null
    },
    {
      "currency_code": "USD",
      "amount": 1000,
      "compare_at_amount": 1100,
      "start_at": null,
      "end_at": null
    }
  ],
  "inventory": {
    "quantity_on_hand": 10,
    "quantity_reserved": 0,
    "low_stock_threshold": 5
  }
}
```

#### Update Variant
**PUT** `/api/admin/products/{productId}/variants/{variantId}`

#### Delete Variant
**DELETE** `/api/admin/products/{productId}/variants/{variantId}`

**Note:** نمی‌توان variant که در سفارش استفاده شده را حذف کرد.

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (Admin access required)
- `404` - Not Found
- `422` - Validation Error / Business Logic Error
- `500` - Server Error

