# Quick Reference - T-Shirts Lab

## 🚀 TL;DR

**O que é?** E-commerce de camisetas personalizadas
**Stack:** Laravel 11 (PHP 8.4) + React 19 + PostgreSQL 15 + Redis 7 + Stripe
**Backend Port:** 8000 | **Frontend Port:** 5173

---

## ⚡ Setup Rápido

```bash
git clone https://github.com/b4knamy/tshirts-lab.git
cd tshirts-lab
docker-compose up -d
# Frontend: http://localhost:5173
# Backend API: http://localhost:8000
```

### Dev Local (sem Docker)
```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve --port=8000

# Frontend
cd frontend
npm install
npm run dev
```

---

## 🔑 Credenciais de Teste

| Tipo | Email | Senha |
|------|-------|-------|
| Super Admin | superadmin@tshirtslab.com | Super@123 |
| Admin | admin@tshirtslab.com | Admin@123 |
| Moderador | moderator@tshirtslab.com | Mod@123 |
| Customer | customer@tshirtslab.com | Customer@123 |

**Cupons:** `WELCOME10`, `FRETE0`, `SUPER25`, `VIP20`, `FLASH50`

---

## �� Endpoints Principais

```
# Auth
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout

# Users (Auth)
GET    /api/v1/users/me
PATCH  /api/v1/users/me
POST   /api/v1/users/me/avatar
GET    /api/v1/users/me/addresses
POST   /api/v1/users/me/addresses
PATCH  /api/v1/users/me/addresses/{id}
DELETE /api/v1/users/me/addresses/{id}

# Staff Management (Admin/SuperAdmin)
GET    /api/v1/users                       # Listar todos os usuários
POST   /api/v1/users                       # Criar staff (MODERATOR/ADMIN)
PATCH  /api/v1/users/{id}                  # Alterar role/status

# Products (público para leitura)
GET    /api/v1/products
GET    /api/v1/products/featured
GET    /api/v1/products/categories
GET    /api/v1/products/slug/{slug}
GET    /api/v1/products/{id}
POST   /api/v1/products                    # Admin
PATCH  /api/v1/products/{id}               # Admin
DELETE /api/v1/products/{id}               # Admin

# Product Images (Admin)
GET    /api/v1/products/{id}/images
POST   /api/v1/products/{id}/images
POST   /api/v1/products/{id}/images/upload
PATCH  /api/v1/products/{id}/images/{imageId}
DELETE /api/v1/products/{id}/images/{imageId}

# Categories (Admin)
GET    /api/v1/categories
POST   /api/v1/categories
PATCH  /api/v1/categories/{id}
DELETE /api/v1/categories/{id}

# Orders
POST   /api/v1/orders                      # { items, coupon_code?, customer_notes? }
GET    /api/v1/orders/my-orders
GET    /api/v1/orders/{id}
GET    /api/v1/orders                      # Admin
PATCH  /api/v1/orders/{id}/status          # Admin

# Payments
POST   /api/v1/payments/create-intent
POST   /api/v1/payments/confirm
GET    /api/v1/payments/{paymentIntentId}
POST   /api/v1/payments/refund             # Admin

# Coupons
GET    /api/v1/coupons/active              # Público
POST   /api/v1/coupons/validate            # Auth — { code, subtotal }
GET    /api/v1/coupons                     # Admin
POST   /api/v1/coupons                     # Admin
GET    /api/v1/coupons/{id}               # Admin
PATCH  /api/v1/coupons/{id}              # Admin
DELETE /api/v1/coupons/{id}             # Admin

# Reviews
GET    /api/v1/products/{id}/reviews       # Público
POST   /api/v1/products/{id}/reviews       # Auth
PATCH  /api/v1/reviews/{id}/reply          # Admin
DELETE /api/v1/reviews/{id}               # Admin

# Webhooks & Health
POST   /api/webhooks/stripe
GET    /api/v1/health
```

