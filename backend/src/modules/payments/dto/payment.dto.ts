import { IsString, IsNumber, IsOptional, IsUUID } from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class CreatePaymentIntentDto {
  @ApiProperty()
  @IsUUID()
  orderId!: string;

  @ApiProperty({ example: 159.8 })
  @IsNumber()
  amount!: number;

  @ApiPropertyOptional({ example: 'USD' })
  @IsOptional()
  @IsString()
  currency?: string = 'USD';
}

export class ConfirmPaymentDto {
  @ApiProperty()
  @IsString()
  paymentIntentId!: string;

  @ApiProperty()
  @IsString()
  paymentMethodId!: string;
}

export class RefundPaymentDto {
  @ApiProperty()
  @IsString()
  paymentIntentId!: string;

  @ApiPropertyOptional({ example: 50.0 })
  @IsOptional()
  @IsNumber()
  amount?: number;
}
