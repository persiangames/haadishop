# راهنمای سریع شروع

## مشکل نصب Composer?

### مشکل 1: Extension fileinfo فعال نیست

**راه‌حل سریع (موقت):**
```bash
composer install --ignore-platform-req=ext-fileinfo
```

**راه‌حل دائمی:**
1. فایل `C:\tools\php84\php.ini` را باز کنید
2. خط `;extension=fileinfo` را پیدا کنید
3. نقطه‌ویرگول را حذف کنید: `extension=fileinfo`
4. فایل را ذخیره کنید
5. دوباره `composer install` را اجرا کنید

---

## مراحل نصب کامل

### 1. فعال‌سازی Extensions (مهم!)

فایل `C:\tools\php84\php.ini` را باز کنید و این extensionها را فعال کنید:

```ini
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=tokenizer
extension=xml
extension=ctype
extension=json
extension=bcmath
extension=curl
extension=gd
extension=zip
```

### 2. نصب Dependencies

```bash
composer install
npm install
```

### 3. تنظیم .env

```bash
copy .env.example .env
```

فایل `.env` را باز کنید و تنظیم کنید:
```env
APP_NAME="HaadiShop"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haadishop
DB_USERNAME=root
DB_PASSWORD=      # اگر خالی است، خالی بگذارید

CACHE_STORE=file    # برای شروع
QUEUE_CONNECTION=database  # برای شروع
```

### 4. تولید کلید

```bash
php artisan key:generate
```

### 5. ایجاد دیتابیس

در MySQL:
```sql
CREATE DATABASE haadishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. اجرای مایگریشن‌ها

```bash
php artisan migrate
php artisan db:seed
```

### 7. اجرای سرور

```bash
php artisan serve
```

سپس: http://localhost:8000

---

## بررسی Extensions

```bash
.\check-php-extensions.ps1
```

یا دستی:
```bash
C:\tools\php84\php.exe -m | findstr fileinfo
```

---

## اگر هنوز مشکل دارید

1. مطمئن شوید PHP در PATH است یا از مسیر کامل استفاده کنید
2. Extensionهای مورد نیاز را فعال کنید
3. اگر نمی‌خواهید extensionها را فعال کنید، از `--ignore-platform-req` استفاده کنید

