import apiClient from './client';
import type { Product, Order, Category, Coupon } from '../../types';

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
      data: { data: Product[]; total: number; page: number; limit: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/products', { params: { ...params, status: params?.status || 'ALL' } }),

  getProduct: (id: string) =>
    apiClient.get<{ data: Product }>(`/api/v1/products/${id}`),

  createProduct: (data: AdminProductPayload) =>
    apiClient.post<{ data: Product }>('/api/v1/products', data),

  updateProduct: (id: string, data: Partial<AdminProductPayload>) =>
    apiClient.patch<{ data: Product }>(`/api/v1/products/${id}`, data),

  deleteProduct: (id: string) =>
    apiClient.delete(`/api/v1/products/${id}`),

  /* Product Images */
  getProductImages: (productId: string) =>
    apiClient.get<{ data: { id: string; image_url: string; alt_text: string; sort_order: number; is_primary: boolean }[] }>(
      `/api/v1/products/${productId}/images`
    ),

  addProductImage: (productId: string, data: { image_url: string; alt_text?: string; sort_order?: number; is_primary?: boolean }) =>
    apiClient.post(`/api/v1/products/${productId}/images`, data),

  uploadProductImage: (productId: string, formData: FormData) =>
    apiClient.post(`/api/v1/products/${productId}/images/upload`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  updateProductImage: (productId: string, imageId: string, data: { alt_text?: string; sort_order?: number; is_primary?: boolean }) =>
    apiClient.patch(`/api/v1/products/${productId}/images/${imageId}`, data),

  deleteProductImage: (productId: string, imageId: string) =>
    apiClient.delete(`/api/v1/products/${productId}/images/${imageId}`),

  /* Categories */
  getCategories: () =>
    apiClient.get<{ data: Category[] }>('/api/v1/products/categories'),

  getCategoriesPaginated: (params?: { page?: number; limit?: number; search?: string }) =>
    apiClient.get<{
      success: boolean;
      data: { data: Category[]; total: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/categories', { params }),

  createCategory: (data: { name: string; description?: string; image_url?: string; is_active?: boolean }) =>
    apiClient.post<{ data: Category }>('/api/v1/categories', data),

  updateCategory: (id: string, data: Partial<{ name: string; description: string; image_url: string; is_active: boolean }>) =>
    apiClient.patch<{ data: Category }>(`/api/v1/categories/${id}`, data),

  deleteCategory: (id: string) =>
    apiClient.delete(`/api/v1/categories/${id}`),

  /* Orders */
  getOrders: (params?: { page?: number; limit?: number }) =>
    apiClient.get<{
      success: boolean;
      data: { data: Order[]; total: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/orders', { params }),

  getOrder: (id: string) =>
    apiClient.get<{ data: Order }>(`/api/v1/orders/${id}`),

  updateOrderStatus: (id: string, status: string, admin_notes?: string) =>
    apiClient.patch(`/api/v1/orders/${id}/status`, { status, admin_notes }),

  /* Coupons */
  getCoupons: (params?: { page?: number; limit?: number; search?: string }) =>
    apiClient.get<{
      success: boolean;
      data: { data: Coupon[]; total: number };
      meta: { total: number; page: number; limit: number; total_pages: number };
    }>('/api/v1/coupons', { params }),

  getCoupon: (id: string) =>
    apiClient.get<{ data: Coupon }>(`/api/v1/coupons/${id}`),

  createCoupon: (data: Partial<Coupon>) =>
    apiClient.post<{ data: Coupon }>('/api/v1/coupons', data),

  updateCoupon: (id: string, data: Partial<Coupon>) =>
    apiClient.patch<{ data: Coupon }>(`/api/v1/coupons/${id}`, data),

  deleteCoupon: (id: string) =>
    apiClient.delete(`/api/v1/coupons/${id}`),
};
