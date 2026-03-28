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
│  ├─ user_addresses                                 │
│  ├─ user_preferences                               │
│  └─ refresh_tokens                                 │
│                                                     │
│  Products & Catalog                                │
│  ├─ categories                                      │
│  ├─ products                                        │
│  ├─ product_images                                 │
│  ├─ designs                                        │
│  ├─ design_tags                                    │
│  ├─ product_reviews                                │
│  └─ product_inventory                              │
│                                                     │
│  Orders & Transactions                             │
│  ├─ orders                                          │
│  ├─ order_items                                    │
│  ├─ order_status_history                           │
│  ├─ payments                                       │
│  ├─ payment_methods                                │
│  └─ invoices                                       │
│                                                     │
│  Shopping & Cart                                   │
│  ├─ shopping_carts                                 │
│  ├─ cart_items                                     │
│  └─ wishlist_items                                 │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Core Tables & Schema

#### 1. Users Table
```sql
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  role VARCHAR(50) NOT NULL DEFAULT 'CUSTOMER',
  is_active BOOLEAN DEFAULT true,
  is_email_verified BOOLEAN DEFAULT false,
  profile_picture_url VARCHAR(255),
  bio TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP WITH TIME ZONE,
  
  -- Indexes
  CONSTRAINT users_email_active UNIQUE (email) WHERE deleted_at IS NULL,
  INDEX idx_users_email (email),
  INDEX idx_users_role (role),
  INDEX idx_users_created_at (created_at DESC)
);

-- Enum para roles
CREATE TYPE user_role AS ENUM ('CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN');
ALTER TABLE users ALTER COLUMN role TYPE user_role USING role::user_role;
```

#### 2. Products Table
```sql
CREATE TABLE categories (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  image_url VARCHAR(255),
  is_active BOOLEAN DEFAULT true,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_categories_slug (slug),
  INDEX idx_categories_active (is_active)
);

CREATE TABLE products (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  sku VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  long_description TEXT,
  category_id UUID NOT NULL REFERENCES categories(id),
  
  -- Pricing
  price DECIMAL(10, 2) NOT NULL,
  cost_price DECIMAL(10, 2),
  discount_price DECIMAL(10, 2),
  discount_percent INTEGER,
  
  -- Inventory
  stock_quantity INTEGER NOT NULL DEFAULT 0,
  reserved_quantity INTEGER DEFAULT 0,
  available_quantity INTEGER GENERATED ALWAYS AS (stock_quantity - reserved_quantity) STORED,
  
  -- Status
  status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
  is_featured BOOLEAN DEFAULT false,
  
  -- Metadata
  weight_kg DECIMAL(5, 2),
  dimensions_cm VARCHAR(50),
  color VARCHAR(50),
  size VARCHAR(10),
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_products_category (category_id),
  INDEX idx_products_sku (sku),
  INDEX idx_products_slug (slug),
  INDEX idx_products_status (status),
  INDEX idx_products_stock (available_quantity),
  INDEX idx_products_price (price),
  FULLTEXT INDEX idx_products_search (name, description)
);

CREATE TABLE product_images (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  image_url VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255),
  sort_order INTEGER DEFAULT 0,
  is_primary BOOLEAN DEFAULT false,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_product_images_product (product_id)
);

CREATE TABLE designs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  image_url VARCHAR(255) NOT NULL,
  category VARCHAR(50) NOT NULL, -- anime, custom, seasonal, etc
  file_url VARCHAR(255),
  is_approved BOOLEAN DEFAULT false,
  created_by UUID REFERENCES users(id),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_designs_product (product_id),
  INDEX idx_designs_category (category),
  INDEX idx_designs_approved (is_approved)
);
```

