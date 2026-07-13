import { Pagination } from '../../../components/common/pagination/Pagination';
import { usePagination } from '../../../hooks';
import { useOrderDetail } from './hooks/detail_hook';
import { useOrderFetching } from './hooks/fetch_hook';
import { useOrderFilters } from './hooks/filter_hook';
import { OrderDetailModal } from './modals/detail';
import { OrderFilters } from './sections/filters';
import { OrderHeader } from './sections/header';
import { OrderTable } from './sections/table';

export function AdminOrders() {
  const pagination = usePagination();
  const filter = useOrderFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useOrderFetching({
    pagination,
    search: filter.search,
    statusFilter: filter.statusFilter,
    paymentFilter: filter.paymentFilter,
  });
  const detail = useOrderDetail();

  return (
    <div>
      <OrderHeader total={pagination.total} />

      <OrderFilters
        search={filter.search}
        statusFilter={filter.statusFilter}
        paymentFilter={filter.paymentFilter}
        onSearchChange={filter.onSearchChange}
        onStatusFilterChange={filter.onStatusFilterChange}
        onPaymentFilterChange={filter.onPaymentFilterChange}
        onClear={filter.clearFilters}
      />

      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <OrderTable
          orders={query.orders}
          isLoading={query.isLoading}
          onView={detail.openOrder}
        />

        {pagination.total_pages > 1 && (
          <Pagination mode="minimalist" {...pagination} />
        )}
      </div>

      <OrderDetailModal
        order={detail.selectedOrder}
        newStatus={detail.newStatus}
        adminNotes={detail.adminNotes}
        isSaving={detail.isSaving}
        onStatusChange={detail.setNewStatus}
        onNotesChange={detail.setAdminNotes}
        onClose={detail.closeOrder}
        onSave={detail.handleStatusUpdate}
      />
    </div>
  );
}
