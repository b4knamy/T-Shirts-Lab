import { screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ProtectedRoute } from '../ProtectedRoute';
import { renderRouteWithProviders } from '../../../test/renderWithProviders';

const useAuthMock = vi.fn();
vi.mock('../../../hooks/useAuth', () => ({
  useAuth: () => useAuthMock()
}));

vi.mock('react-router-dom', async (importOriginal) => {
  const actual = await importOriginal<typeof import('react-router-dom')>();
  return {
    ...actual,
    Navigate: ({ to }: { to: string }) => <div data-testid="navigate" data-to={to} />
  };
});

describe('ProtectedRoute', () => {
  it('renders children if user is authenticated and roles matches', () => {
    useAuthMock.mockReturnValue({
      isAuthenticated: true,
      user: { id: 'u-1', role: 'admin' }
    });

    renderRouteWithProviders(
      <ProtectedRoute roles={['admin']}>
        <div>Secret Admin Content</div>
      </ProtectedRoute>,
      { path: '/admin-dashboard', route: '/admin-dashboard' }
    );

    expect(screen.getByText('Secret Admin Content')).toBeInTheDocument();
    expect(screen.queryByTestId('navigate')).not.toBeInTheDocument();
  });

  it('redirects to /login if user is not authenticated', () => {
    useAuthMock.mockReturnValue({
      isAuthenticated: false,
      user: null
    });

    renderRouteWithProviders(
      <ProtectedRoute>
        <div>Secret Admin Content</div>
      </ProtectedRoute>,
      { path: '/dashboard', route: '/dashboard' }
    );

    expect(screen.queryByText('Secret Admin Content')).not.toBeInTheDocument();
    const navigate = screen.getByTestId('navigate');
    expect(navigate).toBeInTheDocument();
    expect(navigate).toHaveAttribute('data-to', '/login');
  });

  it('redirects to / if user is authenticated but role does not match', () => {
    useAuthMock.mockReturnValue({
      isAuthenticated: true,
      user: { id: 'u-1', role: 'customer' }
    });

    renderRouteWithProviders(
      <ProtectedRoute roles={['admin']}>
        <div>Secret Admin Content</div>
      </ProtectedRoute>,
      { path: '/admin-dashboard', route: '/admin-dashboard' }
    );

    expect(screen.queryByText('Secret Admin Content')).not.toBeInTheDocument();
    const navigate = screen.getByTestId('navigate');
    expect(navigate).toBeInTheDocument();
    expect(navigate).toHaveAttribute('data-to', '/');
  });
});
