# HaadiShop

A modular Laravel-based e-commerce platform with affiliate + lottery, recommendations, loyalty, multi-currency, multi-language, and modern infrastructure.

## Quick start (recommended - Docker)

1) Install prerequisites:
- Docker Desktop for Windows
- Git

2) Start core services (DB, Redis, RabbitMQ, Elasticsearch, Mailhog, MinIO):
```bash
# From D:\Hadishop
docker compose up -d
```

3) Create Laravel app (no local Composer needed):
```bash
# Creates the Laravel app into D:\Hadishop\haadishop
mkdir haadishop
# Use a temporary composer container to scaffold the app
docker run --rm -v "${PWD}\\haadishop:/app" -w /app composer:2 create-project laravel/laravel .
```

4) Enter the app container (optional, once we add app service) or use local PHP if installed.

5) Copy .env and set connection info:
```bash
cd haadishop
copy .env.example .env
# Update DB/Redis/RabbitMQ/MinIO/Mail credentials to match docker-compose
```

6) Generate key and run migrations:
```bash
php artisan key:generate
php artisan migrate
```

7) Run dev server (one of):
```bash
php artisan serve
# or with Node assets after install
npm install && npm run dev
```

## Native setup (alternative)
Install: PHP 8.2+, Composer 2, MySQL 8, Node.js 20+, Redis 7, RabbitMQ 3, Elasticsearch 8, Git.

## Services
- MySQL: localhost:3306 (root/secret, db: haadishop)
- Redis: localhost:6379
- RabbitMQ: AMQP 5672, UI http://localhost:15672
- Elasticsearch: http://localhost:9200
- Mailhog UI: http://localhost:8025
- MinIO: http://localhost:9000 (console http://localhost:9001)

## Next
Once the app is scaffolded, we will add migrations, models, and modules per the provided priority.


