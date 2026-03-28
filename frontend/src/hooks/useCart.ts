import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../store';
import {
  addToCart,
  removeFromCart,
  updateQuantity,
  clearCart,
  toggleCart,
  setCartOpen,
  selectCartItems,
  selectCartItemCount,
  selectCartTotal,
  selectIsCartOpen,
} from '../store/slices/cartSlice';
import type { Product } from '../types';

export function useCart() {
  const dispatch = useAppDispatch();
  const items = useAppSelector(selectCartItems);
  const itemCount = useAppSelector(selectCartItemCount);
  const total = useAppSelector(selectCartTotal);
  const isOpen = useAppSelector(selectIsCartOpen);

  const add = useCallback(
    (product: Product, quantity = 1) => {
      dispatch(addToCart({ product, quantity }));
    },
    [dispatch],
  );

  const remove = useCallback(
    (productId: string) => {
      dispatch(removeFromCart(productId));
    },
    [dispatch],
  );

  const update = useCallback(
    (productId: string, quantity: number) => {
      dispatch(updateQuantity({ productId, quantity }));
    },
    [dispatch],
  );

  const clear = useCallback(() => {
    dispatch(clearCart());
  }, [dispatch]);

  const toggle = useCallback(() => {
    dispatch(toggleCart());
  }, [dispatch]);

  const setOpen = useCallback(
    (open: boolean) => {
      dispatch(setCartOpen(open));
    },
    [dispatch],
  );

  return {
    items,
    itemCount,
    total,
    isOpen,
    add,
    remove,
    update,
    clear,
    toggle,
    setOpen,
  };
}
