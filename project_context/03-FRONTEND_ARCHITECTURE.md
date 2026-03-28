# Arquitetura Frontend - React.js

## 🏗️ Estrutura do Projeto Frontend

```
frontend/
├── src/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Header/
│   │   │   │   ├── Header.tsx
│   │   │   │   ├── Header.module.css
│   │   │   │   └── Header.test.tsx
│   │   │   ├── Footer/
│   │   │   ├── Navigation/
│   │   │   ├── LoadingSpinner/
│   │   │   ├── ErrorBoundary/
│   │   │   └── Modal/
│   │   │
│   │   ├── layout/
│   │   │   ├── MainLayout.tsx
│   │   │   ├── AuthLayout.tsx
│   │   │   └── AdminLayout.tsx
│   │   │
│   │   ├── auth/
│   │   │   ├── LoginForm/
│   │   │   ├── RegisterForm/
│   │   │   ├── ProtectedRoute/
│   │   │   └── PrivateRoute/
│   │   │
│   │   ├── products/
│   │   │   ├── ProductCard/
│   │   │   ├── ProductGrid/
│   │   │   ├── ProductDetail/
│   │   │   ├── ProductFilter/
│   │   │   └── ProductSearch/
│   │   │
│   │   ├── cart/
│   │   │   ├── CartSidebar/
│   │   │   ├── CartItem/
│   │   │   ├── CartSummary/
│   │   │   └── Checkout/
│   │   │
│   │   ├── checkout/
│   │   │   ├── CheckoutForm/
│   │   │   ├── PaymentForm/
│   │   │   ├── AddressForm/
│   │   │   └── OrderSummary/
│   │   │
│   │   ├── user/
│   │   │   ├── ProfileCard/
│   │   │   ├── OrderHistory/
│   │   │   ├── AddressManager/
│   │   │   └── PreferencesPanel/
│   │   │
│   │   └── admin/
│   │       ├── ProductManager/
│   │       ├── OrderManager/
│   │       ├── UserManager/
│   │       └── Dashboard/
│   │
│   ├── pages/
│   │   ├── Home.tsx
│   │   ├── Products.tsx
│   │   ├── ProductDetail.tsx
│   │   ├── Cart.tsx
│   │   ├── Checkout.tsx
│   │   ├── OrderConfirmation.tsx
│   │   ├── Profile.tsx
│   │   ├── Login.tsx
│   │   ├── Register.tsx
│   │   ├── NotFound.tsx
│   │   ├── admin/
│   │   │   ├── Dashboard.tsx
│   │   │   ├── Products.tsx
│   │   │   ├── Orders.tsx
│   │   │   └── Users.tsx
│   │   └── 500.tsx
│   │
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useCart.ts
│   │   ├── useFetch.ts
│   │   ├── useLocalStorage.ts
│   │   ├── usePagination.ts
│   │   ├── useDebounce.ts
│   │   ├── useAsync.ts
│   │   └── useApi.ts
│   │
│   ├── services/
│   │   ├── api/
│   │   │   ├── client.ts           # Axios instance
│   │   │   ├── auth.ts
│   │   │   ├── products.ts
│   │   │   ├── orders.ts
│   │   │   ├── cart.ts
│   │   │   ├── payments.ts
│   │   │   ├── users.ts
│   │   │   └── admin.ts
│   │   │
│   │   ├── storage/
│   │   │   ├── localStorage.ts
│   │   │   ├── sessionStorage.ts
│   │   │   └── cookies.ts
│   │   │
│   │   └── tracking/
│   │       └── analytics.ts
│   │
│   ├── store/
│   │   ├── slices/
│   │   │   ├── authSlice.ts
│   │   │   ├── cartSlice.ts
│   │   │   ├── productSlice.ts
│   │   │   ├── filterSlice.ts
│   │   │   └── uiSlice.ts
│   │   │
│   │   └── store.ts               # Redux store configuration
│   │
│   ├── types/
│   │   ├── api.ts                 # API response types
│   │   ├── entities.ts            # Business entities
│   │   ├── forms.ts               # Form types
│   │   └── ui.ts                  # UI-related types
│   │
│   ├── utils/
│   │   ├── api.ts                 # API utilities
│   │   ├── format.ts              # Formatadores (currency, date)
│   │   ├── validation.ts          # Form validation schemas (Zod)
│   │   ├── constants.ts           # Constantes globais
│   │   └── helpers.ts
│   │
│   ├── styles/
│   │   ├── globals.css            # Global styles
│   │   ├── variables.css          # CSS variables
│   │   ├── responsive.css         # Media queries
│   │   └── animations.css
│   │
│   ├── i18n/                      # Internacionalização (futuro)
│   │   ├── en.json
│   │   ├── pt-BR.json
│   │   └── config.ts
│   │
│   ├── App.tsx                    # Root component
│   ├── App.css
│   ├── main.tsx                   # Entry point
│   └── vite-env.d.ts
│
├── public/
│   ├── images/
│   ├── icons/
│   └── favicon.ico
│
├── tests/
│   ├── unit/
│   ├── integration/
│   └── e2e/
│
├── .env.example
├── package.json
├── tsconfig.json
├── vite.config.ts
├── vitest.config.ts
├── .eslintrc.cjs
├── .prettierrc
├── tailwind.config.ts
└── README.md
```

