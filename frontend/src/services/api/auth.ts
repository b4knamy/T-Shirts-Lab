import apiClient from './client';
import type { LoginResponse } from '../../types';

export interface RegisterData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
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

  refresh: (refreshToken: string) =>
    apiClient.post('/api/v1/auth/refresh', { refreshToken }),

  getProfile: () => apiClient.get('/api/v1/users/me'),
};
