# Architecture Diagrams - Diagramas da Arquitetura

## 🏗️ Arquitetura Geral

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE (Browser)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│         React.js 18 (Vite) - Port 5173                         │
│         ├─ State Management (Redux Toolkit)                    │
│         ├─ HTTP Client (Axios)                                 │
│         ├─ Form Validation (Zod + React Hook Form)            │
│         └─ UI Components (Radix UI + TailwindCSS)             │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                      INTERNET / HTTPS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
├─ API Gateway / Reverse Proxy (Nginx)                          │
│  ├─ Rate Limiting                                              │
│  ├─ Load Balancing                                             │
│  ├─ SSL/TLS Termination                                        │
│  └─ CORS Handling                                              │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                    BACKEND (NestJS) - Port 3000                 │
│                                                                 │
│    ┌─────────────────────────────────────────────────────┐    │
│    │              API Layer (Controllers)                │    │
│    │  ├─ AuthController                                 │    │
│    │  ├─ ProductController                             │    │
│    │  ├─ OrderController                               │    │
│    │  ├─ PaymentController                             │    │
│    │  └─ AdminController                               │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │         Application Layer (Services)                │    │
│    │  ├─ AuthService (JWT, Passport)                   │    │
│    │  ├─ ProductService (CRUD, Search)                 │    │
│    │  ├─ OrderService (Order logic)                    │    │
│    │  ├─ PaymentService (Payment processing)           │    │
│    │  └─ CacheService (Redis operations)               │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │       Business Logic Layer (Use Cases)              │    │
│    │  ├─ CreateOrder                                    │    │
│    │  ├─ ProcessPayment                                │    │
│    │  ├─ ConfirmOrder                                  │    │
│    │  └─ GenerateInvoice                               │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│    ┌─────────────────────────────────────────────────────┐    │
│    │         Data Access Layer (Repositories)            │    │
│    │  ├─ UserRepository                                 │    │
│    │  ├─ ProductRepository                             │    │
│    │  ├─ OrderRepository                               │    │
│    │  └─ PaymentRepository                             │    │
│    └─────────────────────────────────────────────────────┘    │
│                        ↓                                        │
│             ┌──────────┴──────────┬──────────────┐             │
│             ↓                     ↓              ↓              │
│    ┌─────────────────┐  ┌──────────────────┐  ┌──────────┐  │
│    │  PostgreSQL 15  │  │   Redis 7        │  │ Stripe   │  │
│    │  (Primary DB)   │  │  (Cache/Sessions)│  │ (Payment)│  │
│    │  Port 5432      │  │  Port 6379       │  │  API     │  │
│    └─────────────────┘  └──────────────────┘  └──────────┘  │
│                                                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo de Autenticação

```
    ┌─────────────┐
    │   User      │
    │  (Browser)  │
    └──────┬──────┘
           │ 1. POST /auth/login
           │    {email, password}
           ↓
    ┌─────────────────────────────────┐
    │  AuthController                 │
    │  - Validate input (pipes)       │
    │  - Hash password check          │
    └──────┬──────────────────────────┘
           │ 2. Call AuthService
           ↓
    ┌─────────────────────────────────┐
    │  AuthService                    │
    │  - Find user                    │
    │  - Compare password (bcrypt)    │
    │  - Generate JWT tokens          │
    └──────┬──────────────────────────┘
           │ 3. Save refresh token
           ↓
    ┌─────────────────────────────────┐
    │  PostgreSQL                     │
    │  - user                         │
    │  - refresh_token                │
    └─────────────────────────────────┘
           │ 4. Return tokens
           ↓
    ┌─────────────────────────────────┐
    │  User receives:                 │
    │  - accessToken (15 min)         │
    │  - refreshToken (7 dias)        │
    │    in HttpOnly cookie           │
    └─────────────────────────────────┘
           │ 5. Store & use accessToken
           ↓
    ┌─────────────────────────────────┐
    │  Subsequent requests:           │
    │  Authorization: Bearer TOKEN    │
    │  JwtStrategy validates          │
    │  RolesGuard checks roles        │
    └─────────────────────────────────┘
```

