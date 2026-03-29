# Payment Integration - Stripe

## 🏦 Stripe Payment Architecture

### Payment Flow Overview

```
┌──────────────┐
│   Customer   │
│  (Browser)   │
└──────┬───────┘
       │
       │ 1. Add products to cart
       ↓
┌──────────────┐
│  Frontend    │  2. Initiate checkout
│  (React)     │     - Get Client Secret
└──────┬───────┘
       │
       │ 3. Call /api/v1/payments/create-intent
       ↓
┌──────────────┐
│  Backend     │  4. Create Payment Intent
│  (Laravel)   │     - Save to DB with PENDING status
└──────┬───────┘
       │
       │ 5. Stripe API Call
       ↓
┌──────────────┐
│   Stripe     │  6. Return Client Secret
└──────┬───────┘
       │
       │ 7. Return to Frontend
       ↓
┌──────────────┐
│  Stripe.js   │  8. Display Payment Form
│  (Elements)  │     - Customer enters card details
└──────┬───────┘
       │
       │ 9. Confirm Payment
       ↓
┌──────────────┐
│   Stripe     │  10. Process Payment
└──────┬───────┘
       │
       │ 11. Webhook: payment_intent.succeeded
       ↓
┌──────────────┐
│  Backend     │  12. Confirm Order
│  (Webhook)   │     - Update order status
└──────────────┘     - Update payment status
                     - Send confirmation email
```

---

## �� Stripe Configuration

