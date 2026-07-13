import { CheckCircle2, Clock, RefreshCw, Truck, XCircle } from 'lucide-react';
import type { OrderPaymentConfig, OrderStatusConfig } from './types';

export const STATUS_CFG: Record<string, OrderStatusConfig> = {
  PENDING: {
    label: 'Pending',
    style: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    icon: Clock,
  },
  CONFIRMED: {
    label: 'Confirmed',
    style: 'bg-blue-50 text-blue-700 border-blue-200',
    icon: CheckCircle2,
  },
  PROCESSING: {
    label: 'Processing',
    style: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    icon: RefreshCw,
  },
  SHIPPED: {
    label: 'Shipped',
    style: 'bg-purple-50 text-purple-700 border-purple-200',
    icon: Truck,
  },
  DELIVERED: {
    label: 'Delivered',
    style: 'bg-green-50 text-green-700 border-green-200',
    icon: CheckCircle2,
  },
  CANCELLED: {
    label: 'Cancelled',
    style: 'bg-red-50 text-red-700 border-red-200',
    icon: XCircle,
  },
  REFUNDED: {
    label: 'Refunded',
    style: 'bg-gray-50 text-gray-700 border-gray-200',
    icon: RefreshCw,
  },
};

export const PAYMENT_CFG: Record<string, OrderPaymentConfig> = {
  PENDING: { label: 'Awaiting', style: 'text-yellow-600' },
  PROCESSING: { label: 'Processing', style: 'text-indigo-600' },
  COMPLETED: { label: 'Paid', style: 'text-green-600' },
  FAILED: { label: 'Failed', style: 'text-red-600' },
  REFUNDED: { label: 'Refunded', style: 'text-gray-600' },
};

export const TRANSITIONS: string[] = [
  'PENDING',
  'CONFIRMED',
  'PROCESSING',
  'SHIPPED',
  'DELIVERED',
  'CANCELLED',
];
