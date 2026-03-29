# DevOps, Deployment & Infrastructure

## 🐳 Docker & Containerization

### Docker Architecture

```
┌─────────────────────────────────────────────────────┐
│              DOCKER COMPOSE SETUP                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Container 1: PostgreSQL 15                        │
│  ├─ Port: 5432                                      │
│  ├─ Volume: postgres_data                           │
│  └─ Network: app-network                            │
│                                                     │
│  Container 2: Redis 7                              │
│  ├─ Port: 6379                                      │
│  ├─ Volume: redis_data                              │
│  └─ Network: app-network                            │
│                                                     │
│  Container 3: Backend (Laravel + PHP-FPM + Nginx)  │
│  ├─ Port: 8000                                      │
│  ├─ Depends: PostgreSQL, Redis                     │
│  └─ Network: app-network                            │
│                                                     │
│  Container 4: Frontend (React + Vite)              │
│  ├─ Port: 5173                                      │
│  ├─ Depends: Backend                               │
│  └─ Network: app-network                            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Backend Dockerfile (Multi-stage)

```dockerfile
# backend/Dockerfile

# Stage 1: Composer dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Stage 2: Production image
FROM php:8.4-fpm-alpine

# Install system deps
RUN apk add --no-cache \
    nginx supervisor \
    postgresql-dev libpq \
    redis icu-dev \
    && docker-php-ext-install pdo pdo_pgsql opcache intl bcmath \
    && pecl install redis && docker-php-ext-enable redis

# Copy configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf

