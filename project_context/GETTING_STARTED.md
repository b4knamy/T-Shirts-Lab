# Getting Started - Guia de Início

## 👋 Bem-vindo ao T-Shirts Lab!

Este guia vai ajudar você a começar rapidamente.

---

## 📚 Leitura Recomendada (30 min)

### 1. **Entenda o Projeto** (5 min)
Leia: [01-OVERVIEW.md](./01-OVERVIEW.md)
- O que é T-Shirts Lab?
- Qual é o stack?
- Qual é a arquitetura?

### 2. **Escolha sua Trilha** (5 min)

#### Se você faz **Backend**:
→ Leia [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md)

#### Se você faz **Frontend**:
→ Leia [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md)

#### Se você faz **DevOps/Infra**:
→ Leia [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md)

#### Se você faz **Banco de Dados**:
→ Leia [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md)

### 3. **Entenda Segurança e Padrões** (5 min)
Leia: [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md)

### 4. **Consulte quando precisar**

Pagamentos? → [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md)

---

## 🚀 Setup Local (10 min)

### Pré-requisitos
```bash
# Instale:
✅ Docker Desktop (inclui Docker + Docker Compose)
✅ Git
✅ Visual Studio Code (opcional mas recomendado)
✅ Node.js 20 LTS (para desenvolvimento local)
```

### Clone e Inicie

```bash
# 1. Clone
git clone https://github.com/b4knamy/tshirts-lab.git
cd tshirts-lab

# 2. Copie variáveis de ambiente
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 3. Inicie tudo
docker-compose up -d

# 4. Aguarde 2-3 minutos para tudo iniciar
docker-compose logs -f backend

# 5. Quando vir "NestJS successfully started", você está pronto!
```

### Verificar Setup

```bash
# Frontend: http://localhost:5173
# Backend API: http://localhost:3000
# Swagger Docs: http://localhost:3000/api-docs
# PostgreSQL: localhost:5432
# Redis: localhost:6379
```

**Tudo funcionando?** ✅ Parabéns! Você está pronto.

---

## 🎯 Primeira Tarefa

### Backend

```bash
cd backend

# 1. Instale dependências
npm install

# 2. Rode testes
npm run test

# 3. Crie uma branch
git checkout -b feature/seu-nome

# 4. Explore a estrutura
# Ver: 02-BACKEND_ARCHITECTURE.md → "Estrutura do Projeto Backend"
```

### Frontend

```bash
cd frontend

# 1. Instale dependências
npm install

# 2. Rode testes
npm run test

# 3. Crie uma branch
git checkout -b feature/seu-nome

# 4. Explore a estrutura
# Ver: 03-FRONTEND_ARCHITECTURE.md → "Estrutura do Projeto Frontend"
```

---

## 🔍 Entendendo o Fluxo de Compra

Para entender como tudo funciona junto:

```
1. Cliente clica em "Comprar"
   ↓
2. Frontend valida (Zod)
   ↓
3. Frontend faz POST /api/v1/orders
   ↓
4. Backend valida novamente (validation pipes)
   ↓
5. Backend reserva inventário
   ↓
6. Backend cria Payment Intent com Stripe
   ↓
7. Frontend exibe formulário Stripe
   ↓
8. Cliente digita cartão
   ↓
9. Frontend confirma pagamento com Stripe
   ↓
10. Stripe envia webhook para backend
   ↓
11. Backend valida assinatura do webhook
   ↓
12. Backend atualiza status do pedido
   ↓
13. Backend envia email de confirmação
   ↓
14. Cliente recebe confirmação
```

