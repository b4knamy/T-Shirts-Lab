import { useState } from 'react';
import { Pagination } from '../../../components/common/pagination/Pagination';
import { useAuth, usePagination } from '../../../hooks';
import { useStaffFetching } from './hooks/fetch_hook';
import { useStaffFilters } from './hooks/filter_hook';
import { StaffCreateForm } from './sections/create_form';
import { StaffFilters } from './sections/filters';
import { StaffHeader } from './sections/header';
import { StaffTable } from './sections/table';

export function AdminStaff() {
  const { user: currentUser } = useAuth();
  const [showCreateForm, setShowCreateForm] = useState(false);

  const pagination = usePagination();
  const filter = useStaffFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useStaffFetching({
    pagination,
    search: filter.search,
    roleFilter: filter.roleFilter,
  });

  const isSuperAdmin = currentUser?.role === 'SUPER_ADMIN';

  return (
    <div>
      <StaffHeader
        total={pagination.total}
        onCreate={() => setShowCreateForm(true)}
      />

      <StaffFilters
        search={filter.search}
        roleFilter={filter.roleFilter}
        onSearchChange={filter.onSearchChange}
        onRoleFilterChange={filter.onRoleFilterChange}
      />

      {showCreateForm && (
        <StaffCreateForm
          isSuperAdmin={isSuperAdmin}
          onCreated={() => setShowCreateForm(false)}
          onCancel={() => setShowCreateForm(false)}
        />
      )}

      <StaffTable
        users={query.users}
        isLoading={query.isLoading}
        currentUser={currentUser!}
        isSuperAdmin={isSuperAdmin}
      />

      {pagination.total_pages > 1 && (
        <div className="mt-6">
          <Pagination mode="minimalist" {...pagination} />
        </div>
      )}
    </div>
  );
}
