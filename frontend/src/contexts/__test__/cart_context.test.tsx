import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, beforeEach } from 'vitest';
import { CartProvider, loadCartFromStorage, saveCartToStorage } from '../cart_context';
import { useCart } from '../../hooks/useCart';
import type { PropsWithChildren } from 'react';

describe('CartContext & Storage Utilities', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe('loadCartFromStorage & saveCartToStorage', () => {
    it('should return empty array if no storage exists', () => {
      expect(loadCartFromStorage()).toEqual([]);
    });

    it('should load cart items successfully from storage', () => {
      const items = [{ product: { id: 'p-1', price: 10, name: 'Product 1' } as any, quantity: 2 }];
      saveCartToStorage(items);
      expect(loadCartFromStorage()).toEqual(items);
    });

    it('should handle JSON parse errors gracefully by returning empty array', () => {
      localStorage.setItem('cart', 'invalid-json-{');
      expect(loadCartFromStorage()).toEqual([]);
    });
  });

  describe('CartProvider integration', () => {
    it('should load initial items from localStorage if initialItems not provided', () => {
      const items = [{ product: { id: 'p-2', price: 12, name: 'Product 2' } as any, quantity: 1 }];
      saveCartToStorage(items);

      const wrapper = ({ children }: PropsWithChildren) => (
        <CartProvider>{children}</CartProvider>
      );
      const { result } = renderHook(() => useCart(), { wrapper });

      expect(result.current.items).toEqual(items);
    });

    it('should save to localStorage when items update if initialItems not provided', () => {
      const wrapper = ({ children }: PropsWithChildren) => (
        <CartProvider>{children}</CartProvider>
      );
      const { result } = renderHook(() => useCart(), { wrapper });

      const newProduct = { id: 'p-3', price: 15, name: 'Product 3' } as any;
      act(() => {
        result.current.add(newProduct, 1);
      });

      const stored = JSON.parse(localStorage.getItem('cart') || '[]');
      expect(stored).toHaveLength(1);
      expect(stored[0].product.id).toBe('p-3');
    });

    it('should update cart item quantity and not let it go below 1', () => {
      const wrapper = ({ children }: PropsWithChildren) => (
        <CartProvider initialItems={[{ product: { id: 'p-1', price: 10 } as any, quantity: 2 }]}>
          {children}
        </CartProvider>
      );
      const { result } = renderHook(() => useCart(), { wrapper });

      act(() => {
        result.current.update('p-1', 0); // should clamp to 1
      });
      expect(result.current.items[0].quantity).toBe(1);

      act(() => {
        result.current.update('p-1', -5); // should clamp to 1
      });
      expect(result.current.items[0].quantity).toBe(1);
    });

    it('should clear all items in the cart', () => {
      const wrapper = ({ children }: PropsWithChildren) => (
        <CartProvider initialItems={[{ product: { id: 'p-1', price: 10 } as any, quantity: 2 }]}>
          {children}
        </CartProvider>
      );
      const { result } = renderHook(() => useCart(), { wrapper });

      expect(result.current.items).toHaveLength(1);

      act(() => {
        result.current.clear();
      });

      expect(result.current.items).toHaveLength(0);
      expect(result.current.itemCount).toBe(0);
      expect(result.current.total).toBe(0);
    });
  });
});
