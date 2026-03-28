import {
  IsString,
  IsNumber,
  IsOptional,
  IsUUID,
  IsArray,
  ValidateNested,
  Min,
} from 'class-validator';
import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Type } from 'class-transformer';

export class CreateOrderItemDto {
  @ApiProperty()
  @IsUUID()
  productId!: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  designId?: string;

  @ApiProperty({ example: 2 })
  @IsNumber()
  @Min(1)
  quantity!: number;
}

export class CreateOrderDto {
  @ApiProperty({ type: [CreateOrderItemDto] })
  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => CreateOrderItemDto)
  items!: CreateOrderItemDto[];

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  shippingAddressId?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  billingAddressId?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  customerNotes?: string;
}

export class UpdateOrderStatusDto {
  @ApiProperty({ example: 'CONFIRMED' })
  @IsString()
  status!: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  adminNotes?: string;
}
