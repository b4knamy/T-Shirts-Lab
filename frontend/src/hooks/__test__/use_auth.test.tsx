import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuth } from '../useAuth';
import { createHookWrapper } from '../../pages/checkout/hooks/__test__/test_utils';

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

describe('useAuth', () => {
  beforeEach(() => {
    localStorage.clear();
    loginMock.mockReset();
    registerMock.mockReset();
    getProfileMock.mockReset();
  });

  it('provides initial state and login success', async () => {
    const mockUser = {
      id: 'u-1',
      email: 'test@example.com',
      first_name: 'John',
      last_name: 'Doe',
    };
    loginMock.mockResolvedValue({
      data: {
        data: {
          user: mockUser,
          access_token: 'fake-access-token',
          refresh_token: 'fake-refresh-token',
        },
      },
    });

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(() => useAuth(), { wrapper });

    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);

    let loginResult;
    await act(async () => {
      loginResult = await result.current.login({
        email: 'test@example.com',
        password: 'password',
      });
    });

    expect(loginResult).toEqual({
      user: mockUser,
      access_token: 'fake-access-token',
      refresh_token: 'fake-refresh-token',
    });

    expect(localStorage.getItem('accessToken')).toBe('fake-access-token');
    expect(localStorage.getItem('refreshToken')).toBe('fake-refresh-token');
    expect(result.current.user).toEqual(mockUser);
    expect(result.current.isAuthenticated).toBe(true);
  });

  it('handles signOut', () => {
    localStorage.setItem('accessToken', 'token');
    localStorage.setItem('refreshToken', 'rtoken');

    const { wrapper } = createHookWrapper();
    const { result } = renderHook(() => useAuth(), { wrapper });

    act(() => {
      result.current.signOut();
    });

    expect(localStorage.getItem('accessToken')).toBeNull();
    expect(localStorage.getItem('refreshToken')).toBeNull();
    expect(result.current.user).toBeNull();
    expect(result.current.isAuthenticated).toBe(false);
  });
});
