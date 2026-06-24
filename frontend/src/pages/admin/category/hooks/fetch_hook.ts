import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { adminApi } from "../../../../services/api";
import type { Category } from "../../../../types";
import type { UseCategoryFetchingOptions } from "../types";

export function useCategoryFetching({
  pagination,
  search,
  statusFilter,
}: UseCategoryFetchingOptions) {
  const query = useQuery({
    queryKey: [
      "admin-categories",
      pagination.page,
      pagination.limit,
      search,
      statusFilter,
    ],
    queryFn: async () => {
      const response = await adminApi.getCategoriesPaginated({
        page: pagination.page,
        limit: pagination.limit,
        search: search || undefined,
        status: statusFilter || undefined,
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
  }, [pagination, query.data?.meta]);

  return {
    categories: (query.data?.data?.data || []) as Category[],
    total: query.data?.meta?.total ?? 0,
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
  };
}
