# 📖 T-Shirts Lab - Documentação do Projeto

## Sobre

Documentação técnica completa do **T-Shirts Lab**, uma plataforma e-commerce para venda e customização de camisetas construída com **Laravel 11** (PHP 8.4) no backend e **React 19** (TypeScript) no frontend.

---

## Stack Técnico

| Camada | Tecnologia |
|--------|-----------|
| **Backend** | Laravel 11 (PHP 8.4) |
| **Frontend** | React 19 + Vite 6 + TypeScript 5.7 |
| **Banco de Dados** | PostgreSQL 15 |
| **Cache** | Redis 7 (Predis) |
| **Autenticação** | JWT (php-open-source-saver/jwt-auth) |
| **Pagamentos** | Stripe (stripe-php SDK) |
| **CSS** | TailwindCSS v4 |
| **State** | Redux Toolkit |
| **Deploy** | Docker Compose (PHP-FPM + Nginx + Supervisor) |

---

## 📚 Documentos Disponíveis

### Core
| # | Documento | Descrição |
|---|-----------|-----------|
| 1 | [01-OVERVIEW.md](./01-OVERVIEW.md) | Visão geral, stack, funcionalidades, estrutura |
| 2 | [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md) | Arquitetura Laravel: MVC, Eloquent, JWT, rotas |
| 3 | [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md) | Arquitetura React: Redux, Axios, componentes |
| 4 | [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md) | PostgreSQL, migrations, modelos, Redis cache |
| 5 | [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md) | Integração Stripe: PaymentIntent, webhooks |
| 6 | [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md) | Docker, CI/CD, Nginx, Supervisor |
| 7 | [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md) | Padrões Laravel, segurança, validação |

### Referência
| Documento | Descrição |
|-----------|-----------|
| [ARCHITECTURE_DIAGRAMS.md](./ARCHITECTURE_DIAGRAMS.md) | Diagramas ASCII: arquitetura, fluxos, ER |
| [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) | Comandos, endpoints, portas, env vars |
| [GETTING_STARTED.md](./GETTING_STARTED.md) | Guia de setup (Docker e local) |
| [INDEX.md](./INDEX.md) | Índice detalhado com todos os tópicos |

---

## 🚀 Quick Start

### Docker (Recomendado)
```bash
# Clonar e entrar no projeto
git clone <repo-url> tshirtslab && cd tshirtslab

# Configurar backend
cp backend/.env.example backend/.env

# Subir todos os serviços
docker compose up -d

# Migrations e seed
docker compose exec backend php artisan migrate --seed

# Gerar chave JWT
docker compose exec backend php artisan jwt:secret
```

### Local
```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve --port=8000

# Frontend (outro terminal)
cd frontend
npm install
npm run dev
```

### Acessos
| Serviço | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8000/api/v1 |
| Health Check | http://localhost:8000/api/v1/health |

### Credenciais Dev
- **Admin**: admin@tshirtslab.com / Admin@123

---

## 🏗️ Estrutura do Projeto

```
tshirtslab/
├── backend/                 # Laravel 13 (PHP 8.4)
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/  # 7 controllers
│   │   ├── Http/Middleware/           # JWT, Admin
│   │   ├── Models/                    # 9 Eloquent models
│   │   └── Traits/                    # ApiResponse
│   ├── config/                        # auth, cors, jwt, services
│   ├── database/migrations/           # 10 migrations
│   ├── database/seeders/              # DatabaseSeeder
│   ├── docker/                        # nginx, php.ini, supervisor
│   ├── routes/api.php                 # 25 API routes
│   └── Dockerfile
├── frontend/                # React 18 + TypeScript
│   ├── src/
│   │   ├── components/     # UI reutilizáveis
│   │   ├── features/       # Módulos + Redux slices
│   │   ├── pages/          # Páginas da aplicação
│   │   └── services/api/   # Axios client + services
│   └── vite.config.ts
├── docker-compose.yml
├── project_context/         # 📍 Esta documentação
└── README.md
```

---

## 📡 API Endpoints

Todos os endpoints sob `/api/v1/`:

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| POST | /auth/register | - | Registro |
| POST | /auth/login | - | Login |
| POST | /auth/refresh | JWT | Refresh token |
| POST | /auth/logout | JWT | Logout |
| GET | /users/me | JWT | Perfil do usuário |
| PATCH | /users/me | JWT | Atualizar perfil |
| GET | /products | - | Listar produtos |
| GET | /products/featured | - | Produtos destaque |
| GET | /products/categories | - | Listar categorias |
| GET | /products/{id} | - | Detalhe do produto |
| POST | /products | JWT+Admin | Criar produto |
| POST | /orders | JWT | Criar pedido |
| GET | /orders/my-orders | JWT | Meus pedidos |
| POST | /payments/create-intent | JWT | Criar PaymentIntent |
| POST | /payments/confirm | JWT | Confirmar pagamento |
| GET | /health | - | Status da aplicação |
| POST | /webhooks/stripe | Signature | Webhook Stripe |

---

## 🔑 Conceitos Chave

- **Backend MVC**: Routes → Middleware → Controllers → Models → ApiResponse Trait
- **Active Record**: Eloquent models com `$fillable`, `$casts`, relationships
- **JWT Dual Token**: Access (15min) + Refresh (7 dias), rotação automática
- **RBAC**: 4 roles (CUSTOMER, VENDOR, ADMIN, SUPER_ADMIN) via AdminMiddleware
- **API Response**: Padronizado `{ success, data, message, meta }`
- **Pagamentos**: Stripe PaymentIntent flow com webhook confirmation
- **Docker**: PHP-FPM + Nginx via Supervisor em container único

---

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
