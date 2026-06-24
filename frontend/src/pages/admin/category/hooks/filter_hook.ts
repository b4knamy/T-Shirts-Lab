import { useState } from 'react';

export function useCategoryFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  const onSearchChange = (value: string) => {
    setSearch(value);
    onResetPagination();
  };

  const onStatusFilterChange = (value: string) => {
    setStatusFilter(value);
    onResetPagination();
  };

  const clearFilters = () => {
    setSearch('');
    setStatusFilter('');
    onResetPagination();
  };

  return {
    search,
    statusFilter,
    onSearchChange,
    onStatusFilterChange,
    clearFilters,
  };
}
