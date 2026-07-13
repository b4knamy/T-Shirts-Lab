import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import apiClient from '../../../../services/api/client';
import type { User } from '../../../../types';
import type { UseStaffFetchingOptions } from '../types';

interface UsersResponse {
  data: User[];
  meta: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}

export function useStaffFetching({
  pagination,
  search,
  roleFilter,
}: UseStaffFetchingOptions) {
  const query = useQuery({
    queryKey: [
      'admin-staff',
      pagination.page,
      pagination.limit,
      search,
      roleFilter,
    ],
    queryFn: async () => {
      const response = await apiClient.get<{ data: UsersResponse }>(
        '/api/v1/users',
        {
          params: {
            page: pagination.page,
            limit: pagination.limit,
            ...(search ? { search } : {}),
            ...(roleFilter ? { role: roleFilter } : {}),
          },
        },
      );

      return response.data.data;
    },
    placeholderData: keepPreviousData,
  });

  useEffect(() => {
    if (!query.data?.meta) {
      return;
    }

    pagination.applyPagination(query.data.meta);
  }, [query.data?.meta]);

  return {
    users: query.data?.data || [],
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
  };
}
