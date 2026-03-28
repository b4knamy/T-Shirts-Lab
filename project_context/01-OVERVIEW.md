# T-Shirts Lab - Visão Geral do Projeto

## 📋 Informações Básicas

| Aspecto | Descrição |
|--------|-----------|
| **Nome do Projeto** | T-Shirts Lab |
| **Tipo** | E-commerce SaaS |
| **Segmento** | Vestuário personalizado (camisetas com estampas) |
| **Objetivo** | Plataforma de venda de camisetas com personalizações (animes, designs customizados, etc.) |
| **Propósito** | Aprendizado em arquitetura de produção |
| **Status** | Em desenvolvimento |
| **Data de Início** | Março 2026 |

## 🎯 Missão e Visão

### Missão
Oferecer uma plataforma intuitiva e escalável para criação e venda de camisetas personalizadas, permitindo que criadores e consumidores interajam em um ambiente seguro e eficiente.

### Visão
Ser a referência em e-commerce de vestuário personalizado, com tecnologia de ponta e experiência de usuário excepcional.

## 🎨 Principais Features

### MVP (Minimum Viable Product)

- ✅ Catálogo de produtos (camisetas, designs)
- ✅ Carrinho de compras
- ✅ Sistema de autenticação
- ✅ Checkout com integração Stripe
- ✅ Dashboard do usuário
- ✅ Admin panel básico

### Features Futuras

- 🔄 Upload e customização de designs
- 🔄 Galeria comunitária de designs
- 🔄 Reviews e ratings
- 🔄 Sistema de cupons e promoções
- 🔄 Recomendações por IA
- 🔄 Integração com impressoras 3D
- 🔄 Programa de afiliados

## 📊 Arquitetura Geral

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENTE (ReactJS)                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
├─────────────────┬──────────────────┬──────────────────┤
│  API Gateway    │  GraphQL (Future)│  WebSocket       │
│  (NestJS)       │  (Real-time)     │  (Notificações)  │
├─────────────────┴──────────────────┴──────────────────┤
│                                                         │
│              BACKEND (NestJS LTS)                      │
│  ├─ Auth Module                                        │
│  ├─ Products Module                                    │
│  ├─ Orders Module                                      │
│  ├─ Payment Module (Stripe)                           │
│  ├─ Users Module                                       │
│  └─ Cache Layer (Redis)                               │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ PostgreSQL   │  │ Redis Cache  │  │ File Storage │ │
│  │ (Primary DB) │  │ (Sessions)   │  │ (Images)     │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                         │
│  ┌──────────────────────────────────────────────────┐ │
│  │         External Services                        │ │
│  │  ├─ Stripe (Payments)                            │ │
│  │  ├─ SendGrid/Gmail (Email)                       │ │
│  │  └─ AWS S3/Cloudinary (Image Storage)           │ │
│  └──────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## 📦 Stack Técnico

### Frontend
- **Framework**: React.js (LTS)
- **Build Tool**: Vite
- **Styling**: TailwindCSS + CSS Modules
- **State Management**: Zustand / Redux Toolkit
- **HTTP Client**: Axios / React Query
- **Form Validation**: React Hook Form + Zod
- **UI Components**: Radix UI / Shadcn UI
- **Testing**: Vitest + React Testing Library

### Backend
- **Runtime**: Node.js (LTS)
- **Framework**: NestJS (LTS)
- **ORM**: TypeORM / Prisma
- **Validation**: Class-validator
- **Authentication**: JWT + Passport.js
- **Payment**: Stripe SDK
- **Caching**: Redis
- **Testing**: Jest + Supertest
- **Documentation**: Swagger/OpenAPI

### Database
- **Primary**: PostgreSQL (LTS)
- **Cache**: Redis (LTS)
- **Backup**: Automated backups

### DevOps & Infrastructure
- **Containerization**: Docker & Docker Compose
- **Orchestration**: Kubernetes (futuro)
- **CI/CD**: GitHub Actions
- **Monitoring**: Prometheus + Grafana (futuro)
- **Logging**: ELK Stack (futuro)

## 🏗️ Padrões de Arquitetura

- **Backend**: Clean Architecture + SOLID Principles
- **Frontend**: Component-based Architecture
- **API**: RESTful com versionamento (`/api/v1/*`)
- **Database**: Schema migrations com Flyway/Liquibase

## 🔐 Segurança

- SSL/TLS para todas as comunicações
- JWT tokens com refresh tokens
- Rate limiting no API Gateway
- CORS configurado corretamente
- Sanitização de inputs
- Proteção contra SQL Injection
- Autenticação 2FA (futuro)

## 📱 Considerações Mobile

- Responsive design com Mobile First
- Progressive Web App (PWA) ready
- Otimização de performance

## 🚀 Roadmap Geral

| Fase | Timeline | Objetivos |
|------|----------|-----------|
| **MVP** | Mar-Abr 2026 | Auth, Produtos, Checkout, Admin básico |
| **V1** | Mai-Jun 2026 | Customização, Reviews, Dashboard completo |
| **V2** | Jul-Ago 2026 | Recomendações, Afiliados, Analytics |
| **V3+** | Set+ 2026 | Marketplace, API pública, Mobile App |

## 📚 Referências Externas

- [NestJS Documentation](https://docs.nestjs.com/)
- [React Documentation](https://react.dev/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Stripe API Documentation](https://stripe.com/docs)
- [Redis Documentation](https://redis.io/documentation)

---

**Última atualização**: Março 2026
