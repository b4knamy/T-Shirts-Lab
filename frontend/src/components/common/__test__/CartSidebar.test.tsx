import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { CartSidebar } from '../CartSidebar';
import { renderRouteWithProviders } from '../../../test/renderWithProviders';

const setOpenMock = vi.fn();
const removeMock = vi.fn();
const updateMock = vi.fn();

const mockCart = {
  items: [] as any[],
  itemCount: 0,
  total: 0.00,
  isOpen: true,
  setOpen: setOpenMock,
  remove: removeMock,
  update: updateMock
};

vi.mock('../../../hooks/useCart', () => ({
  useCart: () => mockCart
}));

describe('CartSidebar', () => {
  beforeEach(() => {
    setOpenMock.mockReset();
    removeMock.mockReset();
    updateMock.mockReset();
  });

  it('does not render when isOpen is false', () => {
    mockCart.isOpen = false;
    const { container } = renderRouteWithProviders(<CartSidebar />, {
      path: '*',
      route: '/'
    });
    expect(container.firstChild).toBeNull();
  });

  it('renders empty cart message when items is empty', () => {
    mockCart.isOpen = true;
    mockCart.items = [];
    mockCart.itemCount = 0;
    mockCart.total = 0.00;

    renderRouteWithProviders(<CartSidebar />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('Your cart is empty')).toBeInTheDocument();
    expect(screen.getByText('Browse Products')).toBeInTheDocument();
  });

  it('renders cart items, quantities and totals', () => {
    mockCart.isOpen = true;
    mockCart.items = [
      {
        product: {
          id: 'p-1',
          name: 'Cool Shirt',
          price: 25.00,
          images: [{ image_url: 'http://example.com/shirt.jpg' }]
        } as any,
        quantity: 2
      }
    ];
    mockCart.itemCount = 2;
    mockCart.total = 50.00;

    renderRouteWithProviders(<CartSidebar />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('Cart (2)')).toBeInTheDocument();
    expect(screen.getByText('Cool Shirt')).toBeInTheDocument();
    expect(screen.getByText('$25.00')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('Total')).toBeInTheDocument();
    expect(screen.getByText('$50.00')).toBeInTheDocument();
  });

  it('triggers setOpen(false) when close button is clicked', async () => {
    mockCart.isOpen = true;
    mockCart.items = [];
    mockCart.itemCount = 0;
    mockCart.total = 0.00;

    renderRouteWithProviders(<CartSidebar />, {
      path: '*',
      route: '/'
    });

    // Close button has the X icon inside and class "hover:bg-gray-100" or similar
    const closeButton = screen.getAllByRole('button')[0];
    await userEvent.click(closeButton);
    expect(setOpenMock).toHaveBeenCalledWith(false);
  });

  it('triggers update and remove callbacks', async () => {
    mockCart.isOpen = true;
    mockCart.items = [
      {
        product: {
          id: 'p-1',
          name: 'Cool Shirt',
          price: 25.00,
          images: [{ image_url: 'http://example.com/shirt.jpg' }]
        } as any,
        quantity: 2
      }
    ];
    mockCart.itemCount = 2;
    mockCart.total = 50.00;

    const { container } = renderRouteWithProviders(<CartSidebar />, {
      path: '*',
      route: '/'
    });

    // Find Minus, Plus and Trash buttons inside list item
    const buttons = screen.getAllByRole('button');
    // buttons[0] is X close button
    // buttons[1] is Minus button
    // buttons[2] is Plus button
    // buttons[3] is Trash button (aria-label="Remove item")
    
    await userEvent.click(buttons[2]); // Plus
    expect(updateMock).toHaveBeenCalledWith('p-1', 3);

    await userEvent.click(buttons[1]); // Minus
    expect(updateMock).toHaveBeenCalledWith('p-1', 1);

    await userEvent.click(screen.getByLabelText('Remove item'));
    expect(removeMock).toHaveBeenCalledWith('p-1');
  });
});