### Backend (.env)
```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Laravel Config
```php
// config/services.php
'stripe' => [
    'secret' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

### Stripe PHP SDK
```bash
composer require stripe/stripe-php
```

---

## 🔧 Payment Controller (Laravel)

### Create Payment Intent
```php
// app/Http/Controllers/Api/V1/PaymentController.php
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createIntent(Request $request)
    {
        $request->validate([
            'orderId' => 'required|uuid|exists:orders,id',
            'amount' => 'required|numeric|min:0.50',
        ]);

        $order = Order::where('id', $request->orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Criar Payment Intent no Stripe
        $paymentIntent = PaymentIntent::create([
            'amount' => (int) ($request->amount * 100), // centavos
            'currency' => 'brl',
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        // Salvar referência no banco
        Payment::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $request->amount,
            'currency' => 'brl',
            'status' => 'PENDING',
        ]);

        return $this->success([
            'clientSecret' => $paymentIntent->client_secret,
            'paymentIntentId' => $paymentIntent->id,
        ]);
    }
}
```

### Confirm Payment
```php
public function confirm(Request $request)
{
    $request->validate([
        'paymentIntentId' => 'required|string',
    ]);

    $payment = Payment::where('stripe_payment_intent_id', $request->paymentIntentId)
        ->firstOrFail();

    // Verificar status no Stripe
    $paymentIntent = PaymentIntent::retrieve($request->paymentIntentId);

    if ($paymentIntent->status === 'succeeded') {
        $payment->update([
            'status' => 'SUCCEEDED',
            'payment_method' => $paymentIntent->payment_method,
            'paid_at' => now(),
        ]);

        $payment->order->update([
            'payment_status' => 'PAID',
            'status' => 'CONFIRMED',
        ]);

        return $this->success([
            'status' => 'SUCCEEDED',
            'order' => $payment->order->load('items.product'),
        ]);
    }

    return $this->success(['status' => $paymentIntent->status]);
}
```

### Get Payment Status
```php
public function status(string $paymentIntentId)
{
    $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
        ->firstOrFail();

    return $this->success([
        'paymentIntentId' => $payment->stripe_payment_intent_id,
        'status' => $payment->status,
        'amount' => $payment->amount,
        'currency' => $payment->currency,
        'paidAt' => $payment->paid_at,
    ]);
}
```

### Refund Payment
```php
public function refund(Request $request)
{
    $request->validate([
        'paymentIntentId' => 'required|string',
        'amount' => 'nullable|numeric|min:0.01',
    ]);

    $payment = Payment::where('stripe_payment_intent_id', $request->paymentIntentId)
        ->firstOrFail();

    $refundParams = ['payment_intent' => $request->paymentIntentId];
    if ($request->has('amount')) {
        $refundParams['amount'] = (int) ($request->amount * 100);
    }

    $refund = \Stripe\Refund::create($refundParams);

    $payment->update(['status' => 'REFUNDED']);
    $payment->order->update([
        'payment_status' => 'REFUNDED',
        'status' => 'REFUNDED',
    ]);

    return $this->success([
        'refundId' => $refund->id,
        'status' => $refund->status,
    ]);
}
```

---

## 🔔 Webhook Handler

### Route Configuration
```php
// routes/api.php
Route::post('webhooks/stripe', [WebhookController::class, 'handleStripe']);
```

> **Nota**: O webhook NÃO usa o prefixo `/v1/` e NÃO requer autenticação JWT.

### Webhook Controller
```php
// app/Http/Controllers/Api/V1/WebhookController.php
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    use ApiResponse;

    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function handlePaymentSucceeded($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if (# Database & Cache Architecture - PostgreSQL & Redis

## 📊 PostgreSQL Database Design

### Database Schema Overview

```
┌─────────────────────────────────────────────────────┐
│                   T-SHIRTS LAB DB                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Users (Authentication & Profiles)                 │
│  ├─ users                                           │
│  └─ user_addresses                                 │
│                                                     │
│  Products & Catalog                                │
│  ├─ categories                                      │
│  ├─ products                                        │
│  ├─ product_images                                 │
│  └─ designs                                        │
│                                                     │
│  Orders & Transactions                             │
│  ├─ orders                                          │
│  ├─ order_items                                    │
│  └─ payments                                       │
│                                                     │
│  Infrastructure                                    │
│  └─ cache (Laravel cache store)                    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Core Tables & Eloquent Migrations

#### 1. Users Table
```php
// database/migrations/2026_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->string('avatar_url')->nullable();
    $table->enum('role', ['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN'])->default('CUSTOMER');
    $table->boolean('is_active')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 2. Categories Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('image_url')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### 3. Products Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('sku')->unique();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->integer('stock_quantity')->default(0);
    $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['DRAFT', 'ACTIVE', 'INACTIVE', 'OUT_OF_STOCK'])->default('DRAFT');
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_customizable')->default(true);
    $table->json('sizes')->nullable();
    $table->json('colors')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 4. Product Images Table
```php
Schema::create('product_images', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('image_url');
    $table->string('alt_text')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_primary')->default(false);
    $table->timestamps();
});
```

#### 5. Designs Table
```php
Schema::create('designs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('image_url');
    $table->string('category')->nullable();
    $table->boolean('is_approved')->default(false);
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

#### 6. User Addresses Table
```php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->string('label')->default('Principal');
    $table->string('address_line1');
    $table->string('address_line2')->nullable();
    $table->string('city');
    $table->string('state');
    $table->string('zip_code');
    $table->string('country')->default('BR');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### 7. Orders Table
```php
Schema::create('orders', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('order_number')->unique();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->decimal('subtotal', 10, 2);
    $table->decimal('shipping_cost', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->enum('status', [
        'PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED',
        'DELIVERED', 'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->enum('payment_status', [
        'PENDING', 'PAID', 'FAILED', 'REFUNDED'
    ])->default('PENDING');
    $table->json('shipping_address')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 8. Order Items Table
```php
Schema::create('order_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('design_id')->nullable()->constrained()->onDelete('set null');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_price', 10, 2);
    $table->string('size')->nullable();
    $table->string('color')->nullable();
    $table->json('customization_data')->nullable();
    $table->timestamps();
});
```

#### 9. Payments Table
```php
Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->string('stripe_payment_intent_id')->unique();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('brl');
    $table->enum('status', [
        'PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED',
        'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->string('payment_method')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

---

## 🔗 Eloquent Relationships

```
users
  ├── hasMany → orders
  └── hasMany → user_addresses

categories
  └── hasMany → products

products
  ├── belongsTo → category
  ├── hasMany → product_images
  ├── hasMany → designs
  └── hasMany → order_items

orders
  ├── belongsTo → user
  ├── hasMany → order_items
  └── hasOne → payment

order_items
  ├── belongsTo → order
  ├── belongsTo → product
  └── belongsTo → design (nullable)

payment
  └── belongsTo → order
```

### Eloquent Model Example
```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'slug', 'description', 'price',
        'compare_at_price', 'stock_quantity', 'category_id',
        'status', 'is_featured', 'is_customizable',
        'sizes', 'colors', 'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_customizable' => 'boolean',
        'sizes' => 'array',
        'colors' => 'array',
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

---

## 🗄️ Database Configuration

### Laravel `.env` Database Config
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tshirtslab_db
DB_USERNAME=tshirtslab
DB_PASSWORD=tshirtslab_secret
```

### PostgreSQL in Docker Compose
```yaml
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
```

---

## 📊 Indexes & Performance

### Key Indexes (criados via migrations)
```php
// Automáticos (criados pelo Laravel)
// - Primary keys (UUID) em todas as tabelas
// - Unique indexes: users.email, products.sku, products.slug, categories.slug
// - Foreign keys: product.category_id, order.user_id, etc.

// Indexes adicionais recomendados
Schema::table('products', function (Blueprint $table) {
    $table->index(['status', 'is_featured']);  // Consultas de produtos ativos/destaque
    $table->index('category_id');               // Filtro por categoria
});

Schema::table('orders', function (Blueprint $table) {
    $table->index(['user_id', 'status']);       // Pedidos do usuário
    $table->index('order_number');              // Busca por número
});
```

---

## 🗃️ Redis Cache Architecture

### Configuração Laravel
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Estratégia de Cache

| Recurso | TTL | Key Pattern | Invalidação |
|---------|-----|-------------|-------------|
| Lista de Produtos | 1 hora | `products:list:{page}:{filters}` | Ao criar/atualizar produto |
| Produto Individual | 1 hora | `products:{id}` | Ao atualizar produto |
| Produto por Slug | 1 hora | `products:slug:{slug}` | Ao atualizar produto |
| Categorias | 24 horas | `categories:all` | Ao criar/atualizar categoria |
| Produtos Destaque | 1 hora | `products:featured` | Ao mudar destaque |

### Implementação no Controller
```php
// app/Http/Controllers/Api/V1/ProductController.php
public function index(Request $request)
{
    $cacheKey = 'products:list:' . md5(json_encode($request->all()));

    $products = Cache::remember($cacheKey, 3600, function () use ($request) {
        $query = Product::with(['images', 'category', 'designs']);

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        return $query->paginate($request->get('limit', 15));
    });

    return $this->paginated($products);
}
```

### Invalidação de Cache
```php
// Ao criar/atualizar produto
public function store(Request $request)
{
    $product = Product::create($request->validated());

    // Invalida caches relacionados
    Cache::forget('products:featured');
    Cache::flush(); // ou invalidar patterns específicos

    return $this->success($product, 'Produto criado', 201);
}
```

### Redis para Sessions
```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
```

### Redis para Rate Limiting
```php
// bootstrap/app.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(
        $request->user()?->id ?: $request->ip()
    );
});
```

---

## 🛠️ Comandos de Database

### Migrations
```bash
# Executar todas as migrations
php artisan migrate

