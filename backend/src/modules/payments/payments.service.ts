import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Payment } from './entities/payment.entity';
import { StripeProvider } from './providers/stripe.provider';
import { OrdersService } from '../orders/orders.service';
import { PaymentStatus } from '../../common/constants/enums';
import { CreatePaymentIntentDto } from './dto';

@Injectable()
export class PaymentsService {
  constructor(
    @InjectRepository(Payment)
    private readonly paymentRepository: Repository<Payment>,
    private readonly stripeProvider: StripeProvider,
    private readonly ordersService: OrdersService,
  ) {}

  async createPaymentIntent(dto: CreatePaymentIntentDto, userId: string) {
    const order = await this.ordersService.findOne(dto.orderId);

    const intent = await this.stripeProvider.createPaymentIntent(
      Number(order.total),
      dto.currency || 'USD',
      {
        orderId: dto.orderId,
        userId,
        orderNumber: order.orderNumber,
      },
    );

    const payment = this.paymentRepository.create({
      orderId: dto.orderId,
      amount: Number(order.total),
      currency: dto.currency || 'USD',
      paymentMethod: 'stripe',
      stripePaymentIntentId: intent.id,
      status: PaymentStatus.PENDING,
    });

    await this.paymentRepository.save(payment);

    return {
      clientSecret: intent.client_secret,
      paymentIntentId: intent.id,
    };
  }

  async confirmPayment(paymentIntentId: string, paymentMethodId: string) {
    const intent = await this.stripeProvider.confirmPaymentIntent(
      paymentIntentId,
      paymentMethodId,
    );

    if (intent.status === 'succeeded') {
      await this.completePayment(paymentIntentId);
      return { status: 'completed', orderId: intent.metadata.orderId };
    }

    return { status: intent.status };
  }

  async completePayment(paymentIntentId: string): Promise<Payment> {
    const payment = await this.paymentRepository.findOne({
      where: { stripePaymentIntentId: paymentIntentId },
    });

    if (!payment) {
      throw new NotFoundException('Payment not found');
    }

    payment.status = PaymentStatus.COMPLETED;
    payment.completedAt = new Date();

    const updatedPayment = await this.paymentRepository.save(payment);

    // Update order payment status
    await this.ordersService.updatePaymentStatus(
      payment.orderId,
      PaymentStatus.COMPLETED,
    );

    return updatedPayment;
  }

  async refundPayment(paymentIntentId: string, amount?: number) {
    const refund = await this.stripeProvider.createRefund(
      paymentIntentId,
      amount,
    );

    const payment = await this.paymentRepository.findOne({
      where: { stripePaymentIntentId: paymentIntentId },
    });

    if (payment) {
      payment.status = PaymentStatus.REFUNDED;
      await this.paymentRepository.save(payment);

      await this.ordersService.updatePaymentStatus(
        payment.orderId,
        PaymentStatus.REFUNDED,
      );
    }

    return { refundId: refund.id, status: refund.status };
  }

  async getPaymentByOrderId(orderId: string): Promise<Payment | null> {
    return this.paymentRepository.findOne({
      where: { orderId },
    });
  }
}
