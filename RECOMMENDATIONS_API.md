# Recommendations API Documentation

## Base URL
```
http://localhost:8000/api
```

## Overview

سیستم پیشنهادات هوشمند با استفاده از الگوریتم‌های مختلف:
- **Collaborative Filtering (Item-based)**: پیشنهاد بر اساس محصولات مشابه
- **Collaborative Filtering (User-based)**: پیشنهاد بر اساس کاربران مشابه
- **Personalized Recommendations**: پیشنهادات شخصی‌شده بر اساس تاریخچه
- **Related Products**: محصولات مرتبط
- **Popular Products**: محصولات محبوب

---

## Personalized Recommendations

### Get Personalized Recommendations
**GET** `/api/recommendations/personalized?limit=10`

**Query Parameters:**
- `limit` (optional): تعداد محصولات پیشنهادی (default: 10)

**Authentication:** اختیاری (اگر لاگین باشید، پیشنهادات بهتر است)

**Response:**
```json
{
  "recommendations": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "title": "لپ تاپ Dell XPS",
      "short_desc": "...",
      "brand": {
        "id": 1,
        "name": "Dell"
      },
      "categories": [
        {
          "id": 1,
          "slug": "electronics",
          "name": "الکترونیک"
        }
      ],
      "min_price": 50000000
    }
  ]
}
```

**الگوریتم:**
- اگر کاربر لاگین باشد: User-based Collaborative Filtering
- اگر session باشد: Session-based recommendations
- در غیر این صورت: Popular products

---

## Related Products

### Get Related Products
**GET** `/api/recommendations/related/{productId}?limit=8`

**Query Parameters:**
- `limit` (optional): تعداد محصولات (default: 8)

**Response:**
```json
{
  "related_products": [
    {
      "id": 2,
      "slug": "laptop-hp-pavilion",
      "title": "لپ تاپ HP Pavilion",
      "short_desc": "...",
      "brand": {
        "id": 2,
        "name": "HP"
      },
      "min_price": 45000000
    }
  ]
}
```

**الگوریتم:**
1. محصولات همان دسته‌بندی
2. محصولات همان برند
3. Item-based Collaborative Filtering

---

## Popular Products

### Get Popular Products
**GET** `/api/recommendations/popular?limit=10`

**Query Parameters:**
- `limit` (optional): تعداد محصولات (default: 10)

**Response:**
```json
{
  "popular_products": [
    {
      "id": 1,
      "slug": "laptop-dell-xps",
      "title": "لپ تاپ Dell XPS",
      "short_desc": "...",
      "views_count": 1500,
      "orders_count": 120,
      "min_price": 50000000
    }
  ]
}
```

**الگوریتم:**
- مرتب‌سازی بر اساس تعداد سفارش‌ها
- سپس تعداد بازدیدها

---

## Purchase History Recommendations

### Get Purchase History Recommendations
**GET** `/api/recommendations/purchase-history?limit=10` (Requires Auth)

**Query Parameters:**
- `limit` (optional): تعداد محصولات (default: 10)

**Response:**
```json
{
  "recommendations": [
    {
      "id": 3,
      "slug": "laptop-lenovo-thinkpad",
      "title": "لپ تاپ Lenovo ThinkPad",
      "short_desc": "...",
      "min_price": 55000000
    }
  ]
}
```

**الگوریتم:**
- پیشنهاد محصولات از دسته‌بندی‌های محصولات خریداری شده قبلی

---

## Collaborative Filtering

### Item-based Collaborative Filtering

**منطق:**
1. پیدا کردن کاربرانی که محصول X را خریده‌اند
2. پیدا کردن محصولات دیگر که این کاربران خریده‌اند
3. پیشنهاد محصولاتی که بیشترین هم‌رخداد (co-occurrence) را دارند

**مثال:**
- کاربران A, B, C محصول X را خریده‌اند
- کاربر A محصول Y را خریده
- کاربر B محصول Y و Z را خریده
- کاربر C محصول Z را خریده
- **پیشنهاد:** Y (2 هم‌رخداد) و Z (2 هم‌رخداد)

---

### User-based Collaborative Filtering

**منطق:**
1. پیدا کردن کاربرانی که محصولات مشابه خریداری کرده‌اند
2. پیدا کردن محصولات دیگر که این کاربران مشابه خریده‌اند
3. پیشنهاد محصولاتی که بیشترین خرید را دارند

**مثال:**
- کاربر شما: محصولات X, Y را خریده
- کاربر A: محصولات X, Y, Z را خریده (مشابه!)
- کاربر B: محصولات X, Y, W را خریده (مشابه!)
- **پیشنهاد:** Z و W

---

## Caching Strategy

### Cache Keys
- `recommendations:item-based:product:{id}:limit:{limit}` - 24 ساعت
- `recommendations:user-based:user:{id}:limit:{limit}` - 12 ساعت
- `recommendations:purchase-history:user:{id}:limit:{limit}` - 12 ساعت
- `recommendations:session:{sessionId}:limit:{limit}` - 1 ساعت
- `recommendations:popular:limit:{limit}` - 6 ساعت

### Cache Invalidation
- هنگام خرید محصول جدید
- هنگام مشاهده محصول جدید
- هر 24 ساعت برای Item-based
- هر 12 ساعت برای User-based

---

## Performance Considerations

### بهینه‌سازی‌ها:
1. **Caching**: نتایج cache می‌شوند
2. **Limit**: محدود کردن تعداد نتایج
3. **Indexing**: ایندکس‌های مناسب در دیتابیس
4. **Lazy Loading**: استفاده از eager loading برای relationships

### پیشنهادات:
- برای محصولات با تعداد زیاد، از Elasticsearch استفاده کنید
- برای کاربران با تاریخچه زیاد، از Batch Processing استفاده کنید
- برای سیستم‌های بزرگ، از Machine Learning استفاده کنید

---

## Status Codes

- `200` - Success
- `401` - Unauthorized (برای purchase-history)
- `404` - Product not found (برای related products)
- `500` - Server Error

