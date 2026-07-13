import { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Pagination } from '../../../components/common/pagination/Pagination';
import { usePagination } from '../../../hooks';
import { useProductCategories } from './hooks/categories_hook';
import { useProductDelete } from './hooks/delete_hook';
import { useProductFetching } from './hooks/fetch_hook';
import { useProductFilters } from './hooks/filter_hook';
import { useProductForm } from './hooks/form_hook';
import { ProductDeleteModal } from './modals/delete';
import { ProductFormModal } from './modals/form';
import { ProductFilters } from './sections/filters';
import { ProductHeader } from './sections/header';
import { ProductTable } from './sections/table';

export function AdminProducts() {
  const [searchParams, setSearchParams] = useSearchParams();

  const form = useProductForm();
  const deletion = useProductDelete();
  const pagination = usePagination();
  const { categories } = useProductCategories();
  const filter = useProductFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useProductFetching({
    pagination,
    search: filter.search,
    statusFilter: filter.statusFilter,
    categoryFilter: filter.categoryFilter,
  });

  useEffect(() => {
    if (searchParams.get('action') === 'new') {
      form.openNew();
      setSearchParams({}, { replace: true });
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div>
      <ProductHeader total={pagination.total} onCreate={form.openNew} />

      <ProductFilters
        search={filter.search}
        statusFilter={filter.statusFilter}
        categoryFilter={filter.categoryFilter}
        categories={categories}
        onSearchChange={filter.onSearchChange}
        onStatusFilterChange={filter.onStatusFilterChange}
        onCategoryFilterChange={filter.onCategoryFilterChange}
        onClear={filter.clearFilters}
      />

      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <ProductTable
          products={query.products}
          isLoading={query.isLoading}
          onEdit={form.openEdit}
          onDelete={deletion.requestDelete}
        />

        {pagination.total_pages > 1 && (
          <Pagination mode="minimalist" {...pagination} />
        )}
      </div>

      <ProductFormModal
        isOpen={form.isModalOpen}
        editingProduct={form.editingProduct}
        categories={categories}
        form={form.form}
        isSaving={form.isSaving}
        saveError={form.saveError}
        onClose={form.closeModal}
        onChange={form.updateForm}
        onSave={form.saveProduct}
        onRefresh={query.refetch}
      />

      <ProductDeleteModal
        target={deletion.deleteTarget}
        isDeleting={deletion.isDeleting}
        onCancel={deletion.cancelDelete}
        onConfirm={deletion.deleteProduct}
      />
    </div>
  );
}
