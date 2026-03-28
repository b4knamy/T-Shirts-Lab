# Padrões, Segurança e Melhores Práticas

## 🏗️ Padrões de Arquitetura & Design

### 1. Clean Architecture

```
Camadas (de fora para dentro):
┌─────────────────────────────────────────────────┐
│         Controllers / Presenters                │ (Frameworks)
├─────────────────────────────────────────────────┤
│         Use Cases / Application Services        │ (Business Rules)
├─────────────────────────────────────────────────┤
│         Entities / Domain Models                │ (Domain)
├─────────────────────────────────────────────────┤
│         Interfaces / Repositories               │ (Frameworks)
└─────────────────────────────────────────────────┘

Inversão de Dependências:
- Controllers dependem de Use Cases
- Use Cases dependem de Interfaces
- Implementations dependem de Interfaces
```

### 2. SOLID Principles

#### Single Responsibility Principle
```typescript
// ❌ Ruim - Múltiplas responsabilidades
class UserService {
  async createUser(data) { }
  async sendEmail(email) { }
  async logActivity(action) { }
  async generateReport() { }
}

// ✅ Bom - Uma responsabilidade por classe
class CreateUserUseCase {
  constructor(
    private userRepository: UserRepository,
    private emailService: EmailService,
    private eventBus: EventBus,
  ) {}

  async execute(data: CreateUserDTO) {
    const user = await this.userRepository.create(data);
    this.eventBus.emit('user.created', user);
    return user;
  }
}
```

#### Open/Closed Principle
```typescript
// ✅ Aberto para extensão, fechado para modificação
interface PaymentProvider {
  processPayment(amount: number): Promise<PaymentResult>;
}

class StripePaymentProvider implements PaymentProvider {
  async processPayment(amount: number): Promise<PaymentResult> { }
}

class PayPalPaymentProvider implements PaymentProvider {
  async processPayment(amount: number): Promise<PaymentResult> { }
}

// Adicionar novo provider sem modificar código existente
class ApplePaymentProvider implements PaymentProvider {
  async processPayment(amount: number): Promise<PaymentResult> { }
}
```

#### Liskov Substitution Principle
```typescript
// ✅ Subclasses são intercambiáveis
abstract class Order {
  abstract calculateTotal(): number;
  abstract applyDiscount(percent: number): void;
}

class RegularOrder extends Order {
  calculateTotal(): number { }
  applyDiscount(percent: number): void { }
}

class VIPOrder extends Order {
  calculateTotal(): number { }
  applyDiscount(percent: number): void { }
}

// Ambas podem ser usadas da mesma forma
function processOrder(order: Order) {
  const total = order.calculateTotal();
  order.applyDiscount(10);
}
```

#### Interface Segregation Principle
```typescript
// ❌ Ruim - Interface genérica demais
interface IUser {
  login(): void;
  logout(): void;
  updateProfile(): void;
  deleteAccount(): void;
  // ... mais 20 métodos
}

// ✅ Bom - Interfaces específicas
interface IAuthenticable {
  login(): Promise<void>;
  logout(): Promise<void>;
}

interface IProfileManageable {
  updateProfile(data: UpdateProfileDTO): Promise<void>;
}

interface IDeletable {
  deleteAccount(): Promise<void>;
}

class User implements IAuthenticable, IProfileManageable, IDeletable { }
```

#### Dependency Inversion Principle
```typescript
// ❌ Ruim - Dependência direta em implementação
class OrderService {
  private database = new PostgresDatabase();
  
  async getOrder(id: string) {
    return this.database.query(...);
  }
}

// ✅ Bom - Depende de abstração
interface IDatabase {
  query(sql: string): Promise<any>;
}

class OrderService {
  constructor(private database: IDatabase) {}
  
  async getOrder(id: string) {
    return this.database.query(...);
  }
}
```

### 3. Design Patterns Utilizados

#### Adapter Pattern (Stripe Integration)
```typescript
interface PaymentGateway {
  charge(amount: number, cardToken: string): Promise<string>;
}

class StripeAdapter implements PaymentGateway {
  constructor(private stripe: Stripe) {}
  
  async charge(amount: number, cardToken: string): Promise<string> {
    const result = await this.stripe.charges.create({
      amount: Math.round(amount * 100),
      source: cardToken,
      currency: 'usd',
    });
    return result.id;
  }
}
```

#### Factory Pattern (Entity Creation)
```typescript
class ProductFactory {
  static create(data: CreateProductDTO): Product {
    const product = new Product();
    product.name = data.name;
    product.price = data.price;
    product.createdAt = new Date();
    return product;
  }
}

// Uso
const product = ProductFactory.create(productData);
```

#### Observer Pattern (Event System)
```typescript
class EventBus {
  private listeners: Map<string, Function[]> = new Map();
  
  on(event: string, handler: Function) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, []);
    }
    this.listeners.get(event)!.push(handler);
  }
  
  emit(event: string, data: any) {
    const handlers = this.listeners.get(event) || [];
    handlers.forEach(handler => handler(data));
  }
}

// Uso
eventBus.on('order.created', (order) => {
  emailService.sendConfirmation(order);
  inventoryService.reserve(order.items);
});

eventBus.emit('order.created', newOrder);
```

