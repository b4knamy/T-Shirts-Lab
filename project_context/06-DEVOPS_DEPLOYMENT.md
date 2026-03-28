# DevOps, Deployment & Infrastructure

## 🐳 Docker & Containerization

### Docker Architecture

```
┌─────────────────────────────────────────────────────┐
│              DOCKER COMPOSE SETUP                   │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Container 1: PostgreSQL 15                        │
│  ├─ Port: 5432                                      │
│  ├─ Volume: postgres_data                           │
│  └─ Network: app-network                            │
│                                                     │
│  Container 2: Redis 7                              │
│  ├─ Port: 6379                                      │
│  ├─ Volume: redis_data                              │
│  └─ Network: app-network                            │
│                                                     │
│  Container 3: Backend (NestJS)                     │
│  ├─ Port: 3000                                      │
│  ├─ Depends: PostgreSQL, Redis                     │
│  └─ Network: app-network                            │
│                                                     │
│  Container 4: Frontend (React)                     │
│  ├─ Port: 5173                                      │
│  ├─ Depends: Backend                               │
│  └─ Network: app-network                            │
│                                                     │
│  Container 5: Nginx (Proxy)                        │
│  ├─ Port: 80, 443                                   │
│  ├─ Depends: Backend, Frontend                     │
│  └─ Network: app-network                            │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Backend Dockerfile

```dockerfile
# docker/backend/Dockerfile

# Stage 1: Build
FROM node:20-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: Runtime
FROM node:20-alpine

WORKDIR /app

ENV NODE_ENV=production

COPY package*.json ./
RUN npm ci --only=production

COPY --from=builder /app/dist ./dist

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
  CMD node -e "require('http').get('http://localhost:3000/health', (r) => {if (r.statusCode !== 200) throw new Error(r.statusCode)})"

CMD ["node", "dist/main.js"]
```

### Frontend Dockerfile

```dockerfile
# docker/frontend/Dockerfile

# Stage 1: Build
FROM node:20-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: Runtime
FROM nginx:alpine

COPY --from=builder /app/dist /usr/share/nginx/html
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.9'

