import { Search } from 'lucide-react';
import type { StaffFiltersProps } from '../types';

export function StaffFilters({
  search,
  roleFilter,
  onSearchChange,
  onRoleFilterChange,
}: StaffFiltersProps) {
  return (
    <div className="flex flex-wrap items-center gap-4 mb-6">
      <div className="relative">
        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          type="text"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
          placeholder="Search users..."
          className="pl-9 pr-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none w-64"
        />
      </div>
      <select
        value={roleFilter}
        onChange={(event) => onRoleFilterChange(event.target.value)}
        className="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none"
      >
        <option value="">All Roles</option>
        <option value="SUPER_ADMIN">Super Admin</option>
        <option value="ADMIN">Admin</option>
        <option value="MODERATOR">Moderator</option>
        <option value="CUSTOMER">Customer</option>
      </select>
    </div>
  );
}
