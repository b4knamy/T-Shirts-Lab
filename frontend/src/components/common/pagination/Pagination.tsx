import type { PaginationProps } from './type';
import { MinimalistPagination } from './MinimalistPagination';
import { Fragment } from 'react/jsx-runtime';

export function Pagination({ mode, ...pagination}: PaginationProps) {
  return (
    <Fragment>
      { mode === "minimalist" && <MinimalistPagination { ...pagination } /> }
    </Fragment>
  );
}