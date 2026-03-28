# Quick Reference - Resumo Executivo

## 🎯 O que é T-Shirts Lab?

Um e-commerce profissional de camisetas personalizadas (animes, designs customizados, etc) desenvolvido com stack moderno para produção.

## ⚡ TL;DR - Tudo em 2 minutos

### Arquitetura
```
Frontend (React) → Backend (NestJS) → PostgreSQL + Redis
                   ↓
            Stripe (Pagamentos)
```

### Stack
| Layer | Tech | Version |
|-------|------|---------|
| Frontend | React.js + Vite | 18 LTS |
| Backend | NestJS | 10 LTS |
| Database | PostgreSQL | 15 |
| Cache | Redis | 7 |
| Payment | Stripe | 2023-10-16 |
| Runtime | Node.js | 20 LTS |

### Start Local
```bash
docker-compose up -d
# Frontend: http://localhost:5173
# Backend: http://localhost:3000
```

---

## 📂 Documentação em 30 Segundos

Leia `project_context/README.md` para índice completo. Estrutura:

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 01 | OVERVIEW | Visão geral + roadmap |
| 02 | BACKEND_ARCHITECTURE | NestJS completo |
| 03 | FRONTEND_ARCHITECTURE | React completo |
| 04 | DATABASE_CACHE | PostgreSQL + Redis |
| 05 | PAYMENT_STRIPE | Pagamentos |
| 06 | DEVOPS_DEPLOYMENT | Docker + CI/CD |
| 07 | PATTERNS_SECURITY_PRACTICES | Padrões + Segurança |

---

## 🎯 Features por Phase

### MVP (Mar-Abr 2026)
```
✅ Auth (JWT + Refresh Tokens)
✅ Produtos + Carrinho
✅ Checkout
✅ Pagamentos (Stripe)
✅ Admin básico
```

### V1 (Mai-Jun 2026)
```
🔄 Customização de designs
🔄 Reviews + Ratings
🔄 Dashboard completo
```

### V2+ (Jul+)
```
🔄 Recomendações (ML)
🔄 Marketplace
🔄 API pública
```

---

## 🔐 Segurança (Essencial)

✅ JWT com refresh tokens
✅ RBAC (Role-based access control)
✅ Rate limiting
✅ Input validation (Zod)
✅ SQL injection prevention
✅ CORS/CSRF protection
✅ Security headers
✅ PCI compliant (Stripe)

---

## 📡 Principais Endpoints

```
GET    /api/v1/products              # Listar produtos
GET    /api/v1/products/:id          # Detalhe
POST   /api/v1/auth/register         # Registrar
POST   /api/v1/auth/login            # Login
POST   /api/v1/orders                # Criar pedido
POST   /api/v1/payments/create-intent # Stripe intent
POST   /webhooks/stripe              # Webhooks Stripe
```

Completo em: [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md)

---

## 💾 Database (Essencial)

Principais tabelas:
- **users** - Contas + autenticação
- **products** - Catálogo
- **orders** - Pedidos
- **order_items** - Itens dos pedidos
- **payments** - Transações
- **designs** - Estampas/designs

Redis para:
- Sessions
- Cache de produtos
- Rate limiting
- Carrinho abandonado

Completo em: [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md)

---

## 🚀 Deploy

### Local (Dev)
```bash
docker-compose up
```

### Production (Futuro)
```bash
# GitHub Actions → Docker Image → Kubernetes
# Veja 06-DEVOPS_DEPLOYMENT.md
```

---

## 🧪 Testing

```bash
# Backend
npm run test          # Unit tests
npm run test:e2e      # E2E

# Frontend
npm run test          # Unit tests
npm run test:e2e      # E2E (Playwright)
```

Coverage target: **≥ 80%**

---

## 🎨 Padrões Principais

| Padrão | Uso |
|--------|-----|
| Clean Architecture | Organização geral |
| SOLID | Design principles |
| Repository | Data access |
| Factory | Entity creation |
| Observer | Event system |
| Strategy | Payment providers |
| Adapter | Stripe integration |

Completo em: [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md)

---

## 📊 Performance

### Backend
- ✅ Query pagination
- ✅ Redis caching
- ✅ Rate limiting
- ✅ Lazy loading

### Frontend
- ✅ Code splitting
- ✅ Image lazy loading
- ✅ React Query
- ✅ Memoization

---

## 💳 Stripe Integration

### Flow
1. Frontend → Backend: `POST /payments/create-intent`
2. Backend → Stripe: Create Payment Intent
3. Frontend: Display payment form
4. Stripe webhook → Backend: Confirm payment
5. Backend: Update order + send email

Completo em: [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md)

### Test Cards
- `4242 4242 4242 4242` - Success
- `4000 0000 0000 0002` - Declined

---

## 📁 Project Structure (Simplificado)

```
backend/
├── src/
│   ├── auth/        # Authentication
│   ├── products/    # Products module
│   ├── orders/      # Orders module
│   ├── payments/    # Stripe integration
│   ├── common/      # Shared (guards, pipes, etc)
│   └── main.ts

frontend/
├── src/
│   ├── components/  # Reusable components
│   ├── pages/       # Page components
│   ├── store/       # Redux
│   ├── services/    # API calls
│   ├── hooks/       # Custom hooks
│   └── main.tsx

docker-compose.yml   # Local development
```

---

## 🔧 Configuração Mínima

### Backend `.env`
```
NODE_ENV=development
DATABASE_URL=postgresql://user:pass@postgres:5432/db
REDIS_URL=redis://redis:6379
JWT_SECRET=your-secret
STRIPE_SECRET_KEY=sk_test_...
```

### Frontend `.env`
```
VITE_API_BASE_URL=http://localhost:3000
```

---

## ⚠️ Gotchas Importantes

1. **JWT Tokens** - Access: 15min, Refresh: 7 dias
2. **Cache TTL** - Produtos: 1h, Categorias: 24h
3. **Stripe Webhooks** - Sempre validar assinatura
4. **Rate Limit** - 100 req/min por usuário
5. **CORS** - Configurado apenas para domínio do frontend

---

## 📞 Quick Navigation

| Preciso de... | Vá para |
|---------------|---------|
| Setup local | [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md) |
| Criar modulo backend | [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md) |
| Criar componente | [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md) |
| Entender banco | [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md) |
| Implementar pagamento | [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md) |
| Ver padrões | [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md) |
| Overview | [01-OVERVIEW.md](./01-OVERVIEW.md) |

---

## ✅ Checklist Novo Dev

- [ ] Clonou repo
- [ ] Rodou `docker-compose up`
- [ ] Frontend e backend carregam
- [ ] Leu [01-OVERVIEW.md](./01-OVERVIEW.md)
- [ ] Leu documentação da sua área
- [ ] Entende fluxo de auth
- [ ] Pronto para primeira PR!

---

## 🚨 Prioridades

**Nunca faça:**
- ❌ Commitar senhas/secrets
- ❌ Ignorar validação de input
- ❌ Skipped tests
- ❌ Queries sem pagination
- ❌ Cache sem TTL
- ❌ Payment sem webhook verification

**Sempre faça:**
- ✅ Write tests
- ✅ Validate inputs (Zod)
- ✅ Use migrations
- ✅ Cache strategically
- ✅ Follow conventions
- ✅ Document why (not what)

---

**Versão**: 1.0.0 | **Atualizado**: Março 2026