#### Strategy Pattern (Shipping)
```typescript
interface ShippingStrategy {
  calculate(weight: number, distance: number): number;
}

class StandardShipping implements ShippingStrategy {
  calculate(weight: number, distance: number): number {
    return weight * 0.5 + distance * 0.01;
  }
}

class ExpressShipping implements ShippingStrategy {
  calculate(weight: number, distance: number): number {
    return weight * 1.5 + distance * 0.05;
  }
}

class ShippingCalculator {
  constructor(private strategy: ShippingStrategy) {}
  
  calculateCost(weight: number, distance: number): number {
    return this.strategy.calculate(weight, distance);
  }
}
```

## 🔐 Segurança em Profundidade

### 1. Autenticação & Autorização

```typescript
// JWT com refresh tokens
@Injectable()
export class AuthService {
  async login(email: string, password: string) {
    const user = await this.userRepository.findByEmail(email);
    if (!user) throw new UnauthorizedException();
    
    const isValid = await bcrypt.compare(password, user.passwordHash);
    if (!isValid) throw new UnauthorizedException();
    
    const accessToken = this.jwtService.sign(
      { sub: user.id, email: user.email },
      { expiresIn: '15m' }
    );
    
    const refreshToken = this.jwtService.sign(
      { sub: user.id },
      { expiresIn: '7d' }
    );
    
    return { accessToken, refreshToken };
  }
}

// Role-based access control
@UseGuards(AuthGuard, RolesGuard)
@Roles(UserRole.ADMIN)
@Delete('/admin/users/:id')
deleteUser(@Param('id') userId: string) {
  return this.userService.delete(userId);
}
```

### 2. Input Validation & Sanitization

```typescript
// Frontend validation
import { z } from 'zod';

const createProductSchema = z.object({
  name: z.string().min(3).max(255),
  price: z.number().positive(),
  description: z.string().max(2000).optional(),
  sku: z.string().regex(/^[A-Z0-9-]+$/),
});

// Backend validation
@Post('/products')
async createProduct(@Body() dto: CreateProductDTO) {
  // Validate with Zod
  const validated = await createProductSchema.parseAsync(dto);
  
  // Sanitize inputs
  const sanitized = {
    ...validated,
    name: sanitizeString(validated.name),
    description: sanitizeHtml(validated.description),
  };
  
  return this.productService.create(sanitized);
}
```

### 3. SQL Injection Prevention

```typescript
// ❌ Ruim - Vulnerável a SQL injection
const query = `SELECT * FROM users WHERE email = '${email}'`;
database.execute(query);

// ✅ Bom - Query parametrizada
const query = 'SELECT * FROM users WHERE email = $1';
database.execute(query, [email]);

// ✅ Com ORM - Proteção automática
const user = await userRepository.findOne({ email });
```

### 4. CORS Configuration

```typescript
// main.ts
const app = await NestFactory.create(AppModule);

app.enableCors({
  origin: process.env.FRONTEND_URL,
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  maxAge: 3600,
});

app.listen(3000);
```

### 5. Rate Limiting

```typescript
@Controller('api/v1')
export class AppController {
  @UseGuards(ThrottlerGuard)
  @Throttle(100, 60) // 100 requests per 60 seconds
  @Post('/login')
  async login(@Body() credentials: LoginDTO) {
    // ...
  }

  @UseGuards(ThrottlerGuard)
  @Throttle(1000, 60) // Higher limit for API
  @Get('/products')
  async getProducts() {
    // ...
  }
}
```

### 6. Security Headers

```typescript
// middleware/security.middleware.ts
export class SecurityHeadersMiddleware implements NestMiddleware {
  use(req: Request, res: Response, next: NextFunction) {
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('X-Frame-Options', 'DENY');
    res.setHeader('X-XSS-Protection', '1; mode=block');
    res.setHeader(
      'Strict-Transport-Security',
      'max-age=31536000; includeSubDomains'
    );
    res.setHeader(
      'Content-Security-Policy',
      "default-src 'self'; script-src 'self' 'unsafe-inline' https://js.stripe.com"
    );
    next();
  }
}

// app.module.ts
export class AppModule implements NestModule {
  configure(consumer: MiddlewareConsumer) {
    consumer.apply(SecurityHeadersMiddleware).forRoutes('*');
  }
}
```

## 🧪 Testing Strategy

