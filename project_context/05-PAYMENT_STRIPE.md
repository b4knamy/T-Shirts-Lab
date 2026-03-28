# Payment Integration - Stripe

## 🏦 Stripe Payment Architecture

### Payment Flow Overview

```
┌──────────────┐
│   Customer   │
│  (Browser)   │
└──────┬───────┘
       │
       │ 1. Add products to cart
       ↓
┌──────────────┐
│  Frontend    │  2. Initiate checkout
│  (React)     │     - Get Client Secret
└──────┬───────┘
       │
       │ 3. Call /api/v1/payments/create-intent
       ↓
┌──────────────┐
│  Backend     │  4. Create Payment Intent
│  (NestJS)    │     - Save to DB with PENDING status
└──────┬───────┘
       │
       │ 5. Stripe API Call
       ↓
┌──────────────┐
│   Stripe     │  6. Return Client Secret
└──────┬───────┘
       │
       │ 7. Return to Frontend
       ↓
┌──────────────┐
│  Stripe.js   │  8. Display Payment Form
│  (Elements)  │     - Customer enters card details
└──────┬───────┘
       │
       │ 9. Confirm Payment
       ↓
┌──────────────┐
│   Stripe     │  10. Process Payment
└──────┬───────┘
       │
       │ 11. Webhook: payment_intent.succeeded
       ↓
┌──────────────┐
│  Backend     │  12. Confirm Order
│  (Webhook)   │     - Update order status
└──────┬───────┘
       │
       │ 13. Send Confirmation Email
       ↓
┌──────────────┐
│   Customer   │
│  (Email)     │
└──────────────┘
```

## 💳 Stripe Integration Implementation

### Environment Configuration

```env
# .env
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_API_VERSION=2023-10-16
```

### Stripe Service Implementation

```typescript
// stripe.service.ts
import Stripe from 'stripe';

@Injectable()
export class StripeService {
  private stripe: Stripe;

  constructor(private configService: ConfigService) {
    this.stripe = new Stripe(
      this.configService.get('STRIPE_SECRET_KEY'),
      {
        apiVersion: '2023-10-16',
      },
    );
  }

  // Create Payment Intent
  async createPaymentIntent(
    amount: number,
    currency: string = 'USD',
    metadata?: Record<string, any>,
  ): Promise<Stripe.PaymentIntent> {
    try {
      return await this.stripe.paymentIntents.create({
        amount: Math.round(amount * 100), // Stripe uses cents
        currency: currency.toLowerCase(),
        metadata: {
          ...metadata,
          createdAt: new Date().toISOString(),
        },
        automatic_payment_methods: {
          enabled: true,
        },
      });
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Confirm Payment Intent
  async confirmPaymentIntent(
    paymentIntentId: string,
    paymentMethodId: string,
  ): Promise<Stripe.PaymentIntent> {
    try {
      return await this.stripe.paymentIntents.confirm(paymentIntentId, {
        payment_method: paymentMethodId,
      });
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Retrieve Payment Intent
  async getPaymentIntent(
    paymentIntentId: string,
  ): Promise<Stripe.PaymentIntent> {
    try {
      return await this.stripe.paymentIntents.retrieve(paymentIntentId);
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Create Refund
  async createRefund(
    paymentIntentId: string,
    amount?: number,
  ): Promise<Stripe.Refund> {
    try {
      return await this.stripe.refunds.create({
        payment_intent: paymentIntentId,
        amount: amount ? Math.round(amount * 100) : undefined,
      });
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Create Customer
  async createCustomer(
    email: string,
    name: string,
    metadata?: Record<string, any>,
  ): Promise<Stripe.Customer> {
    try {
      return await this.stripe.customers.create({
        email,
        name,
        metadata,
      });
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Save Payment Method
  async attachPaymentMethod(
    paymentMethodId: string,
    customerId: string,
  ): Promise<Stripe.PaymentMethod> {
    try {
      return await this.stripe.paymentMethods.attach(paymentMethodId, {
        customer: customerId,
      });
    } catch (error) {
      this.handleStripeError(error);
    }
  }

  // Handle Stripe Errors
  private handleStripeError(error: any): never {
    if (error instanceof Stripe.errors.StripeError) {
      throw new BadRequestException({
        message: error.message,
        code: error.code,
        statusCode: error.statusCode,
      });
    }
    throw error;
  }
}
```

### Payment Controller