## 🎨 Component Architecture

### Component Types

#### 1. Presentational Components
```typescript
// ProductCard.tsx
interface ProductCardProps {
  product: Product;
  onAddToCart: (id: string) => void;
}

export const ProductCard: React.FC<ProductCardProps> = ({
  product,
  onAddToCart,
}) => {
  return (
    <div className="product-card">
      <img src={product.image} alt={product.name} />
      <h3>{product.name}</h3>
      <p>${product.price}</p>
      <button onClick={() => onAddToCart(product.id)}>
        Add to Cart
      </button>
    </div>
  );
};
```

#### 2. Container Components
```typescript
// ProductsContainer.tsx
export const ProductsContainer: React.FC = () => {
  const { products, loading } = useCart();
  const dispatch = useDispatch();

  return (
    <ProductGrid
      products={products}
      loading={loading}
      onAddToCart={(id) => dispatch(addToCart(id))}
    />
  );
};
```

#### 3. Hook Components
```typescript
// useAuth.ts
export const useAuth = () => {
  const user = useSelector(selectUser);
  const dispatch = useDispatch();

  const login = useCallback(
    async (email: string, password: string) => {
      const data = await authService.login(email, password);
      dispatch(setUser(data));
    },
    [dispatch],
  );

  return { user, login };
};
```

## 🔄 State Management (Redux Toolkit)

### Store Structure
```typescript
// store.ts
import { configureStore } from '@reduxjs/toolkit';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    cart: cartReducer,
    products: productReducer,
    filters: filterReducer,
    ui: uiReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['auth/setUser'],
      },
    }).concat(logger),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
```

### Slice Example
```typescript
// cartSlice.ts
import { createSlice, PayloadAction } from '@reduxjs/toolkit';

interface CartItem {
  productId: string;
  quantity: number;
  price: number;
}

interface CartState {
  items: CartItem[];
  total: number;
  loading: boolean;
}

const initialState: CartState = {
  items: [],
  total: 0,
  loading: false,
};

export const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    addToCart: (state, action: PayloadAction<CartItem>) => {
      const existing = state.items.find(
        (item) => item.productId === action.payload.productId,
      );
      if (existing) {
        existing.quantity += action.payload.quantity;
      } else {
        state.items.push(action.payload);
      }
      state.total += action.payload.price * action.payload.quantity;
    },
    removeFromCart: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter(
        (item) => item.productId !== action.payload,
      );
    },
  },
});

export const { addToCart, removeFromCart } = cartSlice.actions;
export default cartSlice.reducer;
```

## 🎯 Routing Architecture

```typescript
// App.tsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { ProtectedRoute } from './components/auth/ProtectedRoute';

export const App: React.FC = () => {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<MainLayout />}>
          <Route index element={<Home />} />
          <Route path="products" element={<Products />} />
          <Route path="products/:id" element={<ProductDetail />} />
          <Route path="cart" element={<Cart />} />

          {/* Protected Routes */}
          <Route element={<ProtectedRoute />}>
            <Route path="checkout" element={<Checkout />} />
            <Route path="profile" element={<Profile />} />
            <Route path="orders" element={<OrderHistory />} />
          </Route>

          {/* Admin Routes */}
          <Route element={<ProtectedRoute requiredRole="admin" />}>
            <Route path="admin" element={<AdminLayout />}>
              <Route index element={<AdminDashboard />} />
              <Route path="products" element={<ProductManager />} />
              <Route path="orders" element={<OrderManager />} />
            </Route>
          </Route>
        </Route>

        <Route path="/auth" element={<AuthLayout />}>
          <Route path="login" element={<Login />} />
          <Route path="register" element={<Register />} />
        </Route>

        <Route path="*" element={<NotFound />} />
      </Routes>
    </BrowserRouter>
  );
};
```

## 📡 API Integration

