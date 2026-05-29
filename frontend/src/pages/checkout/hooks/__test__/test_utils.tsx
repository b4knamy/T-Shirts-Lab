import type { PropsWithChildren } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { createQueryClient } from '../../../../services/queryClient';
import { createTestStore } from '../../../../test/renderWithProviders';
export {
  createCartItem,
  createCheckoutFormData,
  createCoupon,
  createPreloadedState,
  createProduct,
  createSelectedCheckoutItem,
} from '../../__test__/test_utils';

type PreloadedState = Parameters<typeof createTestStore>[0];

export function createHookWrapper(options?: {
  path?: string;
  preloadedState?: PreloadedState;
  route?: string;
}) {
  const store = createTestStore(options?.preloadedState);
  const queryClient = createQueryClient();

  function Wrapper({ children }: PropsWithChildren) {
    return (
      <QueryClientProvider client={queryClient}>
        <Provider store={store}>
          <MemoryRouter initialEntries={[options?.route ?? '/checkout']}>
            <Routes>
              <Route path={options?.path ?? '/checkout'} element={<>{children}</>} />
            </Routes>
          </MemoryRouter>
        </Provider>
      </QueryClientProvider>
    );
  }

  return {
    queryClient,
    store,
    wrapper: Wrapper,
  };
}