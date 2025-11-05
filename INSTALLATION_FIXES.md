# رفع مشکلات نصب

## مشکل 1: پکیج RabbitMQ

پکیج RabbitMQ از composer.json حذف شد چون نسخه 15.0 وجود ندارد. فعلاً از `database` queue استفاده می‌کنیم.

### برای فعال‌سازی RabbitMQ (اختیاری):

```bash
composer require vladimir-yuldashev/laravel-queue-rabbitmq:^14.0
```

**نکته:** برای RabbitMQ نیاز به extension `ext-sockets` دارید.

---

## مشکل 2: Extension Sockets

اگر می‌خواهید RabbitMQ را فعال کنید، باید extension `ext-sockets` را فعال کنید.

### در Windows (XAMPP/WAMP):

1. فایل `php.ini` را باز کنید (معمولاً در `C:\xampp\php\php.ini` یا `C:\wamp64\bin\php\php8.2\php.ini`)
2. خط زیر را پیدا کنید:
   ```ini
   ;extension=sockets
   ```
3. نقطه‌ویرگول را حذف کنید:
   ```ini
   extension=sockets
   ```
4. PHP را restart کنید

### بررسی:

```bash
php -m | findstr sockets
```

اگر `sockets` را دیدید، extension فعال است.

---

## راه‌حل فعلی (بدون RabbitMQ)

فعلاً از `database` queue استفاده می‌کنیم که نیاز به extension ندارد:

```env
QUEUE_CONNECTION=database
```

سپس migration را اجرا کنید:

```bash
php artisan migrate
```

---

## بعد از رفع مشکلات

```bash
composer install
php artisan migrate
php artisan db:seed
```

