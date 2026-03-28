import apiClient from './client';
import type { Order } from '../../types';

export interface CreateOrderData {
  items: {
    productId: string;
    designId?: string;
    quantity: number;
  }[];
  shippingAddressId?: string;
  billingAddressId?: string;
  customerNotes?: string;
}

export const ordersApi = {
  create: (data: CreateOrderData) =>
    apiClient.post<{ data: Order }>('/api/v1/orders', data),

  getMyOrders: (page?: number, limit?: number) =>
    apiClient.get<{ data: { orders: Order[]; total: number } }>(
      '/api/v1/orders/my-orders',
      { params: { page, limit } },
    ),

  getById: (id: string) =>
    apiClient.get<{ data: Order }>(`/api/v1/orders/${id}`),

  // Admin
  getAll: (page?: number, limit?: number) =>
    apiClient.get('/api/v1/orders', { params: { page, limit } }),

  updateStatus: (id: string, status: string, adminNotes?: string) =>
    apiClient.patch(`/api/v1/orders/${id}/status`, { status, adminNotes }),
};