# Rollback da última migration
php artisan migrate:rollback

# Resetar e re-executar todas
php artisan migrate:fresh

# Resetar, re-executar e popular com seeds
php artisan migrate:fresh --seed

# Ver status das migrations
php artisan migrate:status
```

### Seeding
```bash
# Executar todos os seeders
php artisan db:seed

# Executar seeder específico
php artisan db:seed --class=DatabaseSeeder
```

### Tinker (Console interativo)
```bash
php artisan tinker

# Exemplos no Tinker
>>> User::count()
>>> Product::with('images')->first()
>>> Order::where('status', 'PENDING')->get()
>>> Category::find('uuid-here')->products()->count()
```

---

## �� Monitoramento & Debug

### Query Log (Development)
```php
// Em AppServiceProvider ou Middleware
DB::listen(function ($query) {
    Log::info("Query: {$query->sql}", [
        'bindings' => $query->bindings,
        'time' => $query->time . 'ms',
    ]);
});
```

### Telescope (Laravel Debug Tool)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
# Acesse: http://localhost:8000/telescope
```

### Redis Monitor
```bash
# Ver comandos Redis em tempo real
redis-cli monitor

# Ver uso de memória
redis-cli info memory

# Listar todas as keys
redis-cli keys "*"
```

---

## 🔒 Segurança de Database

1. **UUIDs como PK**: Não expõe sequência de IDs
2. **Soft Deletes**: Users, Products, Orders mantêm histórico
3. **Eloquent Parameterized Queries**: Proteção contra SQL Injection
4. **Password Hashing**: `bcrypt` via `Hash::make()` (automático no model)
5. **Foreign Key Constraints**: Cascade deletes onde apropriado
6. **Validação no Controller**: Request validation antes de queries
7. **Hidden Fields**: `$hidden = ['password']` nos Models

---

