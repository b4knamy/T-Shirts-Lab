# Arquitetura Backend - Laravel 11

## 🏗️ Stack Backend

| Tecnologia | Versão | Propósito |
|-----------|--------|-----------|
| Laravel | 11 | Framework PHP |
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
│   │   │       ├── UserController.php          # Perfil, avatar, endereços
│   │   │       ├── UserManagementController.php # Staff CRUD (Admin/SuperAdmin)
│   │   │       ├── ProductController.php
│   │   │       ├── ProductImageController.php
│   │   │       ├── ProductReviewController.php  # Reviews + admin reply
│   │   │       ├── CategoryController.php
│   │   │       ├── CouponController.php
│   │   │       ├── OrderController.php
│   │   │       ├── PaymentController.php
│   │   │       ├── WebhookController.php
│   │   │       └── HealthController.php
│   │   ├── Middleware/
│   │   │   ├── JwtAuthenticate.php
│   │   │   └── AdminMiddleware.php
│   │   ├── Requests/Api/V1/
│   │   │   ├── Coupon/
│   │   │   │   ├── StoreCouponRequest.php
│   │   │   │   ├── UpdateCouponRequest.php
│   │   │   │   └── ValidateCouponRequest.php
│   │   │   └── ...
│   │   └── Resources/Api/V1/
│   │       ├── CouponResource.php
│   │       └── ...
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── ProductImage.php
│   │   ├── ProductReview.php
│   │   ├── Design.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Payment.php
│   │   ├── Coupon.php
│   │   ├── CouponUsage.php
│   │   └── UserAddress.php
│   ├── Services/
│   │   └── OrderService.php
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
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── OrderFactory.php
│   │   ├── PaymentFactory.php
│   │   └── CouponFactory.php
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
│   │   ├── 2026_01_01_000010_create_cache_table.php
│   │   ├── 2026_01_01_000010_create_product_reviews_table.php
│   │   ├── 2026_03_30_000001_create_coupons_table.php
│   │   └── 2026_04_01_015117_add_moderator_role_to_users_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php         # 24 users (super_admin, admin, moderator, customer, 20 random)
│       ├── CategorySeeder.php     # 5 categorias
│       ├── ProductSeeder.php      # 55 produtos com imagens e designs
│       ├── OrderSeeder.php        # 61 pedidos com itens e pagamentos
│       ├── CouponSeeder.php       # 5 cupons
│       └── ReviewSeeder.php       # 172 reviews com ratings ponderados
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
CUSTOMER     - Comprar, ver pedidos, escrever reviews, gerenciar perfil/endereços
MODERATOR    - Tudo do CUSTOMER + acessa painel admin (sem gerenciar staff)
ADMIN        - Tudo do MODERATOR + gerenciar staff (criar/editar MODERATORs)
SUPER_ADMIN  - Acesso total (pode criar/editar ADMINs e MODERATORs)
```

### Hierarquia de Permissões para Staff
- **SUPER_ADMIN** pode criar e modificar ADMIN e MODERATOR
- **ADMIN** pode criar e modificar apenas MODERATOR
- **MODERATOR** não tem acesso à gestão de staff
- Ninguém pode modificar um SUPER_ADMIN via API

### Fluxo de Auth
```
1. POST /api/v1/auth/register → { user, access_token, refresh_token }
2. POST /api/v1/auth/login → { user, access_token, refresh_token }
3. Requests autenticados: Authorization: Bearer <access_token>
4. Token expirado → POST /api/v1/auth/refresh { refresh_token }
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
| POST | /api/v1/users/me/avatar | Upload de avatar |
| GET | /api/v1/users/me/addresses | Listar endereços |
| POST | /api/v1/users/me/addresses | Criar endereço |
| PATCH | /api/v1/users/me/addresses/{id} | Atualizar endereço |
| DELETE | /api/v1/users/me/addresses/{id} | Remover endereço |

### Staff Management (Admin/SuperAdmin)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/users | Listar todos usuários (paginado, filtros) |
| POST | /api/v1/users | Criar staff (MODERATOR ou ADMIN) |
| PATCH | /api/v1/users/{id} | Alterar role / is_active |

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

### Coupons
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/coupons/active | Promos públicas ativas (Público) |
| POST | /api/v1/coupons/validate | Validar cupom (Auth) |
| GET | /api/v1/coupons | Listar todos (Admin) |
| POST | /api/v1/coupons | Criar cupom (Admin) |
| GET | /api/v1/coupons/{id} | Detalhe (Admin) |
| PATCH | /api/v1/coupons/{id} | Atualizar (Admin) |
| DELETE | /api/v1/coupons/{id} | Deletar (Admin) |

### Categories (Admin)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/categories | Listar paginado (Admin) |
| POST | /api/v1/categories | Criar categoria (Admin) |
| PATCH | /api/v1/categories/{id} | Atualizar (Admin) |
| DELETE | /api/v1/categories/{id} | Deletar (Admin) |

### Reviews
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/products/{id}/reviews | Listar reviews do produto (Público) |
| POST | /api/v1/products/{id}/reviews | Criar review (Auth) |
| PATCH | /api/v1/reviews/{id}/reply | Resposta do admin (Admin) |
| DELETE | /api/v1/reviews/{id} | Deletar review (Admin) |

### Product Images (Admin)
| Method | Endpoint | Descrição |
|--------|----------|-----------|
| GET | /api/v1/products/{id}/images | Listar imagens (Admin) |
| POST | /api/v1/products/{id}/images | Adicionar por URL (Admin) |
| POST | /api/v1/products/{id}/images/upload | Upload de arquivo (Admin) |
| PATCH | /api/v1/products/{id}/images/{imageId} | Atualizar (Admin) |
| DELETE | /api/v1/products/{id}/images/{imageId} | Remover (Admin) |

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
    // Fields: order_number, user_id, subtotal, total, status, payment_status, coupon_id
    // Relations: user(), items(), payment(), shippingAddress(), billingAddress(), coupon()
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

### Coupon
```php
class Coupon extends Model
{
    // Fields: code, description, type (PERCENTAGE/FIXED), value, min_order_amount,
    //         max_discount_amount, usage_limit, usage_count, per_user_limit,
    //         is_active, is_public, starts_at, expires_at
    // Relations: usages(), orders()
    // Methods: isValid(), hasUserReachedLimit($userId), calculateDiscount($subtotal)
}
```

### CouponUsage
```php
class CouponUsage extends Model
{
    // Fields: coupon_id, user_id, order_id
    // Relations: coupon(), user(), order()
}
```

### ProductReview
```php
class ProductReview extends Model
{
    // Fields: user_id, product_id, rating (1-5), comment, admin_reply, admin_replied_at
    // Relations: user(), product()
}
```

### UserAddress
```php
class UserAddress extends Model
{
    // Fields: user_id, street, number, complement, neighborhood, city, state, zip_code, country, is_default
    // Relations: user()
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

**Última atualização**: Abril 2026
