import { useCallback, useState } from 'react';
import type { PaginationMeta, PaginationState } from '../components/common/pagination/type';

const INITIAL_STATE = {
    current_page: 1,
    per_page: 1,
    total: 0,
    last_page: 1,
    from: 1,
    to: 1,
    limit: 20,
    total_pages: 1,
    page: 1,
}

export function usePagination() {

  const [paginate, setPaginate] = useState<PaginationState>(INITIAL_STATE);

  function applyPagination(meta: Partial<PaginationState>) {
      setPaginate((currentState) => {
        return mapToPaginationState({
          ...currentState,
          ...meta
        })
      })
  }

  const goToPreviousPage = () => {
    if (paginate.page > 1) {
      applyPagination({ page: paginate.page - 1 })
    }
  }

  const goToNextPage = () => {
    if (paginate.page <= paginate.last_page) {
      applyPagination({ page: paginate.page + 1 })
    }
  }

  const changePage = (page: number) => {
    if (page === paginate.current_page) {
      return;
    }

    applyPagination({ page })
  }

  const resetPagination = useCallback(() => {
    setPaginate(INITIAL_STATE)
  }, []);

  return {
    ...paginate,
    changePage,
    applyPagination,
    goToPreviousPage,
    goToNextPage,
    resetPagination,
  } as PaginationMeta;
}


function mapToPaginationState(meta: PaginationState): PaginationState {
  const page = normalizePaginationValue(meta.page, 1);
  const limit = normalizePaginationValue(meta.limit, 1);
  const total = Math.max(0, Math.floor(meta.total));
  const totalPages = normalizePaginationValue(meta.total_pages, 1);

  const from = total === 0 ? null : (page - 1) * limit + 1;
  const to = total === 0 ? null : Math.min(total, page * limit);

  return {
    current_page: page,
    last_page: totalPages,
    per_page: limit,
    total,
    from,
    to,
    page,
    total_pages: totalPages,
    limit,
  };
}

function normalizePaginationValue(value: number, fallback: number): number {
  if (!Number.isFinite(value)) {
    return fallback;
  }

  const normalized = Math.floor(value);

  return normalized > 0 ? normalized : fallback;
}