---

## 🛒 Fluxo de Compra (Order)

```
    ┌──────────────────┐
    │  Frontend        │
    │  (Add to cart)   │
    └────────┬─────────┘
             │
             ↓
    ┌──────────────────────────────┐
    │ Redux (Cart Store)           │
    │ {product_id, qty, design_id} │
    └────────┬─────────────────────┘
             │
             ↓ User clicks "Checkout"
    ┌────────────────────────────────────────┐
    │  POST /api/v1/orders                   │
    │  {items: [{productId, qty, designId}]} │
    └────────┬───────────────────────────────┘
             │
             ↓
    ┌────────────────────────────────┐
    │  OrderController               │
    │  - Validate order items        │
    │  - Check stock availability    │
    └────────┬───────────────────────┘
             │
             ↓
    ┌────────────────────────────────┐
    │  OrderService                  │
    │  - Calculate total             │
    │  - Apply discounts             │
    │  - Calculate tax               │
    │  - Reserve inventory           │
    └────────┬───────────────────────┘
             │
             ├─→ Update PostgreSQL (Order + OrderItems)
             │
             ├─→ Reserve stock in Redis
             │
             ↓ Return order with total
    ┌────────────────────────────────┐
    │  Frontend                      │
    │  - Show checkout form          │
    │  - Enter shipping address      │
    │  - Display payment form        │
    └────────┬───────────────────────┘
             │
             ↓ User submits payment
    ┌────────────────────────────────────┐
    │  POST /api/v1/payments/confirm     │
    │  {orderId, paymentMethodId}        │
    └────────┬───────────────────────────┘
             │
             ↓
    ┌────────────────────────────────┐
    │  PaymentController             │
    │  - Validate order exists       │
    │  - Validate amount             │
    └────────┬───────────────────────┘
             │
             ↓
    ┌────────────────────────────────┐
    │  StripeService                 │
    │  - Create Payment Intent       │
    │  - Return client secret        │
    └────────┬───────────────────────┘
             │
             ├─→ Save in PostgreSQL (payments table)
             │
             ↓ Return client secret
    ┌────────────────────────────────┐
    │  Frontend                      │
    │  - Load Stripe.js              │
    │  - Display card form           │
    │  - User enters card details    │
    └────────┬───────────────────────┘
             │
             ↓ User submits card
    ┌────────────────────────────────┐
    │  Stripe (Secure)               │
    │  - Process payment             │
    │  - Return status               │
    └────────┬───────────────────────┘
             │
             ├─ Success? ✅
             │   │
             │   ↓ Webhook: payment_intent.succeeded
             │   ┌────────────────────────────────┐
             │   │  Webhook Handler               │
             │   │  - Verify signature            │
             │   │  - Update payment status       │
             │   │  - Update order status         │
             │   │  - Release reserved stock      │
             │   └────────┬───────────────────────┘
             │            │
             │            ├─→ Update PostgreSQL
             │            │
             │            ├─→ Update Redis (cache)
             │            │
             │            ↓
             │   ┌────────────────────────────────┐
             │   │  SendGrid / Email              │
             │   │  - Send order confirmation     │
             │   │  - Send invoice                │
             │   └────────────────────────────────┘
             │
             └─ Failed? ❌
                 │
                 ↓
                ┌──────────────────────────┐
                │  Release reserved stock  │
                │  Send error notification │
                └──────────────────────────┘
```

---

## 💾 Database Schema (Simplificado)

