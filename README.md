# 👕 T-Shirts Lab - E-commerce de Camisetas Personalizadas

Um projeto de e-commerce profissional para venda de camisetas personalizadas (animes, games, filmes, designs custom). Desenvolvido como estudo avançado em arquitetura de software com stack moderno e padrões de produção.

## 🎯 Objetivo

Criar uma plataforma escalável, segura e de alta performance para venda de camisetas personalizadas, utilizando stack moderno e padrões de desenvolvimento profissional.

## 🏗️ Stack Técnico

- **Frontend**: React 19 + Vite 6 + TypeScript 5.7 + TailwindCSS v4
- **Backend**: Laravel 11 (PHP 8.4)
- **Database**: PostgreSQL 15
- **Cache**: Redis 7 (Predis)
- **Autenticação**: JWT (`php-open-source-saver/jwt-auth`)
- **Pagamentos**: Stripe
- **Infra**: Docker + Docker Compose

## 📚 Documentação

Toda a documentação do projeto está organizada em `project_context/`:

| Documento | Descrição |
|-----------|-----------|
| [README.md](./project_context/README.md) | **Índice da documentação** - Comece aqui! |
| [01-OVERVIEW.md](./project_context/01-OVERVIEW.md) | Visão geral do projeto e stack |
| [02-BACKEND_ARCHITECTURE.md](./project_context/02-BACKEND_ARCHITECTURE.md) | Arquitetura Laravel 11 completa |
| [03-FRONTEND_ARCHITECTURE.md](./project_context/03-FRONTEND_ARCHITECTURE.md) | Arquitetura React 19 completa |
| [04-DATABASE_CACHE.md](./project_context/04-DATABASE_CACHE.md) | PostgreSQL + Redis |
| [05-PAYMENT_STRIPE.md](./project_context/05-PAYMENT_STRIPE.md) | Integração com Stripe |
| [06-DEVOPS_DEPLOYMENT.md](./project_context/06-DEVOPS_DEPLOYMENT.md) | Docker e Deployment |
| [07-PATTERNS_SECURITY_PRACTICES.md](./project_context/07-PATTERNS_SECURITY_PRACTICES.md) | Padrões, segurança e boas práticas |

## 🚀 Quick Start

### Pré-requisitos
- Docker & Docker Compose
- Node.js 20 LTS
- PHP 8.4 + Composer 2
- Git

### Desenvolvimento Local

```bash
# Clone o repositório
git clone https://github.com/b4knamy/tshirts-lab.git
cd tshirts-lab

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
php artisan serve --port=8000

# Frontend (outra aba)
cd frontend
npm install
npm run dev
```

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api/v1/health

### Contas de Teste

| Tipo | Email | Senha |
|------|-------|-------|
| Super Admin | superadmin@tshirtslab.com | Super@123 |
| Admin | admin@tshirtslab.com | Admin@123 |
| Moderador | moderator@tshirtslab.com | Mod@123 |
| Customer | customer@tshirtslab.com | Customer@123 |

### Cupons de Teste
`WELCOME10`, `FRETE0`, `SUPER25`, `VIP20`, `FLASH50`

## 📦 Estrutura do Projeto

```
tshirts-lab/
├── backend/                    # Laravel 11 (PHP 8.4)
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Http/Middleware/
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   └── Traits/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   ├── routes/
│   │   └── api.php
│   └── composer.json
├── frontend/                   # React 19 + Vite + TypeScript
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── store/
│   │   ├── services/api/
│   │   ├── hooks/
│   │   └── types/
│   └── package.json
├── project_context/            # 📚 Documentação técnica
└── README.md
```

## 🎨 Features Implementadas

### ✅ Concluído
- Autenticação completa (JWT + Refresh Tokens)
- RBAC: SUPER_ADMIN > ADMIN > MODERATOR > CUSTOMER
- Catálogo de produtos com filtros e paginação (55+ produtos seedados)
- Carrinho de compras
- Checkout com aplicação de cupom de desconto
- Integração Stripe para pagamentos
- Painel admin completo (Dashboard, Produtos, Pedidos, Categorias, Cupons, Reviews, Staff)
- Gerenciamento de staff (criar admins/moderadores com hierarquia de permissões)
- Sistema de cupons (PERCENTAGE / FIXED) com validação
- Upload e gerenciamento de imagens de produtos
- Perfil de usuário + endereços
- Sistema de reviews e ratings com resposta de admin
- API RESTful versionada (`/api/v1/`) — todas as respostas em snake_case
- Seeders realistas (55 produtos, 172 reviews, 61 pedidos, 24 usuários)

### 🔄 Futuro
- Editor de customização de designs
- Dashboard analytics avançado
- Notificações por email
- Recomendações por ML
- App mobile

## 🔐 Segurança

- JWT Authentication com Refresh Tokens
- Role-Based Access Control (RBAC) com hierarquia de permissões
- Rate Limiting
- Input Validation via Form Requests (Laravel)
- SQL Injection Prevention (Eloquent parameterized queries)
- CORS configurado por variável de ambiente
- Stripe Webhook signature verification

## 🧪 Testes e Desenvolvimento