# App setup
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
```

### Docker Support Files

#### Nginx Config (docker/nginx.conf)
```nginx
server {
    listen 8000;
    server_name _;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### PHP Config (docker/php.ini)
```ini
[PHP]
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
max_execution_time = 30

[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

#### Supervisor Config (docker/supervisord.conf)
```ini
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true

[program:nginx]
command=nginx -g 'daemon off;'
autostart=true
autorestart=true
```

---

## 🐙 Docker Compose

```yaml
# docker-compose.yml
services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: tshirtslab_db
      POSTGRES_USER: tshirtslab
      POSTGRES_PASSWORD: tshirtslab_secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - app-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U tshirtslab"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      APP_KEY: ${APP_KEY}
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: tshirtslab_db
      DB_USERNAME: tshirtslab
      DB_PASSWORD: tshirtslab_secret
      REDIS_HOST: redis
      REDIS_PORT: 6379
      REDIS_CLIENT: predis
      CACHE_STORE: redis
      SESSION_DRIVER: redis
      QUEUE_CONNECTION: redis
      JWT_SECRET: ${JWT_SECRET}
      STRIPE_SECRET_KEY: ${STRIPE_SECRET_KEY}
      STRIPE_WEBHOOK_SECRET: ${STRIPE_WEBHOOK_SECRET}
      FRONTEND_URL: http://localhost:5173
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - app-network

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    ports:
      - "5173:5173"
    environment:
      VITE_API_BASE_URL: http://localhost:8000
    depends_on:
      - backend
    networks:
      - app-network

volumes:
  postgres_data:
  redis_data:

networks:
  app-network:
    driver: bridge
```

---

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  # Backend Tests
  backend-test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: test_db
          POSTGRES_USER: test_user
          POSTGRES_PASSWORD: test_pass
        ports: ["5432:5432"]
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

      redis:
        image: redis:7
        ports: ["6379:6379"]
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pdo_pgsql, redis, bcmath, intl
          coverage: xdebug

      - name: Install Composer Dependencies
        working-directory: ./backend
        run: composer install --no-interaction --prefer-dist

      - name: Setup Environment
        working-directory: ./backend
        run: |
          cp .env.example .env
          php artisan key:generate
          php artisan jwt:secret
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: test_db
          DB_USERNAME: test_user
          DB_PASSWORD: test_pass
          REDIS_HOST: 127.0.0.1

      - name: Run Migrations
        working-directory: ./backend
        run: php artisan migrate --force

      - name: Run Tests
        working-directory: ./backend
        run: php artisan test --coverage

  # Frontend Tests
  frontend-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "20"
          cache: "npm"
          cache-dependency-path: frontend/package-lock.json

      - name: Install Dependencies
        working-directory: ./frontend
        run: npm ci

      - name: Type Check
        working-directory: ./frontend
        run: npx tsc --noEmit

      - name: Lint
        working-directory: ./frontend
        run: npm run lint

      - name: Build
        working-directory: ./frontend
        run: npm run build

  # Docker Build
  docker-build:
    runs-on: ubuntu-latest
    needs: [backend-test, frontend-test]
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4

      - name: Build Backend Image
        run: docker build -t tshirtslab-backend ./backend

      - name: Build Frontend Image
        run: docker build -t tshirtslab-frontend ./frontend
```

---

## 🌐 Production Deployment

### Environment Variables (Production)

```env
# Backend Production
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://api.tshirtslab.com

DB_CONNECTION=pgsql
DB_HOST=prod-db-host
DB_PORT=5432
DB_DATABASE=tshirtslab_prod
DB_USERNAME=prod_user
DB_PASSWORD=<strong-password>

REDIS_HOST=prod-redis-host
REDIS_PORT=6379
REDIS_PASSWORD=<redis-password>
REDIS_CLIENT=predis

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

JWT_SECRET=<production-jwt-secret>
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_live_...

FRONTEND_URL=https://tshirtslab.com
```

### Production Optimization (Laravel)
```bash
# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Composer autoload optimization
composer install --no-dev --optimize-autoloader
```

### Nginx Reverse Proxy (Production)
```nginx
# Production Nginx config
server {
    listen 443 ssl http2;
    server_name api.tshirtslab.com;

    ssl_certificate /etc/ssl/certs/tshirtslab.crt;
    ssl_certificate_key /etc/ssl/private/tshirtslab.key;

    client_max_body_size 20M;

    location / {
        proxy_pass http://backend:8000\;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

server {
    listen 443 ssl http2;
    server_name tshirtslab.com;

    ssl_certificate /etc/ssl/certs/tshirtslab.crt;
    ssl_certificate_key /etc/ssl/private/tshirtslab.key;

    location / {
        proxy_pass http://frontend:5173\;
        proxy_set_header Host $host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

---

## 📊 Monitoring & Logging

### Laravel Logging
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'stderr'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'days' => 14,
    ],
],
```

### Health Check Endpoint
```
GET /api/v1/health

Response:
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2026-03-15T10:00:00Z",
    "services": {
      "database": "connected",
      "redis": "connected",
      "stripe": "configured"
    }
  }
}
```

### Docker Logging
```bash
# Ver logs
docker-compose logs -f backend
docker-compose logs -f --tail=100 backend

# Log files dentro do container
docker-compose exec backend tail -f storage/logs/laravel.log
```

---

## 🔒 Security Hardening

### Production Checklist
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS obrigatório
- [ ] Strong `APP_KEY` e `JWT_SECRET`
- [ ] Database password forte
- [ ] Redis password configurado
- [ ] CORS restrito a domínios de produção
- [ ] Rate limiting ativo
- [ ] Logs monitorados
- [ ] Backups de database configurados
- [ ] Stripe webhook secret de produção
- [ ] Headers de segurança (X-Frame-Options, CSP, etc.)

### Laravel Security Headers (Middleware)
```php
// Adicionar em bootstrap/app.php ou middleware personalizado
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('X-XSS-Protection', '1; mode=block');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
```

---

## 📁 Infrastructure Files

```
tshirtslab/
├── docker-compose.yml            # Dev environment
├── .github/
│   └── workflows/
│       └── ci.yml                # CI/CD pipeline
│
├── backend/
│   ├── Dockerfile                # Multi-stage PHP build
│   └── docker/
│       ├── nginx.conf            # Nginx config (port 8000)
│       ├── php.ini               # PHP optimization
│       └── supervisord.conf      # Process manager
│
└── frontend/
    └── Dockerfile                # React build
```

---

**Runtime**: PHP 8.4-FPM Alpine + Nginx + Supervisor
**Containers**: Docker Compose (4 services)
**CI/CD**: GitHub Actions

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
