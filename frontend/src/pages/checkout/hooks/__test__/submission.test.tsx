import { act, renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCheckoutSubmission } from '../submission';
import {
  createCheckoutFormData,
  createHookWrapper,
  createSelectedCheckoutItem,
} from './test_utils';

const {
  ordersCreateMock,
  paymentsCreateIntentMock,
} = vi.hoisted(() => ({
  ordersCreateMock: vi.fn(),
  paymentsCreateIntentMock: vi.fn(),
}));

vi.mock('../../../../services/api', () => ({
  ordersApi: {
    create: ordersCreateMock,
  },
  paymentsApi: {
    createIntent: paymentsCreateIntentMock,
  },
}));

const originalConsoleError = console.error;

describe('useCheckoutSubmission', () => {
  beforeEach(() => {
    ordersCreateMock.mockReset();
    paymentsCreateIntentMock.mockReset();

    vi.spyOn(console, 'error').mockImplementation((...args) => {
      const [firstArg] = args;

      if (typeof firstArg === 'string' && firstArg.includes('Not implemented: navigation (except hash changes)')) {
        return;
      }

      originalConsoleError(...args);
    });
  });

  it('retries a cancelled order and enters the redirecting state', async () => {
    paymentsCreateIntentMock.mockResolvedValue({
      data: {
        data: {
          checkoutUrl: 'https://checkout.stripe.com/c/pay_retry',
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(
      () => useCheckoutSubmission({
        cancelledOrderId: 'order-123',
        couponCode: undefined,
        selectedItems: [createSelectedCheckoutItem()],
      }),
      { wrapper },
    );

    await act(async () => {
      await result.current.handleRetryCheckout();
    });

    await waitFor(() => {
      expect(paymentsCreateIntentMock).toHaveBeenCalledWith('order-123', 'brl');
    });

    expect(result.current.isRedirecting).toBe(true);
    expect(result.current.error).toBeNull();
  });

  it('submits the selected items and redirects using the created order id', async () => {
    ordersCreateMock.mockResolvedValue({
      data: {
        data: {
          id: 'order-999',
        },
      },
    });
    paymentsCreateIntentMock.mockResolvedValue({
      data: {
        data: {
          checkoutUrl: 'https://checkout.stripe.com/c/pay_submit',
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(
      () => useCheckoutSubmission({
        cancelledOrderId: null,
        couponCode: 'SAVE10',
        selectedItems: [createSelectedCheckoutItem()],
      }),
      { wrapper },
    );

    await act(async () => {
      await result.current.onSubmit(createCheckoutFormData());
    });

    await waitFor(() => {
      expect(ordersCreateMock).toHaveBeenCalledWith({
        items: [{
          product_id: 'product-1',
          quantity: 2,
          design_id: undefined,
          customization_data: undefined,
        }],
        customer_notes: 'Leave at the front desk',
        coupon_code: 'SAVE10',
      });
    });

    expect(paymentsCreateIntentMock).toHaveBeenCalledWith('order-999', 'brl');
    expect(result.current.isRedirecting).toBe(true);
    expect(result.current.error).toBeNull();
  });

  it('exposes the error when submission fails', async () => {
    ordersCreateMock.mockRejectedValue({
      response: {
        data: {
          message: 'Could not place order',
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(
      () => useCheckoutSubmission({
        cancelledOrderId: null,
        couponCode: undefined,
        selectedItems: [createSelectedCheckoutItem()],
      }),
      { wrapper },
    );

    await act(async () => {
      await result.current.onSubmit(createCheckoutFormData());
    });

    await waitFor(() => {
      expect(result.current.error).toBe('Could not place order');
    });

    expect(paymentsCreateIntentMock).not.toHaveBeenCalled();
    expect(result.current.isRedirecting).toBe(false);
  });
});