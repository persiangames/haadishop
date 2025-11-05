# HaadiShop - Advanced E-commerce Platform

یک سیستم مدیریت فروشگاه اینترنتی جامع و حرفه‌ای با Laravel 11

## ویژگی‌های اصلی

- ✅ سیستم احراز هویت پیشرفته با 2FA
- ✅ مدیریت محصولات و دسته‌بندی‌ها
- ✅ سبد خرید و پرداخت چنددرگاهی
- ✅ سیستم بازاریابی مشارکتی (Affiliate)
- ✅ سیستم قرعه‌کشی هوشمند
- ✅ پیشنهادات شخصی‌شده (Collaborative Filtering)
- ✅ برنامه وفاداری چندسطحی
- ✅ چندزبانه و چندارزی
- ✅ پنل مدیریت پیشرفته

## نصب و راه‌اندازی

### پیش‌نیازها
- PHP 8.2+ (extension sockets برای RabbitMQ - اختیاری)
- Composer 2.x
- MySQL 8.0+
- Node.js 20+ (برای فرانت)

### مراحل نصب

1. **نصب وابستگی‌ها:**
```bash
cd D:\Hadishop\haadishop
composer install
npm install
```

**نکته:** اگر خطای RabbitMQ دارید، نگران نباشید. فعلاً از database queue استفاده می‌کنیم.

2. **تنظیم فایل .env:**
```bash
copy .env.example .env
# ویرایش .env و تنظیمات دیتابیس
```

3. **تولید کلید اپلیکیشن:**
```bash
php artisan key:generate
```

4. **ایجاد دیتابیس:**
```sql
CREATE DATABASE haadishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

5. **اجرای مایگریشن‌ها و Seederها:**
```bash
php artisan migrate
php artisan db:seed
```

6. **اجرای سرور توسعه:**
```bash
php artisan serve
```

سپس در مرورگر: http://localhost:8000

## ساختار پروژه

```
haadishop/
├── app/
│   ├── Models/          # مدل‌های Eloquent
│   ├── Http/
│   │   └── Controllers/ # کنترلرها
│   ├── Services/        # سرویس‌های کسب و کار
│   └── Repositories/    # Repository Pattern
├── database/
│   ├── migrations/       # مایگریشن‌های دیتابیس
│   └── seeders/         # Seederها
├── routes/
│   ├── web.php          # Routes وب
│   └── api.php          # Routes API
└── resources/
    ├── views/           # Blade Templates
    ├── js/              # JavaScript
    └── css/             # Stylesheets
```

## مایگریشن‌های ایجاد شده

- ✅ currencies & exchange_rates
- ✅ users & user_addresses
- ✅ roles & permissions
- ✅ categories & brands (با ترجمه)
- ✅ products & product_variants
- ✅ variant_prices & inventories
- ✅ carts & cart_items
- ✅ orders & order_items
- ✅ payments & payment_transactions
- ✅ affiliate_clicks & affiliate_referrals
- ✅ lotteries & lottery_entries & lottery_draws
- ✅ loyalty_tiers & loyalty_points
- ✅ product_views

## مدل‌های ایجاد شده

تمام مدل‌های اصلی با relationships و helper methods ایجاد شده‌اند.

## مراحل بعدی

1. ✅ طراحی دیتابیس و مدل‌ها
2. ⏳ سیستم احراز هویت و 2FA
3. ⏳ سیستم مدیریت محصولات
4. ⏳ سیستم سبد خرید و پرداخت
5. ⏳ سیستم بازاریابی مشارکتی و قرعه‌کشی
6. ⏳ سیستم پیشنهادات هوشمند
7. ⏳ پنل مدیریت
8. ⏳ ویژگی‌های جانبی

## لایسنس

MIT

