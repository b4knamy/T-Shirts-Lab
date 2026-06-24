import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api';
import type { Category } from '../../../../types';
import type { CategoryApiError } from '../types';

function resolveCategoryError(error: unknown, fallbackMessage: string): string {
  const apiError = error as CategoryApiError;
  const fieldErrors = apiError.response?.data?.errors;

  if (fieldErrors) {
    return Object.values(fieldErrors).flat().join('. ');
  }

  return apiError.response?.data?.message || fallbackMessage;
}

export function useCategoryDelete() {
  const queryClient = useQueryClient();

  const [deleteTarget, setDeleteTarget] = useState<Category | null>(null);
  const [deleteError, setDeleteError] = useState<string | null>(null);

  const deleteMutation = useMutation({
    mutationFn: async (categoryId: string) => {
      await adminApi.deleteCategory(categoryId);
    },
  });

  const requestDelete = (category: Category) => {
    setDeleteTarget(category);
    setDeleteError(null);
  };

  const cancelDelete = () => {
    setDeleteTarget(null);
  };

  const deleteCategory = async () => {
    if (!deleteTarget) {
      return;
    }

    setDeleteError(null);

    try {
      await deleteMutation.mutateAsync(deleteTarget.id);
      setDeleteTarget(null);
      await queryClient.invalidateQueries({ queryKey: ['admin-categories'] });
    } catch (error: unknown) {
      setDeleteError(resolveCategoryError(error, 'Failed to delete'));
    }
  };

  return {
    deleteTarget,
    deleteError,
    isDeleting: deleteMutation.isPending,
    requestDelete,
    cancelDelete,
    deleteCategory,
  };
}
