# Arquitetura Frontend - React 19

## 🏗️ Stack Frontend

| Tecnologia | Versão | Propósito |
|-----------|--------|-----------|
| React | 19 | UI framework |
| Vite | 6 | Build tool |
| TypeScript | 5.7 | Type safety |
| TailwindCSS | 4 | Styling |
| Redux Toolkit | 2 | State management |
| Axios | 1.9 | HTTP client |
| Zod | 3 | Schema validation |
| React Hook Form | 7 | Form handling |
| React Router | 6 | Routing |
| Lucide React | - | Icons |

---

## 📂 Estrutura Real do Projeto

```
frontend/
├── src/
│   ├── components/
│   │   ├── layout/
│   │   │   ├── MainLayout.tsx         # Layout principal (Header + outlet)
│   │   │   └── AdminLayout.tsx        # Layout admin (sidebar nav + outlet)
│   │   │
│   │   ├── auth/
│   │   │   └── ProtectedRoute.tsx     # Guarda de rotas (role-aware)
│   │   │
│   │   ├── cart/
│   │   │   └── CartSidebar.tsx        # Sidebar do carrinho (overlay)
│   │   │
│   │   ├── product/
│   │   │   ├── ProductCard.tsx        # Card de produto
│   │   │   ├── ProductReviews.tsx     # Lista de reviews do produto
│   │   │   └── StarRating.tsx         # Componente de estrelas
│   │   │
│   │   └── ui/
│   │       └── PromoBanner.tsx        # Banner de promoções com countdown
│   │
│   ├── pages/
│   │   ├── HomePage.tsx
│   │   ├── ProductsPage.tsx           # Catálogo com filtros e paginação
│   │   ├── ProductDetailPage.tsx      # Detalhe + reviews
│   │   ├── CartPage.tsx
│   │   ├── CheckoutPage.tsx           # Checkout + cupom de desconto
│   │   ├── LoginPage.tsx
│   │   ├── RegisterPage.tsx
│   │   ├── ProfilePage.tsx            # Perfil + avatar + endereços (tabs)
│   │   ├── OrdersPage.tsx
│   │   ├── OrderDetailPage.tsx
│   │   ├── NotFoundPage.tsx
│   │   └── admin/
│   │       ├── index.ts               # Re-exports
│   │       ├── AdminDashboard.tsx     # KPIs e stats
│   │       ├── AdminProducts.tsx      # CRUD + Image Manager
│   │       ├── AdminOrders.tsx        # Listagem + update status
│   │       ├── AdminCategories.tsx    # CRUD categorias
│   │       ├── AdminCoupons.tsx       # CRUD cupons
│   │       ├── AdminReviews.tsx       # Moderar reviews + responder
│   │       └── AdminStaff.tsx         # Gerenciar staff (ADMIN/MODERADOR)
│   │
│   ├── store/
│   │   ├── store.ts
│   │   ├── hooks.ts                   # useAppDispatch, useAppSelector
│   │   └── slices/
│   │       ├── authSlice.ts           # user, access_token, refresh_token
│   │       └── cartSlice.ts           # items[], total
│   │
│   ├── services/
│   │   └── api/
│   │       ├── index.ts               # Re-exports de todos os serviços
│   │       ├── client.ts              # Axios instance + interceptors JWT
│   │       ├── auth.ts                # login, register, refresh, logout
│   │       ├── products.ts            # getAll, getById, getBySlug, featured
│   │       ├── orders.ts              # create (+ coupon_code), myOrders, getById
│   │       ├── payments.ts            # createIntent, confirm, getStatus
│   │       ├── coupons.ts             # getActivePromos, validate
│   │       ├── reviews.ts             # getByProduct, create
│   │       ├── admin.ts               # products CRUD, categories CRUD, images
│   │       └── user.ts                # profile, avatar, addresses CRUD
│   │
│   ├── hooks/
│   │   ├── useAuth.ts                 # user, isAuthenticated, login, logout
│   │   ├── useCart.ts                 # items, total, add, remove, clear
│   │   ├── useProducts.ts             # fetchProducts com filtros
│   │   └── useDebounce.ts
│   │
│   ├── types/
│   │   ├── entities.ts                # User, Product, Order, Coupon, Review...
│   │   └── index.ts                   # Re-exports
│   │
│   ├── App.tsx                        # Roteamento principal
│   └── main.tsx
│
├── index.html
├── vite.config.ts
├── tsconfig.json
└── package.json
```

---

## 🔀 Roteamento (App.tsx)