```
┌─────────────────────────┐
│        USERS            │
├─────────────────────────┤
│ id (PK)                 │
│ email (UNIQUE)          │
│ password_hash           │
│ role (ENUM)             │
│ created_at              │
└──────────┬──────────────┘
           │
           │ 1:N
           ├─────────────────────────────────────────┐
           │                                         │
    ┌──────▼──────────────┐           ┌─────────────▼────────┐
    │    ORDERS           │           │   SHOPPING_CARTS     │
    ├─────────────────────┤           ├──────────────────────┤
    │ id (PK)             │           │ id (PK)              │
    │ user_id (FK)        │           │ user_id (FK)         │
    │ total               │           │ status               │
    │ status (ENUM)       │           │ created_at           │
    │ payment_status      │           │ updated_at           │
    │ created_at          │           └──────────┬───────────┘
    └──────┬──────────────┘                      │
           │                                      │ 1:N
           │ 1:N                                  │
           │                                ┌─────▼─────────────┐
    ┌──────▼──────────────────────┐        │   CART_ITEMS      │
    │    ORDER_ITEMS              │        ├───────────────────┤
    ├─────────────────────────────┤        │ id (PK)           │
    │ id (PK)                     │        │ cart_id (FK)      │
    │ order_id (FK)               │        │ product_id (FK)   │
    │ product_id (FK)             │        │ quantity          │
    │ quantity                    │        │ customization_data│
    │ unit_price                  │        └───────────────────┘
    │ customization_data (JSON)   │
    └──────┬──────────────────────┘
           │
           └────────────────┬────────────────┬──────────────────┐
                           │                 │                  │
                ┌──────────▼─────┐ ┌──────────▼──────┐ ┌────────▼───────────┐
                │   PRODUCTS     │ │  PAYMENTS       │ │  PRODUCT_IMAGES    │
                ├────────────────┤ ├─────────────────┤ ├────────────────────┤
                │ id (PK)        │ │ id (PK)         │ │ id (PK)            │
                │ name           │ │ order_id (FK)   │ │ product_id (FK)    │
                │ price          │ │ stripe_id       │ │ image_url          │
                │ stock          │ │ status (ENUM)   │ │ is_primary         │
                │ category_id    │ │ created_at      │ │ sort_order         │
                └────────────────┘ └─────────────────┘ └────────────────────┘
                       │
                ┌──────▼──────────────┐
                │   CATEGORIES        │
                ├─────────────────────┤
                │ id (PK)             │
                │ name                │
                │ slug                │
                │ image_url           │
                └─────────────────────┘
```

---

## 🔐 Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    Client Request                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  HTTPS/TLS Encryption                       │
    │  - SSL Certificate                          │
    │  - Port 443                                 │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  CORS Validation                            │
    │  - Only allowed origins                     │
    │  - Credentials: true                        │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Rate Limiting                              │
    │  - Max 100 req/min per IP                   │
    │  - Throttle sensitive endpoints             │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Request Validation (Pipes)                 │
    │  - Zod schemas                              │
    │  - Data type validation                     │
    │  - Sanitization                             │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Authentication (Guards)                    │
    │  - JWT Token validation                     │
    │  - Token expiration check                   │
    │  - Signature verification                   │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Authorization (Guards)                     │
    │  - Role-based access control                │
    │  - Permission checks                        │
    │  - Resource ownership validation            │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Business Logic                             │
    │  - Entity validation                        │
    │  - Stock checks                             │
    │  - Amount verification                      │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Database (ORM)                             │
    │  - Parameterized queries                    │
    │  - SQL injection prevention                 │
    │  - Data encryption at rest                  │
    └──────────────────────┬──────────────────────┘
                           │
    ┌──────────────────────▼──────────────────────┐
    │  Logging & Monitoring                       │
    │  - Audit logs                               │
    │  - Alert on suspicious activity             │
    │  - Access logs                              │
    └──────────────────────────────────────────────┘
