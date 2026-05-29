import type { PropsWithChildren, ReactElement } from 'react';
import { configureStore } from '@reduxjs/toolkit';
import { QueryClientProvider } from '@tanstack/react-query';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { render } from '@testing-library/react';
import { CheckoutDraftProvider } from '../pages/checkout/draft_state';
import type { CheckoutDraftState } from '../pages/checkout/types';
import { createQueryClient } from '../services/queryClient';
import cartReducer from '../store/slices/cartSlice';

type TestState = {
  cart: ReturnType<typeof cartReducer>;
};

export function createTestStore(preloadedState?: Partial<TestState>) {
  return configureStore({
    reducer: {
      cart: cartReducer,
    },
    preloadedState: preloadedState as TestState | undefined,
    middleware: (getDefaultMiddleware) =>
      getDefaultMiddleware({
        serializableCheck: false,
      }),
  });
}

export function renderRouteWithProviders(
  element: ReactElement,
  options: {
    initialDraftState?: Partial<CheckoutDraftState>;
    path: string;
    route: string;
    preloadedState?: Partial<TestState>;
  },
) {
  const store = createTestStore(options.preloadedState);
  const queryClient = createQueryClient();

  function Wrapper({ children }: PropsWithChildren) {
    return (
      <QueryClientProvider client={queryClient}>
        <Provider store={store}>
          <CheckoutDraftProvider initialState={options.initialDraftState}>
            <MemoryRouter initialEntries={[options.route]}>
              <Routes>
                <Route path={options.path} element={children} />
              </Routes>
            </MemoryRouter>
          </CheckoutDraftProvider>
        </Provider>
      </QueryClientProvider>
    );
  }

  return {
    store,
    queryClient,
    ...render(element, { wrapper: Wrapper }),
  };
}