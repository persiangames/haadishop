# راهنمای فعال‌سازی PHP Extensions

## مشکل: Extension `fileinfo` فعال نیست

### راه‌حل 1: فعال‌سازی Extension (توصیه می‌شود)

1. فایل `php.ini` را باز کنید:
   - مسیر: `C:\tools\php84\php.ini`

2. خط زیر را پیدا کنید:
   ```ini
   ;extension=fileinfo
   ```

3. نقطه‌ویرگول را حذف کنید:
   ```ini
   extension=fileinfo
   ```

4. فایل را ذخیره کنید

5. PHP را restart کنید (اگر XAMPP/WAMP استفاده می‌کنید، سرویس را restart کنید)

6. بررسی کنید:
   ```bash
   C:\tools\php84\php.exe -m | findstr fileinfo
   ```

اگر `fileinfo` را دیدید، فعال است!

---

### راه‌حل 2: Ignore کردن (موقت)

اگر نمی‌خواهید extension را فعال کنید، می‌توانید موقتاً ignore کنید:

```bash
composer install --ignore-platform-req=ext-fileinfo
```

**⚠️ هشدار:** این فقط برای شروع است. در production حتماً extension را فعال کنید.

---

## Extensionهای مورد نیاز Laravel

برای Laravel 11، این extensionها باید فعال باشند:

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

### بررسی Extensionهای فعال:

```bash
C:\tools\php84\php.exe -m
```

---

## راهنمای سریع

### در XAMPP:
1. XAMPP Control Panel را باز کنید
2. Apache را Stop کنید
3. فایل `C:\xampp\php\php.ini` را باز کنید
4. `extension=fileinfo` را پیدا و فعال کنید
5. Apache را Start کنید

### در WAMP:
1. روی آیکون WAMP کلیک کنید
2. PHP → PHP Extensions
3. `fileinfo` را تیک بزنید
4. Restart All Services

### در PHP Standalone:
1. فایل `C:\tools\php84\php.ini` را باز کنید
2. `extension=fileinfo` را پیدا کنید
3. نقطه‌ویرگول را حذف کنید
4. فایل را ذخیره کنید

---

## بعد از فعال‌سازی

```bash
composer install
```

باید بدون خطا اجرا شود!