```

---

## 📊 Cache Strategy (Redis)

```
Request
   │
   ├─→ Cache Key: product:{productId}
   │    │
   │    ├─ HIT? ✅ Return cached data
   │    │
   │    └─ MISS? ❌
   │       │
   │       ├─→ Query Database
   │       │
   │       ├─→ Store in Cache
   │       │   ├─ TTL: 1 hour
   │       │   ├─ Pattern: product:{id}
   │       │   └─ Serialized JSON
   │       │
   │       └─→ Return data
   │
   ├─ Update Product?
   │   │
   │   ├─→ Update Database
   │   │
   │   ├─→ Invalidate Cache
   │   │   └─ DEL product:{id}
   │   │
   │   └─→ Return success
   │
   └─ Cache Expiration?
       │
       ├─→ Next request triggers refresh
       │
       └─→ New data fetched and cached
```

### Cache Keys

```
sessions:{sessionId}              → HttpOnly cookie, 7 dias
products:list:{page}              → Catálogo, 1 hora
products:search:{query}:{page}    → Resultados busca, 30 min
cart:{userId}                     → Carrinho, 24 horas
ratelimit:{userId}:{endpoint}     → Rate limit, 1 min
trending_products:{date}          → Trending, 1 dia
verify:{email}                    → Email verify token, 1 hora
```

---

## 🚀 CI/CD Pipeline

```
Git Push
   │
   ├─→ GitHub Actions Triggered
   │
   ├─────── Backend Pipeline ─────────┐
   │                                   │
   │  1. Checkout code                │
   │  2. Setup Node 20                │
   │  3. npm install                  │
   │  4. npm run lint                 │
   │  5. npm run build                │
   │  6. npm run test                 │
   │  7. npm run test:e2e             │
   │  8. Build Docker image           │
   │  9. Push to registry             │
   │  10. Deploy to staging           │
   │                                   │
   └──────────────┬────────────────────┘
                  │
   ┌──────────────▼───────────────────┐
   │ ✅ All tests pass?               │
   └──────────────┬───────────────────┘
                  │
                  ├─ YES ✅
                  │   │
                  │   ├─→ Create tag v1.2.3
                  │   │
                  │   ├─→ Mark PR ready to merge
                  │   │
                  │   └─→ Notify team
                  │
                  └─ NO ❌
                      │
                      ├─→ Mark PR with ❌
                      │
                      └─→ Notify developer
```

---

## 📱 Frontend Component Tree (Simplificado)

```
App
├─ Router
│  ├─ MainLayout
│  │  ├─ Header
│  │  │  ├─ Logo
│  │  │  ├─ Navigation
│  │  │  ├─ Cart Icon (with badge)
│  │  │  └─ User Menu
│  │  │
│  │  ├─ Home
│  │  │  ├─ Hero Banner
│  │  │  └─ Featured Products
│  │  │     └─ ProductCard[]
│  │  │
│  │  ├─ Products
│  │  │  ├─ ProductFilter
│  │  │  ├─ ProductSearch
│  │  │  └─ ProductGrid
│  │  │     └─ ProductCard[]
│  │  │
│  │  ├─ Cart
│  │  │  ├─ CartItems[]
│  │  │  └─ CartSummary
│  │  │     └─ Checkout Button
│  │  │
│  │  ├─ Checkout
│  │  │  ├─ AddressForm
│  │  │  ├─ PaymentForm (Stripe)
│  │  │  └─ OrderSummary
│  │  │
│  │  └─ Footer
│  │     ├─ Links
│  │     ├─ Social
│  │     └─ Newsletter
│  │
│  ├─ AuthLayout
│  │  ├─ Login
│  │  └─ Register
│  │
│  └─ AdminLayout
│     ├─ Dashboard
│     ├─ Products Manager
│     ├─ Orders Manager
│     └─ Users Manager
│
└─ Redux Provider
   └─ Store
      ├─ authSlice
      ├─ cartSlice
      ├─ productSlice
      ├─ filterSlice
      └─ uiSlice
```

---

**Última atualização**: Março 2026
