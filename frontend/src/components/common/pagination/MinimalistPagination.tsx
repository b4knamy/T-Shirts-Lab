import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { MinimalistPaginationProps } from './type';

export function MinimalistPagination({ page, total_pages, goToPreviousPage, goToNextPage }: MinimalistPaginationProps) {
  return (
    <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100">
      <p className="text-xs text-gray-500">Page {page} of {total_pages}</p>
      <div className="flex gap-1">
        <button
          onClick={goToPreviousPage}
          disabled={page <= 1}
          className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"
        >
          <ChevronLeft className="w-4 h-4" />
        </button>
        <button
          onClick={goToNextPage}
          disabled={page >= total_pages}
          className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"
        >
          <ChevronRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}