services:
  # PostgreSQL Database
  postgres:
    image: postgres:15-alpine
    container_name: tshirts_lab_postgres
    environment:
      POSTGRES_USER: ${DB_USER:-postgres}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-postgres}
      POSTGRES_DB: ${DB_NAME:-tshirts_lab}
    ports:
      - "${DB_PORT:-5432}:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - app-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USER:-postgres}"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: tshirts_lab_redis
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD:-redis}
    ports:
      - "${REDIS_PORT:-6379}:6379"
    volumes:
      - redis_data:/data
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Backend API
  backend:
    build:
      context: ./backend
      dockerfile: ../docker/backend/Dockerfile
    container_name: tshirts_lab_backend
    environment:
      NODE_ENV: ${NODE_ENV:-development}
      DATABASE_HOST: postgres
      DATABASE_PORT: 5432
      DATABASE_USER: ${DB_USER:-postgres}
      DATABASE_PASSWORD: ${DB_PASSWORD:-postgres}
      DATABASE_NAME: ${DB_NAME:-tshirts_lab}
      REDIS_HOST: redis
      REDIS_PORT: 6379
      REDIS_PASSWORD: ${REDIS_PASSWORD:-redis}
      JWT_SECRET: ${JWT_SECRET}
      JWT_EXPIRATION: ${JWT_EXPIRATION:-3600}
      STRIPE_SECRET_KEY: ${STRIPE_SECRET_KEY}
      STRIPE_PUBLISHABLE_KEY: ${STRIPE_PUBLISHABLE_KEY}
    ports:
      - "${BACKEND_PORT:-3000}:3000"
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    volumes:
      - ./backend/src:/app/src
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Frontend
  frontend:
    build:
      context: ./frontend
      dockerfile: ../docker/frontend/Dockerfile
    container_name: tshirts_lab_frontend
    environment:
      VITE_API_BASE_URL: ${VITE_API_BASE_URL:-http://backend:3000}
    ports:
      - "${FRONTEND_PORT:-5173}:80"
    depends_on:
      - backend
    networks:
      - app-network

  # Nginx Reverse Proxy
  nginx:
    image: nginx:alpine
    container_name: tshirts_lab_nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./docker/nginx/ssl:/etc/nginx/ssl:ro
    depends_on:
      - backend
      - frontend
    networks:
      - app-network

volumes:
  postgres_data:
  redis_data:

networks:
  app-network:
    driver: bridge
```

## 🚀 CI/CD Pipeline (GitHub Actions)

### Backend Testing & Build

```yaml
# .github/workflows/backend.yml
name: Backend CI/CD

on:
  push:
    branches: [main, develop]
    paths:
      - 'backend/**'
      - '.github/workflows/backend.yml'
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:15-alpine
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: test_db
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

      redis:
        image: redis:7-alpine
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - uses: actions/setup-node@v3
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: 'backend/package-lock.json'

      - name: Install dependencies
        run: npm ci
        working-directory: backend

      - name: Lint
        run: npm run lint
        working-directory: backend

      - name: Format Check
        run: npm run format:check
        working-directory: backend

      - name: Build
        run: npm run build
        working-directory: backend

      - name: Unit Tests
        run: npm run test
        working-directory: backend

      - name: E2E Tests
        run: npm run test:e2e
        working-directory: backend
        env:
          DATABASE_URL: postgresql://postgres:postgres@localhost:5432/test_db
          REDIS_URL: redis://localhost:6379

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./backend/coverage/lcov.info
          flags: backend
          fail_ci_if_error: false

  build-image:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2

      - name: Login to Docker Registry
        uses: docker/login-action@v2
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and Push
        uses: docker/build-push-action@v4
        with:
          context: ./backend
          file: ./docker/backend/Dockerfile
          push: true
          tags: ghcr.io/${{ github.repository }}/backend:${{ github.sha }}
          cache-from: type=registry
          cache-to: type=inline
```

### Frontend Testing & Build

```yaml
# .github/workflows/frontend.yml
name: Frontend CI/CD

on:
  push:
    branches: [main, develop]
    paths:
      - 'frontend/**'
      - '.github/workflows/frontend.yml'
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - uses: actions/setup-node@v3
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: 'frontend/package-lock.json'

      - name: Install dependencies
        run: npm ci
        working-directory: frontend

      - name: Lint
        run: npm run lint
        working-directory: frontend

      - name: Format Check
        run: npm run format:check
        working-directory: frontend

      - name: Type Check
        run: npm run type-check
        working-directory: frontend

      - name: Build
        run: npm run build
        working-directory: frontend

      - name: Unit Tests
        run: npm run test
        working-directory: frontend

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./frontend/coverage/lcov.info
          flags: frontend
          fail_ci_if_error: false

  e2e-tests:
    runs-on: ubuntu-latest
    needs: test

    steps:
      - uses: actions/checkout@v3

      - uses: actions/setup-node@v3
        with:
          node-version: '20'

      - name: Install dependencies
        run: npm ci
        working-directory: frontend

      - name: Install Playwright browsers
        run: npx playwright install --with-deps
        working-directory: frontend

      - name: Run E2E tests
        run: npm run test:e2e
        working-directory: frontend

      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: frontend/playwright-report/

  build-image:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2

      - name: Login to Docker Registry
        uses: docker/login-action@v2
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and Push
        uses: docker/build-push-action@v4
        with:
          context: ./frontend
          file: ./docker/frontend/Dockerfile
          push: true
          tags: ghcr.io/${{ github.repository }}/frontend:${{ github.sha }}
          cache-from: type=registry
          cache-to: type=inline
```

## 📦 Kubernetes Deployment (Future)

### Backend Deployment

```yaml
# k8s/backend-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: tshirts-lab-backend
  namespace: production
spec:
  replicas: 3
  selector:
    matchLabels:
      app: backend
  template:
    metadata:
      labels:
        app: backend
    spec:
      containers:
      - name: backend
        image: ghcr.io/b4knamy/tshirts-lab/backend:latest
        ports:
        - containerPort: 3000
        env:
        - name: NODE_ENV
          value: "production"
        - name: DATABASE_HOST
          valueFrom:
            configMapKeyRef:
              name: app-config
              key: db-host
        - name: DATABASE_PASSWORD
          valueFrom:
            secretKeyRef:
              name: app-secrets
              key: db-password
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 3000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health
            port: 3000
          initialDelaySeconds: 5
          periodSeconds: 5
```

### Frontend Service

```yaml
# k8s/frontend-service.yaml
apiVersion: v1
kind: Service
metadata:
  name: tshirts-lab-frontend
  namespace: production
spec:
  type: LoadBalancer
  ports:
  - port: 80
    targetPort: 80
    protocol: TCP
  selector:
    app: frontend
```

## 🌐 Nginx Configuration

```nginx
# docker/nginx/nginx.conf
upstream backend {
    server backend:3000;
}

upstream frontend {
    server frontend:80;
}

server {
    listen 80;
    server_name localhost;

    # Backend API
    location /api/ {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Frontend
    location / {
        proxy_pass http://frontend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
```

## 📊 Monitoring & Logging (Future)

### Prometheus Configuration

```yaml
# monitoring/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'backend'
    static_configs:
      - targets: ['backend:3000']
    metrics_path: '/metrics'
```

### ELK Stack Setup (Future)

```yaml
# docker-compose.elk.yml
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.0.0
    environment:
      - discovery.type=single-node
    ports:
      - "9200:9200"

  kibana:
    image: docker.elastic.co/kibana/kibana:8.0.0
    ports:
      - "5601:5601"

  logstash:
    image: docker.elastic.co/logstash/logstash:8.0.0
    volumes:
      - ./logstash.conf:/usr/share/logstash/pipeline/logstash.conf
```

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Code review aprovado
- [ ] Todos os testes passando
- [ ] Coverage >= 80%
- [ ] Build sem warnings
- [ ] Security scanning completo
- [ ] Performance tests validados

### Deployment
- [ ] Database migrations rodadas
- [ ] Environment variables configuradas
- [ ] SSL certificates válidos
- [ ] Backups feitos
- [ ] Load balancing testado

### Post-Deployment
- [ ] Health checks passando
- [ ] Monitoring alertas funcionando
- [ ] Logs sendo coletados
- [ ] Performance dentro dos SLAs
- [ ] Rollback plan pronto

## 🔒 Security Best Practices

- ✅ Senhas em environment variables (nunca em código)
- ✅ SSL/TLS para toda comunicação
- ✅ Rate limiting em endpoints públicos
- ✅ CORS configurado corretamente
- ✅ Security headers (CSP, X-Frame-Options, etc.)
- ✅ Regular security updates
- ✅ Vulnerability scanning no CI/CD

---

**Última atualização**: Março 2026