#### 3. Orders & Payments
```sql
CREATE TYPE order_status AS ENUM (
  'PENDING',
  'CONFIRMED',
  'PROCESSING',
  'SHIPPED',
  'DELIVERED',
  'CANCELLED',
  'REFUNDED'
);

CREATE TYPE payment_status AS ENUM (
  'PENDING',
  'PROCESSING',
  'COMPLETED',
  'FAILED',
  'REFUNDED',
  'CANCELLED'
);

CREATE TABLE orders (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_number VARCHAR(50) NOT NULL UNIQUE,
  user_id UUID NOT NULL REFERENCES users(id),
  
  -- Pricing
  subtotal DECIMAL(10, 2) NOT NULL,
  discount_amount DECIMAL(10, 2) DEFAULT 0,
  tax_amount DECIMAL(10, 2) NOT NULL,
  shipping_cost DECIMAL(10, 2) NOT NULL,
  total DECIMAL(10, 2) NOT NULL,
  
  -- Status
  status order_status NOT NULL DEFAULT 'PENDING',
  payment_status payment_status NOT NULL DEFAULT 'PENDING',
  
  -- Shipping
  shipping_address_id UUID REFERENCES user_addresses(id),
  billing_address_id UUID REFERENCES user_addresses(id),
  tracking_number VARCHAR(100),
  
  -- Notes
  customer_notes TEXT,
  admin_notes TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  delivered_at TIMESTAMP WITH TIME ZONE,
  
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created (created_at DESC),
  INDEX idx_orders_number (order_number)
);

CREATE TABLE order_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id),
  design_id UUID REFERENCES designs(id),
  
  quantity INTEGER NOT NULL,
  unit_price DECIMAL(10, 2) NOT NULL,
  customization_data JSONB, -- Dados de customização
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_order_items_order (order_id),
  INDEX idx_order_items_product (product_id)
);

CREATE TABLE payments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id UUID NOT NULL UNIQUE REFERENCES orders(id),
  
  payment_method VARCHAR(50) NOT NULL, -- stripe, paypal, etc
  stripe_payment_intent_id VARCHAR(255),
  
  amount DECIMAL(10, 2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'USD',
  status payment_status NOT NULL DEFAULT 'PENDING',
  
  transaction_id VARCHAR(255),
  error_message TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP WITH TIME ZONE,
  
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_status (status),
  INDEX idx_payments_stripe (stripe_payment_intent_id)
);

CREATE TABLE order_status_history (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  status_from order_status,
  status_to order_status NOT NULL,
  notes TEXT,
  changed_by UUID REFERENCES users(id),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_order_history_order (order_id),
  INDEX idx_order_history_created (created_at DESC)
);
```

#### 4. Shopping Cart
```sql
CREATE TABLE shopping_carts (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  status VARCHAR(50) DEFAULT 'ACTIVE', -- ACTIVE, ABANDONED, CONVERTED
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  abandoned_at TIMESTAMP WITH TIME ZONE,
  
  INDEX idx_shopping_carts_user (user_id),
  INDEX idx_shopping_carts_status (status)
);

CREATE TABLE cart_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  cart_id UUID NOT NULL REFERENCES shopping_carts(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id),
  design_id UUID REFERENCES designs(id),
  
  quantity INTEGER NOT NULL DEFAULT 1,
  customization_data JSONB,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE(cart_id, product_id, design_id),
  INDEX idx_cart_items_cart (cart_id)
);
```

#### 5. User Addresses
```sql
CREATE TYPE address_type AS ENUM ('SHIPPING', 'BILLING');

CREATE TABLE user_addresses (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  
  type address_type NOT NULL,
  
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(255) NOT NULL,
  
  street_address VARCHAR(255) NOT NULL,
  street_address_2 VARCHAR(255),
  city VARCHAR(100) NOT NULL,
  state_province VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  country_code VARCHAR(2) NOT NULL,
  
  is_default BOOLEAN DEFAULT false,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_user_addresses_user (user_id),
  INDEX idx_user_addresses_type (type)
);
```

### Database Constraints & Relationships

```typescript
// Relationships Summary
User (1) ──────── (N) Orders
User (1) ──────── (N) Cart Items
User (1) ──────── (N) Addresses
User (1) ──────── (N) Designs

Product (1) ────── (N) Order Items
Product (1) ────── (N) Cart Items
Product (1) ────── (N) Images
Product (1) ────── (N) Designs
Product (N) ────── (1) Category

Order (1) ─────── (N) Order Items
Order (1) ─────── (1) Payment

Design (1) ────── (N) Order Items
Design (1) ────── (N) Cart Items
```

## ⚡ Redis Cache Architecture

### Caching Strategy

```
┌─────────────────────────────────────────────────────┐
│              REDIS CACHE STRUCTURE                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Sessions & Authentication                          │
│  └─ session:{sessionId}                            │
│  └─ refresh_token:{userId}                         │
│  └─ forgot_password:{token}                        │
│                                                     │
│ Products & Catalog                                 │
│  └─ product:{id}                                   │
│  └─ products:list:{page}                           │
│  └─ products:category:{categoryId}                 │
│  └─ products:search:{query}                        │
│  └─ categories:all                                 │
│                                                     │
│ Shopping Cart                                      │
│  └─ cart:{userId}                                  │
│  └─ cart:abandoned:{userId}                        │
│                                                     │
│ Rate Limiting & API Throttling                     │
│  └─ ratelimit:{userId}:{endpoint}                  │
│                                                     │
│ Temporary Data                                     │
│  └─ verification:{email}                           │
│  └─ password_reset:{token}                         │
│                                                     │
│ Analytics & Metrics                                │
│  └─ metrics:daily:{date}                           │
│  └─ trending_products:{day}                        │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Redis Key Naming Convention

```
{resource}:{id}:{attribute}  # Specific key
{resource}:{id}              # Entity key
{resource}:list:{page}       # Collection with pagination
{resource}:search:{query}    # Search results
{resource}:all               # All items cache
```

### Cache Implementation Example

```typescript
// cache.service.ts
@Injectable()
export class CacheService {
  constructor(private cacheManager: Cache) {}

  private generateKey(...parts: (string | number)[]): string {
    return parts.join(':');
  }