### Unit Tests
```typescript
describe('ProductService', () => {
  let service: ProductService;
  let repository: ProductRepository;

  beforeEach(async () => {
    const module = await Test.createTestingModule({
      providers: [
        ProductService,
        {
          provide: ProductRepository,
          useValue: {
            findById: jest.fn(),
            create: jest.fn(),
          },
        },
      ],
    }).compile();

    service = module.get(ProductService);
    repository = module.get(ProductRepository);
  });

  it('should return product by id', async () => {
    const product = { id: '1', name: 'T-Shirt' };
    jest.spyOn(repository, 'findById').mockResolvedValue(product);

    const result = await service.getProduct('1');

    expect(result).toEqual(product);
    expect(repository.findById).toHaveBeenCalledWith('1');
  });

  it('should throw if product not found', async () => {
    jest.spyOn(repository, 'findById').mockResolvedValue(null);

    await expect(service.getProduct('invalid')).rejects.toThrow(
      NotFoundException,
    );
  });
});
```

### Integration Tests
```typescript
describe('Products E2E', () => {
  let app: INestApplication;

  beforeAll(async () => {
    const moduleFixture = await Test.createTestingModule({
      imports: [AppModule],
    }).compile();

    app = moduleFixture.createNestApplication();
    await app.init();
  });

  it('should create and retrieve product', async () => {
    const createDto = { name: 'T-Shirt', price: 29.99 };

    const createRes = await request(app.getHttpServer())
      .post('/api/v1/products')
      .send(createDto)
      .expect(201);

    const productId = createRes.body.id;

    await request(app.getHttpServer())
      .get(`/api/v1/products/${productId}`)
      .expect(200)
      .expect((res) => {
        expect(res.body.name).toEqual(createDto.name);
      });
  });
});
```

## 📊 Performance Optimization

### Backend Performance
```typescript
// 1. Query optimization com pagination
@Get('/products')
async getProducts(
  @Query('page', ParseIntPipe) page: number = 1,
  @Query('limit', ParseIntPipe) limit: number = 20,
) {
  const skip = (page - 1) * limit;
  return this.productRepository.find({
    skip,
    take: limit,
    relations: ['category', 'images'],
  });
}

// 2. Caching estratégico
@Get('/products/:id')
@CacheTTL(3600)
@CacheKey('product_#id')
async getProduct(@Param('id') id: string) {
  return this.productRepository.findOne(id);
}

// 3. Lazy loading de relações
const product = await this.productRepository.findOne(id, {
  relations: ['category'],
  relationLoadStrategy: 'query',
});
```

### Frontend Performance
```typescript
// 1. Code splitting
const ProductDetail = lazy(() => import('./pages/ProductDetail'));

// 2. Image optimization
<img
  src={product.image}
  alt={product.name}
  loading="lazy"
  srcSet={`
    ${product.image}?w=400 400w,
    ${product.image}?w=800 800w
  `}
/>

// 3. React Query com stale while revalidate
const { data } = useQuery({
  queryKey: ['products'],
  queryFn: fetchProducts,
  staleTime: 5 * 60 * 1000,
  cacheTime: 10 * 60 * 1000,
});
```

## 📋 Git Workflow & Versionamento

### Conventional Commits
```
<type>[optional scope]: <description>

feat: add payment integration
fix: correct product pricing calculation
docs: update deployment guide
style: format code
refactor: reorganize cart module
test: add order service tests
chore: update dependencies

BREAKING CHANGE: refactor auth module
```

### Semantic Versioning
```
MAJOR.MINOR.PATCH

1.2.3
↓ ↓ ↓
│ │ └─ Patch: Bug fixes (1.2.3 → 1.2.4)
│ └───── Minor: New features (1.2.0 → 1.3.0)
└─────── Major: Breaking changes (1.0.0 → 2.0.0)
```

### Git Branch Strategy (Git Flow)
```
main (production)
  ↓
release/v1.2.0
  ↓
develop (staging)
  ↓
feature/payment-integration
feature/product-filters
bugfix/cart-calculation
hotfix/critical-bug
```

## 📚 Documentation Standards

### Code Comments
```typescript
/**
 * Calcula o preço final de um produto considerando descontos
 * @param basePrice - Preço base do produto em cents
 * @param discountPercent - Percentual de desconto (0-100)
 * @param taxRate - Taxa de imposto a aplicar (0-1)
 * @returns Preço final em cents
 * @example
 * const price = calculateFinalPrice(2999, 10, 0.08); // $27.31
 */
export function calculateFinalPrice(
  basePrice: number,
  discountPercent: number,
  taxRate: number,
): number {
  const discounted = basePrice * (1 - discountPercent / 100);
  return Math.round(discounted * (1 + taxRate));
}
```

### API Documentation (Swagger)
```typescript
@Controller('api/v1/products')
@ApiTags('Products')
export class ProductController {
  @Get()
  @ApiOperation({ summary: 'List all products' })
  @ApiQuery({ name: 'page', required: false, type: Number })
  @ApiQuery({ name: 'limit', required: false, type: Number })
  @ApiResponse({
    status: 200,
    description: 'Products list',
    type: [ProductDto],
  })
  async getProducts(
    @Query('page') page?: number,
    @Query('limit') limit?: number,
  ) {
    // ...
  }
}
```

---

**Última atualização**: Março 2026
