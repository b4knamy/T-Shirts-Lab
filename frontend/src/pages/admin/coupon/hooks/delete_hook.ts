import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api';
import type { Coupon } from '../../../../types';
import type { CouponApiError } from '../types';

function resolveCouponError(error: unknown, fallbackMessage: string): string {
  const apiError = error as CouponApiError;
  const fieldErrors = apiError.response?.data?.errors;

  if (fieldErrors) {
    return Object.values(fieldErrors).flat().join('. ');
  }

  return apiError.response?.data?.message || fallbackMessage;
}

export function useCouponDelete() {
  const queryClient = useQueryClient();

  const [deleteTarget, setDeleteTarget] = useState<Coupon | null>(null);
  const [deleteError, setDeleteError] = useState<string | null>(null);

  const deleteMutation = useMutation({
    mutationFn: async (couponId: string) => {
      await adminApi.deleteCoupon(couponId);
    },
  });

  const requestDelete = (coupon: Coupon) => {
    setDeleteTarget(coupon);
    setDeleteError(null);
  };

  const cancelDelete = () => {
    setDeleteTarget(null);
  };

  const deleteCoupon = async () => {
    if (!deleteTarget) {
      return;
    }

    setDeleteError(null);

    try {
      await deleteMutation.mutateAsync(deleteTarget.id);
      setDeleteTarget(null);
      await queryClient.invalidateQueries({ queryKey: ['admin-coupons'] });
    } catch (error: unknown) {
      setDeleteError(resolveCouponError(error, 'Failed to delete'));
    }
  };

  return {
    deleteTarget,
    deleteError,
    isDeleting: deleteMutation.isPending,
    requestDelete,
    cancelDelete,
    deleteCoupon,
  };
}
