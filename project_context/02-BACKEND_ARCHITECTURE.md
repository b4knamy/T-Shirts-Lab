# Arquitetura Backend - NestJS

## 🏗️ Estrutura do Projeto Backend

```
backend/
├── src/
│   ├── common/
│   │   ├── decorators/          # Decoradores customizados
│   │   ├── filters/             # Exception filters
│   │   ├── guards/              # Auth guards, Role guards
│   │   ├── interceptors/        # Logging, response formatting
│   │   ├── pipes/               # Validation pipes
│   │   ├── middleware/          # Custom middleware
│   │   └── constants/           # Constantes globais
│   │
│   ├── config/
│   │   ├── database.config.ts   # TypeORM/Prisma config
│   │   ├── cache.config.ts      # Redis config
│   │   ├── stripe.config.ts     # Stripe config
│   │   ├── jwt.config.ts        # JWT secrets
│   │   └── validation.ts        # Env validation
│   │
│   ├── modules/
│   │   ├── auth/
│   │   │   ├── auth.controller.ts
│   │   │   ├── auth.service.ts
│   │   │   ├── auth.module.ts
│   │   │   ├── dto/
│   │   │   ├── strategies/      # JWT, Local strategies
│   │   │   └── interfaces/
│   │   │
│   │   ├── users/
│   │   │   ├── users.controller.ts
│   │   │   ├── users.service.ts
│   │   │   ├── users.module.ts
│   │   │   ├── entities/
│   │   │   ├── dto/
│   │   │   └── repositories/
│   │   │
│   │   ├── products/
│   │   │   ├── products.controller.ts
│   │   │   ├── products.service.ts
│   │   │   ├── products.module.ts
│   │   │   ├── entities/
│   │   │   │   ├── product.entity.ts
│   │   │   │   ├── category.entity.ts
│   │   │   │   └── design.entity.ts
│   │   │   ├── dto/
│   │   │   └── repositories/
│   │   │
│   │   ├── orders/
│   │   │   ├── orders.controller.ts
│   │   │   ├── orders.service.ts
│   │   │   ├── orders.module.ts
│   │   │   ├── entities/
│   │   │   │   ├── order.entity.ts
│   │   │   │   └── order-item.entity.ts
│   │   │   ├── dto/
│   │   │   └── repositories/
│   │   │
│   │   ├── cart/
│   │   │   ├── cart.controller.ts
│   │   │   ├── cart.service.ts
│   │   │   ├── cart.module.ts
│   │   │   ├── dto/
│   │   │   └── interfaces/
│   │   │
│   │   ├── payments/
│   │   │   ├── payments.controller.ts
│   │   │   ├── payments.service.ts
│   │   │   ├── payments.module.ts
│   │   │   ├── providers/
│   │   │   │   └── stripe.provider.ts
│   │   │   ├── dto/
│   │   │   └── entities/
│   │   │
│   │   ├── admin/
│   │   │   ├── admin.controller.ts
│   │   │   ├── admin.service.ts
│   │   │   ├── admin.module.ts
│   │   │   ├── dto/
│   │   │   └── guards/
│   │   │
│   │   ├── notifications/
│   │   │   ├── notifications.service.ts
│   │   │   ├── notifications.module.ts
│   │   │   └── providers/
│   │   │       └── email.provider.ts
│   │   │
│   │   └── health/
│   │       ├── health.controller.ts
│   │       └── health.module.ts
│   │
│   ├── database/
│   │   ├── migrations/          # Migration files
│   │   ├── seeders/             # Database seeders
│   │   └── factories/           # Entity factories para testes
│   │
│   ├── shared/
│   │   ├── services/
│   │   │   ├── cache.service.ts
│   │   │   ├── logger.service.ts
│   │   │   └── file-upload.service.ts
│   │   ├── utils/
│   │   ├── types/
│   │   └── exceptions/
│   │
│   ├── app.module.ts           # Root module
│   └── main.ts                 # Entry point
│
├── test/
│   ├── unit/                   # Unit tests
│   ├── integration/            # Integration tests
│   └── e2e/                    # E2E tests
│
├── docker/
│   ├── Dockerfile
│   └── docker-compose.yml
│
├── .env.example
├── package.json
├── tsconfig.json
├── jest.config.js
├── .eslintrc.js
├── .prettierrc
└── README.md
```

## 🔄 Padrões de Design & Princípios

### SOLID Principles
- **S**ingle Responsibility: Um serviço = uma responsabilidade
- **O**pen/Closed: Aberto para extensão, fechado para modificação
- **L**iskov Substitution: Interfaces bem definidas
- **I**nterface Segregation: Interfaces específicas, não genéricas
- **D**ependency Inversion: Injeção de dependências

### Design Patterns Utilizados

```typescript
// 1. Repository Pattern
@Injectable()
export class UserRepository {
  async findById(id: string): Promise<User> { }
  async save(user: User): Promise<User> { }
}

// 2. Dependency Injection (Built-in NestJS)
@Injectable()
export class AuthService {
  constructor(
    private readonly userRepository: UserRepository,
    private readonly jwtService: JwtService,
  ) {}
}

// 3. Observer Pattern (Events)
@Injectable()
export class OrderCreatedListener {
  @OnEvent('order.created')
  handleOrderCreated(payload: OrderCreatedEvent) { }
}

// 4. Strategy Pattern (Payment providers)
interface PaymentStrategy {
  processPayment(amount: number): Promise<void>;
}
```

## 📡 API Design

### Versionamento
```
GET /api/v1/products
GET /api/v2/products  # Futuro
```

