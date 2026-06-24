import { renderHook, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useCheckoutDraft } from '../draft';
import {
  createCartItem,
  createCheckoutDraftState,
  createHookWrapper,
  createPreloadedState,
  createProduct,
} from './test_utils';

describe('useCheckoutDraft', () => {
  it('derives selected items, subtotal, and shipping from the draft selection', () => {
    const cartItems = [
      createCartItem(2),
      createCartItem(1, {
        product: createProduct({
          id: 'product-2',
          name: 'Heavy Hoodie',
          price: 40,
          discount_price: undefined,
          slug: 'heavy-hoodie',
          sku: 'HOOD-001',
        }),
      }),
    ];

    const { wrapper } = createHookWrapper({
      initialDraftState: createCheckoutDraftState({
        cartItems,
        checkoutItems: [
          { productId: 'product-1', quantity: 1 },
          { productId: 'product-2', quantity: 3 },
        ],
        draftInitialized: true,
      }),
      preloadedState: createPreloadedState({ cartItems }),
    });

    const { result } = renderHook(() => useCheckoutDraft(cartItems, false), {
      wrapper,
    });

    expect(result.current.selectedItems).toHaveLength(2);
    expect(result.current.selectedItems[0].checkoutQuantity).toBe(1);
    expect(result.current.selectedItems[1].checkoutQuantity).toBe(1);
    expect(result.current.subtotal).toBe(58);
    expect(result.current.shipping).toBe(0);
    expect(result.current.isPreparingDraft).toBe(false);
  });

  it('initializes the checkout draft from cart items when needed', async () => {
    const cartItems = [createCartItem()];
    const { wrapper } = createHookWrapper({
      preloadedState: createPreloadedState({ cartItems }),
    });

    const { result } = renderHook(() => useCheckoutDraft(cartItems, false), {
      wrapper,
    });

    await waitFor(() => {
      expect(result.current.isPreparingDraft).toBe(false);
    });

    expect(result.current.selectedItems).toHaveLength(1);
    expect(result.current.selectedItems[0].checkoutQuantity).toBe(2);
    expect(result.current.subtotal).toBe(36);
  });

  it('does not initialize the draft while handling a cancelled checkout', () => {
    const cartItems = [createCartItem()];
    const { wrapper } = createHookWrapper({
      preloadedState: createPreloadedState({ cartItems }),
    });

    const { result } = renderHook(() => useCheckoutDraft(cartItems, true), {
      wrapper,
    });

    expect(result.current.isPreparingDraft).toBe(false);
    expect(result.current.selectedItems).toHaveLength(0);
    expect(result.current.subtotal).toBe(0);
  });
});
