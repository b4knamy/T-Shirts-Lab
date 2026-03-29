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
│   │   │   ├── ProfileForm/
│   │   │   ├── OrderHistory/
│   │   │   └── AddressBook/
│   │   │
│   │   └── admin/
│   │       ├── Dashboard/
│   │       ├── ProductManagement/
│   │       ├── OrderManagement/
│   │       └── UserManagement/
│   │
│   ├── pages/
│   │   ├── HomePage.tsx
│   │   ├── ProductsPage.tsx
│   │   ├── ProductDetailPage.tsx
│   │   ├── CartPage.tsx
│   │   ├── CheckoutPage.tsx
│   │   ├── LoginPage.tsx
│   │   ├── RegisterPage.tsx
│   │   ├── ProfilePage.tsx
│   │   ├── OrdersPage.tsx
│   │   ├── NotFoundPage.tsx
│   │   └── admin/
│   │       ├── AdminDashboard.tsx
│   │       ├── AdminProducts.tsx
│   │       └── AdminOrders.tsx
│   │
│   ├── store/
│   │   ├── store.ts
│   │   ├── hooks.ts
│   │   └── slices/
│   │       ├── authSlice.ts
│   │       ├── cartSlice.ts
│   │       ├── productSlice.ts
│   │       └── orderSlice.ts
│   │
│   ├── services/
│   │   └── api/
│   │       ├── client.ts          # Axios instance
│   │       ├── auth.ts            # Auth endpoints
│   │       ├── products.ts        # Product endpoints
│   │       ├── orders.ts          # Order endpoints
│   │       └── payments.ts        # Payment endpoints
│   │
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useCart.ts
│   │   ├── useProducts.ts
│   │   └── useDebounce.ts
│   │
│   ├── types/
│   │   ├── auth.types.ts
│   │   ├── product.types.ts
│   │   ├── order.types.ts
│   │   └── common.types.ts
│   │
│   ├── utils/
│   │   ├── formatters.ts
│   │   ├── validators.ts
│   │   └── constants.ts
│   │
│   ├── styles/
│   │   └── globals.css
│   │
│   ├── App.tsx
│   ├── main.tsx
│   └── vite-env.d.ts
│
├── public/
│   └── assets/
│
├── index.html
├── vite.config.ts
├── tailwind.config.ts
├── tsconfig.json
├── package.json
├── .env
└── .env.example
```

---

## 🧩 Tipos de Componentes

### 1. Presentational Components (UI puro)
```tsx
// Apenas renderização, sem lógica de negócio
interface ProductCardProps {
  product: Product;
  onAddToCart: (product: Product) => void;
}

