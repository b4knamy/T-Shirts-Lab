import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Package, ShoppingCart, DollarSign, TrendingUp, ArrowUpRight } from 'lucide-react';
import { adminApi } from '../../services/api/admin';

interface DashboardStats {
  total_products: number;
  total_orders: number;
  revenue: number;
  pending_orders: number;
}

export function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats>({
    total_products: 0, total_orders: 0, revenue: 0, pending_orders: 0,
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const [prodRes, orderRes] = await Promise.all([
          adminApi.getProducts({ limit: 1 }),
          adminApi.getOrders({ limit: 100 }),
        ]);

        const orders = orderRes.data.data.data || [];
        const revenue = orders.reduce((sum: number, o: { total: number }) => sum + Number(o.total), 0);
        const pending = orders.filter((o: { status: string }) => o.status === 'PENDING').length;

        setStats({
          total_products: prodRes.data.meta?.total ?? 0,
          total_orders: orderRes.data.meta?.total ?? orders.length,
          revenue,
          pending_orders: pending,
        });
      } catch {
        // silently handle
      } finally {
        setIsLoading(false);
      }
    };
    load();
  }, []);

  const cards = [
    { label: 'Total Products', value: stats.total_products, icon: Package, color: 'bg-blue-500', link: '/admin/products' },
    { label: 'Total Orders',   value: stats.total_orders,   icon: ShoppingCart, color: 'bg-purple-500', link: '/admin/orders' },
    { label: 'Revenue',        value: `$${stats.revenue.toFixed(2)}`, icon: DollarSign, color: 'bg-green-500', link: '/admin/orders' },
    { label: 'Pending Orders', value: stats.pending_orders, icon: TrendingUp, color: 'bg-yellow-500', link: '/admin/orders' },
  ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 mt-1">Overview of your store performance</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {cards.map((card) => {
          const Icon = card.icon;
          return (
            <Link
              key={card.label}
              to={card.link}
              className="bg-white border border-gray-100 rounded-2xl p-6 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5 group"
            >
              <div className="flex items-center justify-between mb-4">
                <div className={`w-12 h-12 ${card.color} rounded-xl flex items-center justify-center`}>
                  <Icon className="w-6 h-6 text-white" />
                </div>
                <ArrowUpRight className="w-5 h-5 text-gray-300 group-hover:text-accent transition-colors" />
              </div>
              <p className="text-2xl font-bold text-gray-900">
                {isLoading ? <span className="inline-block w-16 h-7 bg-gray-100 animate-pulse rounded" /> : card.value}
              </p>
              <p className="text-sm text-gray-500 mt-1">{card.label}</p>
            </Link>
          );
        })}
      </div>

      {/* Quick actions */}
      <div className="bg-white border border-gray-100 rounded-2xl p-6">
        <h2 className="font-semibold text-gray-800 mb-4">Quick Actions</h2>
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/products?action=new"
            className="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-accent-light transition-colors shadow-md shadow-accent/20"
          >
            <Package className="w-4 h-4" /> New Product
          </Link>
          <Link
            to="/admin/orders"
            className="inline-flex items-center gap-2 border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
          >
            <ShoppingCart className="w-4 h-4" /> Manage Orders
          </Link>
        </div>
      </div>
    </div>
  );
}
