import type { PropsWithChildren, ReactElement } from 'react';
import { configureStore } from '@reduxjs/toolkit';
import { QueryClientProvider } from '@tanstack/react-query';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { render } from '@testing-library/react';
import { createQueryClient } from '../services/queryClient';
import cartReducer from '../store/slices/cartSlice';
import checkoutReducer from '../store/slices/checkoutSlice';

type TestState = {
  cart: ReturnType<typeof cartReducer>;
  checkout: ReturnType<typeof checkoutReducer>;
};

export function createTestStore(preloadedState?: Partial<TestState>) {
  return configureStore({
    reducer: {
      cart: cartReducer,
      checkout: checkoutReducer,
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
          <MemoryRouter initialEntries={[options.route]}>
            <Routes>
              <Route path={options.path} element={children} />
            </Routes>
          </MemoryRouter>
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