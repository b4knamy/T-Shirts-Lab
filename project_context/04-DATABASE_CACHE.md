# Database & Cache Architecture - PostgreSQL & Redis

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
