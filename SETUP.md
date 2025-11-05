# راهنمای نصب و راه‌اندازی HaadiShop

## پیش‌نیازها (برای توسعه لوکال)

### 1. نصب PHP 8.2+
- دانلود از: https://windows.php.net/download/
- یا استفاده از XAMPP/WAMP که شامل PHP است
- افزودن PHP به PATH سیستم

### 2. نصب Composer
**روش 1: دانلود مستقیم (توصیه می‌شود)**
- دانلود Composer-Setup.exe از: https://getcomposer.org/download/
- اجرا و نصب (Composer را به PATH اضافه می‌کند)

**روش 2: دستی (PowerShell)**
```powershell
# در PowerShell با دسترسی Administrator
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
# سپس فایل composer.phar را به PATH اضافه کنید یا به نام composer.exe نامگذاری کنید
```

**بررسی نصب:**
```powershell
composer --version
```

### 3. نصب MySQL 8.0
- دانلود از: https://dev.mysql.com/downloads/installer/
- یا استفاده از XAMPP/WAMP که شامل MySQL است
- ایجاد دیتابیس: `haadishop`

### 4. نصب Node.js 20+
- دانلود از: https://nodejs.org/
- بررسی: `node --version` و `npm --version`

---

## ساخت پروژه Laravel

بعد از نصب Composer، این دستورات را اجرا کنید:

```powershell
cd D:\Hadishop
composer create-project laravel/laravel haadishop
```

یا اگر Composer را به صورت محلی (composer.phar) نصب کرده‌اید:
```powershell
php composer.phar create-project laravel/laravel haadishop
```

---

## تنظیمات اولیه

### 1. کپی فایل .env
```powershell
cd D:\Hadishop\haadishop
copy .env.example .env
```

### 2. ویرایش .env
```env
APP_NAME="HaadiShop"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haadishop
DB_USERNAME=root
DB_PASSWORD=      # اگر خالی است، خالی بگذارید

CACHE_DRIVER=file    # برای شروع، بعداً Redis
QUEUE_CONNECTION=sync # برای شروع، بعداً rabbitmq
```

### 3. ساخت کلید اپلیکیشن
```powershell
php artisan key:generate
```

### 4. اجرای مایگریشن‌ها
```powershell
php artisan migrate
```

---

## ساخت دیتابیس از SQL

اگر می‌خواهید از فایل schema.sql استفاده کنید:

```powershell
# در MySQL Command Line یا phpMyAdmin
mysql -u root -p
CREATE DATABASE haadishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# سپس
mysql -u root -p haadishop < D:\Hadishop\database\schema.sql
```

---

## اجرای سرور توسعه

```powershell
cd D:\Hadishop\haadishop
php artisan serve
```

سپس در مرورگر: http://localhost:8000

---

## نصب وابستگی‌های NPM (برای فرانت)

```powershell
cd D:\Hadishop\haadishop
npm install
npm run dev
```

---

## نکات مهم

- اگر Composer نصب نیست، می‌توانید از روش Docker استفاده کنید (فایل docker-compose.yml موجود است)
- برای Redis، RabbitMQ، Elasticsearch در مراحل بعد نیاز دارید
- فعلاً با file-based cache و sync queue شروع می‌کنیم

---

## بعد از نصب Composer

بعد از نصب Composer، به من بگویید تا ادامه بدهم و مایگریشن‌ها و مدل‌ها را اضافه کنم.