const ProductCard: React.FC<ProductCardProps> = ({ product, onAddToCart }) => {
  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden">
      <img src={product.images[0]?.imageUrl} alt={product.name} />
      <div className="p-4">
        <h3 className="text-lg font-semibold">{product.name}</h3>
        <p className="text-gray-600">R$ {product.price.toFixed(2)}</p>
        <button onClick={() => onAddToCart(product)}>
          Adicionar ao Carrinho
        </button>
      </div>
    </div>
  );
};
```

### 2. Container Components (Lógica + Data)
```tsx
// Conecta ao Redux e gerencia dados
const ProductsContainer: React.FC = () => {
  const dispatch = useAppDispatch();
  const { products, loading, error } = useAppSelector(state => state.products);

  useEffect(() => {
    dispatch(fetchProducts());
  }, [dispatch]);

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorMessage message={error} />;

  return <ProductGrid products={products} />;
};
```

### 3. Custom Hooks (Lógica reutilizável)
```tsx
const useAuth = () => {
  const dispatch = useAppDispatch();
  const { user, token, loading } = useAppSelector(state => state.auth);

  const login = async (credentials: LoginCredentials) => {
    return dispatch(loginUser(credentials)).unwrap();
  };

  const logout = () => {
    dispatch(logoutUser());
  };

  return { user, token, loading, login, logout, isAuthenticated: cat > /home/b4knamy/Projects/tshirtslab/project_context/GETTING_STARTED.md << 'MDEOF'
# Getting Started - T-Shirts Lab

## 📋 Pré-requisitos

### Para Desenvolvimento com Docker (Recomendado)
- Docker 24+
- Docker Compose 2.x
- Git

### Para Desenvolvimento Local
- PHP 8.4+
- Composer 2.x
- Node.js 20+ e npm 10+
- PostgreSQL 15+
- Redis 7+
- Git

---

## 🚀 Quick Start com Docker

### 1. Clone o Repositório
```bash
git clone https://github.com/b4knamy/tshirts-lab.git
cd tshirts-lab
```

### 2. Configure as Variáveis de Ambiente
```bash
# Backend
cp backend/.env.example backend/.env

# Frontend
cp frontend/.env.example frontend/.env
```

### 3. Inicie os Containers
```bash
docker-compose up -d
```

Isso irá iniciar:
- **Backend (Laravel)**: http://localhost:8000
- **Frontend (React)**: http://localhost:5173
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

### 4. Execute as Migrations e Seeds
```bash
docker-compose exec backend php artisan migrate --seed
```

### 5. Acesse a Aplicação
- **Frontend**: http://localhost:5173
- **API**: http://localhost:8000/api/v1/health

### Login Admin
- Email: `admin@tshirtslab.com`
- Senha: `Admin@123`

---

## 🛠️ Desenvolvimento Local (sem Docker)

### 1. PostgreSQL e Redis

Instale e configure PostgreSQL e Redis localmente:

```bash
# Ubuntu/Debian
sudo apt install postgresql redis-server

# macOS
brew install postgresql redis

# Inicie os serviços
sudo systemctl start postgresql redis
# ou macOS: brew services start postgresql redis
```

Crie o banco de dados:
```sql
CREATE DATABASE tshirtslab_db;
CREATE USER tshirtslab WITH PASSWORD 'tshirtslab_secret';
GRANT ALL PRIVILEGES ON DATABASE tshirtslab_db TO tshirtslab;
```

### 2. Backend (Laravel)

```bash
cd backend

# Instale dependências
composer install

# Configure ambiente
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Edite .env com suas configurações de banco
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=tshirtslab_db
# DB_USERNAME=tshirtslab
# DB_PASSWORD=tshirtslab_secret

# Execute migrations e seeds
php artisan migrate --seed

# Inicie o servidor
php artisan serve --port=8000
```

O backend estará disponível em http://localhost:8000.

### 3. Frontend (React)

```bash
cd frontend

# Instale dependências
npm install

# Configure ambiente
cp .env.example .env
# Verifique que VITE_API_BASE_URL=http://localhost:8000

# Inicie o dev server
npm run dev
```

O frontend estará disponível em http://localhost:5173.

---

## ⚙️ Configuração do Backend (.env)

### Variáveis Essenciais

```env
# Aplicação
APP_NAME=TShirtsLab
APP_ENV=local
APP_KEY=                    # Gerada por php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tshirtslab_db
DB_USERNAME=tshirtslab
DB_PASSWORD=tshirtslab_secret

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# JWT
JWT_SECRET=                 # Gerada por php artisan jwt:secret
JWT_TTL=15                  # Minutos (access token)
JWT_REFRESH_TTL=10080       # Minutos (7 dias, refresh token)

# Stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# CORS
FRONTEND_URL=http://localhost:5173
```

### Variáveis do Frontend (.env)

```env
VITE_API_BASE_URL=http://localhost:8000
```

---

## 🧪 Verificação da Instalação

### 1. Health Check
```bash
curl http://localhost:8000/api/v1/health
```

Resposta esperada:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "services": {
      "database": "connected",
      "redis": "connected",
      "stripe": "configured"
    }
  }
}
```

### 2. Login de Teste
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@tshirtslab.com","password":"Admin@123"}'
```

### 3. Listar Produtos
```bash
curl http://localhost:8000/api/v1/products
```