**Database**: PostgreSQL 15 | **Cache**: Redis 7 (Predis) | **ORM**: Eloquent (Laravel 13)

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
MDEOFpayment) return;

        $payment->update([
            'status' => 'SUCCEEDED',
            'payment_method' => $paymentIntent->payment_method,
            'paid_at' => now(),
            'metadata' => $paymentIntent->toArray(),
        ]);

        $payment->order->update([
            'payment_status' => 'PAID',
            'status' => 'CONFIRMED',
        ]);

        // TODO: Enviar email de confirmação
    }

    private function handlePaymentFailed($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if (# Database & Cache Architecture - PostgreSQL & Redis

## 📊 PostgreSQL Database Design

### Database Schema Overview

```
┌─────────────────────────────────────────────────────┐
│                   T-SHIRTS LAB DB                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Users (Authentication & Profiles)                 │
│  ├─ users                                           │
│  └─ user_addresses                                 │
│                                                     │
│  Products & Catalog                                │
│  ├─ categories                                      │
│  ├─ products                                        │
│  ├─ product_images                                 │
│  └─ designs                                        │
│                                                     │
│  Orders & Transactions                             │
│  ├─ orders                                          │
│  ├─ order_items                                    │
│  └─ payments                                       │
│                                                     │
│  Infrastructure                                    │
│  └─ cache (Laravel cache store)                    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Core Tables & Eloquent Migrations

#### 1. Users Table
```php
// database/migrations/2026_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->string('avatar_url')->nullable();
    $table->enum('role', ['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN'])->default('CUSTOMER');
    $table->boolean('is_active')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 2. Categories Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('image_url')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### 3. Products Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('sku')->unique();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->integer('stock_quantity')->default(0);
    $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['DRAFT', 'ACTIVE', 'INACTIVE', 'OUT_OF_STOCK'])->default('DRAFT');
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_customizable')->default(true);
    $table->json('sizes')->nullable();
    $table->json('colors')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 4. Product Images Table
```php
Schema::create('product_images', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('image_url');
    $table->string('alt_text')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_primary')->default(false);
    $table->timestamps();
});
```

#### 5. Designs Table
```php
Schema::create('designs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('image_url');
    $table->string('category')->nullable();
    $table->boolean('is_approved')->default(false);
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

#### 6. User Addresses Table
```php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->string('label')->default('Principal');
    $table->string('address_line1');
    $table->string('address_line2')->nullable();
    $table->string('city');
    $table->string('state');
    $table->string('zip_code');
    $table->string('country')->default('BR');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### 7. Orders Table
```php
Schema::create('orders', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('order_number')->unique();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->decimal('subtotal', 10, 2);
    $table->decimal('shipping_cost', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->enum('status', [
        'PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED',
        'DELIVERED', 'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->enum('payment_status', [
        'PENDING', 'PAID', 'FAILED', 'REFUNDED'
    ])->default('PENDING');
    $table->json('shipping_address')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 8. Order Items Table
```php
Schema::create('order_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('design_id')->nullable()->constrained()->onDelete('set null');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_price', 10, 2);
    $table->string('size')->nullable();
    $table->string('color')->nullable();
    $table->json('customization_data')->nullable();
    $table->timestamps();
});
```

#### 9. Payments Table
```php
Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->string('stripe_payment_intent_id')->unique();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('brl');
    $table->enum('status', [
        'PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED',
        'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->string('payment_method')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

---

## 🔗 Eloquent Relationships

```
users
  ├── hasMany → orders
  └── hasMany → user_addresses

categories
  └── hasMany → products

products
  ├── belongsTo → category
  ├── hasMany → product_images
  ├── hasMany → designs
  └── hasMany → order_items

orders
  ├── belongsTo → user
  ├── hasMany → order_items
  └── hasOne → payment

order_items
  ├── belongsTo → order
  ├── belongsTo → product
  └── belongsTo → design (nullable)

payment
  └── belongsTo → order
```

### Eloquent Model Example
```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'slug', 'description', 'price',
        'compare_at_price', 'stock_quantity', 'category_id',
        'status', 'is_featured', 'is_customizable',
        'sizes', 'colors', 'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_customizable' => 'boolean',
        'sizes' => 'array',
        'colors' => 'array',
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

---

## 🗄️ Database Configuration

### Laravel `.env` Database Config
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tshirtslab_db
DB_USERNAME=tshirtslab
DB_PASSWORD=tshirtslab_secret
```

### PostgreSQL in Docker Compose
```yaml
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
```

---

## 📊 Indexes & Performance

### Key Indexes (criados via migrations)
```php
// Automáticos (criados pelo Laravel)
// - Primary keys (UUID) em todas as tabelas
// - Unique indexes: users.email, products.sku, products.slug, categories.slug
// - Foreign keys: product.category_id, order.user_id, etc.

// Indexes adicionais recomendados
Schema::table('products', function (Blueprint $table) {
    $table->index(['status', 'is_featured']);  // Consultas de produtos ativos/destaque
    $table->index('category_id');               // Filtro por categoria
});

Schema::table('orders', function (Blueprint $table) {
    $table->index(['user_id', 'status']);       // Pedidos do usuário
    $table->index('order_number');              // Busca por número
});
```

---

## 🗃️ Redis Cache Architecture

### Configuração Laravel
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Estratégia de Cache

| Recurso | TTL | Key Pattern | Invalidação |
|---------|-----|-------------|-------------|
| Lista de Produtos | 1 hora | `products:list:{page}:{filters}` | Ao criar/atualizar produto |
| Produto Individual | 1 hora | `products:{id}` | Ao atualizar produto |
| Produto por Slug | 1 hora | `products:slug:{slug}` | Ao atualizar produto |
| Categorias | 24 horas | `categories:all` | Ao criar/atualizar categoria |
| Produtos Destaque | 1 hora | `products:featured` | Ao mudar destaque |

### Implementação no Controller
```php
// app/Http/Controllers/Api/V1/ProductController.php
public function index(Request $request)
{
    $cacheKey = 'products:list:' . md5(json_encode($request->all()));

    $products = Cache::remember($cacheKey, 3600, function () use ($request) {
        $query = Product::with(['images', 'category', 'designs']);

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        return $query->paginate($request->get('limit', 15));
    });

    return $this->paginated($products);
}
```

### Invalidação de Cache
```php
// Ao criar/atualizar produto
public function store(Request $request)
{
    $product = Product::create($request->validated());

    // Invalida caches relacionados
    Cache::forget('products:featured');
    Cache::flush(); // ou invalidar patterns específicos

    return $this->success($product, 'Produto criado', 201);
}
```

### Redis para Sessions
```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
```

### Redis para Rate Limiting
```php
// bootstrap/app.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(
        $request->user()?->id ?: $request->ip()
    );
});
```

---

## 🛠️ Comandos de Database

### Migrations
```bash
# Executar todas as migrations
php artisan migrate

# Rollback da última migration
php artisan migrate:rollback

# Resetar e re-executar todas
php artisan migrate:fresh

# Resetar, re-executar e popular com seeds
php artisan migrate:fresh --seed

# Ver status das migrations
php artisan migrate:status
```

### Seeding
```bash
# Executar todos os seeders
php artisan db:seed

# Executar seeder específico
php artisan db:seed --class=DatabaseSeeder
```

### Tinker (Console interativo)
```bash
php artisan tinker

# Exemplos no Tinker
>>> User::count()
>>> Product::with('images')->first()
>>> Order::where('status', 'PENDING')->get()
>>> Category::find('uuid-here')->products()->count()
```

---

## �� Monitoramento & Debug

### Query Log (Development)
```php
// Em AppServiceProvider ou Middleware
DB::listen(function ($query) {
    Log::info("Query: {$query->sql}", [
        'bindings' => $query->bindings,
        'time' => $query->time . 'ms',
    ]);
});
```

### Telescope (Laravel Debug Tool)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
# Acesse: http://localhost:8000/telescope
```

### Redis Monitor
```bash
# Ver comandos Redis em tempo real
redis-cli monitor

# Ver uso de memória
redis-cli info memory

# Listar todas as keys
redis-cli keys "*"
```

---

## 🔒 Segurança de Database

1. **UUIDs como PK**: Não expõe sequência de IDs
2. **Soft Deletes**: Users, Products, Orders mantêm histórico
3. **Eloquent Parameterized Queries**: Proteção contra SQL Injection
4. **Password Hashing**: `bcrypt` via `Hash::make()` (automático no model)
5. **Foreign Key Constraints**: Cascade deletes onde apropriado
6. **Validação no Controller**: Request validation antes de queries
7. **Hidden Fields**: `$hidden = ['password']` nos Models

---

**Database**: PostgreSQL 15 | **Cache**: Redis 7 (Predis) | **ORM**: Eloquent (Laravel 13)

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
MDEOFpayment) return;

        $payment->update(['status' => 'FAILED']);
        $payment->order->update(['payment_status' => 'FAILED']);
    }

    private function handleChargeRefunded($charge)
    {
        $payment = Payment::where('stripe_payment_intent_id', $charge->payment_intent)->first();
        if (# Database & Cache Architecture - PostgreSQL & Redis

## 📊 PostgreSQL Database Design

### Database Schema Overview

```
┌─────────────────────────────────────────────────────┐
│                   T-SHIRTS LAB DB                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Users (Authentication & Profiles)                 │
│  ├─ users                                           │
│  └─ user_addresses                                 │
│                                                     │
│  Products & Catalog                                │
│  ├─ categories                                      │
│  ├─ products                                        │
│  ├─ product_images                                 │
│  └─ designs                                        │
│                                                     │
│  Orders & Transactions                             │
│  ├─ orders                                          │
│  ├─ order_items                                    │
│  └─ payments                                       │
│                                                     │
│  Infrastructure                                    │
│  └─ cache (Laravel cache store)                    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Core Tables & Eloquent Migrations

#### 1. Users Table
```php
// database/migrations/2026_01_01_000001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->string('avatar_url')->nullable();
    $table->enum('role', ['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN'])->default('CUSTOMER');
    $table->boolean('is_active')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 2. Categories Table
```php
Schema::create('categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('image_url')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

#### 3. Products Table
```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('sku')->unique();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->decimal('compare_at_price', 10, 2)->nullable();
    $table->integer('stock_quantity')->default(0);
    $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['DRAFT', 'ACTIVE', 'INACTIVE', 'OUT_OF_STOCK'])->default('DRAFT');
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_customizable')->default(true);
    $table->json('sizes')->nullable();
    $table->json('colors')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 4. Product Images Table
```php
Schema::create('product_images', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('image_url');
    $table->string('alt_text')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_primary')->default(false);
    $table->timestamps();
});
```

#### 5. Designs Table
```php
Schema::create('designs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('image_url');
    $table->string('category')->nullable();
    $table->boolean('is_approved')->default(false);
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

#### 6. User Addresses Table
```php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->string('label')->default('Principal');
    $table->string('address_line1');
    $table->string('address_line2')->nullable();
    $table->string('city');
    $table->string('state');
    $table->string('zip_code');
    $table->string('country')->default('BR');
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### 7. Orders Table
```php
Schema::create('orders', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('order_number')->unique();
    $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
    $table->decimal('subtotal', 10, 2);
    $table->decimal('shipping_cost', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->enum('status', [
        'PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED',
        'DELIVERED', 'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->enum('payment_status', [
        'PENDING', 'PAID', 'FAILED', 'REFUNDED'
    ])->default('PENDING');
    $table->json('shipping_address')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

#### 8. Order Items Table
```php
Schema::create('order_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('design_id')->nullable()->constrained()->onDelete('set null');
    $table->integer('quantity');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_price', 10, 2);
    $table->string('size')->nullable();
    $table->string('color')->nullable();
    $table->json('customization_data')->nullable();
    $table->timestamps();
});
```

#### 9. Payments Table
```php
Schema::create('payments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
    $table->string('stripe_payment_intent_id')->unique();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('brl');
    $table->enum('status', [
        'PENDING', 'PROCESSING', 'SUCCEEDED', 'FAILED',
        'CANCELLED', 'REFUNDED'
    ])->default('PENDING');
    $table->string('payment_method')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

---

## 🔗 Eloquent Relationships

```
users
  ├── hasMany → orders
  └── hasMany → user_addresses

categories
  └── hasMany → products

products
  ├── belongsTo → category
  ├── hasMany → product_images
  ├── hasMany → designs
  └── hasMany → order_items

orders
  ├── belongsTo → user
  ├── hasMany → order_items
  └── hasOne → payment

order_items
  ├── belongsTo → order
  ├── belongsTo → product
  └── belongsTo → design (nullable)

payment
  └── belongsTo → order
```

### Eloquent Model Example
```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'slug', 'description', 'price',
        'compare_at_price', 'stock_quantity', 'category_id',
        'status', 'is_featured', 'is_customizable',
        'sizes', 'colors', 'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_customizable' => 'boolean',
        'sizes' => 'array',
        'colors' => 'array',
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

---

## 🗄️ Database Configuration

### Laravel `.env` Database Config
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tshirtslab_db
DB_USERNAME=tshirtslab
DB_PASSWORD=tshirtslab_secret
```

### PostgreSQL in Docker Compose
```yaml
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
```

---

## 📊 Indexes & Performance

### Key Indexes (criados via migrations)
```php
// Automáticos (criados pelo Laravel)
// - Primary keys (UUID) em todas as tabelas
// - Unique indexes: users.email, products.sku, products.slug, categories.slug
// - Foreign keys: product.category_id, order.user_id, etc.

// Indexes adicionais recomendados
Schema::table('products', function (Blueprint $table) {
    $table->index(['status', 'is_featured']);  // Consultas de produtos ativos/destaque
    $table->index('category_id');               // Filtro por categoria
});

Schema::table('orders', function (Blueprint $table) {
    $table->index(['user_id', 'status']);       // Pedidos do usuário
    $table->index('order_number');              // Busca por número
});
```

---

## 🗃️ Redis Cache Architecture

### Configuração Laravel
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Estratégia de Cache

| Recurso | TTL | Key Pattern | Invalidação |
|---------|-----|-------------|-------------|
| Lista de Produtos | 1 hora | `products:list:{page}:{filters}` | Ao criar/atualizar produto |
| Produto Individual | 1 hora | `products:{id}` | Ao atualizar produto |
| Produto por Slug | 1 hora | `products:slug:{slug}` | Ao atualizar produto |
| Categorias | 24 horas | `categories:all` | Ao criar/atualizar categoria |
| Produtos Destaque | 1 hora | `products:featured` | Ao mudar destaque |

### Implementação no Controller
```php
// app/Http/Controllers/Api/V1/ProductController.php
public function index(Request $request)
{
    $cacheKey = 'products:list:' . md5(json_encode($request->all()));

    $products = Cache::remember($cacheKey, 3600, function () use ($request) {
        $query = Product::with(['images', 'category', 'designs']);

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        return $query->paginate($request->get('limit', 15));
    });

    return $this->paginated($products);
}
```

### Invalidação de Cache
```php
// Ao criar/atualizar produto
public function store(Request $request)
{
    $product = Product::create($request->validated());

    // Invalida caches relacionados
    Cache::forget('products:featured');
    Cache::flush(); // ou invalidar patterns específicos

    return $this->success($product, 'Produto criado', 201);
}
```

### Redis para Sessions
```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
```

### Redis para Rate Limiting
```php
// bootstrap/app.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by(
        $request->user()?->id ?: $request->ip()
    );
});
```

---

## 🛠️ Comandos de Database

### Migrations
```bash
# Executar todas as migrations
php artisan migrate

# Rollback da última migration
php artisan migrate:rollback

# Resetar e re-executar todas
php artisan migrate:fresh

# Resetar, re-executar e popular com seeds
php artisan migrate:fresh --seed

# Ver status das migrations
php artisan migrate:status
```

### Seeding
```bash
# Executar todos os seeders
php artisan db:seed

# Executar seeder específico
php artisan db:seed --class=DatabaseSeeder
```

### Tinker (Console interativo)
```bash
php artisan tinker

# Exemplos no Tinker
>>> User::count()
>>> Product::with('images')->first()
>>> Order::where('status', 'PENDING')->get()
>>> Category::find('uuid-here')->products()->count()
```

---

## �� Monitoramento & Debug

### Query Log (Development)
```php
// Em AppServiceProvider ou Middleware
DB::listen(function ($query) {
    Log::info("Query: {$query->sql}", [
        'bindings' => $query->bindings,
        'time' => $query->time . 'ms',
    ]);
});
```

### Telescope (Laravel Debug Tool)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
# Acesse: http://localhost:8000/telescope
```

### Redis Monitor
```bash
# Ver comandos Redis em tempo real
redis-cli monitor

# Ver uso de memória
redis-cli info memory

# Listar todas as keys
redis-cli keys "*"
```

---

## 🔒 Segurança de Database

1. **UUIDs como PK**: Não expõe sequência de IDs
2. **Soft Deletes**: Users, Products, Orders mantêm histórico
3. **Eloquent Parameterized Queries**: Proteção contra SQL Injection
4. **Password Hashing**: `bcrypt` via `Hash::make()` (automático no model)
5. **Foreign Key Constraints**: Cascade deletes onde apropriado
6. **Validação no Controller**: Request validation antes de queries
7. **Hidden Fields**: `$hidden = ['password']` nos Models

---

**Database**: PostgreSQL 15 | **Cache**: Redis 7 (Predis) | **ORM**: Eloquent (Laravel 13)

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
MDEOFpayment) return;

        $payment->update(['status' => 'REFUNDED']);
        $payment->order->update([
            'payment_status' => 'REFUNDED',
            'status' => 'REFUNDED',
        ]);
    }
}
```

---

## 🖥️ Frontend Integration

### Stripe Elements Setup
```tsx
// src/components/checkout/PaymentForm.tsx
import { loadStripe } from '@stripe/stripe-js';
import { Elements, PaymentElement, useStripe, useElements } from '@stripe/react-stripe-js';

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY);

