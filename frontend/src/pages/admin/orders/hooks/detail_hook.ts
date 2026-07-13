import { useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { Order } from '../../../../types';

export function useOrderDetail() {
  const queryClient = useQueryClient();

  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [newStatus, setNewStatus] = useState('');
  const [adminNotes, setAdminNotes] = useState('');
  const [isSaving, setIsSaving] = useState(false);

  const openOrder = async (order: Order) => {
    try {
      const res = await adminApi.getOrder(order.id);
      setSelectedOrder(res.data.data);
      setNewStatus(res.data.data.status);
      setAdminNotes(res.data.data.admin_notes || '');
    } catch {
      setSelectedOrder(order);
      setNewStatus(order.status);
      setAdminNotes('');
    }
  };

  const closeOrder = () => {
    setSelectedOrder(null);
  };

  const handleStatusUpdate = async () => {
    if (!selectedOrder || newStatus === selectedOrder.status) {
      return;
    }

    setIsSaving(true);
    try {
      await adminApi.updateOrderStatus(
        selectedOrder.id,
        newStatus,
        adminNotes || undefined,
      );
      setSelectedOrder(null);
      await queryClient.invalidateQueries({ queryKey: ['admin-orders'] });
    } catch {
      // silently fail
    } finally {
      setIsSaving(false);
    }
  };

  return {
    selectedOrder,
    newStatus,
    adminNotes,
    isSaving,
    openOrder,
    closeOrder,
    setNewStatus,
    setAdminNotes,
    handleStatusUpdate,
  };
}
