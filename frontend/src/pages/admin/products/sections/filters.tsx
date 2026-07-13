import { Search, X } from 'lucide-react';
import type { ProductFiltersProps } from '../types';

export function ProductFilters({
  search,
  statusFilter,
  categoryFilter,
  categories,
  onSearchChange,
  onStatusFilterChange,
  onCategoryFilterChange,
  onClear,
}: ProductFiltersProps) {
  return (
    <div className="bg-white border border-gray-100 rounded-2xl mb-6 overflow-hidden">
      <div className="flex items-center px-5 py-3 gap-3">
        <Search className="w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Search products by name, SKU…"
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
          <option value="ACTIVE">Active</option>
          <option value="INACTIVE">Inactive</option>
          <option value="DRAFT">Draft</option>
          <option value="OUT_OF_STOCK">Out of Stock</option>
        </select>
        <select
          value={categoryFilter}
          onChange={(event) => onCategoryFilterChange(event.target.value)}
          className="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent bg-white"
        >
          <option value="">All Categories</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
        {(statusFilter || categoryFilter) && (
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
