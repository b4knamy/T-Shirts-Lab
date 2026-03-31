# 📑 Índice da Documentação - T-Shirts Lab

## Visão Geral

Este diretório contém a documentação técnica completa do projeto T-Shirts Lab, uma plataforma e-commerce para venda e customização de camisetas.

**Stack**: Laravel 11 (PHP 8.4) + React 19 (TypeScript) + PostgreSQL 15 + Redis 7

---

## 📚 Documentos

### 1. [01-OVERVIEW.md](./01-OVERVIEW.md) - Visão Geral do Projeto
- Descrição do projeto e objetivos de negócio
- Stack tecnológico completo (Laravel, React, PostgreSQL, Redis, Stripe)
- Estrutura de diretórios do monorepo
- Funcionalidades principais (catálogo, customização, pagamentos, admin)
- Roles de usuário (CUSTOMER, VENDOR, ADMIN, SUPER_ADMIN)
- Padrão de resposta da API

### 2. [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md) - Arquitetura Backend (Laravel)
- Arquitetura MVC do Laravel 11
- Estrutura de diretórios do backend PHP
- Configuração do framework (bootstrap/app.php, config/)
- Modelos Eloquent e relacionamentos (Active Record)
- Controllers API versionados (Api/V1/) — 10 Controllers
- Sistema de autenticação JWT (php-open-source-saver/jwt-auth)
- Middleware customizado (JwtAuthenticate, AdminMiddleware)
- Trait ApiResponse para padronização de respostas (chave `data` genérica)
- Sistema de cupons de desconto (Coupon, CouponUsage, CouponController)
- CRUD de categorias (CategoryController)
- Gerenciamento de imagens de produtos (ProductImageController — URL + upload)
- Factories e Seeders realistas (Order, Coupon, Payment)
- Rotas da API (routes/api.php) — 40+ endpoints
- Rate limiting e CORS

### 3. [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md) - Arquitetura Frontend (React)
- Arquitetura baseada em features do React 19
- Configuração Vite + TypeScript 5.7
- Gerenciamento de estado com Redux Toolkit
- Serviço HTTP com Axios (interceptors, refresh token)
- Sistema de rotas (React Router v6)
- Validação de formulários (Zod + React Hook Form)
- Estilização com TailwindCSS v4
- Integração com API Laravel (port 8000)
- Painel Admin completo: Dashboard, Products (CRUD + Image Manager), Orders, Categories, Coupons
- Banner de promoções públicas (PromoBanner) com countdown
- Componentes reutilizáveis e patterns

### 4. [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md) - Banco de Dados e Cache
- PostgreSQL 15 como banco principal
- Migrations do Laravel (Schema Builder)
- Modelos Eloquent com UUIDs
- Relacionamentos (hasMany, belongsTo, hasOne)
- Índices e constraints
- Redis 7 para caching (Predis)
- Estratégia de cache (TTLs, invalidação)
- DatabaseSeeder com dados iniciais
- Configuração de conexões (.env)

### 5. [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md) - Integração de Pagamentos (Stripe)
- Integração Stripe via stripe-php SDK v20
- PaymentController (create-intent, confirm, status, refund)
- WebhookController com verificação de assinatura
- Fluxo completo: frontend → backend → Stripe → webhook
- Tratamento de eventos (payment_intent.succeeded, failed, refunded)
- Segurança: STRIPE_WEBHOOK_SECRET, idempotência
- Configuração de ambiente (.env)

### 6. [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md) - DevOps e Deploy
- Docker Compose: backend, frontend, PostgreSQL, Redis
- Dockerfile multi-stage (composer:2 → php:8.4-fpm-alpine)
- Supervisor (PHP-FPM + Nginx no mesmo container)
- Configurações Nginx (proxy para PHP-FPM :9000)
- Configurações PHP (php.ini, opcache)
- Variáveis de ambiente
- GitHub Actions CI/CD pipeline
- Health check endpoint
- Comandos de deploy (artisan migrate, cache, optimize)

### 7. [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md) - Padrões e Segurança
- Padrões Laravel: MVC, Active Record, Middleware Pipeline, Service Container
- Trait pattern (ApiResponse)
- Convenções de código PHP (PSR-12)
- Segurança: JWT com tokens curtos, bcrypt, CSRF, rate limiting
- Validação de entrada (Form Request, Controller validation)
- Proteção contra SQL Injection (Eloquent/Query Builder)
- Proteção contra XSS e mass assignment ($fillable)
- Verificação de webhook Stripe (HMAC)
- CORS configurado
- Logs e monitoramento

