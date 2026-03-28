import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { PaymentsService } from './payments.service';
import { PaymentsController } from './payments.controller';
import { WebhooksController } from './webhooks.controller';
import { StripeProvider } from './providers/stripe.provider';
import { Payment } from './entities/payment.entity';
import { OrdersModule } from '../orders/orders.module';

@Module({
  imports: [TypeOrmModule.forFeature([Payment]), OrdersModule],
  controllers: [PaymentsController, WebhooksController],
  providers: [PaymentsService, StripeProvider],
  exports: [PaymentsService],
})
export class PaymentsModule {}
