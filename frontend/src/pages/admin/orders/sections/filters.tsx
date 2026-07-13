import { Search, X } from 'lucide-react';
import type { OrderFiltersProps } from '../types';

export function OrderFilters({
  search,
  statusFilter,
  paymentFilter,
  onSearchChange,
  onStatusFilterChange,
  onPaymentFilterChange,
  onClear,
}: OrderFiltersProps) {
  return (
    <div className="bg-white border border-gray-100 rounded-2xl mb-6 overflow-hidden">
      <div className="flex items-center px-5 py-3 gap-3">
        <Search className="w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Search by order number, customer name or email…"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
          className="flex-1 outline-none text-sm bg-transparent"
        />
        {search && (
          <button
            onClick={() => onSearchChange('')}
            className="text-gray-400 hover:text-gray-600"
          >
            <X className="w-4 h-4" />
          </button>
        )}
      </div>
      <div className="flex flex-wrap items-center gap-3 px-5 pb-3 border-t border-gray-50 pt-3">
        <select
          value={statusFilter}
          onChange={(event) => onStatusFilterChange(event.target.value)}
          className="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent bg-white"
        >
          <option value="">All Statuses</option>
          <option value="PENDING">Pending</option>
          <option value="CONFIRMED">Confirmed</option>
          <option value="PROCESSING">Processing</option>
          <option value="SHIPPED">Shipped</option>
          <option value="DELIVERED">Delivered</option>
          <option value="CANCELLED">Cancelled</option>
          <option value="REFUNDED">Refunded</option>
        </select>
        <select
          value={paymentFilter}
          onChange={(event) => onPaymentFilterChange(event.target.value)}
          className="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent bg-white"
        >
          <option value="">All Payments</option>
          <option value="PENDING">Awaiting</option>
          <option value="PROCESSING">Processing</option>
          <option value="COMPLETED">Paid</option>
          <option value="FAILED">Failed</option>
          <option value="REFUNDED">Refunded</option>
        </select>
        {(search || statusFilter || paymentFilter) && (
          <button
            onClick={onClear}
            className="text-xs text-accent hover:underline"
          >
            Clear filters
          </button>
        )}
      </div>
    </div>
  );
}