### Axios Client Setup
```typescript
// services/api/client.ts
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request Interceptor
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('accessToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response Interceptor
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle token refresh or logout
      localStorage.removeItem('accessToken');
      window.location.href = '/auth/login';
    }
    return Promise.reject(error);
  },
);
```

### Service Layer
```typescript
// services/api/products.ts
import { apiClient } from './client';

export const productService = {
  async getAll(
    page: number = 1,
    limit: number = 20,
    filters?: ProductFilters,
  ) {
    const { data } = await apiClient.get('/v1/products', {
      params: { page, limit, ...filters },
    });
    return data;
  },

  async getById(id: string) {
    const { data } = await apiClient.get(`/v1/products/${id}`);
    return data;
  },

  async create(product: CreateProductDTO) {
    const { data } = await apiClient.post('/v1/products', product);
    return data;
  },

  async update(id: string, product: UpdateProductDTO) {
    const { data } = await apiClient.patch(`/v1/products/${id}`, product);
    return data;
  },

  async delete(id: string) {
    await apiClient.delete(`/v1/products/${id}`);
  },
};
```

## 🎨 Styling Strategy

### TailwindCSS + CSS Modules

```tsx
// ProductCard.module.css
.card {
  @apply rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow;
}

.image {
  @apply w-full h-48 object-cover rounded-md mb-4;
}

.title {
  @apply text-lg font-semibold text-gray-800 mb-2;
}

.price {
  @apply text-2xl font-bold text-blue-600 mb-4;
}

.button {
  @apply w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition-colors;
}
```

```tsx
// ProductCard.tsx
import styles from './ProductCard.module.css';

export const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
  return (
    <div className={styles.card}>
      <img src={product.image} alt={product.name} className={styles.image} />
      <h3 className={styles.title}>{product.name}</h3>
      <p className={styles.price}>${product.price}</p>
      <button className={styles.button}>Add to Cart</button>
    </div>
  );
};
```

## 🧪 Testing Strategy

### Unit Tests (Vitest + React Testing Library)
```tsx
// ProductCard.test.tsx
import { render, screen } from '@testing-library/react';
import { ProductCard } from './ProductCard';

describe('ProductCard', () => {
  it('should render product name', () => {
    const product = { id: '1', name: 'T-Shirt', price: 29.99 };
    render(<ProductCard product={product} onAddToCart={() => {}} />);
    expect(screen.getByText('T-Shirt')).toBeInTheDocument();
  });

  it('should call onAddToCart when button is clicked', () => {
    const onAddToCart = vi.fn();
    render(<ProductCard product={product} onAddToCart={onAddToCart} />);
    screen.getByText('Add to Cart').click();
    expect(onAddToCart).toHaveBeenCalled();
  });
});
```

### E2E Tests (Playwright)
```typescript
// tests/e2e/checkout.spec.ts
import { test, expect } from '@playwright/test';

test('complete checkout flow', async ({ page }) => {
  await page.goto('http://localhost:5173');
  await page.click('[data-testid="product-card"]');
  await page.click('[data-testid="add-to-cart"]');
  await page.click('[data-testid="cart-link"]');
  await page.click('[data-testid="checkout-button"]');
  
  expect(page.url()).toContain('/checkout');
});
```

## 🚀 Performance Optimization

### Code Splitting
```typescript
// Lazy loading de páginas
const ProductDetail = lazy(() => import('./pages/ProductDetail'));
const AdminDashboard = lazy(() => import('./pages/admin/Dashboard'));

export const App = () => (
  <Suspense fallback={<LoadingSpinner />}>
    <Routes>
      <Route path="products/:id" element={<ProductDetail />} />
      <Route path="admin" element={<AdminDashboard />} />
    </Routes>
  </Suspense>
);
```

### Image Optimization
```tsx
<img
  src={product.image}
  alt={product.name}
  loading="lazy"
  decoding="async"
  srcSet={`
    ${product.image}?w=400 400w,
    ${product.image}?w=800 800w,
    ${product.image}?w=1200 1200w
  `}
/>
```

### React Query for Data Fetching
```typescript
import { useQuery } from '@tanstack/react-query';

export const useProducts = () => {
  return useQuery({
    queryKey: ['products'],
    queryFn: () => productService.getAll(),
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
  });
};
```

## 🔐 Security Best Practices

- ✅ HTTPS-only communication
- ✅ Content Security Policy (CSP)
- ✅ XSS Prevention (React escapes by default)
- ✅ CSRF Protection
- ✅ Secure token storage (HttpOnly cookies)
- ✅ Input validation (Zod schemas)
- ✅ Sanitization of user inputs

## 🚀 Vite Configuration

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
    minify: 'terser',
  },
});
```

---

**Última atualização**: Março 2026
