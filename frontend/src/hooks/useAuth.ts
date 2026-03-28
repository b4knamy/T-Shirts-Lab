import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../store';
import { loginUser, registerUser, fetchProfile, logout, clearError } from '../store/slices/authSlice';
import type { RegisterData, LoginData } from '../services/api/auth';

export function useAuth() {
  const dispatch = useAppDispatch();
  const { user, isAuthenticated, isLoading, error } = useAppSelector((state) => state.auth);

  const login = useCallback(
    async (data: LoginData) => {
      return dispatch(loginUser(data)).unwrap();
    },
    [dispatch],
  );

  const register = useCallback(
    async (data: RegisterData) => {
      return dispatch(registerUser(data)).unwrap();
    },
    [dispatch],
  );

  const loadProfile = useCallback(async () => {
    return dispatch(fetchProfile()).unwrap();
  }, [dispatch]);

  const signOut = useCallback(() => {
    dispatch(logout());
  }, [dispatch]);

  const resetError = useCallback(() => {
    dispatch(clearError());
  }, [dispatch]);

  return {
    user,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    loadProfile,
    signOut,
    resetError,
  };
}
