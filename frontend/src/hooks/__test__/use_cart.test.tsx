import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it } from 'vitest';
import { useCart } from '../useCart';
import {
  createHookWrapper,
  createProduct,
} from '../../pages/checkout/hooks/__test__/test_utils';

describe('useCart', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('provides initial state and actions', () => {
    const { wrapper } = createHookWrapper({
      preloadedState: { cart: { items: [], isOpen: false } },
    });
    const { result } = renderHook(() => useCart(), { wrapper });

    expect(result.current.items).toEqual([]);
    expect(result.current.itemCount).toBe(0);
    expect(result.current.total).toBe(0);
    expect(result.current.isOpen).toBe(false);
  });

  it('adds item to the cart', () => {
    const { wrapper } = createHookWrapper({
      preloadedState: { cart: { items: [], isOpen: false } },
    });
    const { result } = renderHook(() => useCart(), { wrapper });

    const p = createProduct({ id: 'p-1', price: 15 });

    act(() => {
      result.current.add(p, 2);
    });

    expect(result.current.items).toHaveLength(1);
    expect(result.current.items[0].product.id).toBe('p-1');
    expect(result.current.items[0].quantity).toBe(2);
    expect(result.current.itemCount).toBe(2);
    expect(result.current.total).toBe(30);
  });

  it('removes item from the cart', () => {
    const p = createProduct({ id: 'p-1', price: 10 });
    const { wrapper } = createHookWrapper({
      preloadedState: {
        cart: {
          items: [{ product: p, quantity: 3 }],
          isOpen: false,
        },
      },
    });
    const { result } = renderHook(() => useCart(), { wrapper });

    expect(result.current.items).toHaveLength(1);

    act(() => {
      result.current.remove('p-1');
    });

    expect(result.current.items).toHaveLength(0);
    expect(result.current.itemCount).toBe(0);
  });

  it('updates product quantity', () => {
    const p = createProduct({ id: 'p-1', price: 10 });
    const { wrapper } = createHookWrapper({
      preloadedState: {
        cart: {
          items: [{ product: p, quantity: 1 }],
          isOpen: false,
        },
      },
    });
    const { result } = renderHook(() => useCart(), { wrapper });

    act(() => {
      result.current.update('p-1', 4);
    });

    expect(result.current.items[0].quantity).toBe(4);
    expect(result.current.itemCount).toBe(4);
  });

  it('toggles cart visibility', () => {
    const { wrapper } = createHookWrapper();
    const { result } = renderHook(() => useCart(), { wrapper });

    expect(result.current.isOpen).toBe(false);

    act(() => {
      result.current.toggle();
    });
    expect(result.current.isOpen).toBe(true);

    act(() => {
      result.current.setOpen(false);
    });
    expect(result.current.isOpen).toBe(false);
  });
});
