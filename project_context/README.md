# T-Shirts Lab - Documentação do Projeto

## 📚 Índice de Documentação

Este diretório contém toda a documentação contextual do projeto T-Shirts Lab, organizada por responsabilidade para melhor manutenção e escalabilidade.

---

## 📑 Estrutura de Documentos

### 1. **01-OVERVIEW.md** - Visão Geral do Projeto
Informações fundamentais sobre o projeto:
- Missão, visão e objetivos
- Stack técnico completo (NestJS, React, PostgreSQL, Redis, Stripe)
- Arquitetura geral em diagrama
- Principais features (MVP e futuras)
- Roadmap geral do projeto
- Links para referências externas

**Quando usar**: Quando você precisa entender o projeto como um todo ou referenciar tecnologias

---

### 2. **02-BACKEND_ARCHITECTURE.md** - Arquitetura Backend (NestJS)
Documentação completa da estrutura backend:
- Estrutura de pastas do projeto
- Padrões de design (Repository, Dependency Injection, Observer, Strategy)
- Design de API (versionamento, response format, rate limiting)
- Autenticação & Autorização (JWT, RBAC)
- Design de banco de dados (entidades principais)
- Configuração TypeORM
- Caching com Redis
- Estratégias de testing
- CI/CD pipeline

**Quando usar**: Ao desenvolver no backend, ao entender fluxos de autenticação, ou ao implementar novos módulos

---

### 3. **03-FRONTEND_ARCHITECTURE.md** - Arquitetura Frontend (React)
Documentação completa da estrutura frontend:
- Estrutura de pastas do projeto
- Tipos de componentes (Presentacional, Container, Hooks)
- State management com Redux Toolkit
- Routing architecture
- Integração com API (Axios, interceptors)
- Estratégia de styling (TailwindCSS + CSS Modules)
- Testing (Vitest, React Testing Library)
- Performance optimization
- Segurança (XSS prevention, CSRF protection)

**Quando usar**: Ao desenvolver no frontend, ao criar componentes, ou ao gerenciar estado global

---

### 4. **04-DATABASE_CACHE.md** - Database & Cache (PostgreSQL & Redis)
Documentação de persistência de dados:
- Schema completo do banco de dados
- Todas as tabelas com relacionamentos
- Constraints e integridade referencial
- Arquitetura Redis com key naming conventions
- Estratégia de caching (TTL, invalidação)
- Implementação de CacheService
- Query optimization
- Migrations e versionamento do schema
- Segurança de dados (RLS, backups)

**Quando usar**: Ao modelar dados, ao otimizar queries, ao implementar caching, ou ao gerenciar migrations

---

### 5. **05-PAYMENT_STRIPE.md** - Integração de Pagamentos (Stripe)
Documentação completa de pagamentos:
- Fluxo de pagamento passo a passo
- Implementação de StripeService
- Controllers e DTOs
- Payment Service com lógica de negócio
- Webhook handling
- Error handling especializado
- PCI compliance e segurança
- Implementação de idempotência
- Analytics de pagamentos
- Cards de teste

**Quando usar**: Ao implementar pagamentos, ao processar refunds, ou ao gerenciar webhooks

---

### 6. **06-DEVOPS_DEPLOYMENT.md** - DevOps, Docker & Deployment
Documentação de infraestrutura e deployment:
- Docker e Docker Compose completo
- Dockerfiles otimizados (multi-stage builds)
- CI/CD pipeline com GitHub Actions
- Kubernetes configuration (futuro)
- Nginx como reverse proxy
- Monitoring e logging (ELK Stack - futuro)
- Deployment checklist
- Security best practices

**Quando usar**: Ao configurar ambiente local, ao preparar produção, ou ao implementar CI/CD

---

### 7. **07-PATTERNS_SECURITY_PRACTICES.md** - Padrões & Segurança
Documentação de melhores práticas:
- SOLID Principles com exemplos
- Clean Architecture
- Design Patterns (Adapter, Factory, Observer, Strategy)
- Segurança em profundidade:
  - Autenticação com JWT
  - Role-based access control
  - Input validation & sanitization
  - SQL injection prevention
  - CORS configuration
  - Rate limiting
  - Security headers
- Testing strategy (Unit, Integration, E2E)
- Performance optimization
- Git workflow (Conventional Commits, Git Flow)
- Documentation standards

**Quando usar**: Ao implementar novas features, ao revisar código, ou ao estabelecer padrões

---

## 🎯 Como Usar Esta Documentação

### Por Tarefa

**Implementar novo módulo backend:**
1. Consulte `02-BACKEND_ARCHITECTURE.md` para a estrutura
2. Consulte `04-DATABASE_CACHE.md` para modelar dados
3. Consulte `07-PATTERNS_SECURITY_PRACTICES.md` para padrões

**Implementar novo componente frontend:**
1. Consulte `03-FRONTEND_ARCHITECTURE.md` para estrutura
2. Consulte `07-PATTERNS_SECURITY_PRACTICES.md` para padrões

**Configurar ambiente local:**
1. Consulte `06-DEVOPS_DEPLOYMENT.md` para Docker
2. Consulte `01-OVERVIEW.md` para dependências

**Implementar feature de pagamento:**
1. Consulte `05-PAYMENT_STRIPE.md` completamente
2. Consulte `04-DATABASE_CACHE.md` para schema de pagamento

**Fazer deploy:**
1. Consulte `06-DEVOPS_DEPLOYMENT.md` completamente
2. Consulte `07-PATTERNS_SECURITY_PRACTICES.md` para segurança

---

