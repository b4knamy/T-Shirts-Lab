import apiClient from './client';
import type { Order } from '../../types';

export interface CreateOrderData {
  items: {
    product_id: string;
    design_id?: string;
    quantity: number;
  }[];
  shipping_address_id?: string;
  billing_address_id?: string;
  customer_notes?: string;
  coupon_code?: string;
}

export const ordersApi = {
  create: (data: CreateOrderData) =>
    apiClient.post<{ data: Order }>('/api/v1/orders', data),

  getMyOrders: (page?: number, limit?: number) =>
    apiClient.get<{ data: { data: Order[]; total: number } }>(
      '/api/v1/orders/my-orders',
      { params: { page, limit } },
    ),

  getById: (id: string) =>
    apiClient.get<{ data: Order }>(`/api/v1/orders/${id}`),

  // Admin
  getAll: (page?: number, limit?: number) =>
    apiClient.get('/api/v1/orders', { params: { page, limit } }),

  updateStatus: (id: string, status: string, admin_notes?: string) =>
    apiClient.patch(`/api/v1/orders/${id}/status`, { status, admin_notes }),
};