### 4. Frontend
Abra http://localhost:5173 no navegador e verifique:
- A página carrega sem erros
- Produtos são exibidos
- Login funciona com as credenciais de teste

---

## 📝 Comandos Úteis

### Backend (Laravel)
```bash
# Artisan
php artisan migrate              # Executar migrations
php artisan migrate:fresh --seed # Resetar DB e popular
php artisan route:list           # Listar todas as rotas
php artisan tinker               # Console interativo
php artisan cache:clear          # Limpar cache
php artisan config:clear         # Limpar cache de config
php artisan queue:work           # Processar fila

# Testes
php artisan test                 # Executar todos os testes
php artisan test --filter=Auth   # Filtrar testes

# Composer
composer install                 # Instalar dependências
composer update                  # Atualizar dependências
composer dump-autoload           # Regenerar autoload
```

### Frontend (React)
```bash
npm run dev          # Servidor de desenvolvimento
npm run build        # Build de produção
npm run preview      # Preview do build
npm run lint         # Executar linter
```

### Docker
```bash
docker-compose up -d              # Iniciar todos os serviços
docker-compose down               # Parar todos os serviços
docker-compose logs -f backend    # Ver logs do backend
docker-compose logs -f frontend   # Ver logs do frontend
docker-compose exec backend bash  # Shell no container backend
docker-compose build --no-cache   # Rebuild sem cache
```

---

## �� Stripe (Ambiente de Teste)

### 1. Criar Conta Stripe
Acesse https://dashboard.stripe.com e crie uma conta de teste.

### 2. Obter API Keys
No Dashboard do Stripe → Developers → API Keys:
- **Secret key**: `sk_test_...`

### 3. Configurar Webhook (Local)
```bash
# Instale o Stripe CLI
brew install stripe/stripe-cli/stripe  # macOS
# ou baixe de https://stripe.com/docs/stripe-cli

# Login
stripe login

# Forward webhooks para o backend local
stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

O CLI exibirá o `whsec_...` - adicione ao `.env` como `STRIPE_WEBHOOK_SECRET`.

### 4. Cartões de Teste
| Número | Resultado |
|--------|-----------|
| 4242 4242 4242 4242 | Sucesso |
| 4000 0000 0000 0002 | Recusado |
| 4000 0000 0000 3220 | 3D Secure |

---

## ❓ Troubleshooting

### Erro: "Could not find driver" (pdo_pgsql)
```bash
# PHP precisa da extensão pgsql
sudo apt install php8.4-pgsql  # Ubuntu
brew install php@8.4            # macOS (inclui pgsql)
```

### Erro: "Connection refused" no PostgreSQL
```bash
# Verifique se o PostgreSQL está rodando
sudo systemctl status postgresql
# Verifique as configurações no .env
```

### Erro: "Token not found" ou 401
```bash
# Regenere o JWT secret
php artisan jwt:secret --force
# Limpe o cache
php artisan config:clear
php artisan cache:clear
```

### Erro: CORS no Frontend
```bash
# Verifique FRONTEND_URL no .env do backend
# Deve ser exatamente: http://localhost:5173
php artisan config:clear
```

### Frontend não conecta ao Backend
```bash
# Verifique se o backend está rodando na porta 8000
curl http://localhost:8000/api/v1/health
# Verifique VITE_API_BASE_URL no .env do frontend
```

### Redis não conecta
```bash
# Verifique se o Redis está rodando
redis-cli ping  # Deve retornar PONG
# Verifique REDIS_HOST e REDIS_PORT no .env
```

---

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
MDEOFtoken };
};
```

---

## 📦 State Management (Redux Toolkit)

### Store Configuration
```typescript
// src/store/store.ts
import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import cartReducer from './slices/cartSlice';
import productReducer from './slices/productSlice';
import orderReducer from './slices/orderSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    cart: cartReducer,
    products: productReducer,
    orders: orderReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
```