---

## 💾 Database (Essencial)

Principais tabelas:
- **users** - Contas + autenticação
- **categories** - Categorias de produtos
- **products** - Catálogo
- **product_images** - Imagens dos produtos
- **designs** - Estampas/designs
- **orders** - Pedidos
- **order_items** - Itens dos pedidos
- **payments** - Transações
- **user_addresses** - Endereços

Redis para:
- Sessions
- Cache de produtos (1h)
- Cache de categorias (24h)
- Rate limiting

---

## 🎨 Padrões Principais

| Padrão | Uso |
|--------|-----|
| MVC | Organização Laravel |
| Repository (Eloquent) | Data access |
| Trait (ApiResponse) | Respostas padronizadas |
| Middleware | Auth + Admin guards |
| UUID | Primary keys |

---

## 💳 Stripe Integration

### Flow
1. Frontend → Backend: `POST /api/v1/payments/create-intent`
2. Backend → Stripe: Create Payment Intent
3. Frontend: Display payment form
4. Stripe webhook → Backend: Confirm payment
5. Backend: Update order + send email

### Test Cards
- `4242 4242 4242 4242` - Success
- `4000 0000 0000 0002` - Declined

---

## 📁 Project Structure (Simplificado)

```
backend/                              # Laravel 11 (PHP 8.4)
├── app/
│   ├── Http/Controllers/Api/V1/     # 12 Controllers
│   ├── Http/Middleware/             # JWT + Admin
│   ├── Http/Requests/               # Form Requests (validação)
│   ├── Http/Resources/              # API Resources (snake_case)
│   ├── Models/                      # Eloquent Models (UUIDs)
│   ├── Services/                    # Lógica de negócio
│   ├── Repositories/                # Data access layer
│   └── Traits/                      # ApiResponse
├── config/                          # Auth, JWT, CORS, Services, Cache
├── database/
│   ├── migrations/                  # 14 migration files
│   ├── seeders/                     # 6 seeders (55 produtos, 24 users, 172 reviews)
│   └── factories/                   # Order, Payment, User, Coupon factories
├── routes/api.php                   # 50+ endpoints versionados
└── .env

frontend/                             # React 19 + Vite 6 + TypeScript 5.7
├── src/
│   ├── components/                  # Reusable components (Header, Cart, Reviews...)
│   ├── pages/                       # Page components + admin/
│   ├── store/                       # Redux Toolkit (auth, cart slices)
│   ├── services/api/                # Axios API clients por domínio
│   ├── hooks/                       # useAuth, useCart, useProducts...
│   ├── types/                       # TypeScript entities
│   └── main.tsx

docker-compose.yml                    # Desenvolvimento local
```

---

## 🔧 Configuração Mínima

### Backend `.env`
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tshirtslab_db
DB_USERNAME=tshirtslab
DB_PASSWORD=tshirtslab_secret
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
JWT_SECRET=your-secret
STRIPE_SECRET_KEY=sk_test_...
FRONTEND_URL=http://localhost:5173
```

### Frontend `.env`
```
VITE_API_BASE_URL=http://localhost:8000
```

---

## ⚠️ Gotchas Importantes

1. **JWT Tokens** - Access: 15min, Refresh: 7 dias
2. **Cache TTL** - Produtos: 1h, Categorias: 24h
3. **Stripe Webhooks** - Sempre validar assinatura
4. **CORS** - Configurado apenas para FRONTEND_URL
5. **UUIDs** - Todas as tabelas usam UUID como PK
6. **snake_case** - API retorna **snake_case** em todas as respostas (sem exceção)
7. **MODERATOR** - Acessa admin panel mas não pode gerenciar staff
8. **Hierarquia de roles**: SUPER_ADMIN > ADMIN > MODERATOR > CUSTOMER

---

**Versão**: Laravel 11 + React 19 | **Atualizado**: Abril 2026
