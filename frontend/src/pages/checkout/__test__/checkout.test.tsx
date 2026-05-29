import { act, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { updateQuantity } from '../../../store/slices/cartSlice';
import { renderRouteWithProviders } from '../../../test/renderWithProviders';
import { CheckoutPage } from '../index';
import {
  createCheckoutDraftState,
  createCheckoutFormData,
  createCoupon,
  createPreloadedState,
} from './test_utils';

const {
  couponsValidateMock,
  ordersCreateMock,
  paymentsCreateIntentMock,
  toastErrorMock,
  toastSuccessMock,
} = vi.hoisted(() => ({
  couponsValidateMock: vi.fn(),
  ordersCreateMock: vi.fn(),
  paymentsCreateIntentMock: vi.fn(),
  toastErrorMock: vi.fn(),
  toastSuccessMock: vi.fn(),
}));

vi.mock('../../../services/api', () => ({
  ordersApi: {
    create: ordersCreateMock,
  },
  paymentsApi: {
    createIntent: paymentsCreateIntentMock,
  },
  couponsApi: {
    validate: couponsValidateMock,
  },
}));

vi.mock('react-hot-toast', () => ({
  toast: {
    error: toastErrorMock,
    success: toastSuccessMock,
  },
}));

function renderCheckoutPage(options?: {
  initialDraftState?: ReturnType<typeof createCheckoutDraftState>;
  route?: string;
  preloadedState?: ReturnType<typeof createPreloadedState>;
}) {
  return renderRouteWithProviders(<CheckoutPage />, {
    initialDraftState: options?.initialDraftState,
    path: '/checkout',
    route: options?.route ?? '/checkout',
    preloadedState: options?.preloadedState ?? createPreloadedState(),
  });
}

function getInput(name: string): HTMLInputElement {
  const input = document.querySelector(`input[name="${name}"]`);

  if (!(input instanceof HTMLInputElement)) {
    throw new Error(`Input not found for ${name}`);
  }

  return input;
}

function getTextarea(name: string): HTMLTextAreaElement {
  const textarea = document.querySelector(`textarea[name="${name}"]`);

  if (!(textarea instanceof HTMLTextAreaElement)) {
    throw new Error(`Textarea not found for ${name}`);
  }

  return textarea;
}

async function fillCheckoutForm(user: ReturnType<typeof userEvent.setup>) {
  const formData = createCheckoutFormData();

  await user.type(getInput('shippingAddress.street'), formData.shippingAddress.street);
  await user.type(getInput('shippingAddress.number'), formData.shippingAddress.number);
  await user.type(getInput('shippingAddress.neighborhood'), formData.shippingAddress.neighborhood);
  await user.type(getInput('shippingAddress.city'), formData.shippingAddress.city);
  await user.type(getInput('shippingAddress.state'), formData.shippingAddress.state);
  await user.type(getInput('shippingAddress.zipCode'), formData.shippingAddress.zipCode);

  if (formData.customerNotes) {
    await user.type(getTextarea('customerNotes'), formData.customerNotes);
  }
}

describe('checkout', () => {
  beforeEach(() => {
    couponsValidateMock.mockReset();
    ordersCreateMock.mockReset();
    paymentsCreateIntentMock.mockReset();
    toastErrorMock.mockReset();
    toastSuccessMock.mockReset();

    vi.spyOn(console, 'error').mockImplementation((...args) => {
      const [firstArg] = args;

      if (typeof firstArg === 'string' && firstArg.includes('Not implemented: navigation (except hash changes)')) {
        return;
      }
    });
  });

  it('shows the empty cart state when there are no cart items', () => {
    renderCheckoutPage({
      preloadedState: createPreloadedState({ cartItems: [] }),
    });

    expect(screen.getByText('Your cart is empty')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /browse products/i })).toBeInTheDocument();
  });

  it('shows the no items selected state and opens the cart when asked', async () => {
    const user = userEvent.setup();
    const view = renderCheckoutPage({
      initialDraftState: createCheckoutDraftState({
        checkoutItems: [],
        draftInitialized: true,
      }),
    });

    expect(screen.getByText('No items selected')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /choose cart items/i }));

    expect(view.store.getState().cart.isOpen).toBe(true);
  });

  it('shows cancelled checkout recovery and retries payment for the returned order', async () => {
    paymentsCreateIntentMock.mockResolvedValue({
      data: {
        data: {
          checkoutUrl: 'https://checkout.stripe.com/c/pay_test_123',
        },
      },
    });

    const user = userEvent.setup();
    renderCheckoutPage({
      route: '/checkout?checkout=cancelled&order_id=order-123',
    });

    expect(screen.getByText('Checkout cancelled')).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: /try payment again/i }));

    await waitFor(() => {
      expect(paymentsCreateIntentMock).toHaveBeenCalledWith('order-123', 'brl');
    });

    expect(await screen.findByText('Redirecting...')).toBeInTheDocument();
  });

  it('invalidates an applied coupon when the selected subtotal changes', async () => {
    couponsValidateMock.mockResolvedValue({
      data: {
        data: {
          coupon: createCoupon('SAVE10'),
          discount: 10,
        },
      },
    });

    const user = userEvent.setup();
    const view = renderCheckoutPage();

    await user.type(screen.getByPlaceholderText('Coupon code'), 'SAVE10');
    await user.click(screen.getByRole('button', { name: /apply/i }));

    expect(await screen.findByText('SAVE10')).toBeInTheDocument();

    act(() => {
      view.store.dispatch(updateQuantity({ productId: 'product-1', quantity: 1 }));
    });

    await waitFor(() => {
      expect(screen.getByText(/coupon removed because your checkout selection changed/i)).toBeInTheDocument();
    });

    expect(screen.queryByText(/applied/i)).not.toBeInTheDocument();
    expect(screen.getByDisplayValue('SAVE10')).toBeInTheDocument();
  });

  it('submits the selected items with coupon and notes, then redirects to Stripe', async () => {
    couponsValidateMock.mockResolvedValue({
      data: {
        data: {
          coupon: createCoupon('SAVE10'),
          discount: 10,
        },
      },
    });
    ordersCreateMock.mockResolvedValue({
      data: {
        data: {
          id: 'order-999',
        },
      },
    });
    paymentsCreateIntentMock.mockResolvedValue({
      data: {
        data: {
          checkoutUrl: 'https://checkout.stripe.com/c/pay_test_submit',
        },
      },
    });

    const user = userEvent.setup();
    renderCheckoutPage();

    await user.type(screen.getByPlaceholderText('Coupon code'), 'SAVE10');
    await user.click(screen.getByRole('button', { name: /apply/i }));
    await screen.findByText('SAVE10');

    await fillCheckoutForm(user);
    await user.click(screen.getByRole('button', { name: /place order/i }));

    await waitFor(() => {
      expect(ordersCreateMock).toHaveBeenCalledWith({
        items: [{
          product_id: 'product-1',
          quantity: 2,
          design_id: undefined,
          customization_data: undefined,
        }],
        customer_notes: 'Leave at the front desk',
        coupon_code: 'SAVE10',
      });
    });

    expect(paymentsCreateIntentMock).toHaveBeenCalledWith('order-999', 'brl');
    expect(await screen.findByText('Redirecting...')).toBeInTheDocument();
  });

  it('shows the submission error when order creation fails', async () => {
    ordersCreateMock.mockRejectedValue({
      response: {
        data: {
          message: 'Could not place order',
        },
      },
    });

    const user = userEvent.setup();
    renderCheckoutPage();

    await fillCheckoutForm(user);
    await user.click(screen.getByRole('button', { name: /place order/i }));

    expect(await screen.findByText('Could not place order')).toBeInTheDocument();
    expect(paymentsCreateIntentMock).not.toHaveBeenCalled();
  });
});