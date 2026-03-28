import {
  IsString,
  IsNumber,
  IsOptional,
  IsBoolean,
  IsEnum,
  IsUUID,
  Min,
  MaxLength,
} from 'class-validator';
import { ApiProperty, ApiPropertyOptional, PartialType } from '@nestjs/swagger';
import { ProductStatus } from '../../../common/constants/enums';
import { Type } from 'class-transformer';

export class CreateProductDto {
  @ApiProperty({ example: 'Camiseta Naruto Shippuden' })
  @IsString()
  @MaxLength(255)
  name!: string;

  @ApiProperty({ example: 'naruto-shippuden' })
  @IsString()
  @MaxLength(255)
  slug!: string;

  @ApiProperty({ example: 'NAR-001' })
  @IsString()
  @MaxLength(100)
  sku!: string;

  @ApiProperty({ example: 'Camiseta com estampa oficial do Naruto' })
  @IsString()
  description!: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  longDescription?: string;

  @ApiProperty({ example: '550e8400-e29b-41d4-a716-446655440000' })
  @IsUUID()
  categoryId!: string;

  @ApiProperty({ example: 79.9 })
  @IsNumber()
  @Min(0)
  @Type(() => Number)
  price!: number;

  @ApiPropertyOptional({ example: 40.0 })
  @IsOptional()
  @IsNumber()
  @Min(0)
  @Type(() => Number)
  costPrice?: number;

  @ApiProperty({ example: 100 })
  @IsNumber()
  @Min(0)
  @Type(() => Number)
  stockQuantity!: number;

  @ApiPropertyOptional({ example: 'M' })
  @IsOptional()
  @IsString()
  size?: string;

  @ApiPropertyOptional({ example: 'Black' })
  @IsOptional()
  @IsString()
  color?: string;

  @ApiPropertyOptional({ example: false })
  @IsOptional()
  @IsBoolean()
  isFeatured?: boolean;
}

export class UpdateProductDto extends PartialType(CreateProductDto) {}

export class ProductQueryDto {
  @ApiPropertyOptional({ example: 1 })
  @IsOptional()
  @IsNumber()
  @Min(1)
  @Type(() => Number)
  page?: number = 1;

  @ApiPropertyOptional({ example: 20 })
  @IsOptional()
  @IsNumber()
  @Min(1)
  @Type(() => Number)
  limit?: number = 20;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  search?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsUUID()
  categoryId?: string;

  @ApiPropertyOptional({ enum: ProductStatus })
  @IsOptional()
  @IsEnum(ProductStatus)
  status?: ProductStatus;

  @ApiPropertyOptional()
  @IsOptional()
  @IsNumber()
  @Type(() => Number)
  minPrice?: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsNumber()
  @Type(() => Number)
  maxPrice?: number;
}