Isso está documentado em:
- Fluxo: [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md#stripe-payment-architecture)
- Backend: [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md)
- Frontend: [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md)

---

## 🧪 Rodando Testes

```bash
# Backend
cd backend
npm run test              # Unit tests
npm run test:watch       # Watch mode
npm run test:e2e         # E2E tests
npm run test:cov         # Com coverage

# Frontend
cd frontend
npm run test              # Unit tests
npm run test:watch       # Watch mode
npm run test:e2e         # Playwright E2E
npm run test:cov         # Com coverage
```

**Target**: ≥ 80% coverage

---

## 📝 Git Workflow

```bash
# 1. Crie branch da feature
git checkout -b feature/my-awesome-feature

# 2. Faça commits com mensagens semânticas
git commit -m "feat: add new feature"
git commit -m "fix: correct bug"
git commit -m "docs: update readme"

# 3. Faça push
git push origin feature/my-awesome-feature

# 4. Abra Pull Request no GitHub
# → CI/CD roda automaticamente
# → Aguarde revisão
# → Merge quando aprovado

# 5. Delete branch local
git branch -d feature/my-awesome-feature
```

### Mensagens de Commit (Conventional Commits)
```
feat:     Nova feature
fix:      Correção de bug
docs:     Documentação
style:    Formatação (sem lógica)
refactor: Reorganização (sem lógica)
perf:     Performance
test:     Adicionar/atualizar testes
chore:    Atualizações de dependências
```

**Exemplo válido:**
```bash
git commit -m "feat: add product filter by category"
git commit -m "fix: correct price calculation in cart"
```

---

## 🐛 Troubleshooting

### Docker não inicia
```bash
# Verificar logs
docker-compose logs

# Resetar (cuidado!)
docker-compose down -v
docker-compose up -d
```

### PostgreSQL não conecta
```bash
# Verificar se container está rodando
docker-compose ps

# Ver se porta 5432 está em uso
lsof -i :5432

# Resetar dados
docker-compose down -v postgres
docker-compose up -d postgres
```

### Redis não conecta
```bash
# Entrar no container
docker-compose exec redis redis-cli

# Ping para verificar
PING
```

### Testes falhando
```bash
# Limpar cache
rm -rf node_modules package-lock.json
npm install

# Resetar DB de teste
docker-compose down -v
docker-compose up -d

# Rodar tests novamente
npm run test
```

### Build falha
```bash
# Verifique TypeScript
npm run type-check

# Verifique lint
npm run lint

# Verifique testes
npm run test
```

---

## 🤔 Perguntas Frequentes

### P: Como adiciono uma nova variável de ambiente?
**R:** 
1. Adicione em `.env.example`
2. Documente em `project_context/06-DEVOPS_DEPLOYMENT.md`
3. Valide em `config/validation.ts` (backend) ou `.env` (frontend)

### P: Como faço deploy para produção?
**R:** Veja [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md#deployment-checklist)

### P: Como integro novo payment provider?
**R:** Veja [05-PAYMENT_STRIPE.md](./05-PAYMENT_STRIPE.md) → Strategy Pattern

### P: Quantas linhas de código?
**R:** Target: 3000-5000 lines por módulo (bem arquitetado é melhor que grande)

### P: Qual é o SLA de performance?
**R:** 
- API response time: < 200ms (p95)
- Frontend paint: < 2s
- Cache hit rate: > 80%

### P: Como reporto um bug?
**R:**
1. Verifique se já existe issue
2. Crie issue com:
   - Descrição clara
   - Steps to reproduce
   - Expected vs actual
   - Logs/screenshots

---

## 🎯 Próximos Passos

### Semana 1
- [ ] Entender a arquitetura geral
- [ ] Clonar e rodar local
- [ ] Rodas todos os testes
- [ ] Explorar código base

### Semana 2
- [ ] Fazer primeira pequena fix/feature
- [ ] Abrir Pull Request
- [ ] Code review e merge

### Semana 3+
- [ ] Features mais complexas
- [ ] Explorar testes
- [ ] Melhorar performance
- [ ] Documentar descobertas

---

## 📚 Referências Rápidas

### Para estudo:
- [Clean Code - Robert C. Martin](https://www.oreilly.com/library/view/clean-code-a/9780136083238/)
- [NestJS Docs](https://docs.nestjs.com/)
- [React Docs](https://react.dev/)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)

### Cheat Sheets:
- Git: `git --help`
- Docker: `docker --help`
- npm: `npm help`

### Dentro deste projeto:
- Arquitetura: [01-OVERVIEW.md](./01-OVERVIEW.md)
- Backend: [02-BACKEND_ARCHITECTURE.md](./02-BACKEND_ARCHITECTURE.md)
- Frontend: [03-FRONTEND_ARCHITECTURE.md](./03-FRONTEND_ARCHITECTURE.md)
- Database: [04-DATABASE_CACHE.md](./04-DATABASE_CACHE.md)
- DevOps: [06-DEVOPS_DEPLOYMENT.md](./06-DEVOPS_DEPLOYMENT.md)
- Padrões: [07-PATTERNS_SECURITY_PRACTICES.md](./07-PATTERNS_SECURITY_PRACTICES.md)

---

## 👥 Suporte

### Precisa de ajuda?

1. **Explore a documentação**
   - Pesquise o tópico em `project_context/`
   - Use `QUICK_REFERENCE.md` para navegação rápida

2. **Procure por exemplos**
   - Veja como similar foi feito no código
   - Adapte para seu caso

3. **Abra uma issue**
   - Descreva seu problema claramente
   - Inclua logs e context

4. **Pergunte no Slack/Discord**
   - Equipe está aqui para ajudar!

---

## ✅ Você está pronto!

Parabéns por ter chegado até aqui! 🎉

**Próximo passo:** Escolha sua trilha (Backend/Frontend/DevOps) e comece a explorar a documentação específica.

**Dúvida?** Volte para [README.md](./README.md) e navegue por documentação específica.

---

**Status**: ✅ Você está pronto para começar!
**Última atualização**: Março 2026
