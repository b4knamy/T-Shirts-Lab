import apiClient from './client';

export const paymentsApi = {
  createIntent: (orderId: string, currency?: string) =>
    apiClient.post<{
      data: { clientSecret: string; paymentIntentId: string };
    }>('/api/v1/payments/create-intent', { orderId, currency }),

  confirmPayment: (paymentIntentId: string, paymentMethodId: string) =>
    apiClient.post('/api/v1/payments/confirm', {
      paymentIntentId,
      paymentMethodId,
    }),

  getStatus: (paymentIntentId: string) =>
    apiClient.get(`/api/v1/payments/${paymentIntentId}`),

  refund: (paymentIntentId: string, amount?: number) =>
    apiClient.post('/api/v1/payments/refund', { paymentIntentId, amount }),
};
