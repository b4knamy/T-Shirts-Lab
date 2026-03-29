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
