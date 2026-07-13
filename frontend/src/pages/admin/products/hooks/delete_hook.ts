import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { Product } from '../../../../types';

export function useProductDelete() {
  const queryClient = useQueryClient();

  const [deleteTarget, setDeleteTarget] = useState<Product | null>(null);

  const deleteMutation = useMutation({
    mutationFn: async (productId: string) => {
      await adminApi.deleteProduct(productId);
    },
  });

  const requestDelete = (product: Product) => {
    setDeleteTarget(product);
  };

  const cancelDelete = () => {
    setDeleteTarget(null);
  };

  const deleteProduct = async () => {
    if (!deleteTarget) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(deleteTarget.id);
      setDeleteTarget(null);
      await queryClient.invalidateQueries({ queryKey: ['admin-products'] });
    } catch {
      // silently fail
    }
  };

  return {
    deleteTarget,
    isDeleting: deleteMutation.isPending,
    requestDelete,
    cancelDelete,
    deleteProduct,
  };
}
