import { renderHook, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useCheckout } from '../checkout';
import { createCartItem, createHookWrapper, createPreloadedState } from './test_utils';

describe('useCheckout', () => {
  it('composes the checkout concerns and exposes the derived total', async () => {
    const cartItems = [createCartItem()];
    const { wrapper } = createHookWrapper({
      preloadedState: createPreloadedState({ cartItems }),
    });

    const { result } = renderHook(() => useCheckout(), { wrapper });

    await waitFor(() => {
      expect(result.current.draft.selectedItems).toHaveLength(1);
    });

    expect(result.current.cart.items).toHaveLength(1);
    expect(result.current.status.isCancelledCheckoutStatus).toBe(false);
    expect(result.current.form.getValues('shippingAddress.country')).toBe('BR');
    expect(result.current.draft.subtotal).toBe(36);
    expect(result.current.draft.shipping).toBe(9.99);
    expect(result.current.finalTotal).toBe(45.99);
    expect(result.current.submission.isProcessing).toBe(false);
    expect(result.current.coupon.discountAmount).toBe(0);
  });
});