### Response Format
```typescript
// Success
{
  "success": true,
  "data": { /* payload */ },
  "meta": {
    "timestamp": "2026-03-28T10:00:00Z",
    "version": "1.0.0"
  }
}

// Error
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Product with ID 123 not found",
    "statusCode": 404
  },
  "meta": {
    "timestamp": "2026-03-28T10:00:00Z"
  }
}
```

### Rate Limiting
```typescript
// Global rate limiting
@UseGuards(ThrottlerGuard)
@Throttle(100, 60) // 100 requests por 60 segundos
```

## 🔐 Authentication & Authorization

### JWT Strategy
- Access Token: 15 minutos
- Refresh Token: 7 dias
- Armazenado em HttpOnly cookies no frontend

```typescript
@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
  constructor(private configService: ConfigService) {
    super({
      jwtFromRequest: extractJwtFromCookie,
      ignoreExpiration: false,
      secretOrKey: configService.get('JWT_SECRET'),
    });
  }

  validate(payload: any) {
    return { userId: payload.sub, email: payload.email };
  }
}
```

### Role-Based Access Control (RBAC)
```typescript
enum UserRole {
  ADMIN = 'admin',
  CUSTOMER = 'customer',
  VENDOR = 'vendor',
}

@UseGuards(AuthGuard, RolesGuard)
@Roles(UserRole.ADMIN)
@Post('/admin/products')
createProduct() { }
```

## 💾 Database Design

### Entidades Principais

```typescript
// User
@Entity()
export class User {
  @PrimaryGeneratedColumn('uuid')
  id: string;
  
  @Column({ unique: true })
  email: string;
  
  @Column()
  password: string; // bcryptjs
  
  @Column()
  firstName: string;
  
  @Column()
  lastName: string;
  
  @Column({ type: 'enum', enum: UserRole })
  role: UserRole;
  
  @OneToMany(() => Order, order => order.user)
  orders: Order[];
  
  @CreateDateColumn()
  createdAt: Date;
  
  @UpdateDateColumn()
  updatedAt: Date;
}

// Product
@Entity()
export class Product {
  @PrimaryGeneratedColumn('uuid')
  id: string;
  
  @Column()
  name: string;
  
  @Column('decimal', { precision: 10, scale: 2 })
  price: number;
  
  @Column('text')
  description: string;
  
  @Column()
  sku: string;
  
  @Column()
  stock: number;
  
  @ManyToOne(() => Category)
  category: Category;
  
  @OneToMany(() => Design, design => design.product)
  designs: Design[];
  
  @CreateDateColumn()
  createdAt: Date;
}

// Order
@Entity()
export class Order {
  @PrimaryGeneratedColumn('uuid')
  id: string;
  
  @ManyToOne(() => User, user => user.orders)
  user: User;
  
  @OneToMany(() => OrderItem, item => item.order)
  items: OrderItem[];
  
  @Column('decimal', { precision: 10, scale: 2 })
  total: number;
  
  @Column({ type: 'enum', enum: OrderStatus })
  status: OrderStatus;
  
  @CreateDateColumn()
  createdAt: Date;
}

// Payment
@Entity()
export class Payment {
  @PrimaryGeneratedColumn('uuid')
  id: string;
  
  @OneToOne(() => Order)
  order: Order;
  
  @Column()
  stripePaymentIntentId: string;
  
  @Column({ type: 'enum', enum: PaymentStatus })
  status: PaymentStatus;
  
  @CreateDateColumn()
  createdAt: Date;
}
```

## ⚙️ Configuração TypeORM

```typescript
// database.config.ts
export default () => ({
  type: 'postgres',
  host: process.env.DATABASE_HOST,
  port: process.env.DATABASE_PORT,
  username: process.env.DATABASE_USER,
  password: process.env.DATABASE_PASSWORD,
  database: process.env.DATABASE_NAME,
  entities: [__dirname + '/../**/*.entity{.ts,.js}'],
  migrations: [__dirname + '/migrations/*{.ts,.js}'],
  synchronize: false, // Use migrations!
  logging: false,
  ssl: process.env.NODE_ENV === 'production',
});
```

## 🚀 Performance & Caching

### Redis Caching Strategy
```typescript
@Injectable()
export class CacheService {
  constructor(private cacheManager: Cache) {}

  async get<T>(key: string): Promise<T | undefined> {
    return this.cacheManager.get(key);
  }

  async set<T>(key: string, value: T, ttl: number): Promise<void> {
    await this.cacheManager.set(key, value, ttl * 1000);
  }
}

// Usage em serviços
@Injectable()
export class ProductService {
  async getProduct(id: string): Promise<Product> {
    const cacheKey = `product:${id}`;
    const cached = await this.cacheService.get<Product>(cacheKey);
    
    if (cached) return cached;
    
    const product = await this.productRepository.findById(id);
    await this.cacheService.set(cacheKey, product, 3600); // 1 hora
    
    return product;
  }
}
```

## 🧪 Testing Strategy

### Unit Tests
```bash
npm run test:unit
```

### Integration Tests
```bash
npm run test:integration
```

### E2E Tests
```bash
npm run test:e2e
```

## 📊 Logging & Monitoring

```typescript
@Injectable()
export class LoggerService {
  private logger = new Logger();

  log(message: string) {
    this.logger.log(message);
  }

  error(message: string, trace?: string) {
    this.logger.error(message, trace);
  }

  debug(message: string) {
    this.logger.debug(message);
  }
}
```

## 🔄 CI/CD Pipeline

```yaml
# .github/workflows/backend.yml
name: Backend Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
      redis:
        image: redis:7
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install
      - run: npm run lint
      - run: npm run test:unit
      - run: npm run test:e2e
```

---

**Última atualização**: Março 2026