```tsx
// Rotas públicas
<Route path="/" element={<MainLayout />}>
  <Route index element={<HomePage />} />
  <Route path="products" element={<ProductsPage />} />
  <Route path="products/:slug" element={<ProductDetailPage />} />
  <Route path="cart" element={<CartPage />} />
  <Route path="login" element={<LoginPage />} />
  <Route path="register" element={<RegisterPage />} />

  // Rotas autenticadas (CUSTOMER+)
  <Route element={<ProtectedRoute />}>
    <Route path="checkout" element={<CheckoutPage />} />
    <Route path="orders" element={<OrdersPage />} />
    <Route path="orders/:id" element={<OrderDetailPage />} />
    <Route path="profile" element={<ProfilePage />} />
  </Route>
</Route>

// Painel Admin (ADMIN | SUPER_ADMIN | MODERATOR)
<Route path="/admin" element={
  <ProtectedRoute roles={['ADMIN', 'SUPER_ADMIN', 'MODERATOR']}>
    <AdminLayout />
  </ProtectedRoute>
}>
  <Route index element={<AdminDashboard />} />
  <Route path="products" element={<AdminProducts />} />
  <Route path="orders" element={<AdminOrders />} />
  <Route path="categories" element={<AdminCategories />} />
  <Route path="coupons" element={<AdminCoupons />} />
  <Route path="reviews" element={<AdminReviews />} />
  <Route path="staff" element={<AdminStaff />} />   {/* ADMIN/SUPER_ADMIN apenas via UI */}
</Route>
```

---

## 🏪 State Management (Redux Toolkit)

### Auth Slice
```typescript
// Tokens armazenados em localStorage como 'access_token' e 'refresh_token'
interface AuthState {
  user: User | null;
  access_token: string | null;
  refresh_token: string | null;
  loading: boolean;
}
```

### Cart Slice
```typescript
interface CartItem {
  product: Product;
  quantity: number;
  design_id?: string;
}

interface CartState {
  items: CartItem[];
  total: number;  // Calculado automaticamente
}
```

---

## 🔌 Serviço HTTP (Axios + Interceptors)

```typescript
// services/api/client.ts
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
});

// Request: Anexa Bearer token automaticamente
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Response: Renova token expirado automaticamente (401)
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Tenta refresh, reenvia request original
      // Se refresh falhar → logout
    }
    return Promise.reject(error);
  }
);
```

---

## 📋 Tipos (entities.ts)

```typescript
export const UserRole = {
  CUSTOMER:    'CUSTOMER',
  MODERATOR:   'MODERATOR',
  ADMIN:       'ADMIN',
  SUPER_ADMIN: 'SUPER_ADMIN',
} as const;

export interface User {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  avatar_url?: string;
  role: 'CUSTOMER' | 'MODERATOR' | 'ADMIN' | 'SUPER_ADMIN';
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  slug: string;
  description: string;
  price: number;
  discount_price?: number;
  discount_percent?: number;
  stock_quantity: number;
  status: 'ACTIVE' | 'INACTIVE' | 'OUT_OF_STOCK';
  is_featured: boolean;
  category: Category;
  images: ProductImage[];
}

export interface Order {
  id: string;
  order_number: string;
  status: 'PENDING' | 'PROCESSING' | 'SHIPPED' | 'DELIVERED' | 'CANCELLED';
  payment_status: 'PENDING' | 'COMPLETED' | 'FAILED' | 'REFUNDED';
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  shipping_cost: number;
  total: number;
  items: OrderItem[];
  coupon?: Coupon;
  customer_notes?: string;
  created_at: string;
}
```

---

## �� Padrões Principais

### API — snake_case em tudo
Todas as respostas da API e todos os dados enviados ao backend usam **snake_case**. Sem exceções.

```typescript
// ✅ Correto
{ first_name: 'João', access_token: '...', coupon_code: 'WELCOME10' }

// ❌ Nunca
{ firstName: 'João', accessToken: '...', couponCode: 'WELCOME10' }
```

### Response Envelope Padrão
```json
{
  "success": true,
  "data": { ... },
  "message": "Success",
  "meta": { "total": 55, "page": 1, "limit": 12, "total_pages": 5 }
}
```

### ProtectedRoute
```tsx
// Aceita lista de roles — sem role = qualquer usuário autenticado
<ProtectedRoute roles={['ADMIN', 'SUPER_ADMIN', 'MODERATOR']}>
  <AdminLayout />
</ProtectedRoute>
```

### AdminLayout — Navegação Condicional
```
adminOnly: true → visível apenas para ADMIN e SUPER_ADMIN
adminOnly: false/undefined → visível para todos no admin (incluindo MODERATOR)

Nav items:
- Dashboard     (todos)
- Produtos      (todos)
- Pedidos       (todos)
- Categorias    (todos)
- Cupons        (todos)
- Reviews       (todos)
- Staff         (adminOnly: true — apenas ADMIN/SUPER_ADMIN)
```

---

## 🛒 Checkout com Cupom

O `CheckoutPage` suporta aplicação de cupom de desconto:

1. Usuário digita código e clica **Aplicar**
2. Frontend chama `POST /api/v1/coupons/validate` com `{ code, subtotal }`
3. Backend valida e retorna `{ coupon, discount }`
4. Desconto é exibido no resumo do pedido
5. Na criação do pedido, `coupon_code` é enviado no body

---

## 🔑 Variáveis de Ambiente

```env
# frontend/.env
VITE_API_BASE_URL=http://localhost:8000
```

---

**Última atualização**: Abril 2026
