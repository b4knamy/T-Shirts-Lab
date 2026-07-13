import { useState } from 'react';

export function useProductFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');

  const onSearchChange = (value: string) => {
    setSearch(value);
    onResetPagination();
  };

  const onStatusFilterChange = (value: string) => {
    setStatusFilter(value);
    onResetPagination();
  };

  const onCategoryFilterChange = (value: string) => {
    setCategoryFilter(value);
    onResetPagination();
  };

  const clearFilters = () => {
    setStatusFilter('');
    setCategoryFilter('');
    onResetPagination();
  };

  return {
    search,
    statusFilter,
    categoryFilter,
    onSearchChange,
    onStatusFilterChange,
    onCategoryFilterChange,
    clearFilters,
  };
}
