import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import apiClient from '../../../../services/api/client';
import type { User } from '../../../../types';

export function useStaffRow(user: User) {
  const queryClient = useQueryClient();

  const [editing, setEditing] = useState(false);
  const [newRole, setNewRole] = useState(user.role);
  const [saving, setSaving] = useState(false);

  const startEditing = () => setEditing(true);

  const cancelEditing = () => {
    setEditing(false);
    setNewRole(user.role);
  };

  const handleSaveRole = async () => {
    setSaving(true);
    try {
      await apiClient.patch(`/api/v1/users/${user.id}`, { role: newRole });
      setEditing(false);
      await queryClient.invalidateQueries({ queryKey: ['admin-staff'] });
    } catch {
      // silent
    } finally {
      setSaving(false);
    }
  };

  const handleToggleActive = async () => {
    try {
      await apiClient.patch(`/api/v1/users/${user.id}`, {
        is_active: !user.is_active,
      });
      await queryClient.invalidateQueries({ queryKey: ['admin-staff'] });
    } catch {
      // silent
    }
  };

  return {
    editing,
    newRole,
    saving,
    setNewRole,
    startEditing,
    cancelEditing,
    handleSaveRole,
    handleToggleActive,
  };
}
