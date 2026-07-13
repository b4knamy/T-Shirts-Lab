import { Filter, Search, X } from 'lucide-react';
import type { ReviewFiltersProps } from '../types';

export function ReviewFilters({
  searchProduct,
  ratingFilter,
  filterUnreplied,
  onSearchProductChange,
  onRatingFilterChange,
  onToggleUnreplied,
}: ReviewFiltersProps) {
  return (
    <div className="flex items-center gap-3 flex-wrap">
      <div className="relative">
        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          type="text"
          value={searchProduct}
          onChange={(event) => onSearchProductChange(event.target.value)}
          placeholder="Search by product…"
          className="pl-9 pr-8 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent w-52"
        />
        {searchProduct && (
          <button
            onClick={() => onSearchProductChange('')}
            className="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        )}
      </div>
      <select
        value={ratingFilter}
        onChange={(event) => onRatingFilterChange(event.target.value)}
        className="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent bg-white"
      >
        <option value="">All Ratings</option>
        <option value="5">5 Stars</option>
        <option value="4">4 Stars</option>
        <option value="3">3 Stars</option>
        <option value="2">2 Stars</option>
        <option value="1">1 Star</option>
      </select>
      <button
        onClick={onToggleUnreplied}
        className={`flex items-center gap-2 px-4 py-2 text-sm rounded-lg border transition-colors ${
          filterUnreplied
            ? 'bg-accent text-white border-accent'
            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
        }`}
      >
        <Filter className="w-4 h-4" />
        {filterUnreplied ? 'Showing Unreplied' : 'Filter Unreplied'}
      </button>
    </div>
  );
}