### Auth Slice
```typescript
// src/store/slices/authSlice.ts
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { authApi } from '../../services/api/auth';

export const loginUser = createAsyncThunk(
  'auth/login',
  async (credentials: LoginCredentials) => {
    const response = await authApi.login(credentials);
    return response.data; // { user, accessToken, refreshToken }
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState: {
    user: null,
    token: localStorage.getItem('accessToken'),
    refreshToken: localStorage.getItem('refreshToken'),
    loading: false,
    error: null,
  },
  reducers: {
    logoutUser: (state) => {
      state.user = null;
      state.token = null;
      state.refreshToken = null;
      localStorage.removeItem('accessToken');
      localStorage.removeItem('refreshToken');
    },
    setTokens: (state, action) => {
      state.token = action.payload.accessToken;
      state.refreshToken = action.payload.refreshToken;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginUser.pending, (state) => { state.loading = true; })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload.user;
        state.token = action.payload.accessToken;
        state.refreshToken = action.payload.refreshToken;
        localStorage.setItem('accessToken', action.payload.accessToken);
        localStorage.setItem('refreshToken', action.payload.refreshToken);
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message;
      });
  },
});
```

### Cart Slice
```typescript
// src/store/slices/cartSlice.ts
interface CartItem {
  product: Product;
  design?: Design;
  quantity: number;
  customizationData?: Record<string, any>;
}

const cartSlice = createSlice({
  name: 'cart',
  initialState: {
    items: [] as CartItem[],
    total: 0,
  },
  reducers: {
    addToCart: (state, action: PayloadAction<CartItem>) => {
      const existing = state.items.find(
        item => item.product.id === action.payload.product.id
          && item.design?.id === action.payload.design?.id
      );
      if (existing) {
        existing.quantity += action.payload.quantity;
      } else {
        state.items.push(action.payload);
      }
      state.total = state.items.reduce(
        (sum, item) => sum + item.product.price * item.quantity, 0
      );
    },
    removeFromCart: (state, action: PayloadAction<string>) => {
      state.items = state.items.filter(item => item.product.id !== action.payload);
      state.total = state.items.reduce(
        (sum, item) => sum + item.product.price * item.quantity, 0
      );
    },
    clearCart: (state) => {
      state.items = [];
      state.total = 0;
    },
  },
});
```

---

## 🌐 API Integration

### Axios Client
```typescript
// src/services/api/client.ts
import axios from 'axios';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  headers: { 'Content-Type': 'application/json' },
});

// Request interceptor - adiciona JWT token
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('accessToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor - token refresh automático
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = localStorage.getItem('refreshToken');
        const response = await axios.post(
          `${import.meta.env.VITE_API_BASE_URL}/api/v1/auth/refresh`,
          {},
          { headers: { Authorization: `Bearer ${refreshToken}` } }
        );

        const { accessToken, refreshToken: newRefreshToken } = response.data.data;
        localStorage.setItem('accessToken', accessToken);
        localStorage.setItem('refreshToken', newRefreshToken);

        originalRequest.headers.Authorization = `Bearer ${accessToken}`;
        return apiClient(originalRequest);
      } catch (refreshError) {
        localStorage.removeItem('accessToken');
        localStorage.removeItem('refreshToken');
        window.location.href = '/login';
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
```

### API Service Examples
```typescript
// src/services/api/products.ts
import apiClient from './client';

export const productsApi = {
  getAll: (params?: { page?: number; limit?: number; category?: string }) =>
    apiClient.get('/api/v1/products', { params }),

  getById: (id: string) =>
    apiClient.get(`/api/v1/products/${id}`),

  getBySlug: (slug: string) =>
    apiClient.get(`/api/v1/products/slug/${slug}`),

  getFeatured: () =>
    apiClient.get('/api/v1/products/featured'),

  getCategories: () =>
    apiClient.get('/api/v1/products/categories'),
};

// src/services/api/orders.ts
export const ordersApi = {
  create: (data: CreateOrderDTO) =>
    apiClient.post('/api/v1/orders', data),

  getMyOrders: () =>
    apiClient.get('/api/v1/orders/my-orders'),

  getById: (id: string) =>
    apiClient.get(`/api/v1/orders/${id}`),
};

// src/services/api/payments.ts
export const paymentsApi = {
  createIntent: (data: { orderId: string; amount: number }) =>
    apiClient.post('/api/v1/payments/create-intent', data),

  confirm: (data: { paymentIntentId: string }) =>
    apiClient.post('/api/v1/payments/confirm', data),

  getStatus: (paymentIntentId: string) =>
    apiClient.get(`/api/v1/payments/${paymentIntentId}`),
};
```

