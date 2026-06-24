import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api';
import type { Category } from '../../../../types';
import { EMPTY_CATEGORY_FORM } from '../constants';
import type { CategoryApiError, CategoryFormData } from '../types';

function resolveCategoryError(error: unknown, fallbackMessage: string): string {
  const apiError = error as CategoryApiError;
  const fieldErrors = apiError.response?.data?.errors;

  if (fieldErrors) {
    return Object.values(fieldErrors).flat().join('. ');
  }

  return apiError.response?.data?.message || fallbackMessage;
}

export function useCategoryForm() {
  const queryClient = useQueryClient();

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [form, setForm] = useState<CategoryFormData>({
    ...EMPTY_CATEGORY_FORM,
  });
  const [saveError, setSaveError] = useState<string | null>(null);

  const saveMutation = useMutation({
    mutationFn: async (payload: {
      form: CategoryFormData;
      editingCategory: Category | null;
    }) => {
      if (payload.editingCategory) {
        await adminApi.updateCategory(payload.editingCategory.id, payload.form);
        return;
      }

      await adminApi.createCategory(payload.form);
    },
  });

  const openNew = () => {
    setEditingCategory(null);
    setForm({ ...EMPTY_CATEGORY_FORM });
    setSaveError(null);
    setIsModalOpen(true);
  };

  const openEdit = (category: Category) => {
    setEditingCategory(category);
    setForm({
      name: category.name,
      description: category.description || '',
      image_url: category.image_url || '',
      is_active: category.is_active,
    });
    setSaveError(null);
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const updateForm = <K extends keyof CategoryFormData>(
    field: K,
    value: CategoryFormData[K],
  ) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const saveCategory = async () => {
    setSaveError(null);

    try {
      await saveMutation.mutateAsync({ form, editingCategory });
      setIsModalOpen(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-categories'] });
    } catch (error: unknown) {
      setSaveError(resolveCategoryError(error, 'Failed to save'));
    }
  };

  return {
    isModalOpen,
    editingCategory,
    form,
    isSaving: saveMutation.isPending,
    saveError,
    openNew,
    openEdit,
    closeModal,
    updateForm,
    saveCategory,
  };
}
