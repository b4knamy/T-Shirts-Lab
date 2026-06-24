import { useEffect } from 'react';
import type { CartItem } from '../../../types';
import {
  useCheckoutDraftInitialState,
  useCheckoutDraftState,
} from '../draft_state';
import { buildSelectedCheckoutItems } from '../utils';

export function useCheckoutDraft(
  cartItems: CartItem[],
  isCancelledCheckoutStatus: boolean,
) {
  const initialState = useCheckoutDraftInitialState();
  const [state, dispatch] = useCheckoutDraftState(initialState);

  useEffect(() => {
    dispatch({
      type: 'sync-with-cart',
      cartItems,
    });

    if (!isCancelledCheckoutStatus) {
      dispatch({
        type: 'initialize-from-cart',
        cartItems,
      });
    }
  }, [cartItems, isCancelledCheckoutStatus]);

  const selectedItems = buildSelectedCheckoutItems(cartItems, state.items);
  const subtotal = selectedItems.reduce(
    (totalAmount, item) =>
      totalAmount +
      Number(item.product.discount_price || item.product.price) *
        item.checkoutQuantity,
    0,
  );
  const shipping = subtotal >= 50 ? 0 : 9.99;
  const isPreparingDraft =
    !isCancelledCheckoutStatus &&
    cartItems.length > 0 &&
    !state.draftInitialized;

  return {
    isPreparingDraft,
    selectedItems,
    shipping,
    subtotal,
  };
}
