import { Link } from 'react-router-dom';
import {
  Package,
  ShoppingCart,
  DollarSign,
  TrendingUp,
  ArrowUpRight,
} from 'lucide-react';
import type { DashboardCardProps } from '../type';

export function DashboardCards({ stats, isLoading }: DashboardCardProps) {
  const cards = [
    {
      label: 'Total Products',
      value: stats.total_products,
      icon: Package,
      color: 'bg-blue-500',
      link: '/admin/products',
    },
    {
      label: 'Total Orders',
      value: stats.total_orders,
      icon: ShoppingCart,
      color: 'bg-purple-500',
      link: '/admin/orders',
    },
    {
      label: 'Revenue',
      value: `$${stats.revenue.toFixed(2)}`,
      icon: DollarSign,
      color: 'bg-green-500',
      link: '/admin/orders',
    },
    {
      label: 'Pending Orders',
      value: stats.pending_orders,
      icon: TrendingUp,
      color: 'bg-yellow-500',
      link: '/admin/orders',
    },
  ];

  return (
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
              <div
                className={`w-12 h-12 ${card.color} rounded-xl flex items-center justify-center`}
              >
                <Icon className="w-6 h-6 text-white" />
              </div>
              <ArrowUpRight className="w-5 h-5 text-gray-300 group-hover:text-accent transition-colors" />
            </div>
            <p className="text-2xl font-bold text-gray-900">
              {isLoading ? (
                <span className="inline-block w-16 h-7 bg-gray-100 animate-pulse rounded" />
              ) : (
                card.value
              )}
            </p>
            <p className="text-sm text-gray-500 mt-1">{card.label}</p>
          </Link>
        );
      })}
    </div>
  );
}
