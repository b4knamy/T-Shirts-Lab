import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';
import apiClient from '../../../../services/api/client';
import type { AdminReview, UseReviewFetchingOptions } from '../types';

interface AdminReviewsResponse {
  data: AdminReview[];
  meta: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}

export function useReviewFetching({
  pagination,
  filterUnreplied,
  ratingFilter,
  searchProduct,
}: UseReviewFetchingOptions) {
  const query = useQuery({
    queryKey: [
      'admin-reviews',
      pagination.page,
      pagination.limit,
      filterUnreplied,
      ratingFilter,
      searchProduct,
    ],
    queryFn: async () => {
      const response = await apiClient.get<{ data: AdminReviewsResponse }>(
        '/api/v1/reviews',
        {
          params: {
            page: pagination.page,
            limit: pagination.limit,
            ...(filterUnreplied ? { unreplied: '1' } : {}),
            ...(ratingFilter ? { rating: ratingFilter } : {}),
            ...(searchProduct ? { search: searchProduct } : {}),
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
    reviews: query.data?.data || [],
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
  };
}
