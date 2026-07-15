export interface PaginationState {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  page: number;
  total_pages: number;
  limit: number;
}

export type PaginationHandlers = {
  applyPagination: (meta: Partial<PaginationState>) => void;
  changePage: (next: number | ((page: number) => number)) => void;
  goToPreviousPage: () => void;
  goToNextPage: () => void;
  resetPagination: () => void;
};

export type PaginationMeta = PaginationHandlers & PaginationState;

export type PaginationProps = {
  mode: 'minimalist';
} & PaginationMeta;

export type MinimalistPaginationProps = Pick<
  PaginationMeta,
  'page' | 'total_pages' | 'goToNextPage' | 'goToPreviousPage'
>;

export type PaginateQuery = Pick<PaginationMeta, 'page' | 'limit'>;
