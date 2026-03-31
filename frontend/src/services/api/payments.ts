import apiClient from './client';

export const paymentsApi = {
  createIntent: (order_id: string, currency?: string) =>
    apiClient.post<{
      data: { client_secret: string; payment_intent_id: string };
    }>('/api/v1/payments/create-intent', { order_id, currency }),

  confirmPayment: (payment_intent_id: string, payment_method_id: string) =>
    apiClient.post('/api/v1/payments/confirm', {
      payment_intent_id,
      payment_method_id,
    }),

  getStatus: (payment_intent_id: string) =>
    apiClient.get(`/api/v1/payments/${payment_intent_id}`),

  refund: (payment_intent_id: string, amount?: number) =>
    apiClient.post('/api/v1/payments/refund', { payment_intent_id, amount }),
};
