import apiClient from './client';
import type { User, UserAddress } from '../../types';

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  phone?: string;
}

export interface AddressData {
  label?: string;
  street: string;
  number: string;
  complement?: string;
  neighborhood?: string;
  city: string;
  state: string;
  zip_code: string;
  country?: string;
  is_default?: boolean;
}

export const userApi = {
  getProfile: () =>
    apiClient.get<{ data: User }>('/api/v1/users/me'),

  updateProfile: (data: UpdateProfileData) =>
    apiClient.patch<{ data: User }>('/api/v1/users/me', data),

  uploadAvatar: (file: File) => {
    const formData = new FormData();
    formData.append('avatar', file);
    return apiClient.post<{ data: User }>('/api/v1/users/me/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  getAddresses: () =>
    apiClient.get<{ data: UserAddress[] }>('/api/v1/users/me/addresses'),

  createAddress: (data: AddressData) =>
    apiClient.post<{ data: UserAddress }>('/api/v1/users/me/addresses', data),

  updateAddress: (id: string, data: Partial<AddressData>) =>
    apiClient.patch<{ data: UserAddress }>(`/api/v1/users/me/addresses/${id}`, data),

  deleteAddress: (id: string) =>
    apiClient.delete(`/api/v1/users/me/addresses/${id}`),
};