## 🏗️ Decisões Arquiteturais Principais

### Por que múltiplos arquivos ao invés de um único?

✅ **Vantagens:**
- Facilita localização de informações específicas
- Permite que diferentes times trabalhem em paralelo
- Melhor versionamento e histórico de mudanças
- Documentação mais modular e reutilizável
- Reduz conflitos de merge em documentação
- Melhor para manutenção a longo prazo

❌ **Alternativa (um arquivo único):**
- Ficaria com 5000+ linhas
- Difícil localizar seções específicas
- Conflitos frequentes em versionamento
- Menos escalável conforme projeto cresce

---

## 📊 Stack Completo

| Camada | Tecnologia | Versão | Propósito |
|--------|-----------|--------|----------|
| **Frontend** | React.js | 18 LTS | UI/UX |
| | Vite | 5 | Build tool |
| | TailwindCSS | 3 | Styling |
| | Redux Toolkit | 1.9 | State management |
| | Axios | 1.6 | HTTP client |
| | Zod | 3 | Validation |
| **Backend** | NestJS | 10 LTS | Framework |
| | Node.js | 20 LTS | Runtime |
| | TypeORM | 0.3 | ORM |
| | Passport.js | 0.7 | Authentication |
| | Jest | 29 | Testing |
| **Database** | PostgreSQL | 15 | Primary DB |
| **Cache** | Redis | 7 | Sessions & Cache |
| **Payment** | Stripe | API v2023-10-16 | Payment processor |
| **DevOps** | Docker | Latest | Containerization |
| | GitHub Actions | - | CI/CD |
| | Kubernetes | - | Orchestration (futuro) |

---

## 🔄 Fluxos Principais

### Fluxo de Autenticação
```
Login → JWT Token → HttpOnly Cookie → Refresh Token
↓
Request com Bearer Token → Passport Guard → RolesGuard → Controller
```

### Fluxo de Compra
```
Add to Cart → Checkout → Create Payment Intent → Stripe Payment
↓
Webhook Confirmation → Update Order → Send Email
```

### Fluxo de Deployment
```
Git Push → GitHub Actions → Build & Test → Docker Image
↓
Registry → Docker Compose / Kubernetes → Production
```

---

## 📝 Convenções de Documentação

### Nomenclatura de Arquivos
- `NN-CATEGORY_NAME.md` (NN = número sequencial)
- UPPER_CASE para nomes em inglês
- Use hífens entre palavras

### Estrutura de Seções
- Títulos com emojis relevantes
- Sumários ao início (quando > 50 linhas)
- Exemplos de código com ✅ (bom) e ❌ (ruim)
- Diagramas ASCII para visualização
- Tabelas para comparações

### Links Internos
```markdown
[Backend Architecture](./02-BACKEND_ARCHITECTURE.md)
[Payment Integration](./05-PAYMENT_STRIPE.md#webhook-handling)
```

---

## 🚀 Próximas Pastas de Documentação (Futuro)

- `08-TESTING_STRATEGY.md` - Estratégia detalhada de testes
- `09-MONITORING_OBSERVABILITY.md` - Observabilidade e monitoring
- `10-DISASTER_RECOVERY.md` - Plano de recuperação de desastres
- `11-PRODUCT_ROADMAP.md` - Roadmap detalhado com épicos
- `12-API_DOCUMENTATION.md` - OpenAPI/Swagger completo
- `13-TROUBLESHOOTING.md` - Guia de resolução de problemas comuns

---

## 👥 Responsabilidades por Documentação

| Documento | Owner | Review | Frequência |
|-----------|-------|--------|-----------|
| 01-OVERVIEW | Tech Lead | - | Trimestral |
| 02-BACKEND_ARCHITECTURE | Backend Lead | Tech Lead | Mensal |
| 03-FRONTEND_ARCHITECTURE | Frontend Lead | Tech Lead | Mensal |
| 04-DATABASE_CACHE | DBA / Backend Lead | Tech Lead | Semestral |
| 05-PAYMENT_STRIPE | Payment Owner | Tech Lead | Quando muda Stripe |
| 06-DEVOPS_DEPLOYMENT | DevOps / Backend Lead | Tech Lead | Trimestral |
| 07-PATTERNS_SECURITY_PRACTICES | Tech Lead | All | Trimestral |

---

## 📞 Suporte e Dúvidas

Para dúvidas sobre:
- **Arquitetura geral**: Veja `01-OVERVIEW.md`
- **Backend**: Veja `02-BACKEND_ARCHITECTURE.md` e `07-PATTERNS_SECURITY_PRACTICES.md`
- **Frontend**: Veja `03-FRONTEND_ARCHITECTURE.md`
- **Dados**: Veja `04-DATABASE_CACHE.md`
- **Pagamentos**: Veja `05-PAYMENT_STRIPE.md`
- **Deploy**: Veja `06-DEVOPS_DEPLOYMENT.md`
- **Segurança**: Veja `07-PATTERNS_SECURITY_PRACTICES.md`

---

## ✅ Checklist para Novos Desenvolvedores

- [ ] Leu `01-OVERVIEW.md` para entender o projeto
- [ ] Leu documentação da sua área (Frontend/Backend/DevOps)
- [ ] Clonou repositório e seguiu `06-DEVOPS_DEPLOYMENT.md`
- [ ] Conseguiu fazer build local
- [ ] Leu `07-PATTERNS_SECURITY_PRACTICES.md` para padrões
- [ ] Está pronto para sua primeira feature!

---

**Última atualização**: Março 2026
**Versão**: 1.0.0
**Status**: ✅ Produção
