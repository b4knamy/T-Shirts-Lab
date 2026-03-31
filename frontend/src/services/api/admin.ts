import apiClient from './client';
import type { Product, Order, Category } from '../../types';

/* ─── Products ──────────────────────────────────────────────────────────── */

export interface AdminProductPayload {
  name: string;
  description: string;
  long_description?: string;
  category_id: string;
  price: number;
  cost_price?: number;
  discount_price?: number;
  discount_percent?: number;
  stock_quantity: number;
  sku?: string;
  is_featured?: boolean;
  status?: string;
  color?: string;
  size?: string;
}

export const adminApi = {
  /* Products */
  getProducts: (params?: { page?: number; limit?: number; search?: string; status?: string; categoryId?: string }) =>
    apiClient.get<{
      success: boolean;
      data: { products: Product[]; total: number; page: number; limit: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/products', { params: { ...params, status: params?.status || undefined } }),

  getProduct: (id: string) =>
    apiClient.get<{ data: Product }>(`/api/v1/products/${id}`),

  createProduct: (data: AdminProductPayload) =>
    apiClient.post<{ data: Product }>('/api/v1/products', data),

  updateProduct: (id: string, data: Partial<AdminProductPayload>) =>
    apiClient.patch<{ data: Product }>(`/api/v1/products/${id}`, data),

  deleteProduct: (id: string) =>
    apiClient.delete(`/api/v1/products/${id}`),

  /* Categories */
  getCategories: () =>
    apiClient.get<{ data: Category[] }>('/api/v1/products/categories'),

  /* Orders */
  getOrders: (params?: { page?: number; limit?: number }) =>
    apiClient.get<{
      success: boolean;
      data: { orders: Order[]; total: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/orders', { params }),

  getOrder: (id: string) =>
    apiClient.get<{ data: Order }>(`/api/v1/orders/${id}`),

  updateOrderStatus: (id: string, status: string, admin_notes?: string) =>
    apiClient.patch(`/api/v1/orders/${id}/status`, { status, admin_notes }),
};
