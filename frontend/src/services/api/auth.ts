import apiClient from './client';
import type { LoginResponse } from '../../types';

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

export const authApi = {
  register: (data: RegisterData) =>
    apiClient.post<{ data: LoginResponse }>('/api/v1/auth/register', data),

  login: (data: LoginData) =>
    apiClient.post<{ data: LoginResponse }>('/api/v1/auth/login', data),

  refresh: (refresh_token: string) =>
    apiClient.post('/api/v1/auth/refresh', { refresh_token }),

  getProfile: () => apiClient.get('/api/v1/users/me'),
};