### Formato de Resposta da API (Laravel Backend)

O backend Laravel retorna respostas padronizadas via `ApiResponse` trait:

```json
// Sucesso
{
  "success": true,
  "data": { ... },
  "message": "Operação realizada com sucesso"
}

// Sucesso com paginação
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "currentPage": 1,
    "perPage": 15,
    "total": 100,
    "lastPage": 7
  }
}

// Erro
{
  "success": false,
  "message": "Mensagem de erro",
  "errors": { ... }
}
```

---

## 🛣️ Routing Architecture

```typescript
// src/App.tsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';

const App = () => (
  <BrowserRouter>
    <Routes>
      {/* Public Routes */}
      <Route path="/" element={<MainLayout />}>
        <Route index element={<HomePage />} />
        <Route path="products" element={<ProductsPage />} />
        <Route path="products/:slug" element={<ProductDetailPage />} />
        <Route path="login" element={<LoginPage />} />
        <Route path="register" element={<RegisterPage />} />
      </Route>

      {/* Protected Routes */}
      <Route element={<ProtectedRoute />}>
        <Route path="/" element={<MainLayout />}>
          <Route path="cart" element={<CartPage />} />
          <Route path="checkout" element={<CheckoutPage />} />
          <Route path="profile" element={<ProfilePage />} />
          <Route path="orders" element={<OrdersPage />} />
        </Route>
      </Route>

      {/* Admin Routes */}
      <Route element={<ProtectedRoute requiredRole="ADMIN" />}>
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<AdminDashboard />} />
          <Route path="products" element={<AdminProducts />} />
          <Route path="orders" element={<AdminOrders />} />
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  </BrowserRouter>
);
```

### Protected Route Component
```tsx
const ProtectedRoute: React.FC<{ requiredRole?: string }> = ({ requiredRole }) => {
  const { isAuthenticated, user } = useAuth();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (requiredRole && user?.role !== requiredRole && user?.role !== 'SUPER_ADMIN') {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
};
```

---

## 🎨 Styling Strategy

### TailwindCSS v4 + CSS Modules

```tsx
// TailwindCSS para layout e utilitários
<div className="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
  <ProductCard product={product} />
</div>

// CSS Modules para componentes complexos
import styles from './Header.module.css';

const Header = () => (
  <header className={styles.header}>
    <nav className={styles.nav}>...</nav>
  </header>
);
```

### Design Tokens (Tailwind config)
```typescript
// tailwind.config.ts
export default {
  theme: {
    extend: {
      colors: {
        primary: { 50: '#eff6ff', 500: '#3b82f6', 700: '#1d4ed8' },
        secondary: { 50: '#f0fdf4', 500: '#22c55e', 700: '#15803d' },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['Poppins', 'sans-serif'],
      },
    },
  },
};
```

---

## 🧪 Testing Strategy

### Ferramentas
- **Vitest**: Test runner (compatível com Vite)
- **React Testing Library**: Testes de componentes
- **MSW**: Mock de API

### Exemplo de Teste
```tsx
// ProductCard.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { ProductCard } from './ProductCard';

const mockProduct = {
  id: '1',
  name: 'Camiseta Anime',
  slug: 'camiseta-anime',
  price: 79.90,
  images: [{ imageUrl: '/test.jpg', altText: 'Test' }],
};

describe('ProductCard', () => {
  it('renders product info', () => {
    render(<ProductCard product={mockProduct} onAddToCart={vi.fn()} />);
    expect(screen.getByText('Camiseta Anime')).toBeInTheDocument();
    expect(screen.getByText('R$ 79.90')).toBeInTheDocument();
  });

  it('calls onAddToCart when button clicked', () => {
    const onAddToCart = vi.fn();
    render(<ProductCard product={mockProduct} onAddToCart={onAddToCart} />);
    fireEvent.click(screen.getByText(/adicionar/i));
    expect(onAddToCart).toHaveBeenCalledWith(mockProduct);
  });
});
```

