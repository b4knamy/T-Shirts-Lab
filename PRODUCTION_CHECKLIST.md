# Production Checklist — T-Shirts Lab

Run through every item on this list before deploying to **any** production environment.
Check each box only after you have verified it, not just set it.

---

## 1. Environment Variables

Copy `.env.example` to `.env` and fill in every value below.
Never commit `.env` to version control.

```env
# Application
APP_NAME="T-Shirts Lab"
APP_ENV=production
APP_KEY=                        # Generate: php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
FRONTEND_URL=https://yourdomain.com

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres                # Docker service name, or real host
DB_PORT=5432
DB_DATABASE=tshirtslab
DB_USERNAME=
DB_PASSWORD=

# Redis
REDIS_HOST=redis                # Docker service name, or real host
REDIS_PORT=6379
REDIS_PASSWORD=null

# Cache / Session / Queue
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database       # Switch to redis in high-traffic setups

# JWT
JWT_SECRET=                     # Generate: php artisan jwt:secret

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Mail (transactional)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="T-Shirts Lab"

# Logging
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning               # Never use 'debug' in production
```

---

## 2. Secret Generation

Run these commands **once** per environment. Never reuse secrets across environments.

```bash
# Application encryption key
php artisan key:generate --force

# JWT secret
php artisan jwt:secret --force
```

> ⚠️ Rotating `APP_KEY` will invalidate all encrypted data (sessions, cookies).
> Rotating `JWT_SECRET` will invalidate all active user tokens (force re-login).

---

## 3. Database

```bash
# Run all migrations
php artisan migrate --force

# Confirm the failed_jobs table exists (required for queue driver=database)
php artisan queue:failed-table   # only if migration doesn't exist yet
php artisan migrate --force

# Seed only if this is a fresh environment that needs baseline data
php artisan db:seed --force
```

Confirm these tables exist after migration:
- `users`, `products`, `product_images`, `categories`
- `orders`, `order_items`, `payments`
- `coupons`, `coupon_usages`
- `product_reviews`, `user_addresses`, `designs`
- `cache`, `failed_jobs`

---

## 4. Caches & Optimisation

Run in this exact order — wrong order causes stale config/route caches.

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

composer dump-autoload --optimize --classmap-authoritative --no-dev
```

---

## 5. Storage & Permissions

```bash
# Create the public storage symlink (required for uploaded images)
php artisan storage:link

# Fix permissions (run as the user that owns the app files)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 6. Queue Worker (Supervisor)

The `supervisord.conf` currently runs only `php-fpm` and `nginx`.
If you use `QUEUE_CONNECTION=database` (or redis), add a queue worker:

```ini
[program:laravel-worker]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

Verify it's running:
```bash
supervisorctl status
```

---

## 7. Scheduler (Cron)

If any `artisan schedule` commands are defined, add a cron inside the container
or on the host:

```cron
* * * * * www-data php /var/www/html/artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Stripe Webhook

1. In the [Stripe Dashboard](https://dashboard.stripe.com/webhooks) create a new endpoint:
   `https://api.yourdomain.com/api/webhooks/stripe`

2. Subscribe to at minimum:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

3. Copy the **Signing Secret** → paste into `STRIPE_WEBHOOK_SECRET` in `.env`.

4. Test the webhook with:
   ```bash
   stripe listen --forward-to https://api.yourdomain.com/api/webhooks/stripe
   stripe trigger payment_intent.succeeded
   ```

---

## 9. CORS

Confirm `FRONTEND_URL` in `.env` matches the **exact** origin the browser sends
(scheme + domain + port, no trailing slash).

```bash
# Quick sanity check from the server
curl -I -X OPTIONS https://api.yourdomain.com/api/v1/health \
  -H "Origin: https://yourdomain.com" \
  -H "Access-Control-Request-Method: GET"
# Must return: Access-Control-Allow-Origin: https://yourdomain.com
```

---

## 10. Security Headers

Nginx already sets:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`

Before launch also add to `nginx.conf`:
```nginx
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
add_header Content-Security-Policy "default-src 'self'" always;
```

Validate with [securityheaders.com](https://securityheaders.com).

---

## 11. Rate Limiting

Current limits (confirm they match business requirements):

| Route | Limit |
|---|---|
| All `/api/v1/*` | 60 req / min per IP |
| `POST /auth/login` | 10 req / min per IP |
| `POST /auth/register` | 10 req / min per IP |
| `POST /auth/refresh` | 20 req / min per IP |

---

## 12. Logging

- `LOG_LEVEL=warning` — never `debug` in production (leaks sensitive request data).
- Logs are mounted to `./backend/storage/logs` via Docker volume.
- If using the Loki/Grafana stack (`docker-compose.logging.yml`), confirm `promtail` is scraping the correct path.

---

## 13. Health Check

```bash
curl https://api.yourdomain.com/up
# Expected: HTTP 200 {"status": "ok"}

curl https://api.yourdomain.com/api/v1/health
# Expected: HTTP 200 with db + redis status
```

---

## 14. Pre-Launch Test Run

```bash
# On the CI / staging server (not production DB)
composer test

# Must show: 0 failed
```

---

## 15. Known Gaps (implement before full public launch)

These items were identified during audit and are **not yet implemented**:

- [ ] **Password reset flow** — no `forgot-password` / `reset-password` endpoints
- [ ] **Transactional emails** — no order confirmation, payment receipt, or status change emails
- [ ] **Account deletion** — no `DELETE /users/me` (required for LGPD/GDPR)
- [ ] **Stock race condition** — `OrderService::createOrder` needs `lockForUpdate()` on stock check
- [ ] **Tax calculation** — `tax_amount` is hardcoded to `0`
- [ ] **Queue worker in supervisord** — `queue:work` not yet in `supervisord.conf`
- [ ] **Nginx hardened headers** — `Referrer-Policy`, `Permissions-Policy`, `CSP` not yet set

---

## Quick Reference Commands

```bash
# Start full stack
docker compose up -d

# Run migrations inside the running container
docker exec tshirtslab-backend php artisan migrate --force

# Clear all caches inside the running container
docker exec tshirtslab-backend php artisan optimize:clear

# Re-cache after a config change
docker exec tshirtslab-backend php artisan optimize

# Tail application logs
docker exec tshirtslab-backend tail -f storage/logs/laravel.log

# Check queue failed jobs
docker exec tshirtslab-backend php artisan queue:failed
```
