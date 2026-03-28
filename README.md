# 👕 T-Shirts Lab - E-commerce de Camisetas Personalizadas

Um projeto de e-commerce de produção para venda de camisetas personalizadas (animes, designs customizados, etc). Desenvolvido como estudo avançado em arquitetura de software.

## 🎯 Objetivo

Criar uma plataforma escalável, segura e de alta performance para venda de camisetas personalizadas, utilizando stack moderno e padrões de desenvolvimento profissional.

## 🏗️ Stack Técnico

- **Frontend**: React.js 18 LTS + Vite
- **Backend**: NestJS 10 LTS
- **Database**: PostgreSQL 15
- **Cache**: Redis 7
- **Pagamentos**: Stripe
- **Infra**: Docker, Docker Compose, GitHub Actions

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