import { useEffect } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store';
import {
  initializeDraftFromCart,
  selectCheckoutDraftInitialized,
  selectCheckoutDraftItems,
  syncDraftWithCart,
} from '../../../store/slices/checkoutSlice';
import type { CartItem } from '../../../types';
import { buildSelectedCheckoutItems } from '../utils';

export function useCheckoutDraft(cartItems: CartItem[], isCancelledCheckoutStatus: boolean) {
  const dispatch = useAppDispatch();
  const checkoutDraftItems = useAppSelector(selectCheckoutDraftItems);
  const isCheckoutDraftInitialized = useAppSelector(selectCheckoutDraftInitialized);

  useEffect(() => {
    dispatch(syncDraftWithCart(cartItems));

    if (!isCancelledCheckoutStatus) {
      dispatch(initializeDraftFromCart(cartItems));
    }
  }, [cartItems, dispatch, isCancelledCheckoutStatus]);

  const selectedItems = buildSelectedCheckoutItems(cartItems, checkoutDraftItems);
  const subtotal = selectedItems.reduce(
    (totalAmount, item) => totalAmount + Number(item.product.discount_price || item.product.price) * item.checkoutQuantity,
    0,
  );
  const shipping = subtotal >= 50 ? 0 : 9.99;
  const isPreparingDraft = !isCancelledCheckoutStatus && cartItems.length > 0 && !isCheckoutDraftInitialized;

  return {
    isPreparingDraft,
    selectedItems,
    shipping,
    subtotal,
  };
}