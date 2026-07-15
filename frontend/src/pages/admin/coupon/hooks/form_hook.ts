import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api';
import type { Coupon } from '../../../../types';
import { buildCouponPayload } from '../utils';
import type { CouponApiError, CouponFormData } from '../types';

function resolveCouponError(error: unknown, fallbackMessage: string): string {
  const apiError = error as CouponApiError;
  const fieldErrors = apiError.response?.data?.errors;

  if (fieldErrors) {
    return Object.values(fieldErrors).flat().join('. ');
  }

  return apiError.response?.data?.message || fallbackMessage;
}

export function useCouponForm() {
  const queryClient = useQueryClient();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState<Coupon | null>(null);
  const [saveError, setSaveError] = useState<string | null>(null);

  const saveMutation = useMutation({
    mutationFn: async (payload: {
      form: CouponFormData;
      editingCoupon: Coupon | null;
    }) => {
      const requestPayload = buildCouponPayload(payload.form);

      if (payload.editingCoupon) {
        await adminApi.updateCoupon(payload.editingCoupon.id, requestPayload);
        return;
      }

      await adminApi.createCoupon(requestPayload);
    },
  });

  const openNew = () => {
    setEditingCoupon(null);
    setSaveError(null);
    setIsModalOpen(true);
  };

  const openEdit = (coupon: Coupon) => {
    setEditingCoupon(coupon);
    setSaveError(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const saveCoupon = async (data: CouponFormData) => {
    setSaveError(null);

    try {
      await saveMutation.mutateAsync({ form: data, editingCoupon });
      setIsModalOpen(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-coupons'] });
    } catch (error: unknown) {
      setSaveError(resolveCouponError(error, 'Failed to save'));
    }
  };

  return {
    isModalOpen,
    editingCoupon,
    isSaving: saveMutation.isPending,
    saveError,
    openNew,
    openEdit,
    closeModal,
    saveCoupon,
  };
}
