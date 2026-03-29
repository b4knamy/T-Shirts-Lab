# Architecture Diagrams - Diagramas da Arquitetura

## 🏗️ Arquitetura Geral

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE (Browser)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│         React.js 18 (Vite 8) - Port 5173                      │
│         ├─ State Management (Redux Toolkit)                    │
│         ├─ HTTP Client (Axios)                                 │
│         ├─ Form Validation (Zod + React Hook Form)            │
│         └─ UI Components (TailwindCSS v4)                     │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                      INTERNET / HTTPS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│         BACKEND (Laravel 13 + PHP 8.4) - Port 8000            │
│                                                                 │
│    ┌─────────────────────────────────────────────────────┐    │
│    │              Routes (api.php)                        │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │         Middleware Layer                             │    │
│    │  ├─ JwtAuthenticate (JWT validation)              │    │
│    │  ├─ AdminMiddleware (RBAC)                        │    │
│    │  └─ RateLimiter (60 req/min)                      │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │         Controllers (Api/V1/*)                      │    │
│    │  ├─ AuthController                                 │    │
│    │  ├─ UserController                                │    │
│    │  ├─ ProductController                             │    │
│    │  ├─ OrderController                               │    │
│    │  ├─ PaymentController                             │    │
│    │  ├─ WebhookController                             │    │
│    │  └─ HealthController                              │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │         Eloquent Models (Active Record)             │    │
│    │  ├─ User, Category, Product                       │    │
│    │  ├─ ProductImage, Design                          │    │
│    │  ├─ Order, OrderItem                              │    │
│    │  ├─ Payment, UserAddress                          │    │
│    │  └─ Traits: ApiResponse, HasUuids, SoftDeletes   │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌──────────────────┐  ┌──────────────────┐                 │
│    │   PostgreSQL 15  │  │     Redis 7      │                 │
│    │   (Database)     │  │  (Cache/Session) │                 │
│    │   Port: 5432     │  │  Port: 6379      │                 │
│    └──────────────────┘  └──────────────────┘                 │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                     EXTERNAL SERVICES                           │
│    ┌──────────────────┐                                        │
│    │   Stripe API     │  (Payment Processing)                  │
│    │   + Webhooks     │                                        │
│    └──────────────────┘                                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔐 Authentication Flow

```
┌──────────┐         ┌──────────┐         ┌──────────┐
│  Client  │         │  Laravel │         │ Database │
└────┬─────┘         └────┬─────┘         └────┬─────┘
     │                    │                    │
     │ POST /auth/login   │                    │
     │ {email, password}  │                    │
     │──────────────────→│                    │
     │                    │ Find user by email │
     │                    │──────────────────→│
     │                    │←──────────────────│
     │                    │                    │
     │                    │ Verify password    │
     │                    │ (bcrypt)           │
     │                    │                    │
     │                    │ Generate JWT       │
     │                    │ (access + refresh) │
     │                    │                    │
     │ {accessToken,      │                    │
     │  refreshToken,     │                    │
     │  user}             │                    │
     │←──────────────────│                    │
     │                    │                    │
     │ GET /products      │                    │
     │ Auth: Bearer xxx   │                    │
     │──────────────────→│                    │
     │                    │ JwtAuthenticate    │
     │                    │ middleware         │
     │                    │ validates token    │
     │                    │                    │
     │ {products: [...]}  │                    │
     │←──────────────────│                    │
     │                    │                    │
     │ Token expired!     │                    │
     │ POST /auth/refresh │                    │
     │ Auth: Bearer rfx   │                    │
     │──────────────────→│                    │
     │                    │ Validate refresh   │
     │                    │ Generate new pair  │
     │ {newAccessToken,   │                    │
     │  newRefreshToken}  │                    │
     │←──────────────────│                    │
```

---

## 💳 Payment Flow

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│  Client  │    │  Laravel │    │  Stripe  │    │ Database │
└────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘
     │               │               │               │
     │ POST /orders  │               │               │
     │ {items:[...]} │               │               │
     │─────────────→│               │               │
     │               │ Create order  │               │
     │               │─────────────────────────────→│
     │ {order}       │               │               │
     │←─────────────│               │               │
     │               │               │               │
     │ POST /payments│               │               │
     │ /create-intent│               │               │
     │─────────────→│               │               │
     │               │ PaymentIntent │               │
     │               │ ::create()   │               │
     │               │─────────────→│               │
     │               │ clientSecret  │               │
     │               │←─────────────│               │
     │               │               │               │
     │               │ Save payment  │               │
     │               │─────────────────────────────→│
     │ {clientSecret}│               │               │
     │←─────────────│               │               │
     │               │               │               │
     │ confirmPayment│               │               │
     │ (Stripe.js)  │               │               │
     │─────────────────────────────→│               │
     │               │               │               │
     │               │   Webhook     │               │
     │               │   payment_    │               │
     │               │   intent.     │               │
     │               │   succeeded   │               │
     │               │←─────────────│               │
     │               │               │               │
     │               │ Update order  │               │
     │               │ + payment     │               │
     │               │─────────────────────────────→│
     │               │               │               │
```

---

## 📊 Database Entity Relationship

```
┌───────────────────┐
│      users        │
│───────────────────│
│ id (UUID, PK)     │
│ first_name        │
│ last_name         │
│ email (unique)    │
│ password          │
│ role (enum)       │
│ is_active         │
│ timestamps        │
└─────┬──────┬──────┘
      │      │
      │      │ hasMany
      │      ↓
      │  ┌───────────────────┐
      │  │  user_addresses   │
      │  │───────────────────│
      │  │ id (UUID, PK)     │
      │  │ user_id (FK)      │
      │  │ address_line1     │
      │  │ city, state, zip  │
      │  │ is_default        │
      │  └───────────────────┘
      │
      │ hasMany
      ↓
┌───────────────────┐        ┌───────────────────┐
│     orders        │        │    categories     │
│───────────────────│        │───────────────────│
│ id (UUID, PK)     │        │ id (UUID, PK)     │
│ user_id (FK)      │        │ name              │
│ order_number      │        │ slug (unique)     │
│ subtotal, total   │        │ is_active         │
│ status (enum)     │        └─────┬─────────────┘
│ payment_status    │              │
│ timestamps        │              │ hasMany
└─────┬──────┬──────┘              ↓
      │      │            ┌───────────────────┐
      │      │            │     products      │
      │      │            │───────────────────│
      │      │            │ id (UUID, PK)     │
      │      │            │ sku (unique)      │
      │      │            │ name, slug        │
      │      │            │ price             │
      │      │            │ category_id (FK)  │
      │      │            │ status (enum)     │
      │      │            │ is_featured       │
      │      │            └──┬─────────┬──────┘
      │      │               │         │
      │      │    hasMany    │         │ hasMany
      │      │               ↓         ↓
      │      │     ┌──────────────┐ ┌──────────┐
      │      │     │product_images│ │ designs  │
      │      │     │──────────────│ │──────────│
      │      │     │ image_url    │ │ name     │
      │      │     │ is_primary   │ │ image_url│
      │      │     │ sort_order   │ │ category │
      │      │     └──────────────┘ └──────────┘
      │      │
      │      │ hasOne
      │      ↓
      │  ┌───────────────────┐
      │  │    payments       │
      │  │───────────────────│
      │  │ id (UUID, PK)     │
      │  │ order_id (FK)     │
      │  │ stripe_pi_id      │
      │  │ amount, currency  │
      │  │ status (enum)     │
      │  │ paid_at           │
      │  └───────────────────┘
      │
      │ hasMany
      ↓
┌───────────────────┐
│   order_items     │
│───────────────────│
│ id (UUID, PK)     │
│ order_id (FK)     │
│ product_id (FK)   │
│ design_id (FK?)   │
│ quantity          │
│ unit_price        │
│ total_price       │
│ customization_data│
└───────────────────┘
```

---

## 🐳 Docker Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Docker Compose                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────────┐  ┌─────────────────────┐         │
│  │    PostgreSQL 15     │  │      Redis 7        │         │
│  │    :5432             │  │      :6379          │         │
│  │    postgres_data vol │  │      redis_data vol │         │
│  └──────────┬──────────┘  └──────────┬──────────┘         │
│             │                        │                     │
│             └────────┬───────────────┘                     │
│                      │                                     │
│  ┌───────────────────┴─────────────────────────┐          │
│  │         Backend Container :8000              │          │
│  │  ┌──────────────────────────────────────┐   │          │
│  │  │  Supervisor                          │   │          │
│  │  │  ├─ PHP 8.4-FPM (FastCGI :9000)    │   │          │
│  │  │  └─ Nginx (HTTP :8000)              │   │          │
│  │  └──────────────────────────────────────┘   │          │
│  │  Laravel 13 Application                     │          │
│  │  /var/www/html                              │          │
│  └─────────────────────────────────────────────┘          │
│                                                             │
│  ┌─────────────────────────────────────────────┐          │
│  │         Frontend Container :5173             │          │
│  │  React 18 + Vite 8 (Dev Server)            │          │
│  └─────────────────────────────────────────────┘          │
│                                                             │
│              Network: app-network (bridge)                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛣️ Request Lifecycle (Laravel)

```
HTTP Request
    │
    ↓
┌─────────────────┐
│   Nginx         │  (port 8000)
│   (web server)  │  Serve static files
└────────┬────────┘  or forward to PHP-FPM
         │
         ↓
┌─────────────────┐
│   PHP-FPM       │  (port 9000)
│   (FastCGI)     │  Execute PHP
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  public/        │
│  index.php      │  Bootstrap Laravel
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  bootstrap/     │
│  app.php        │  Configure middleware, routes
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Global         │
│  Middleware      │  CORS, Rate Limiting
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Route          │
│  Resolution     │  Match URL to controller action
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Route          │
│  Middleware      │  jwt.auth, admin
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Controller     │
│  Method         │  Business logic
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  Eloquent       │
│  Model/Query    │  Database interaction
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  ApiResponse    │
│  Trait          │  Format JSON response
└────────┬────────┘
         │
         ↓
HTTP Response (JSON)
```

---

## 📡 API Route Map

```
/api
├── /v1
│   ├── /auth
│   │   ├── POST /register        (public)
│   │   ├── POST /login           (public)
│   │   ├── POST /refresh         (jwt.auth)
│   │   └── POST /logout          (jwt.auth)
│   │
│   ├── /users
│   │   ├── GET  /me              (jwt.auth)
│   │   └── PATCH /me             (jwt.auth)
│   │
│   ├── /products
│   │   ├── GET  /                (public)
│   │   ├── GET  /featured        (public)
│   │   ├── GET  /categories      (public)
│   │   ├── GET  /slug/{slug}     (public)
│   │   ├── GET  /{id}            (public)
│   │   ├── POST /                (jwt.auth + admin)
│   │   ├── PATCH /{id}           (jwt.auth + admin)
│   │   └── DELETE /{id}          (jwt.auth + admin)
│   │
│   ├── /orders
│   │   ├── POST /                (jwt.auth)
│   │   ├── GET  /my-orders       (jwt.auth)
│   │   ├── GET  /{id}            (jwt.auth)
│   │   ├── GET  /                (jwt.auth + admin)
│   │   └── PATCH /{id}/status    (jwt.auth + admin)
│   │
│   ├── /payments
│   │   ├── POST /create-intent   (jwt.auth)
│   │   ├── POST /confirm         (jwt.auth)
│   │   ├── GET  /{piId}          (jwt.auth)
│   │   └── POST /refund          (jwt.auth + admin)
│   │
│   └── /health
│       └── GET  /                (public)
│
└── /webhooks
    └── POST /stripe              (public, signature verified)
```

---

**Backend**: Laravel 13 (PHP 8.4) | Port 8000
**Frontend**: React 18 (Vite 8) | Port 5173

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