---

## ⚡ Performance Optimization

### 1. Code Splitting & Lazy Loading
```tsx
const AdminDashboard = React.lazy(() => import('./pages/admin/AdminDashboard'));
const CheckoutPage = React.lazy(() => import('./pages/CheckoutPage'));

// No router
<Suspense fallback={<LoadingSpinner />}>
  <Route path="/admin" element={<AdminDashboard />} />
</Suspense>
```

### 2. Memoization
```tsx
const ProductGrid = React.memo<ProductGridProps>(({ products }) => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      {products.map(product => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
});
```

### 3. Image Optimization
```tsx
// Lazy loading de imagens
<img
  src={product.images[0]?.imageUrl}
  alt={product.name}
  loading="lazy"
  decoding="async"
/>
```

---

## 🔒 Frontend Security

### 1. XSS Prevention
- React escapa automaticamente todas as expressões JSX
- Nunca usar `dangerouslySetInnerHTML` com dados do usuário
- Sanitizar inputs com Zod antes de enviar ao backend

### 2. CSRF Protection
- JWT em `localStorage` (não cookies) - não vulnerável a CSRF
- Token enviado explicitamente no header `Authorization`

### 3. Sensitive Data
- Nenhuma chave secreta no frontend
- Stripe usa `publishable key` (segura para client-side)
- Variáveis de ambiente via `VITE_*` prefix

### 4. Form Validation (Zod)
```typescript
import { z } from 'zod';

const loginSchema = z.object({
  email: z.string().email('Email inválido'),
  password: z.string().min(6, 'Mínimo 6 caracteres'),
});

const registerSchema = z.object({
  firstName: z.string().min(2).max(100),
  lastName: z.string().min(2).max(100),
  email: z.string().email(),
  password: z.string().min(8).regex(
    /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/,
    'Deve conter maiúscula, minúscula e número'
  ),
  confirmPassword: z.string(),
}).refine(data => data.password === data.confirmPassword, {
  message: 'Senhas não coincidem',
  path: ['confirmPassword'],
});
```

---

## 🔗 Integração com Backend Laravel

### Base URL
```env
VITE_API_BASE_URL=http://localhost:8000
```

### Proxy (Development)
```typescript
// vite.config.ts
export default defineConfig({
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
});
```

### Autenticação
O frontend se comunica com o backend Laravel via JWT:
1. **Login**: `POST /api/v1/auth/login` → recebe `{ accessToken, refreshToken }`
2. **Requests**: Header `Authorization: Bearer <accessToken>`
3. **Refresh**: Automático via interceptor quando recebe 401
4. **Logout**: `POST /api/v1/auth/logout` + limpa localStorage

### Formato de Dados
- **Backend (Laravel)**: Eloquent models usam `snake_case` internamente
- **API Response**: Controllers retornam `camelCase` para o frontend
- **Frontend (React)**: TypeScript interfaces usam `camelCase`

---

## 📝 Convenções de Código

| Aspecto | Convenção |
|---------|-----------|
| Componentes | PascalCase (`ProductCard.tsx`) |
| Hooks | camelCase com `use` prefix (`useAuth.ts`) |
| Tipos | PascalCase com suffix (`Product.types.ts`) |
| Constantes | UPPER_SNAKE_CASE (`API_BASE_URL`) |
| CSS Modules | camelCase (`styles.headerContainer`) |
| Arquivos | PascalCase para componentes, camelCase para utils |

---

**Stack Frontend**: React 18 + TypeScript 5.7 + Vite 8 + Redux Toolkit + TailwindCSS v4
**Backend**: Laravel 13 (PHP 8.4) em http://localhost:8000

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
