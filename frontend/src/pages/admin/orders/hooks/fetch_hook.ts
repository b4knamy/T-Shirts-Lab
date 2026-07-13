import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { Order } from '../../../../types';
import type { UseOrderFetchingOptions } from '../types';

export function useOrderFetching({
  pagination,
  search,
  statusFilter,
  paymentFilter,
}: UseOrderFetchingOptions) {
  const query = useQuery({
    queryKey: [
      'admin-orders',
      pagination.page,
      pagination.limit,
      search,
      statusFilter,
      paymentFilter,
    ],
    queryFn: async () => {
      const response = await adminApi.getOrders({
        page: pagination.page,
        limit: pagination.limit,
        search: search || undefined,
        status: statusFilter || undefined,
        payment_status: paymentFilter || undefined,
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
    orders: (query.data?.data?.data || []) as Order[],
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
  };
}
