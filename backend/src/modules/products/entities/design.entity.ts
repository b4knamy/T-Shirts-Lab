import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Product } from './product.entity';
import { User } from '../../users/entities/user.entity';

@Entity('designs')
export class Design {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'product_id' })
  productId!: string;

  @ManyToOne(() => Product, (product) => product.designs, {
    onDelete: 'CASCADE',
  })
  @JoinColumn({ name: 'product_id' })
  product!: Product;

  @Column({ length: 100 })
  name!: string;

  @Column({ type: 'text', nullable: true })
  description?: string;

  @Column({ name: 'image_url', length: 255 })
  imageUrl!: string;

  @Column({ length: 50 })
  category!: string;

  @Column({ name: 'file_url', length: 255, nullable: true })
  fileUrl?: string;

  @Column({ name: 'is_approved', default: false })
  isApproved!: boolean;

  @Column({ name: 'created_by', nullable: true })
  createdBy?: string;

  @ManyToOne(() => User, { nullable: true })
  @JoinColumn({ name: 'created_by' })
  creator?: User;

  @CreateDateColumn({ name: 'created_at', type: 'timestamptz' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at', type: 'timestamptz' })
  updatedAt!: Date;
}
