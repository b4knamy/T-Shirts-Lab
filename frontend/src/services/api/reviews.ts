import apiClient from './client';
import type { ProductReview, ReviewsResponse } from '../../types';

export const reviewsApi = {
  getProductReviews: (productId: string, page = 1) =>
    apiClient.get<{ data: ReviewsResponse }>(`/api/v1/products/${productId}/reviews`, {
      params: { page },
    }),

  createReview: (productId: string, data: { rating: number; comment?: string }) =>
    apiClient.post<{ data: ProductReview }>(`/api/v1/products/${productId}/reviews`, data),

  updateReview: (reviewId: string, data: { rating?: number; comment?: string }) =>
    apiClient.patch<{ data: ProductReview }>(`/api/v1/reviews/${reviewId}`, data),

  adminReply: (reviewId: string, admin_reply: string) =>
    apiClient.post<{ data: ProductReview }>(`/api/v1/reviews/${reviewId}/reply`, { admin_reply }),

  deleteReview: (reviewId: string) =>
    apiClient.delete(`/api/v1/reviews/${reviewId}`),
};
