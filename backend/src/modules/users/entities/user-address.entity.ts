import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { User } from './user.entity';
import { AddressType } from '../../../common/constants/enums';

@Entity('user_addresses')
export class UserAddress {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'user_id' })
  userId!: string;

  @ManyToOne(() => User)
  @JoinColumn({ name: 'user_id' })
  user!: User;

  @Column({ type: 'enum', enum: AddressType })
  type!: AddressType;

  @Column({ name: 'first_name', length: 100 })
  firstName!: string;

  @Column({ name: 'last_name', length: 100 })
  lastName!: string;

  @Column({ length: 20 })
  phone!: string;

  @Column({ length: 255 })
  email!: string;

  @Column({ name: 'street_address', length: 255 })
  streetAddress!: string;

  @Column({ name: 'street_address_2', length: 255, nullable: true })
  streetAddress2?: string;

  @Column({ length: 100 })
  city!: string;

  @Column({ name: 'state_province', length: 100 })
  stateProvince!: string;

  @Column({ name: 'postal_code', length: 20 })
  postalCode!: string;

  @Column({ name: 'country_code', length: 2 })
  countryCode!: string;

  @Column({ name: 'is_default', default: false })
  isDefault!: boolean;

  @CreateDateColumn({ name: 'created_at', type: 'timestamptz' })
  createdAt!: Date;
}
