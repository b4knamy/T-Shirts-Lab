# Visão Geral do Projeto - T-Shirts Lab

## 📋 Informações Básicas

| Campo | Valor |
|-------|-------|
| **Nome** | T-Shirts Lab |
| **Tipo** | E-commerce de camisetas personalizadas |
| **Stack Backend** | Laravel 11 (PHP 8.4) |
| **Stack Frontend** | React 19 + Vite + TypeScript |
| **Database** | PostgreSQL 15 |
| **Cache** | Redis 7 |
| **Pagamentos** | Stripe |
| **Containerização** | Docker + Docker Compose |

---

## 🎯 Missão & Visão

### Missão
Criar uma plataforma e-commerce profissional de camisetas personalizadas (anime, games, filmes, designs custom) com stack moderno e práticas de produção.

### Visão
Ser referência em e-commerce de camisetas no Brasil, oferecendo experiência premium com personalização e designs exclusivos.

---

## ⚡ Features

### MVP (Fase 1)
- ✅ Autenticação completa (JWT + Refresh Tokens)
- ✅ Catálogo de produtos com filtros e busca
- ✅ Carrinho de compras
- ✅ Checkout e processamento de pedidos
- ✅ Integração Stripe para pagamentos
- ✅ Painel admin completo (CRUD produtos, categorias, pedidos, cupons, imagens)
- ✅ API RESTful versionada (/api/v1/)
- ✅ Sistema de cupons de desconto (PERCENTAGE/FIXED)
- ✅ Banner de promoções públicas no frontend
- ✅ Upload e gerenciamento de imagens de produtos
- ✅ CRUD completo de categorias
- ✅ Seeders e factories realistas para desenvolvimento

### V1 (Fase 2 - Futuro)
- 🔄 Editor de customização de designs
- 🔄 Sistema de reviews e ratings
- 🔄 Dashboard admin completo com analytics
- 🔄 Notificações por email

### V2+ (Futuro)
- 🔄 Recomendações por ML
- 🔄 Marketplace para designers
- 🔄 API pública
- 🔄 App mobile

---

## 🏗️ Arquitetura Geral

```
┌─────────────────────────────────────────────────┐
│          CLIENTE (Browser)                      │
│  React 19 + Vite + TypeScript                   │
│  Redux Toolkit | Axios | TailwindCSS v4          │
│  Port 5173                                      │
├─────────────────────────────────────────────────┤
│               HTTPS / API                        │
├─────────────────────────────────────────────────┤
│          BACKEND (Laravel 11)                    │
│  PHP 8.4 | Eloquent ORM | JWT Auth              │
│  Stripe SDK | Redis Cache                        │
│  Port 8000                                       │
├──────────┬──────────────┬───────────────────────┤
│          │              │                        │
│ PostgreSQL 15    Redis 7      Stripe API         │
│ Port 5432       Port 6379                        │
└──────────┴──────────────┴───────────────────────┘
```

---

## 🛠️ Stack Técnico Completo

| Camada | Tecnologia | Versão | Propósito |
|--------|-----------|--------|-----------|
| **Frontend** | React.js | 19 | UI/UX |
| | Vite | 6 | Build tool |
| | TypeScript | 5.7 | Type safety |
| | TailwindCSS | 4 | Styling |
| | Redux Toolkit | 2 | State management |
| | Axios | 1.9 | HTTP client |
| | Zod | 3 | Validation |
| | React Hook Form | 7 | Form handling |
| **Backend** | Laravel | 11 | Framework |
| | PHP | 8.4 | Runtime |
| | Eloquent ORM | - | ORM |
| | JWT Auth | 2.9 | Authentication |
| | Stripe PHP SDK | 20 | Payments |
| | Predis | 3.4 | Redis client |
| **Database** | PostgreSQL | 15 | Primary DB |
| **Cache** | Redis | 7 | Cache & Sessions |
| **Payment** | Stripe | API v2023-10-16 | Payment processor |
| **DevOps** | Docker | Latest | Containerization |
| | Docker Compose | 3.9 | Orchestration |
| | GitHub Actions | - | CI/CD |

---

## 🏛️ Padrões de Arquitetura

- **MVC** (Model-View-Controller) via Laravel
- **Repository Pattern** (via Eloquent Models)
- **Service Layer** (para lógica de negócios complexa)
- **API Response Standardization** (trait ApiResponse)
- **JWT Stateless Auth** com refresh tokens
- **RBAC** (Role-Based Access Control) via middleware

---

## 📅 Roadmap

### Fase 1: MVP (Mar-Abr 2026)
- Backend Laravel com API completa
- Frontend React consumindo API
- Docker Compose para ambiente local
- Integração Stripe

### Fase 2: V1 (Mai-Jun 2026)
- Customização de designs
- Reviews e ratings
- Dashboard admin

### Fase 3: V2 (Jul+ 2026)
- Recomendações ML
- Marketplace
- App mobile

---

## 📚 Referências

- [Laravel Docs](https://laravel.com/docs)
- [React Docs](https://react.dev/)
- [Stripe PHP SDK](https://stripe.com/docs/api?lang=php)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)
- [Redis Docs](https://redis.io/docs/)
- [JWT Auth for Laravel](https://github.com/PHP-Open-Source-Saver/jwt-auth)

---

**Última atualização**: Março 2026
