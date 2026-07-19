import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { Header } from '../Header';
import { renderRouteWithProviders } from '../../../test/renderWithProviders';

const signOutMock = vi.fn();
const useAuthMock = vi.fn();

vi.mock('../../../hooks/useAuth', () => ({
  useAuth: () => useAuthMock()
}));

const toggleMock = vi.fn();
const useCartMock = vi.fn();

vi.mock('../../../hooks/useCart', () => ({
  useCart: () => useCartMock()
}));

const navigateMock = vi.fn();
vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return {
    ...actual,
    useNavigate: () => navigateMock
  };
});

describe('Header', () => {
  beforeEach(() => {
    signOutMock.mockReset();
    toggleMock.mockReset();
    navigateMock.mockReset();
  });

  it('renders links and handles search form submission', async () => {
    useAuthMock.mockReturnValue({
      user: null,
      isAuthenticated: false,
      signOut: signOutMock
    });
    useCartMock.mockReturnValue({
      itemCount: 0,
      toggle: toggleMock
    });

    renderRouteWithProviders(<Header />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('Products')).toBeInTheDocument();
    expect(screen.getByText('Men')).toBeInTheDocument();
    expect(screen.getByText('Women')).toBeInTheDocument();
    expect(screen.getByText('Kids')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Search t-shirts...');
    await userEvent.type(searchInput, 'V-Neck');
    await userEvent.keyboard('{Enter}');

    expect(navigateMock).toHaveBeenCalledWith('/products?search=V-Neck');
  });

  it('renders cart itemCount and handles click to toggle cart sidebar', async () => {
    useAuthMock.mockReturnValue({
      user: null,
      isAuthenticated: false,
      signOut: signOutMock
    });
    useCartMock.mockReturnValue({
      itemCount: 5,
      toggle: toggleMock
    });

    renderRouteWithProviders(<Header />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('5')).toBeInTheDocument();

    const cartBtn = screen.getByRole('button', { name: 'Cart' });
    await userEvent.click(cartBtn);

    expect(toggleMock).toHaveBeenCalled();
  });

  it('renders user info, admin link and handles signOut for ADMIN users', async () => {
    useAuthMock.mockReturnValue({
      user: { id: 'u-1', first_name: 'AdminBob', role: 'ADMIN' },
      isAuthenticated: true,
      signOut: signOutMock
    });
    useCartMock.mockReturnValue({
      itemCount: 0,
      toggle: toggleMock
    });

    renderRouteWithProviders(<Header />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('AdminBob')).toBeInTheDocument();
    
    const logoutBtn = screen.getByRole('button', { name: 'Logout' });
    await userEvent.click(logoutBtn);

    expect(signOutMock).toHaveBeenCalled();
  });

  it('does not render admin link for customer users', () => {
    useAuthMock.mockReturnValue({
      user: { id: 'u-2', first_name: 'CustomerJohn', role: 'CUSTOMER' },
      isAuthenticated: true,
      signOut: signOutMock
    });
    useCartMock.mockReturnValue({
      itemCount: 0,
      toggle: toggleMock
    });

    renderRouteWithProviders(<Header />, {
      path: '*',
      route: '/'
    });

    expect(screen.getByText('CustomerJohn')).toBeInTheDocument();
    
    const links = screen.getAllByRole('link');
    const hasAdminLink = links.some(link => link.getAttribute('href') === '/admin');
    expect(hasAdminLink).toBe(false);
  });
});
