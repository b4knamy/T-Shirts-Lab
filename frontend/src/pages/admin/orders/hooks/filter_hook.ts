import { useState } from 'react';

export function useOrderFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentFilter, setPaymentFilter] = useState('');

  const onSearchChange = (value: string) => {
    setSearch(value);
    onResetPagination();
  };

  const onStatusFilterChange = (value: string) => {
    setStatusFilter(value);
    onResetPagination();
  };

  const onPaymentFilterChange = (value: string) => {
    setPaymentFilter(value);
    onResetPagination();
  };

  const clearFilters = () => {
    setSearch('');
    setStatusFilter('');
    setPaymentFilter('');
    onResetPagination();
  };

  return {
    search,
    statusFilter,
    paymentFilter,
    onSearchChange,
    onStatusFilterChange,
    onPaymentFilterChange,
    clearFilters,
  };
}
