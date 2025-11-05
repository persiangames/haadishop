# Infrastructure Setup Guide

## Docker Services

### Services Overview

1. **app** - PHP-FPM Application
2. **nginx** - Web Server
3. **mysql** - Database
4. **redis** - Cache & Queue
5. **rabbitmq** - Message Queue
6. **elasticsearch** - Search Engine
7. **mailhog** - Email Testing
8. **minio** - Object Storage (S3-compatible)

---

## Setup Instructions

### 1. Start All Services

```bash
cd D:\Hadishop\haadishop
docker compose up -d
```

### 2. Install Dependencies

```bash
docker compose exec app composer install
docker compose exec app npm install
```

### 3. Setup Environment

```bash
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
```

### 4. Update .env File

```env
APP_NAME="HaadiShop"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=haadishop
DB_USERNAME=haadi
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

ELASTICSEARCH_HOST=http://elasticsearch:9200
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_INDEX_PREFIX=haadishop

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 5. Run Migrations

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### 6. Index Products in Elasticsearch

```bash
docker compose exec app php artisan search:reindex-products
```

---

## Service URLs

- **Application**: http://localhost:8000
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)
- **Mailhog UI**: http://localhost:8025
- **MinIO Console**: http://localhost:9001 (admin/adminadmin)
- **Elasticsearch**: http://localhost:9200

---

## Redis Configuration

### Cache

```php
// استفاده از Redis برای Cache
Cache::put('key', 'value', 3600);
Cache::get('key');
```

### Session

```env
SESSION_DRIVER=redis
```

---

## RabbitMQ Configuration

### Queue Jobs

```php
// Dispatch job to RabbitMQ
dispatch(new \App\Jobs\AutoDrawLottery($lotteryId));
```

### Queue Workers

```bash
docker compose exec app php artisan queue:work rabbitmq
```

---

## Elasticsearch Configuration

### Index Product

```php
// Index کردن محصول
$searchService->indexProduct($product);
```

### Search Products

```php
// جستجوی محصولات
$result = $searchService->searchProducts('laptop', [], 1, 15);
```

### Reindex All Products

```bash
php artisan search:reindex-products
```

---

## Commands

### Artisan Commands

```bash
# Check lottery draws
php artisan lottery:check-draws

# Check inventory alerts
php artisan inventory:check-alerts

# Update exchange rates
php artisan currency:update-rates

# Reindex products
php artisan search:reindex-products

# Queue worker
php artisan queue:work rabbitmq
```

---

## Scheduled Tasks

### Cron Jobs

```bash
# در crontab یا Docker
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### Laravel Scheduler

```php
// app/Console/Kernel.php
$schedule->command('lottery:check-draws')->everyFiveMinutes();
$schedule->command('inventory:check-alerts')->hourly();
$schedule->command('currency:update-rates')->everySixHours();
```

---

## Performance Optimization

### Redis Caching

- Cache نتایج Queryهای سنگین
- Cache API responses
- Cache recommendations
- Cache exchange rates

### Queue System

- پردازش پرداخت‌ها
- ارسال ایمیل‌ها
- Index کردن محصولات
- قرعه‌کشی خودکار

### Elasticsearch

- جستجوی پیشرفته محصولات
- فیلترهای پیچیده
- مرتب‌سازی بر اساس relevance

---

## Security Considerations

1. **Environment Variables**: هرگز `.env` را commit نکنید
2. **Database**: از password قوی استفاده کنید
3. **Redis**: در production از password استفاده کنید
4. **RabbitMQ**: در production از user/password قوی استفاده کنید
5. **Elasticsearch**: در production از security features استفاده کنید

---

## Monitoring

### Health Checks

```bash
# Application
curl http://localhost:8000/up

# Elasticsearch
curl http://localhost:9200/_cluster/health

# RabbitMQ
curl http://localhost:15672/api/overview
```

---

## Troubleshooting

### Redis Connection Issues

```bash
docker compose exec redis redis-cli ping
```

### RabbitMQ Connection Issues

```bash
docker compose exec rabbitmq rabbitmqctl status
```

### Elasticsearch Connection Issues

```bash
curl http://localhost:9200
```

---

## Production Deployment

### Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis
QUEUE_CONNECTION=rabbitmq
ELASTICSEARCH_ENABLED=true
```

### Optimizations

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

