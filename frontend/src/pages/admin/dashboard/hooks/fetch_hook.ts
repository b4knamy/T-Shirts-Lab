import { useEffect, useState } from 'react';
import { adminApi } from '../../../../services/api/admin';
import type { DashboardStats } from '../type';

export function useAdminDashboard() {
  const [stats, setStats] = useState<DashboardStats>({
    total_products: 0,
    total_orders: 0,
    revenue: 0,
    pending_orders: 0,
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    const load = async () => {
      try {
        const [prodRes, orderRes] = await Promise.all([
          adminApi.getProducts({ limit: 1 }),
          adminApi.getOrders({ limit: 100 }),
        ]);

        const orders = orderRes.data.data.data || [];
        const revenue = orders.reduce(
          (sum: number, o: { total: number }) => sum + Number(o.total),
          0,
        );
        const pending = orders.filter(
          (o: { status: string }) => o.status === 'PENDING',
        ).length;

        if (!mounted) return;
        setStats({
          total_products: prodRes.data.meta?.total ?? 0,
          total_orders: orderRes.data.meta?.total ?? orders.length,
          revenue,
          pending_orders: pending,
        });
      } catch {
        // silently handle
      } finally {
        if (mounted) setIsLoading(false);
      }
    };
    load();
    return () => {
      mounted = false;
    };
  }, []);

  return { stats, isLoading };
}
