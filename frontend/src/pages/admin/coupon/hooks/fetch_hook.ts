import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { adminApi } from "../../../../services/api";
import type { Coupon } from "../../../../types";
import type { UseCouponFetchingOptions } from "../types";

export function useCouponFetching({
  pagination,
  search,
  typeFilter,
  statusFilter,
}: UseCouponFetchingOptions) {
  const query = useQuery({
    queryKey: [
      "admin-coupons",
      pagination.page,
      pagination.limit,
      search,
      typeFilter,
      statusFilter,
    ],
    queryFn: async () => {
      const response = await adminApi.getCoupons({
        page: pagination.page,
        limit: pagination.limit,
        search: search || undefined,
        type: typeFilter || undefined,
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
  }, [query.data?.meta]);

  return {
    coupons: (query.data?.data?.data || []) as Coupon[],
    total: query.data?.meta?.total ?? 0,
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    fetchError: query.error,
  };
}