```typescript
// payment.controller.ts
@Controller('api/v1/payments')
@UseGuards(AuthGuard)
export class PaymentController {
  constructor(
    private readonly paymentService: PaymentService,
    private readonly stripeService: StripeService,
  ) {}

  @Post('create-intent')
  async createPaymentIntent(@Body() dto: CreatePaymentIntentDTO) {
    const intent = await this.stripeService.createPaymentIntent(
      dto.amount,
      dto.currency,
      {
        orderId: dto.orderId,
        userId: dto.userId,
      },
    );

    // Save payment record
    await this.paymentService.createPayment({
      orderId: dto.orderId,
      amount: dto.amount,
      currency: dto.currency,
      stripePaymentIntentId: intent.id,
      status: 'PENDING',
    });

    return {
      clientSecret: intent.client_secret,
      paymentIntentId: intent.id,
    };
  }

  @Post('confirm')
  async confirmPayment(@Body() dto: ConfirmPaymentDTO) {
    const intent = await this.stripeService.confirmPaymentIntent(
      dto.paymentIntentId,
      dto.paymentMethodId,
    );

    if (intent.status === 'succeeded') {
      await this.paymentService.completePayment(dto.paymentIntentId);
      return { status: 'completed', orderId: intent.metadata.orderId };
    }

    return { status: intent.status };
  }

  @Get(':paymentIntentId')
  async getPaymentStatus(
    @Param('paymentIntentId') paymentIntentId: string,
  ) {
    return this.stripeService.getPaymentIntent(paymentIntentId);
  }

  @Post('refund')
  @Roles(UserRole.ADMIN)
  async refundPayment(@Body() dto: RefundPaymentDTO) {
    const refund = await this.stripeService.createRefund(
      dto.paymentIntentId,
      dto.amount,
    );

    await this.paymentService.refundPayment(dto.paymentIntentId, refund.id);

    return { refundId: refund.id, status: refund.status };
  }
}
```

### Payment Service

```typescript
// payment.service.ts
@Injectable()
export class PaymentService {
  constructor(
    private readonly paymentRepository: PaymentRepository,
    private readonly orderService: OrderService,
    private readonly notificationService: NotificationService,
    private readonly cacheService: CacheService,
  ) {}

  async createPayment(data: CreatePaymentDTO): Promise<Payment> {
    const payment = await this.paymentRepository.create(data);
    await this.cacheService.set(`payment:${data.stripePaymentIntentId}`, payment);
    return payment;
  }

  async completePayment(paymentIntentId: string): Promise<Payment> {
    const payment = await this.paymentRepository.update(paymentIntentId, {
      status: 'COMPLETED',
      completedAt: new Date(),
    });

    // Update order status
    await this.orderService.updateOrderStatus(payment.orderId, 'CONFIRMED');

    // Send confirmation email
    const order = await this.orderService.getOrder(payment.orderId);
    await this.notificationService.sendOrderConfirmation(order);

    // Invalidate cache
    await this.cacheService.invalidate(`payment:${paymentIntentId}`);

    return payment;
  }

  async refundPayment(paymentIntentId: string, refundId: string): Promise<Payment> {
    const payment = await this.paymentRepository.update(paymentIntentId, {
      status: 'REFUNDED',
      refundId,
      updatedAt: new Date(),
    });

    // Update order status
    await this.orderService.updateOrderStatus(payment.orderId, 'REFUNDED');

    // Restore stock
    const order = await this.orderService.getOrder(payment.orderId);
    for (const item of order.items) {
      await this.orderService.releaseInventory(item.productId, item.quantity);
    }

    // Send refund notification
    await this.notificationService.sendRefundNotification(order);

    return payment;
  }
}
```

## 🪝 Webhook Handling

### Webhook Controller

```typescript
// webhook.controller.ts
@Controller('webhooks')
export class WebhookController {
  constructor(
    private readonly stripeService: StripeService,
    private readonly paymentService: PaymentService,
    private readonly logger: LoggerService,
  ) {}

  @Post('stripe')
  async handleStripeWebhook(
    @Req() request: Request,
    @Headers('stripe-signature') signature: string,
  ) {
    const event = await this.verifyStripeSignature(request, signature);

    switch (event.type) {
      case 'payment_intent.succeeded':
        await this.handlePaymentSucceeded(event.data.object);
        break;

      case 'payment_intent.payment_failed':
        await this.handlePaymentFailed(event.data.object);
        break;

      case 'charge.refunded':
        await this.handleChargeRefunded(event.data.object);
        break;

      default:
        this.logger.debug(`Unhandled event type: ${event.type}`);
    }

    return { received: true };
  }

  private async verifyStripeSignature(
    request: Request,
    signature: string,
  ): Promise<Stripe.Event> {
    const rawBody = request.rawBody || (await getRawBody(request));
    const secret = process.env.STRIPE_WEBHOOK_SECRET;

    try {
      return stripe.webhooks.constructEvent(rawBody, signature, secret);
    } catch (error) {
      throw new BadRequestException(`Webhook Error: ${error.message}`);
    }
  }

  private async handlePaymentSucceeded(paymentIntent: Stripe.PaymentIntent) {
    this.logger.log(`Payment succeeded: ${paymentIntent.id}`);
    await this.paymentService.completePayment(paymentIntent.id);
  }

  private async handlePaymentFailed(paymentIntent: Stripe.PaymentIntent) {
    this.logger.error(`Payment failed: ${paymentIntent.id}`);
    // Update payment status to FAILED
    // Notify user
  }

  private async handleChargeRefunded(charge: Stripe.Charge) {
    this.logger.log(`Charge refunded: ${charge.id}`);
    // Handle refund logic
  }
}
```

