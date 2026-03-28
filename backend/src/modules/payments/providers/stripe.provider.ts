import { Injectable, BadRequestException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import Stripe from 'stripe';

@Injectable()
export class StripeProvider {
  private stripe: Stripe;

  constructor(private configService: ConfigService) {
    this.stripe = new Stripe(
      this.configService.get<string>('STRIPE_SECRET_KEY') || '',
      {
        apiVersion: '2023-10-16' as Stripe.LatestApiVersion,
      },
    );
  }

  async createPaymentIntent(
    amount: number,
    currency = 'USD',
    metadata?: Record<string, string>,
  ): Promise<Stripe.PaymentIntent> {
    try {
      return await this.stripe.paymentIntents.create({
        amount: Math.round(amount * 100),
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

  async getPaymentIntent(
    paymentIntentId: string,
  ): Promise<Stripe.PaymentIntent> {
    try {
      return await this.stripe.paymentIntents.retrieve(paymentIntentId);
    } catch (error) {
      this.handleStripeError(error);
    }
  }

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

  async createCustomer(
    email: string,
    name: string,
    metadata?: Record<string, string>,
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

  constructWebhookEvent(payload: Buffer, signature: string): Stripe.Event {
    const webhookSecret = this.configService.get<string>(
      'STRIPE_WEBHOOK_SECRET',
    );
    return this.stripe.webhooks.constructEvent(
      payload,
      signature,
      webhookSecret || '',
    );
  }

  private handleStripeError(error: unknown): never {
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