const CheckoutForm: React.FC<{ clientSecret: string }> = ({ clientSecret }) => {
  const stripe = useStripe();
  const elements = useElements();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!stripe || !elements) return;

    setLoading(true);

    const { error: submitError } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: `${window.location.origin}/checkout/success`,
      },
    });

    if (submitError) {
      setError(submitError.message || 'Erro no pagamento');
    }

    setLoading(false);
  };

  return (
    <form onSubmit={handleSubmit}>
      <PaymentElement />
      {error && <p className="text-red-500 mt-2">{error}</p>}
      <button
        type="submit"
        disabled={!stripe || loading}
        className="w-full mt-4 bg-blue-600 text-white py-3 rounded"
      >
        {loading ? 'Processando...' : 'Pagar'}
      </button>
    </form>
  );
};

// Wrapper com Elements provider
const PaymentForm: React.FC<{ clientSecret: string }> = ({ clientSecret }) => (
  <Elements stripe={stripePromise} options={{ clientSecret }}>
    <CheckoutForm clientSecret={clientSecret} />
  </Elements>
);
```

### Payment Flow no Frontend
```typescript
// src/pages/CheckoutPage.tsx
const CheckoutPage = () => {
  const [clientSecret, setClientSecret] = useState('');
  const { items, total } = useAppSelector(state => state.cart);

  const handleCheckout = async () => {
    // 1. Criar pedido
    const orderResponse = await ordersApi.create({
      items: items.map(item => ({
        productId: item.product.id,
        designId: item.design?.id,
        quantity: item.quantity,
        size: item.size,
        color: item.color,
      })),
    });

    const order = orderResponse.data.data;

    // 2. Criar Payment Intent
    const paymentResponse = await paymentsApi.createIntent({
      orderId: order.id,
      amount: order.total,
    });

    // 3. Setar client secret para Stripe Elements
    setClientSecret(paymentResponse.data.data.clientSecret);
  };

  return (
    <div>
      <OrderSummary items={items} total={total} />
      {clientSecret ? (
        <PaymentForm clientSecret={clientSecret} />
      ) : (
        <button onClick={handleCheckout}>Ir para Pagamento</button>
      )}
    </div>
  );
};
```

---

## 🧪 Testing Stripe

### Cartões de Teste
| Número | Resultado |
|--------|-----------|
| 4242 4242 4242 4242 | Sucesso |
| 4000 0000 0000 0002 | Recusado |
| 4000 0000 0000 3220 | Requer 3D Secure |
| 4000 0025 0000 3155 | 3DS Required |

### Stripe CLI (Webhooks Local)
```bash
# Instalar Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# Trigger evento de teste
stripe trigger payment_intent.succeeded
```

### Teste Manual
```bash
# 1. Criar pedido
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"productId":"uuid","quantity":1}]}'

