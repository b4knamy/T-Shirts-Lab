import { Plus } from 'lucide-react';
import type { CategoryHeaderProps } from '../types';

export function CategoryHeader({ total, onCreate }: CategoryHeaderProps) {
  return (
    <div className="flex items-center justify-between mb-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Categories</h1>
        <p className="text-gray-500 mt-1">{total} categories</p>
      </div>
      <button
        onClick={onCreate}
        className="inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-accent/20"
      >
        <Plus className="w-4 h-4" /> New Category
      </button>
    </div>
  );
}
