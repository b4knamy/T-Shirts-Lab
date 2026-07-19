import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { AuthProvider } from '../auth_context';
import { useAuth } from '../../hooks/useAuth';
import type { PropsWithChildren } from 'react';

const { loginMock, registerMock, getProfileMock } = vi.hoisted(() => ({
  loginMock: vi.fn(),
  registerMock: vi.fn(),
  getProfileMock: vi.fn(),
}));

vi.mock('../../services/api/auth', () => ({
  authApi: {
    login: loginMock,
    register: registerMock,
    getProfile: getProfileMock,
  },
}));

describe('AuthContext Integration', () => {
  beforeEach(() => {
    localStorage.clear();
    loginMock.mockReset();
    registerMock.mockReset();
    getProfileMock.mockReset();
  });

  it('should initialize based on localStorage if no initial values provided', () => {
    localStorage.setItem('accessToken', 'mock-token');

    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.user).toBeNull();
  });

  it('should perform login successfully', async () => {
    const mockUser = { id: 'u-1', email: 'user@example.com' };
    loginMock.mockResolvedValue({
      data: {
        data: {
          user: mockUser,
          access_token: 'access-123',
          refresh_token: 'refresh-123',
        },
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    let loginResult;
    await act(async () => {
      loginResult = await result.current.login({ email: 'user@example.com', password: 'password' });
    });

    expect(loginResult).toEqual({
      user: mockUser,
      access_token: 'access-123',
      refresh_token: 'refresh-123',
    });
    expect(localStorage.getItem('accessToken')).toBe('access-123');
    expect(localStorage.getItem('refreshToken')).toBe('refresh-123');
    expect(result.current.user).toEqual(mockUser);
    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.error).toBeNull();
  });

  it('should handle login error', async () => {
    loginMock.mockRejectedValue({
      response: {
        data: {
          error: {
            message: 'Invalid credentials',
          },
        },
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    await act(async () => {
      await expect(
        result.current.login({ email: 'user@example.com', password: 'password' })
      ).rejects.toThrow('Invalid credentials');
    });

    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.error).toBe('Invalid credentials');
  });

  it('should load profile successfully', async () => {
    const mockUser = { id: 'u-1', email: 'user@example.com' };
    getProfileMock.mockResolvedValue({
      data: {
        data: mockUser,
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    let profileResult;
    await act(async () => {
      profileResult = await result.current.loadProfile();
    });

    expect(profileResult).toEqual(mockUser);
    expect(result.current.user).toEqual(mockUser);
    expect(result.current.isAuthenticated).toBe(true);
  });

  it('should handle loadProfile failure by signout', async () => {
    getProfileMock.mockRejectedValue(new Error('Profile fetch failed'));

    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider initialIsAuthenticated={true}>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    await act(async () => {
      await expect(result.current.loadProfile()).rejects.toThrow('Failed to fetch profile');
    });

    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);
  });

  it('should listen to window auth:logout event and sign out', () => {
    localStorage.setItem('accessToken', 'mock-token');
    const wrapper = ({ children }: PropsWithChildren) => (
      <AuthProvider>{children}</AuthProvider>
    );
    const { result } = renderHook(() => useAuth(), { wrapper });

    expect(result.current.isAuthenticated).toBe(true);

    act(() => {
      window.dispatchEvent(new Event('auth:logout'));
    });

    expect(result.current.isAuthenticated).toBe(false);
    expect(localStorage.getItem('accessToken')).toBeNull();
  });
});
