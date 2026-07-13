import { Eye, ShoppingCart } from 'lucide-react';
import type { OrderTableProps } from '../types';
import {
  formatCurrency,
  formatOrderDate,
  getOrderPaymentConfig,
  getOrderStatusConfig,
} from '../utils';

export function OrderTable({ orders, isLoading, onView }: OrderTableProps) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-gray-100">
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Order
            </th>
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
              Date
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Items
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Total
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Payment
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-50">
          {isLoading ? (
            Array.from({ length: 5 }).map((_, index) => (
              <tr key={index}>
                <td colSpan={7} className="px-5 py-4">
                  <div className="h-5 bg-gray-100 rounded animate-pulse" />
                </td>
              </tr>
            ))
          ) : orders.length === 0 ? (
            <tr>
              <td colSpan={7} className="px-5 py-16 text-center text-gray-400">
                <ShoppingCart className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                <p className="font-medium">No orders yet</p>
              </td>
            </tr>
          ) : (
            orders.map((order) => {
              const statusCfg = getOrderStatusConfig(order.status);
              const paymentCfg = getOrderPaymentConfig(order.payment_status);
              return (
                <tr
                  key={order.id}
                  className="hover:bg-gray-50/50 transition-colors"
                >
                  <td className="px-5 py-3.5">
                    <p className="font-semibold text-gray-900">
                      #{order.order_number}
                    </p>
                    <p className="text-xs text-gray-400">
                      {order.id.slice(0, 8)}…
                    </p>
                  </td>
                  <td className="px-5 py-3.5 hidden md:table-cell text-gray-500">
                    {formatOrderDate(order.created_at)}
                  </td>
                  <td className="px-5 py-3.5 text-center text-gray-600">
                    {order.items?.length ?? '—'}
                  </td>
                  <td className="px-5 py-3.5 text-right font-semibold">
                    {formatCurrency(order.total)}
                  </td>
                  <td className="px-5 py-3.5 text-center">
                    <span
                      className={`inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full border ${statusCfg.style}`}
                    >
                      {statusCfg.label}
                    </span>
                  </td>
                  <td className="px-5 py-3.5 text-center">
                    <span
                      className={`text-xs font-semibold ${paymentCfg.style}`}
                    >
                      {paymentCfg.label}
                    </span>
                  </td>
                  <td className="px-5 py-3.5 text-right">
                    <button
                      onClick={() => onView(order)}
                      className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                      title="View / Update"
                    >
                      <Eye className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
}
