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
import { OrderStatus, PaymentStatus } from '../../../common/constants/enums';
import { User } from '../../users/entities/user.entity';
import { OrderItem } from './order-item.entity';

@Entity('orders')
export class Order {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Index({ unique: true })
  @Column({ name: 'order_number', length: 50, unique: true })
  orderNumber!: string;

  @Column({ name: 'user_id' })
  userId!: string;

  @ManyToOne(() => User, (user) => user.orders)
  @JoinColumn({ name: 'user_id' })
  user!: User;

  // Pricing
  @Column({ type: 'decimal', precision: 10, scale: 2 })
  subtotal!: number;

  @Column({
    name: 'discount_amount',
    type: 'decimal',
    precision: 10,
    scale: 2,
    default: 0,
  })
  discountAmount!: number;

  @Column({
    name: 'tax_amount',
    type: 'decimal',
    precision: 10,
    scale: 2,
  })
  taxAmount!: number;

  @Column({
    name: 'shipping_cost',
    type: 'decimal',
    precision: 10,
    scale: 2,
  })
  shippingCost!: number;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  total!: number;

  // Status
  @Column({
    type: 'enum',
    enum: OrderStatus,
    default: OrderStatus.PENDING,
  })
  status!: OrderStatus;

  @Column({
    name: 'payment_status',
    type: 'enum',
    enum: PaymentStatus,
    default: PaymentStatus.PENDING,
  })
  paymentStatus!: PaymentStatus;

  // Shipping
  @Column({ name: 'shipping_address_id', nullable: true })
  shippingAddressId?: string;

  @Column({ name: 'billing_address_id', nullable: true })
  billingAddressId?: string;

  @Column({ name: 'tracking_number', length: 100, nullable: true })
  trackingNumber?: string;

  // Notes
  @Column({ name: 'customer_notes', type: 'text', nullable: true })
  customerNotes?: string;

  @Column({ name: 'admin_notes', type: 'text', nullable: true })
  adminNotes?: string;

  @OneToMany(() => OrderItem, (item) => item.order, { cascade: true })
  items!: OrderItem[];

  @CreateDateColumn({ name: 'created_at', type: 'timestamptz' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at', type: 'timestamptz' })
  updatedAt!: Date;

  @Column({ name: 'delivered_at', type: 'timestamptz', nullable: true })
  deliveredAt?: Date;
}
