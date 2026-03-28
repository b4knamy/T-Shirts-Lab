import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository, FindOptionsWhere, ILike, Between } from 'typeorm';
import { Product } from './entities/product.entity';
import { Category } from './entities/category.entity';
import { CreateProductDto, UpdateProductDto, ProductQueryDto } from './dto';
import { ProductStatus } from '../../common/constants/enums';

@Injectable()
export class ProductsService {
  constructor(
    @InjectRepository(Product)
    private readonly productRepository: Repository<Product>,
    @InjectRepository(Category)
    private readonly categoryRepository: Repository<Category>,
  ) {}

  async create(createProductDto: CreateProductDto): Promise<Product> {
    const product = this.productRepository.create(createProductDto);
    return this.productRepository.save(product);
  }

  async findAll(query: ProductQueryDto): Promise<{
    products: Product[];
    total: number;
    page: number;
    limit: number;
  }> {
    const page = query.page || 1;
    const limit = query.limit || 20;

    const where: FindOptionsWhere<Product> = {};

    if (query.status) {
      where.status = query.status;
    } else {
      where.status = ProductStatus.ACTIVE;
    }

    if (query.categoryId) {
      where.categoryId = query.categoryId;
    }

    if (query.search) {
      where.name = ILike(`%${query.search}%`);
    }

    const [products, total] = await this.productRepository.findAndCount({
      where,
      relations: ['category', 'images'],
      skip: (page - 1) * limit,
      take: limit,
      order: { createdAt: 'DESC' },
    });

    return { products, total, page, limit };
  }

  async findOne(id: string): Promise<Product> {
    const product = await this.productRepository.findOne({
      where: { id },
      relations: ['category', 'images', 'designs'],
    });

    if (!product) {
      throw new NotFoundException(`Product with ID ${id} not found`);
    }

    return product;
  }

  async findBySlug(slug: string): Promise<Product> {
    const product = await this.productRepository.findOne({
      where: { slug },
      relations: ['category', 'images', 'designs'],
    });

    if (!product) {
      throw new NotFoundException(`Product with slug ${slug} not found`);
    }

    return product;
  }

  async update(
    id: string,
    updateProductDto: UpdateProductDto,
  ): Promise<Product> {
    const product = await this.findOne(id);
    Object.assign(product, updateProductDto);
    return this.productRepository.save(product);
  }

  async remove(id: string): Promise<void> {
    const product = await this.findOne(id);
    product.status = ProductStatus.INACTIVE;
    await this.productRepository.save(product);
  }

  // Categories
  async findAllCategories(): Promise<Category[]> {
    return this.categoryRepository.find({
      where: { isActive: true },
      order: { sortOrder: 'ASC' },
    });
  }

  async createCategory(data: Partial<Category>): Promise<Category> {
    const category = this.categoryRepository.create(data);
    return this.categoryRepository.save(category);
  }

  async getFeaturedProducts(limit = 10): Promise<Product[]> {
    return this.productRepository.find({
      where: { isFeatured: true, status: ProductStatus.ACTIVE },
      relations: ['category', 'images'],
      take: limit,
      order: { createdAt: 'DESC' },
    });
  }
}
