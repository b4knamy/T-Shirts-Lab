import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { Product } from '../../../../types';
import type { UseProductFetchingOptions } from '../types';

export function useProductFetching({
  pagination,
  search,
  statusFilter,
  categoryFilter,
}: UseProductFetchingOptions) {
  const query = useQuery({
    queryKey: [
      'admin-products',
      pagination.page,
      pagination.limit,
      search,
      statusFilter,
      categoryFilter,
    ],
    queryFn: async () => {
      const response = await adminApi.getProducts({
        page: pagination.page,
        limit: pagination.limit,
        search: search || undefined,
        status: statusFilter || undefined,
        categoryId: categoryFilter || undefined,
      });

      return response.data;
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
    products: (query.data?.data?.data || []) as Product[],
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
    refetch: query.refetch,
  };
}