# 2. Criar payment intent
curl -X POST http://localhost:8000/api/v1/payments/create-intent \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"orderId":"order-uuid","amount":79.90}'

# 3. Verificar status
curl http://localhost:8000/api/v1/payments/pi_xxx \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🔒 Segurança de Pagamentos

1. **Stripe Webhook Signature**: Validação obrigatória de assinatura
2. **Server-side amounts**: Valores calculados no backend, nunca confiar no frontend
3. **Idempotency**: Payment Intent IDs são únicos por pedido
4. **PCI Compliance**: Dados de cartão nunca tocam nosso servidor (Stripe Elements)
5. **HTTPS**: Obrigatório em produção para Stripe
6. **Secret Key**: Apenas no backend, nunca exposta
7. **Webhook Secret**: Validação de origem Stripe

---

## 📊 Payment States

```
                    ┌─────────┐
                    │ PENDING │
                    └────┬────┘
                         │
                    ┌────┴────┐
           ┌────── │PROCESSING│ ──────┐
           │       └──────────┘       │
           ↓                          ↓
    ┌──────────┐              ┌───────────┐
    │SUCCEEDED │              │  FAILED   │
    └────┬─────┘              └───────────┘
         │
         ↓
    ┌──────────┐
    │ REFUNDED │
    └──────────┘
```

| Status | Descrição |
|--------|-----------|
| PENDING | Payment Intent criado, aguardando pagamento |
| PROCESSING | Pagamento em processamento |
| SUCCEEDED | Pagamento confirmado |
| FAILED | Pagamento falhou |
| CANCELLED | Pagamento cancelado |
| REFUNDED | Pagamento reembolsado |

---

**Payment Gateway**: Stripe (stripe/stripe-php v20)
**Backend**: Laravel 13 (PHP 8.4) em http://localhost:8000

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
