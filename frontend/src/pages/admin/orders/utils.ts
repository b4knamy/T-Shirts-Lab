import type { Order } from '../../../types';
import { PAYMENT_CFG, STATUS_CFG } from './constants';

export function formatOrderDate(value: string) {
  return new Date(value).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

export function formatCurrency(value: number | string) {
  return `$${Number(value).toFixed(2)}`;
}

export function getOrderStatusConfig(status: Order['status']) {
  return STATUS_CFG[status] ?? STATUS_CFG['PENDING'];
}

export function getOrderPaymentConfig(status: Order['payment_status']) {
  return PAYMENT_CFG[status] ?? PAYMENT_CFG['PENDING'];
}
