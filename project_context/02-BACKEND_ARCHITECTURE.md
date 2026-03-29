# Arquitetura Backend - Laravel 13

## 🏗️ Stack Backend

| Tecnologia | Versão | Propósito |
|-----------|--------|-----------|
| Laravel | 13 | Framework PHP |
| PHP | 8.4 | Runtime |
| Eloquent ORM | - | ORM / Database |
| JWT Auth | 2.9 | Autenticação stateless |
| Stripe PHP SDK | 20 | Pagamentos |
| Predis | 3.4 | Redis client |
| PostgreSQL | 15 | Banco de dados |
| Redis | 7 | Cache & Sessions |

---

## 📂 Estrutura do Projeto Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/
│   │   │       ├── AuthController.php
│   │   │       ├── UserController.php
│   │   │       ├── ProductController.php
│   │   │       ├── OrderController.php
│   │   │       ├── PaymentController.php
│   │   │       ├── WebhookController.php
│   │   │       └── HealthController.php
│   │   └── Middleware/
│   │       ├── JwtAuthenticate.php
│   │       └── AdminMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── ProductImage.php
│   │   ├── Design.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Payment.php
│   │   └── UserAddress.php
│   └── Traits/
│       └── ApiResponse.php
├── bootstrap/
│   └── app.php
├── config/
│   ├── auth.php
│   ├── cors.php
│   ├── jwt.php
│   └── services.php
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_000001_create_users_table.php
│   │   ├── 2026_01_01_000002_create_categories_table.php
│   │   ├── 2026_01_01_000003_create_products_table.php
│   │   ├── 2026_01_01_000004_create_product_images_table.php
│   │   ├── 2026_01_01_000005_create_designs_table.php
│   │   ├── 2026_01_01_000006_create_user_addresses_table.php
│   │   ├── 2026_01_01_000007_create_orders_table.php
│   │   ├── 2026_01_01_000008_create_order_items_table.php
│   │   ├── 2026_01_01_000009_create_payments_table.php
│   │   └── 2026_01_01_000010_create_cache_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── docker/
│   ├── nginx.conf
│   ├── php.ini
│   └── supervisord.conf
├── routes/
│   └── api.php
├── .env
├── .env.example
├── composer.json
└── Dockerfile
```

---

## 📡 API Design

### Versionamento
Todas as rotas seguem o prefixo `/api/v1/`:
```
GET /api/v1/products
POST /api/v1/auth/login
POST /api/v1/orders
```

### Response Format Padrão
```json
{
  "success": true,
  "data": { ... },
  "message": "Success",
  "meta": {
    "total": 100,
    "page": 1,
    "limit": 20,
    "totalPages": 5
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation error",
  "errors": { ... }
}
```

### ApiResponse Trait
```php
trait ApiResponse {
    protected function success($data, string $message, int $statusCode): JsonResponse;
    protected function error(string $message, int $statusCode, $errors): JsonResponse;
    protected function paginated($data, int $total, int $page, int $limit): JsonResponse;
}
```

---

## 🔐 Autenticação & Autorização

### JWT Strategy
- **Package**: php-open-source-saver/jwt-auth
- **Access Token TTL**: 15 minutos
- **Refresh Token TTL**: 7 dias (10080 minutos)
- **Auth Guard**: `api` driver `jwt`
- **Token Storage**: Bearer token via Authorization header

### Middleware
- `jwt.auth` - Verifica JWT válido e usuário ativo
- `admin` - Verifica role ADMIN ou SUPER_ADMIN

### RBAC (Roles)
```
CUSTOMER     - Comprar, ver pedidos
VENDOR       - Gerenciar produtos próprios
ADMIN        - CRUD completo
SUPER_ADMIN  - Acesso total
```

### Fluxo de Auth
```
1. POST /api/v1/auth/register → { user, accessToken, refreshToken }
2. POST /api/v1/auth/login → { user, accessToken, refreshToken }
3. Requests autenticados: Authorization: Bearer <accessToken>
4. Token expirado → POST /api/v1/auth/refresh { refreshToken }
```

---

## 📡 Endpoints Completos

### Auth (Público)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| POST | /api/v1/auth/register | Registrar novo usuário |
| POST | /api/v1/auth/login | Login |
| POST | /api/v1/auth/refresh | Renovar tokens |
| POST | /api/v1/auth/logout | Logout (auth) |

### Users (Autenticado)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/users/me | Perfil do usuário logado |
| PATCH | /api/v1/users/me | Atualizar perfil |

### Products (Público para leitura)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/products | Listar com filtros/paginação |
| GET | /api/v1/products/featured | Produtos destaque |
| GET | /api/v1/products/categories | Listar categorias |
| GET | /api/v1/products/slug/{slug} | Buscar por slug |
| GET | /api/v1/products/{id} | Detalhe por ID |
| POST | /api/v1/products | Criar (Admin) |
| PATCH | /api/v1/products/{id} | Atualizar (Admin) |
| DELETE | /api/v1/products/{id} | Deletar (Admin) |

### Orders (Autenticado)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| POST | /api/v1/orders | Criar pedido |
| GET | /api/v1/orders/my-orders | Meus pedidos |
| GET | /api/v1/orders/{id} | Detalhe do pedido |
| GET | /api/v1/orders | Listar todos (Admin) |
| PATCH | /api/v1/orders/{id}/status | Atualizar status (Admin) |

### Payments (Autenticado)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| POST | /api/v1/payments/create-intent | Criar Payment Intent |
| POST | /api/v1/payments/confirm | Confirmar pagamento |
| GET | /api/v1/payments/{id} | Status do pagamento |
| POST | /api/v1/payments/refund | Reembolso (Admin) |

### Webhooks
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| POST | /api/webhooks/stripe | Stripe webhook handler |

### Health
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/health | Health check |

---

## 💾 Models (Eloquent)

### User
```php
class User extends Authenticatable implements JWTSubject
{
    // Fields: email, password_hash, first_name, last_name, phone, role, is_active
    // Relations: orders(), addresses()
    // JWT: getJWTIdentifier(), getJWTCustomClaims()
}
```

### Product
```php
class Product extends Model
{
    // Fields: sku, name, slug, description, price, stock_quantity, status, is_featured
    // Relations: category(), images(), designs(), orderItems()
}
```

### Order
```php
class Order extends Model
{
    // Fields: order_number, user_id, subtotal, total, status, payment_status
    // Relations: user(), items(), payment(), shippingAddress(), billingAddress()
}
```

### Payment
```php
class Payment extends Model
{
    // Fields: order_id, stripe_payment_intent_id, amount, currency, status
    // Relations: order()
}
```

---

## ⚙️ Configuração

### Database (config/database.php)
- Driver: `pgsql` (PostgreSQL)
- Eloquent ORM com UUIDs
- Migrations versionadas

### Cache (config/cache.php)
- Driver: Redis via Predis
- Prefix: `tshirtslab_`
- TTL estratégico por tipo

### Auth (config/auth.php)
- Default guard: `api`
- Driver: `jwt` (php-open-source-saver/jwt-auth)
- Provider: Eloquent Users

### CORS (config/cors.php)
- Origin: Frontend URL (env FRONTEND_URL)
- Credentials: true
- Headers: Content-Type, Authorization

---

## �� Comandos de Desenvolvimento

```bash
# Instalar dependências
composer install

# Rodar migrations
php artisan migrate

# Rodar seeders
php artisan db:seed

# Reset completo (migrate + seed)
php artisan migrate:fresh --seed

# Listar rotas
php artisan route:list

# Limpar caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Servidor de desenvolvimento
php artisan serve --port=8000

# Testes
php artisan test
```

---

## 🧪 Testing

```bash
php artisan test                    # Todos os testes
php artisan test --filter=AuthTest  # Testes específicos
php artisan test --coverage         # Com coverage
```

---

**Última atualização**: Março 2026
