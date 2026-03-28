import {
  Controller,
  Post,
  Get,
  Body,
  Param,
  UseGuards,
  Headers,
  RawBodyRequest,
  Req,
} from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { ApiTags, ApiOperation, ApiBearerAuth } from '@nestjs/swagger';
import { Request } from 'express';
import { PaymentsService } from './payments.service';
import { StripeProvider } from './providers/stripe.provider';
import {
  CreatePaymentIntentDto,
  ConfirmPaymentDto,
  RefundPaymentDto,
} from './dto';
import { CurrentUser, Roles } from '../../common/decorators';
import { RolesGuard } from '../../common/guards';
import { UserRole } from '../../common/constants/enums';

@ApiTags('Payments')
@Controller('api/v1/payments')
export class PaymentsController {
  constructor(
    private readonly paymentsService: PaymentsService,
    private readonly stripeProvider: StripeProvider,
  ) {}

  @Post('create-intent')
  @UseGuards(AuthGuard('jwt'))
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Create a Stripe payment intent' })
  async createPaymentIntent(
    @Body() dto: CreatePaymentIntentDto,
    @CurrentUser('userId') userId: string,
  ) {
    return this.paymentsService.createPaymentIntent(dto, userId);
  }

  @Post('confirm')
  @UseGuards(AuthGuard('jwt'))
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Confirm a payment' })
  async confirmPayment(@Body() dto: ConfirmPaymentDto) {
    return this.paymentsService.confirmPayment(
      dto.paymentIntentId,
      dto.paymentMethodId,
    );
  }

  @Get(':paymentIntentId')
  @UseGuards(AuthGuard('jwt'))
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Get payment status' })
  async getPaymentStatus(@Param('paymentIntentId') paymentIntentId: string) {
    return this.stripeProvider.getPaymentIntent(paymentIntentId);
  }

  @Post('refund')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Refund a payment (Admin only)' })
  async refundPayment(@Body() dto: RefundPaymentDto) {
    return this.paymentsService.refundPayment(dto.paymentIntentId, dto.amount);
  }
}