  // Product caching
  async getProduct(productId: string): Promise<Product | undefined> {
    const key = this.generateKey('product', productId);
    return this.cacheManager.get(key);
  }

  async setProduct(product: Product, ttl: number = 3600): Promise<void> {
    const key = this.generateKey('product', product.id);
    await this.cacheManager.set(key, product, ttl * 1000);
  }

  async invalidateProduct(productId: string): Promise<void> {
    const key = this.generateKey('product', productId);
    await this.cacheManager.del(key);
  }

  // Cart caching
  async getCart(userId: string): Promise<Cart | undefined> {
    const key = this.generateKey('cart', userId);
    return this.cacheManager.get(key);
  }

  async setCart(userId: string, cart: Cart): Promise<void> {
    const key = this.generateKey('cart', userId);
    await this.cacheManager.set(key, cart, 86400 * 1000); // 24h
  }

  // Search results
  async getSearchResults(query: string, page: number): Promise<Product[] | undefined> {
    const key = this.generateKey('products:search', query, page);
    return this.cacheManager.get(key);
  }

  // Rate limiting
  async checkRateLimit(userId: string, endpoint: string): Promise<boolean> {
    const key = this.generateKey('ratelimit', userId, endpoint);
    const current = await this.cacheManager.get<number>(key);
    
    if (!current) {
      await this.cacheManager.set(key, 1, 60 * 1000); // 1 min window
      return true;
    }

    if (current >= 100) return false; // 100 requests per minute
    
    await this.cacheManager.set(key, current + 1, 60 * 1000);
    return true;
  }
}
```

### TTL (Time To Live) Strategy

| Tipo de Cache | TTL | Razão |
|---------------|-----|-------|
| Produtos | 1h | Dados de catálogo mudam com frequência |
| Categorias | 24h | Dados relativamente estáticos |
| User Sessions | 7 dias | Refresh tokens válidos por 7 dias |
| Carrinho Abandonado | 30 dias | Para retargeting |
| Resultados de Busca | 30min | Para melhorar UX em buscas frequentes |
| Password Reset Tokens | 1h | Tokens temporários |
| Rate Limit Counters | 1min | Janela de rate limiting |

## 🔄 Data Synchronization

### PostgreSQL ↔ Redis Sync Strategy

```typescript
// product.service.ts
@Injectable()
export class ProductService {
  constructor(
    private readonly productRepository: ProductRepository,
    private readonly cacheService: CacheService,
  ) {}

  async getProduct(id: string): Promise<Product> {
    // Try cache first (Cache-Aside Pattern)
    const cached = await this.cacheService.getProduct(id);
    if (cached) return cached;

    // If not in cache, fetch from database
    const product = await this.productRepository.findById(id);
    if (!product) throw new NotFoundException();

    // Update cache
    await this.cacheService.setProduct(product);

    return product;
  }

  async updateProduct(id: string, data: UpdateProductDTO): Promise<Product> {
    // Update database
    const product = await this.productRepository.update(id, data);

    // Invalidate cache
    await this.cacheService.invalidateProduct(id);

    // Also invalidate related caches
    await this.cacheService.invalidateProductsList();

    return product;
  }
}
```

## 📋 Database Migrations

### Migration Structure

```bash
database/
└── migrations/
    ├── 001_create_users_table.sql
    ├── 002_create_products_table.sql
    ├── 003_create_orders_table.sql
    ├── 004_create_indexes.sql
    └── 005_add_audit_columns.sql
```

### Example Migration

```sql
-- migration: 001_create_users_table.sql
-- timestamp: 2026-03-28T10:00:00Z
-- description: Create initial users table

BEGIN;

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- For full-text search

CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);

COMMIT;
```

## 🔍 Query Optimization

### Common Queries Optimization

```sql
-- Get products with related data (optimized)
SELECT 
  p.*,
  c.name as category_name,
  COUNT(DISTINCT pi.id) as image_count,
  AVG(pr.rating) as avg_rating,
  COUNT(DISTINCT pr.id) as review_count
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN product_images pi ON p.id = pi.product_id
LEFT JOIN product_reviews pr ON p.id = pr.product_id
WHERE p.status = 'ACTIVE'
GROUP BY p.id, c.id
ORDER BY p.created_at DESC
LIMIT 20 OFFSET 0;

-- Get user orders with details (optimized)
SELECT 
  o.*,
  json_agg(json_build_object(
    'id', oi.id,
    'product_id', oi.product_id,
    'quantity', oi.quantity,
    'unit_price', oi.unit_price
  )) as items
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.user_id = $1
GROUP BY o.id
ORDER BY o.created_at DESC;
```

## 🔒 Data Security

- ✅ Password hashing com bcrypt
- ✅ Sensitive data encrypted at rest
- ✅ Row-level security (RLS) para dados multi-tenant
- ✅ Audit logging para operações críticas
- ✅ Regular backups automatizados
- ✅ PII (Personally Identifiable Information) compliance

---

**Última atualização**: Março 2026
