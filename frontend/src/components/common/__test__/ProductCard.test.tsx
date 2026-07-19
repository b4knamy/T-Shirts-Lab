import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ProductCard } from '../ProductCard';
import { renderRouteWithProviders } from '../../../test/renderWithProviders';

const mockProduct = {
  id: 'p-1',
  name: 'Cool Shirt',
  slug: 'cool-shirt',
  price: 25.00,
  stock_quantity: 10,
  reserved_quantity: 2,
  is_featured: false,
  category: { id: 'c-1', name: 'Apparel' },
  images: [
    { id: 'img-1', image_url: 'http://example.com/shirt.jpg', is_primary: true, alt_text: 'Shirt primary' }
  ]
};

const addMock = vi.fn();
vi.mock('../../../hooks/useCart', () => ({
  useCart: () => ({
    add: addMock
  })
}));

describe('ProductCard', () => {
  it('renders product information correctly', () => {
    renderRouteWithProviders(<ProductCard product={mockProduct as any} />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('Apparel')).toBeInTheDocument();
    expect(screen.getByText('Cool Shirt')).toBeInTheDocument();
    expect(screen.getByText('$25.00')).toBeInTheDocument();
    
    const img = screen.getByRole('img', { name: 'Shirt primary' });
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('src', 'http://example.com/shirt.jpg');
  });

  it('renders fallback when product has no images', () => {
    const productNoImg = { ...mockProduct, images: [] };
    renderRouteWithProviders(<ProductCard product={productNoImg as any} />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('No Image')).toBeInTheDocument();
  });

  it('renders discount price and badge correctly', () => {
    const discountedProduct = {
      ...mockProduct,
      discount_price: 20.00,
      discount_percent: 20
    };
    renderRouteWithProviders(<ProductCard product={discountedProduct as any} />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('$20.00')).toBeInTheDocument();
    expect(screen.getByText('$25.00')).toHaveClass('line-through');
    expect(screen.getByText('-20%')).toBeInTheDocument();
  });

  it('renders featured badge correctly', () => {
    const featuredProduct = {
      ...mockProduct,
      is_featured: true
    };
    renderRouteWithProviders(<ProductCard product={featuredProduct as any} />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('Featured')).toBeInTheDocument();
  });

  it('disables add to cart button if stock is not available', () => {
    const outOfStockProduct = {
      ...mockProduct,
      stock_quantity: 5,
      reserved_quantity: 5
    };
    renderRouteWithProviders(<ProductCard product={outOfStockProduct as any} />, {
      path: '*',
      route: '/'
    });

    const button = screen.getByRole('button', { name: 'Add to cart' });
    expect(button).toBeDisabled();
  });

  it('triggers useCart add function when add to cart button clicked', async () => {
    renderRouteWithProviders(<ProductCard product={mockProduct as any} />, {
      path: '*',
      route: '/'
    });

    const button = screen.getByRole('button', { name: 'Add to cart' });
    await userEvent.click(button);

    expect(addMock).toHaveBeenCalledWith(mockProduct);
  });
});
