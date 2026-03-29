# Quick Reference - T-Shirts Lab

## 🚀 TL;DR

**O que é?** E-commerce de camisetas personalizadas
**Stack:** Laravel 13 (PHP) + React 18 + PostgreSQL 15 + Redis 7 + Stripe
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
| Admin | admin@tshirtslab.com | Admin@123 |
| Customer | customer@tshirtslab.com | Customer@123 |

---

## �� Endpoints Principais

```
# Auth
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout

# Users
GET    /api/v1/users/me
PATCH  /api/v1/users/me

# Products
GET    /api/v1/products
GET    /api/v1/products/featured
GET    /api/v1/products/categories
GET    /api/v1/products/slug/{slug}
GET    /api/v1/products/{id}
POST   /api/v1/products                    # Admin
PATCH  /api/v1/products/{id}               # Admin
DELETE /api/v1/products/{id}               # Admin

# Orders
POST   /api/v1/orders
GET    /api/v1/orders/my-orders
GET    /api/v1/orders/{id}
GET    /api/v1/orders                      # Admin
PATCH  /api/v1/orders/{id}/status          # Admin

# Payments
POST   /api/v1/payments/create-intent
POST   /api/v1/payments/confirm
GET    /api/v1/payments/{paymentIntentId}
POST   /api/v1/payments/refund             # Admin

# Webhooks
POST   /api/webhooks/stripe
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
backend/                              # Laravel 13
├── app/
│   ├── Http/Controllers/Api/V1/     # Controllers
│   ├── Http/Middleware/             # JWT + Admin
│   ├── Models/                      # Eloquent Models
│   └── Traits/                      # ApiResponse
├── config/                          # Auth, JWT, CORS, Services
├── database/migrations/             # 10 migration files
├── routes/api.php                   # All API routes
└── .env

frontend/                             # React 18 + Vite
├── src/
│   ├── components/                  # Reusable components
│   ├── pages/                       # Page components
│   ├── store/                       # Redux
│   ├── services/                    # API calls
│   ├── hooks/                       # Custom hooks
│   └── main.tsx

docker-compose.yml                    # Local development
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
6. **camelCase** - API retorna camelCase para o frontend, Models usam snake_case

---

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
