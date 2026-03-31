import apiClient from './client';
import type { Coupon } from '../../types';

export const couponsApi = {
  /** Get publicly visible active promo coupons (no auth required) */
  getActivePromos: () =>
    apiClient.get<{ data: Coupon[] }>('/api/v1/coupons/active'),

  /** Validate a coupon code against a subtotal (requires auth) */
  validate: (code: string, subtotal: number) =>
    apiClient.post<{ data: { coupon: Coupon; discount: number } }>(
      '/api/v1/coupons/validate',
      { code, subtotal }
    ),
};