## 💰 Payment Error Handling

```typescript
// payment-error.handler.ts
export const handleStripeError = (error: Stripe.StripeError) => {
  switch (error.type) {
    case 'StripeCardError':
      // Card declined or other card errors
      return {
        statusCode: 400,
        message: error.message,
        code: error.code,
      };

    case 'StripeRateLimitError':
      // Too many requests
      return {
        statusCode: 429,
        message: 'Too many requests. Please try again later.',
      };

    case 'StripeInvalidRequestError':
      // Invalid parameters
      return {
        statusCode: 400,
        message: error.message,
      };

    case 'StripeAuthenticationError':
      // Authentication failed
      return {
        statusCode: 401,
        message: 'Authentication failed.',
      };

    case 'StripeConnectionError':
      // Network error
      return {
        statusCode: 503,
        message: 'Service temporarily unavailable.',
      };

    default:
      return {
        statusCode: 500,
        message: 'An unexpected error occurred.',
      };
  }
};
```

## 🔐 PCI Compliance & Security

### Best Practices

- ✅ **Never log sensitive data**: Card numbers, CVC, etc.
- ✅ **Use Stripe Elements**: Never handle raw card data
- ✅ **Use HTTPS**: All communication encrypted
- ✅ **Implement CSP**: Content Security Policy headers
- ✅ **Validate amounts**: Always validate on backend
- ✅ **Idempotency keys**: Prevent duplicate charges

### Idempotency Implementation

```typescript
// payment.controller.ts
@Post('create-intent')
async createPaymentIntent(
  @Body() dto: CreatePaymentIntentDTO,
  @Headers('idempotency-key') idempotencyKey: string,
) {
  const cacheKey = `idempotency:${idempotencyKey}`;
  
  // Check if request was already processed
  const cached = await this.cacheService.get(cacheKey);
  if (cached) return cached;

  const result = await this.stripeService.createPaymentIntent(
    dto.amount,
    dto.currency,
  );

  // Cache the result
  await this.cacheService.set(cacheKey, result, 86400); // 24h

  return result;
}
```

## 📊 Payment Analytics

```typescript
// payment-analytics.service.ts
@Injectable()
export class PaymentAnalyticsService {
  async getDailyRevenue(date: Date): Promise<number> {
    return this.paymentRepository.sumByDate(date, 'COMPLETED');
  }

  async getPaymentMethodStats(): Promise<any> {
    return this.paymentRepository.groupByPaymentMethod();
  }

  async getFailureRate(): Promise<number> {
    const total = await this.paymentRepository.count();
    const failed = await this.paymentRepository.count({ status: 'FAILED' });
    return (failed / total) * 100;
  }

  async getAverageOrderValue(): Promise<number> {
    return this.paymentRepository.getAverageAmount();
  }
}
```

## 🧪 Testing Stripe Integration

### Test Cards

| Card Number | Description |
|-------------|-------------|
| 4242 4242 4242 4242 | Visa - Success |
| 5555 5555 5555 4444 | Mastercard - Success |
| 378282246310005 | American Express - Success |
| 4000 0000 0000 0002 | Card Declined |
| 4000 0000 0000 0341 | 3D Secure Required |

### Unit Tests

```typescript
// payment.service.spec.ts
describe('PaymentService', () => {
  let service: PaymentService;
  let stripeService: StripeService;

  beforeEach(async () => {
    const module = await Test.createTestingModule({
      providers: [
        PaymentService,
        {
          provide: StripeService,
          useValue: {
            createPaymentIntent: jest.fn(),
            confirmPaymentIntent: jest.fn(),
          },
        },
      ],
    }).compile();

    service = module.get(PaymentService);
    stripeService = module.get(StripeService);
  });

  it('should create payment intent', async () => {
    const mock = {
      id: 'pi_123',
      client_secret: 'secret_123',
      status: 'requires_payment_method',
    };

    jest.spyOn(stripeService, 'createPaymentIntent').mockResolvedValue(mock);

    const result = await service.createPayment({
      orderId: 'order_123',
      amount: 100,
      currency: 'USD',
    });

    expect(result).toBeDefined();
  });
});
```

---

**Última atualização**: Março 2026
