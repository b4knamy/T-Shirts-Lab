import { Search, X } from 'lucide-react';
import type { CategoryFiltersProps } from '../types';

export function CategoryFilters({
  search,
  statusFilter,
  onSearchChange,
  onStatusFilterChange,
  onClear,
}: CategoryFiltersProps) {
  return (
    <div className="bg-white border border-gray-100 rounded-2xl mb-6 overflow-hidden">
      <div className="flex items-center px-5 py-3 gap-3">
        <Search className="w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Search categories..."
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
        <select
          value={statusFilter}
          onChange={(event) => onStatusFilterChange(event.target.value)}
          className="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent bg-white"
        >
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        {(search || statusFilter) && (
          <button
            onClick={onClear}
            className="text-xs text-accent hover:underline whitespace-nowrap"
          >
            Clear
          </button>
        )}
      </div>
    </div>
  );
}
