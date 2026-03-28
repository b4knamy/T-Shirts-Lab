import apiClient from './client';
import type { Product, Category } from '../../types';

export interface ProductQueryParams {
  page?: number;
  limit?: number;
  search?: string;
  categoryId?: string;
  status?: string;
  sortBy?: string;
  minPrice?: number;
  maxPrice?: number;
}

export const productsApi = {
  getAll: (params?: ProductQueryParams) =>
    apiClient.get<{ data: { products: Product[]; total: number; page: number; limit: number } }>(
      '/api/v1/products',
      { params },
    ),

  getById: (id: string) =>
    apiClient.get<{ data: Product }>(`/api/v1/products/${id}`),

  getBySlug: (slug: string) =>
    apiClient.get<{ data: Product }>(`/api/v1/products/slug/${slug}`),

  getFeatured: (limit?: number) =>
    apiClient.get<{ data: Product[] }>('/api/v1/products/featured', {
      params: { limit },
    }),

  getCategories: () =>
    apiClient.get<{ data: Category[] }>('/api/v1/products/categories'),

  // Admin
  create: (data: Partial<Product>) =>
    apiClient.post<{ data: Product }>('/api/v1/products', data),

  update: (id: string, data: Partial<Product>) =>
    apiClient.patch<{ data: Product }>(`/api/v1/products/${id}`, data),

  delete: (id: string) => apiClient.delete(`/api/v1/products/${id}`),
};
