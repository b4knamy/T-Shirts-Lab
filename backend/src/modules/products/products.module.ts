import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ProductsService } from './products.service';
import { ProductsController } from './products.controller';
import { Product } from './entities/product.entity';
import { Category } from './entities/category.entity';
import { ProductImage } from './entities/product-image.entity';
import { Design } from './entities/design.entity';

@Module({
  imports: [
    TypeOrmModule.forFeature([Product, Category, ProductImage, Design]),
  ],
  controllers: [ProductsController],
  providers: [ProductsService],
  exports: [ProductsService],
})
export class ProductsModule {}
