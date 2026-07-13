import type { OrderHeaderProps } from '../types';

export function OrderHeader({ total }: OrderHeaderProps) {
  return (
    <div className="mb-6">
      <h1 className="text-2xl font-bold text-gray-900">Orders</h1>
      <p className="text-gray-500 mt-1">{total} orders total</p>
    </div>
  );
}
