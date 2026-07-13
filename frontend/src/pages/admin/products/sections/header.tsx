import { Plus } from 'lucide-react';
import type { ProductHeaderProps } from '../types';

export function ProductHeader({ total, onCreate }: ProductHeaderProps) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Products</h1>
        <p className="text-gray-500 mt-1">{total} products total</p>
      </div>
      <button
        onClick={onCreate}
        className="inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-accent/20"
      >
        <Plus className="w-4 h-4" /> New Product
      </button>
    </div>
  );
}
