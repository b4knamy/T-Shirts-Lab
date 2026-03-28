import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToOne,
  JoinColumn,
  Index,
} from 'typeorm';
import { PaymentStatus } from '../../../common/constants/enums';
import { Order } from '../../orders/entities/order.entity';

@Entity('payments')
export class Payment {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'order_id', unique: true })
  orderId!: string;

  @OneToOne(() => Order)
  @JoinColumn({ name: 'order_id' })
  order!: Order;

  @Column({ name: 'payment_method', length: 50 })
  paymentMethod!: string;

  @Index()
  @Column({
    name: 'stripe_payment_intent_id',
    length: 255,
    nullable: true,
  })
  stripePaymentIntentId?: string;

  @Column({ type: 'decimal', precision: 10, scale: 2 })
  amount!: number;

  @Column({ length: 3, default: 'USD' })
  currency!: string;

  @Column({
    type: 'enum',
    enum: PaymentStatus,
    default: PaymentStatus.PENDING,
  })
  status!: PaymentStatus;

  @Column({ name: 'transaction_id', length: 255, nullable: true })
  transactionId?: string;

  @Column({ name: 'error_message', type: 'text', nullable: true })
  errorMessage?: string;

  @CreateDateColumn({ name: 'created_at', type: 'timestamptz' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at', type: 'timestamptz' })
  updatedAt!: Date;

  @Column({ name: 'completed_at', type: 'timestamptz', nullable: true })
  completedAt?: Date;
}