```bash
# Reset e re-seed do banco
php artisan migrate:fresh --seed

# Listar rotas
php artisan route:list

# Limpar caches
php artisan cache:clear && php artisan config:clear

# TypeScript check (frontend)
cd frontend && npx tsc --noEmit
```

## 📞 Suporte

Para detalhes de arquitetura, veja [project_context/README.md](./project_context/README.md).

---

**Stack**: Laravel 11 + React 19 + PostgreSQL 15 + Redis 7 + Stripe
**Última atualização**: Abril 2026
**Status**: 🚀 Em desenvolvimento

## 📚 Documentação

Toda a documentação do projeto está organizada em `project_context/`:

| Documento | Descrição |
|-----------|-----------|
| [README.md](./project_context/README.md) | **Índice da documentação** - Comece aqui! |
| [01-OVERVIEW.md](./project_context/01-OVERVIEW.md) | Visão geral do projeto e stack |
| [02-BACKEND_ARCHITECTURE.md](./project_context/02-BACKEND_ARCHITECTURE.md) | Arquitetura NestJS completa |
| [03-FRONTEND_ARCHITECTURE.md](./project_context/03-FRONTEND_ARCHITECTURE.md) | Arquitetura React completa |
| [04-DATABASE_CACHE.md](./project_context/04-DATABASE_CACHE.md) | PostgreSQL + Redis |
| [05-PAYMENT_STRIPE.md](./project_context/05-PAYMENT_STRIPE.md) | Integração com Stripe |
| [06-DEVOPS_DEPLOYMENT.md](./project_context/06-DEVOPS_DEPLOYMENT.md) | Docker, CI/CD e Deployment |
| [07-PATTERNS_SECURITY_PRACTICES.md](./project_context/07-PATTERNS_SECURITY_PRACTICES.md) | Padrões, segurança e melhores práticas |

## 🚀 Quick Start

### Pré-requisitos
- Docker & Docker Compose
- Node.js 20 LTS
- Git

### Local Development

```bash
# Clone o repositório
git clone https://github.com/b4knamy/tshirts-lab.git
cd tshirts-lab

# Inicie os serviços com Docker Compose
docker-compose up -d

# Frontend estará em: http://localhost:5173
# Backend estará em: http://localhost:3000
# API Docs: http://localhost:3000/api-docs
```

Para mais informações, veja [06-DEVOPS_DEPLOYMENT.md](./project_context/06-DEVOPS_DEPLOYMENT.md).

## 📦 Estrutura do Projeto

```
tshirts-lab/
├── backend/                    # NestJS application
│   ├── src/
│   ├── test/
│   ├── docker/
│   └── package.json
├── frontend/                   # React application
│   ├── src/
│   ├── tests/
│   ├── docker/
│   └── package.json
├── docker-compose.yml
├── project_context/            # 📚 Documentação (LEIA AQUI!)
└── README.md                   # Este arquivo
```

## 🎨 Principais Features

### MVP
- ✅ Catálogo de produtos
- ✅ Carrinho de compras
- ✅ Autenticação JWT
- ✅ Checkout com Stripe
- ✅ Admin panel básico

### Futuro
- 🔄 Customização de designs
- 🔄 Galeria comunitária
- 🔄 Sistema de recomendações
- 🔄 Programa de afiliados

Veja roadmap completo em [01-OVERVIEW.md](./project_context/01-OVERVIEW.md).

## 🔐 Segurança

- JWT Authentication com Refresh Tokens
- Role-Based Access Control (RBAC)
- Rate Limiting
- Input Validation & Sanitization
- SQL Injection Prevention
- CORS/CSRF Protection
- Security Headers
- PCI Compliance para Stripe

Leia mais em [07-PATTERNS_SECURITY_PRACTICES.md](./project_context/07-PATTERNS_SECURITY_PRACTICES.md).

## 🧪 Testing

```bash
# Backend
cd backend
npm run test          # Unit tests
npm run test:e2e      # E2E tests

# Frontend
cd frontend
npm run test          # Unit tests
npm run test:e2e      # E2E tests
```

## 📊 API Documentation

Swagger/OpenAPI disponível em: `http://localhost:3000/api-docs`

## 🚀 Deployment

```bash
# Build e push das imagens Docker
docker-compose build
docker push ghcr.io/b4knamy/tshirts-lab/backend
docker push ghcr.io/b4knamy/tshirts-lab/frontend

# Deploy em produção
# Veja 06-DEVOPS_DEPLOYMENT.md para Kubernetes
```

## 🤝 Contribuindo

1. Crie uma branch para sua feature: `git checkout -b feature/my-feature`
2. Siga o padrão de commits: `git commit -m "feat: descrição"`
3. Faça push: `git push origin feature/my-feature`
4. Abra um Pull Request

## 📝 Licença

MIT

## 👨‍💻 Autor

**b4knamy** - Portfolio: [github.com/b4knamy](https://github.com/b4knamy)

## 📞 Suporte

Para dúvidas sobre arquitetura, veja [project_context/README.md](./project_context/README.md).

---

**Última atualização**: Março 2026
**Status**: 🚀 Em desenvolvimento