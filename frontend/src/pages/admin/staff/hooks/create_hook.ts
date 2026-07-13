import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import apiClient from '../../../../services/api/client';
import { EMPTY_STAFF_FORM } from '../constants';
import type { CreateStaffFormData } from '../types';

export function useStaffCreate() {
  const queryClient = useQueryClient();

  const [isFormOpen, setIsFormOpen] = useState(false);
  const [form, setForm] = useState<CreateStaffFormData>({
    ...EMPTY_STAFF_FORM,
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const openForm = () => {
    setForm({ ...EMPTY_STAFF_FORM });
    setError(null);
    setIsFormOpen(true);
  };

  const closeForm = () => {
    setIsFormOpen(false);
  };

  const updateForm = <K extends keyof CreateStaffFormData>(
    field: K,
    value: CreateStaffFormData[K],
  ) => {
    setForm((current) => ({ ...current, [field]: value }));
  };

  const handleSubmit = async () => {
    setSaving(true);
    setError(null);
    try {
      await apiClient.post('/api/v1/users', form);
      setIsFormOpen(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-staff'] });
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e.response?.data?.message || 'Failed to create staff member');
    } finally {
      setSaving(false);
    }
  };

  return {
    isFormOpen,
    form,
    saving,
    error,
    openForm,
    closeForm,
    updateForm,
    handleSubmit,
  };
}
