import { useState } from 'react';

export function useStaffFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');

  const onSearchChange = (value: string) => {
    setSearch(value);
    onResetPagination();
  };

  const onRoleFilterChange = (value: string) => {
    setRoleFilter(value);
    onResetPagination();
  };

  return {
    search,
    roleFilter,
    onSearchChange,
    onRoleFilterChange,
  };
}