### 8. [ARCHITECTURE_DIAGRAMS.md](./ARCHITECTURE_DIAGRAMS.md) - Diagramas de Arquitetura
- Diagrama geral da arquitetura (ASCII art)
- Fluxo de autenticação (Client ↔ Laravel ↔ Database)
- Fluxo de pagamento (Client ↔ Laravel ↔ Stripe ↔ Database)
- Diagrama ER do banco de dados
- Arquitetura Docker (containers, networks, volumes)
- Request Lifecycle do Laravel (Nginx → PHP-FPM → Laravel)
- Mapa de rotas da API

### 9. [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) - Referência Rápida
- Stack resumida
- Comandos essenciais (Artisan, Docker, Frontend)
- Portas dos serviços
- Endpoints da API organizados por recurso
- Variáveis de ambiente principais
- Credenciais padrão (desenvolvimento)
- Checklist de setup rápido

### 10. [GETTING_STARTED.md](./GETTING_STARTED.md) - Guia de Início
- Pré-requisitos (PHP 8.4, Composer, Node.js, Docker)
- Setup passo a passo
- Opção 1: Docker Compose (recomendado)
- Opção 2: Setup local manual
- Configuração do .env (backend e frontend)
- Rodando migrations e seeders
- Verificação de saúde dos serviços
- Troubleshooting comum

---

## 🗂️ Estrutura do Projeto

```
tshirtslab/
├── backend/                 # Laravel 11 (PHP 8.4)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/   # 10 Controllers
│   │   │   ├── Middleware/           # JWT, Admin
│   │   │   ├── Requests/Api/V1/     # Form Requests (Coupon, etc.)
│   │   │   └── Resources/Api/V1/    # API Resources
│   │   ├── Models/                   # 11 Eloquent Models
│   │   ├── Services/                 # OrderService
│   │   └── Traits/                   # ApiResponse
│   ├── bootstrap/app.php            # App configuration
│   ├── config/                      # auth, cors, jwt, services
│   ├── database/
│   │   ├── factories/               # Order, Payment, Coupon factories
│   │   ├── migrations/              # 12 migration files
│   │   └── seeders/                 # User, Category, Product, Order, Coupon seeders
│   ├── docker/                      # nginx.conf, php.ini, supervisord.conf
│   ├── routes/api.php               # 40+ API routes
│   ├── Dockerfile                   # Multi-stage PHP build
│   └── composer.json                # PHP dependencies
├── frontend/                # React 19 (TypeScript)
│   ├── src/
│   │   ├── components/
│   │   │   ├── common/              # CartSidebar, PromoBanner, ProtectedRoute, ErrorBoundary
│   │   │   └── layout/              # MainLayout, AuthLayout, AdminLayout, Header, Footer
│   │   ├── pages/                   # 10+ pages + admin panel (5 admin pages)
│   │   ├── services/api/            # Axios client, 6 API services (admin, coupons, etc.)
│   │   ├── store/                   # Redux store + slices
│   │   └── types/                   # TypeScript interfaces (entities.ts)
│   ├── vite.config.ts               # Vite configuration
│   └── package.json                 # Node dependencies
├── docker-compose.yml       # Orquestração dos containers
├── project_context/         # 📍 Esta documentação
└── README.md
```

---

## 🔍 Como Usar Esta Documentação

| Objetivo | Documento |
|----------|-----------|
| Entender o projeto | [01-OVERVIEW.md](./01-OVERVIEW.md) |
| Configurar o ambiente | [GETTING_STARTED.md](./GETTING_STARTED.md) |
| Referência rápida de comandos | [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) |
| Backend / API | [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md) |
| Frontend / React | [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md) |
| Banco de dados / Cache | [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md) |
| Pagamentos / Stripe | [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md) |
| Docker / Deploy | [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md) |
| Padrões / Segurança | [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md) |
| Diagramas visuais | [ARCHITECTURE_DIAGRAMS.md](./ARCHITECTURE_DIAGRAMS.md) |

---

## 📊 Resumo Técnico

| Componente | Tecnologia | Versão |
|-----------|-----------|--------|
| Backend Framework | Laravel | 11.x |
| Linguagem Backend | PHP | 8.4 |
| Frontend Framework | React | 19.x |
| Bundler Frontend | Vite | 6.x |
| TypeScript | TypeScript | 5.7 |
| Banco de Dados | PostgreSQL | 15 |
| Cache/Session | Redis | 7 |
| Autenticação | JWT (php-open-source-saver/jwt-auth) | 2.9 |
| Pagamentos | Stripe (stripe-php) | 20.x |
| CSS Framework | TailwindCSS | 4.x |
| State Management | Redux Toolkit | Latest |
| Containerização | Docker Compose | Latest |
| Web Server | Nginx | Alpine |
| Process Manager | Supervisor | Latest |

---

**Versão**: 2.0.0 (Laravel) | **Atualizado**: Março 2026
