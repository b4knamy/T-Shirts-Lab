import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  OneToMany,
  JoinColumn,
  Index,
} from 'typeorm';
import { ProductStatus } from '../../../common/constants/enums';
import { Category } from './category.entity';
import { ProductImage } from './product-image.entity';
import { Design } from './design.entity';

@Entity('products')
export class Product {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Index({ unique: true })
  @Column({ length: 100, unique: true })
  sku!: string;

  @Column({ length: 255 })
  name!: string;

  @Index({ unique: true })
  @Column({ length: 255, unique: true })
  slug!: string;

  @Column({ type: 'text' })
  description!: string;

  @Column({ name: 'long_description', type: 'text', nullable: true })
  longDescription?: string;

  @Column({ name: 'category_id' })
  categoryId!: string;

  @ManyToOne(() => Category)
  @JoinColumn({ name: 'category_id' })
  category!: Category;

  // Pricing
  @Column({ type: 'decimal', precision: 10, scale: 2 })
  price!: number;

  @Column({
    name: 'cost_price',
    type: 'decimal',
    precision: 10,
    scale: 2,
    nullable: true,
  })
  costPrice?: number;

  @Column({
    name: 'discount_price',
    type: 'decimal',
    precision: 10,
    scale: 2,
    nullable: true,
  })
  discountPrice?: number;

  @Column({ name: 'discount_percent', nullable: true })
  discountPercent?: number;

  // Inventory
  @Column({ name: 'stock_quantity', default: 0 })
  stockQuantity!: number;

  @Column({ name: 'reserved_quantity', default: 0 })
  reservedQuantity!: number;

  // Status
  @Column({ type: 'enum', enum: ProductStatus, default: ProductStatus.ACTIVE })
  status!: ProductStatus;

  @Column({ name: 'is_featured', default: false })
  isFeatured!: boolean;

  // Metadata
  @Column({
    name: 'weight_kg',
    type: 'decimal',
    precision: 5,
    scale: 2,
    nullable: true,
  })
  weightKg?: number;

  @Column({ name: 'dimensions_cm', length: 50, nullable: true })
  dimensionsCm?: string;

  @Column({ length: 50, nullable: true })
  color?: string;

  @Column({ length: 10, nullable: true })
  size?: string;

  @OneToMany(() => ProductImage, (image) => image.product, { cascade: true })
  images!: ProductImage[];

  @OneToMany(() => Design, (design) => design.product, { cascade: true })
  designs!: Design[];

  @CreateDateColumn({ name: 'created_at', type: 'timestamptz' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at', type: 'timestamptz' })
  updatedAt!: Date;

  get availableQuantity(): number {
    return this.stockQuantity - this.reservedQuantity;
  }
}
