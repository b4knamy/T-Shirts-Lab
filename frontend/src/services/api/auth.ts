import apiClient from './client';
import type { LoginResponse } from '../../types';

interface MessageResponse {
  success: boolean;
  data: null;
  message: string;
}

export interface RegisterData {
  email: string;
  password: string;
  first_name: string;
  last_name: string;
  phone?: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface ForgotPasswordData {
  email: string;
}

export interface ResetPasswordData {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export const authApi = {
  register: (data: RegisterData) =>
    apiClient.post<{ data: LoginResponse }>('/api/v1/auth/register', data),

  login: (data: LoginData) =>
    apiClient.post<{ data: LoginResponse }>('/api/v1/auth/login', data),

  forgotPassword: (data: ForgotPasswordData) =>
    apiClient.post<MessageResponse>('/api/v1/auth/forgot-password', data),

  resetPassword: (data: ResetPasswordData) =>
    apiClient.post<MessageResponse>('/api/v1/auth/reset-password', data),

  refresh: (refresh_token: string) =>
    apiClient.post('/api/v1/auth/refresh', { refresh_token }),

  getProfile: () => apiClient.get('/api/v1/users/me'),
};
