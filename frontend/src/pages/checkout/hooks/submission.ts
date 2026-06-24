import { useState } from 'react';
import { ordersApi, paymentsApi } from '../../../services/api';
import type { CheckoutFormData, UseCheckoutSubmissionOptions } from '../types';
import { resolveErrorMessage } from '../utils';

export function useCheckoutSubmission({
  cancelledOrderId,
  couponCode,
  selectedItems,
}: UseCheckoutSubmissionOptions) {
  const [error, setError] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [isRedirecting, setIsRedirecting] = useState(false);

  const createCheckoutUrl = async (orderId: string): Promise<string> => {
    const checkoutResponse = await paymentsApi.createIntent(orderId, 'brl');
    const checkoutUrl = checkoutResponse.data.data.checkoutUrl;

    if (!checkoutUrl) {
      throw new Error('Checkout URL is missing');
    }

    return checkoutUrl;
  };

  const redirectToCheckout = (checkoutUrl: string) => {
    setIsRedirecting(true);
    window.location.href = checkoutUrl;
  };

  const handleRetryCheckout = async () => {
    if (!cancelledOrderId) {
      return;
    }

    setIsProcessing(true);
    setError(null);

    try {
      const checkoutUrl = await createCheckoutUrl(cancelledOrderId);
      redirectToCheckout(checkoutUrl);
    } catch (err: unknown) {
      setError(resolveErrorMessage(err));
    } finally {
      setIsProcessing(false);
    }
  };

  const onSubmit = async (data: CheckoutFormData) => {
    if (selectedItems.length === 0) {
      return;
    }

    setIsProcessing(true);
    setError(null);

    try {
      const orderResponse = await ordersApi.create({
        items: selectedItems.map((item) => ({
          product_id: item.product.id,
          quantity: item.checkoutQuantity,
          design_id: item.design_id,
          customization_data: item.customization_data,
        })),
        customer_notes: data.customerNotes,
        ...(couponCode ? { coupon_code: couponCode } : {}),
      });

      const order = orderResponse.data.data;
      const checkoutUrl = await createCheckoutUrl(order.id);

      redirectToCheckout(checkoutUrl);
    } catch (err: unknown) {
      setError(resolveErrorMessage(err));
    } finally {
      setIsProcessing(false);
    }
  };

  return {
    error,
    handleRetryCheckout,
    isProcessing,
    isRedirecting,
    onSubmit,
  };
}
