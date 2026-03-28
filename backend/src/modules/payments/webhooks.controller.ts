import {
  Controller,
  Post,
  Headers,
  Req,
  HttpCode,
  HttpStatus,
  Logger,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiExcludeEndpoint } from '@nestjs/swagger';
import { StripeProvider } from './providers/stripe.provider';
import { PaymentsService } from './payments.service';
import type Stripe from 'stripe';

interface RawBodyRequestLike {
  rawBody?: Buffer;
}

@ApiTags('Webhooks')
@Controller('webhooks')
export class WebhooksController {
  private readonly logger = new Logger(WebhooksController.name);

  constructor(
    private readonly stripeProvider: StripeProvider,
    private readonly paymentsService: PaymentsService,
  ) {}

  @Post('stripe')
  @HttpCode(HttpStatus.OK)
  @ApiExcludeEndpoint()
  async handleStripeWebhook(
    @Headers('stripe-signature') signature: string,
    @Req() req: RawBodyRequestLike,
  ) {
    let event: Stripe.Event;

    try {
      event = this.stripeProvider.constructWebhookEvent(
        req.rawBody as Buffer,
        signature,
      );
    } catch (err) {
      this.logger.error('Webhook signature verification failed', err);
      return { received: false };
    }

    switch (event.type) {
      case 'payment_intent.succeeded': {
        const paymentIntent = event.data.object;
        this.logger.log(`Payment succeeded: ${paymentIntent.id}`);
        await this.paymentsService.completePayment(paymentIntent.id);
        break;
      }

      case 'payment_intent.payment_failed': {
        const paymentIntent = event.data.object;
        this.logger.warn(`Payment failed: ${paymentIntent.id}`);
        break;
      }

      default:
        this.logger.log(`Unhandled event type: ${event.type}`);
    }

    return { received: true };
  }
}
