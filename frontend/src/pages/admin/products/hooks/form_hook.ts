import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { Product } from '../../../../types';
import { EMPTY_PRODUCT_FORM } from '../constants';
import type { ProductFormData } from '../types';
import { resolveProductError } from '../utils';

export function useProductForm() {
  const queryClient = useQueryClient();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [form, setForm] = useState<ProductFormData>({
    ...EMPTY_PRODUCT_FORM,
  });
  const [saveError, setSaveError] = useState<string | null>(null);

  const saveMutation = useMutation({
    mutationFn: async (payload: {
      form: ProductFormData;
      editingProduct: Product | null;
    }) => {
      if (payload.editingProduct) {
        await adminApi.updateProduct(payload.editingProduct.id, payload.form);
        return;
      }

      await adminApi.createProduct(payload.form);
    },
  });

  const openNew = () => {
    setEditingProduct(null);
    setForm({ ...EMPTY_PRODUCT_FORM });
    setSaveError(null);
    setIsModalOpen(true);
  };

  const openEdit = (product: Product) => {
    setEditingProduct(product);
    setForm({
      name: product.name,
      description: product.description,
      long_description: product.long_description || '',
      category_id: product.category_id,
      price: product.price,
      cost_price: product.cost_price || 0,
      discount_price: product.discount_price || 0,
      discount_percent: product.discount_percent || 0,
      stock_quantity: product.stock_quantity,
      sku: product.sku || '',
      is_featured: product.is_featured,
      status: product.status,
      color: product.color || '',
      size: product.size || '',
    });
    setSaveError(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const updateForm = <K extends keyof ProductFormData>(
    field: K,
    value: ProductFormData[K],
  ) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const saveProduct = async () => {
    setSaveError(null);

    try {
      await saveMutation.mutateAsync({ form, editingProduct });
      setIsModalOpen(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-products'] });
    } catch (error: unknown) {
      setSaveError(resolveProductError(error, 'Failed to save product'));
    }
  };

  return {
    isModalOpen,
    editingProduct,
    form,
    isSaving: saveMutation.isPending,
    saveError,
    openNew,
    openEdit,
    closeModal,
    updateForm,
    saveProduct,
  };
}
