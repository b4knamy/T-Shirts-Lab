import { useState } from "react";

export function useCouponFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");

  const onSearchChange = (value: string) => {
    setSearch(value);
    onResetPagination();
  };

  const onTypeFilterChange = (value: string) => {
    setTypeFilter(value);
    onResetPagination();
  };

  const onStatusFilterChange = (value: string) => {
    setStatusFilter(value);
    onResetPagination();
  };

  const clearFilters = () => {
    setSearch("");
    setTypeFilter("");
    setStatusFilter("");
    onResetPagination();
  };

  return {
    search,
    typeFilter,
    statusFilter,
    onSearchChange,
    onTypeFilterChange,
    onStatusFilterChange,
    clearFilters,
  };
}
