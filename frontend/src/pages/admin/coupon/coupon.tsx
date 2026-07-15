import { Pagination } from '../../../components/common/pagination/Pagination';
import { usePagination } from '../../../hooks';
import { useCouponDelete } from './hooks/delete_hook';
import { useCouponFetching } from './hooks/fetch_hook';
import { useCouponFilters } from './hooks/filter_hook';
import { useCouponForm } from './hooks/form_hook';
import { useCouponView } from './hooks/view_hook';
import { CouponDeleteModal } from './modals/delete';
import { CouponFormModal } from './modals/form';
import { CouponViewModal } from './modals/view';
import { CouponFilters } from './sections/filters';
import { CouponHeader } from './sections/header';
import { CouponTable } from './sections/table';

export function AdminCoupons() {
  const form = useCouponForm();
  const deletion = useCouponDelete();
  const view = useCouponView();
  const pagination = usePagination();
  const filter = useCouponFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useCouponFetching({
    pagination,
    search: filter.search,
    typeFilter: filter.typeFilter,
    statusFilter: filter.statusFilter,
  });

  return (
    <div>
      <CouponHeader total={pagination.total} onCreate={form.openNew} />

      <CouponFilters
        search={filter.search}
        typeFilter={filter.typeFilter}
        statusFilter={filter.statusFilter}
        onSearchChange={filter.onSearchChange}
        onTypeFilterChange={filter.onTypeFilterChange}
        onStatusFilterChange={filter.onStatusFilterChange}
        onClear={filter.clearFilters}
      />

      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <CouponTable
          coupons={query.coupons}
          isLoading={query.isLoading}
          onView={view.openView}
          onEdit={form.openEdit}
          onDelete={deletion.requestDelete}
        />

        {pagination.total_pages > 1 && (
          <Pagination mode="minimalist" {...pagination} />
        )}
      </div>

      <CouponViewModal target={view.viewTarget} onClose={view.closeView} />

      <CouponFormModal
        isOpen={form.isModalOpen}
        editingCoupon={form.editingCoupon}
        isSaving={form.isSaving}
        saveError={form.saveError}
        onClose={form.closeModal}
        onSave={form.saveCoupon}
      />

      <CouponDeleteModal
        target={deletion.deleteTarget}
        error={deletion.deleteError}
        isDeleting={deletion.isDeleting}
        onCancel={deletion.cancelDelete}
        onConfirm={deletion.deleteCoupon}
      />
    </div>
  );
}
