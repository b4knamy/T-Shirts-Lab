import type { PropsWithChildren, ReactElement } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { render } from '@testing-library/react';
import { CheckoutDraftProvider } from '../pages/checkout/draft_state';
import type { CheckoutDraftState } from '../pages/checkout/types';
import { createQueryClient } from '../services/queryClient';
import { CartProvider } from '../contexts/cart_context';
import { AuthProvider } from '../contexts/auth_context';
import { ProductProvider } from '../contexts/product_context';
import { useCart } from '../hooks/useCart';

type TestState = {
  cart: {
    items: any[];
    isOpen: boolean;
  };
};

export function renderRouteWithProviders(
  element: ReactElement,
  options: {
    initialDraftState?: Partial<CheckoutDraftState>;
    path: string;
    route: string;
    preloadedState?: Partial<TestState>;
  },
) {
  const queryClient = createQueryClient();
  const initialCartItems = options.preloadedState?.cart?.items;
  const initialCartIsOpen = options.preloadedState?.cart?.isOpen;

  let currentCartContext: any = null;

  function StateSpy() {
    currentCartContext = useCart();
    return null;
  }

  const store = {
    getState() {
      return {
        cart: {
          items: currentCartContext
            ? currentCartContext.items
            : (initialCartItems ?? []),
          isOpen: currentCartContext
            ? currentCartContext.isOpen
            : (initialCartIsOpen ?? false),
        },
      };
    },
    dispatch(action: any) {
      if (!currentCartContext) return;
      if (action.type === 'cart/updateQuantity') {
        currentCartContext.update(
          action.payload.productId,
          action.payload.quantity,
        );
      } else if (action.type === 'cart/addToCart') {
        currentCartContext.add(action.payload.product, action.payload.quantity);
      } else if (action.type === 'cart/removeFromCart') {
        currentCartContext.remove(action.payload);
      } else if (action.type === 'cart/clearCart') {
        currentCartContext.clear();
      } else if (action.type === 'cart/toggleCart') {
        currentCartContext.toggle();
      } else if (action.type === 'cart/setCartOpen') {
        currentCartContext.setOpen(action.payload);
      }
    },
  };

  function Wrapper({ children }: PropsWithChildren) {
    return (
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <CartProvider
            initialItems={initialCartItems}
            initialIsOpen={initialCartIsOpen}
          >
            <ProductProvider>
              <StateSpy />
              <CheckoutDraftProvider initialState={options.initialDraftState}>
                <MemoryRouter initialEntries={[options.route]}>
                  <Routes>
                    <Route path={options.path} element={children} />
                  </Routes>
                </MemoryRouter>
              </CheckoutDraftProvider>
            </ProductProvider>
          </CartProvider>
        </AuthProvider>
      </QueryClientProvider>
    );
  }

  return {
    store,
    queryClient,
    ...render(element, { wrapper: Wrapper }),
  };
}
