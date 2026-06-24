import { CategoryDeleteModal } from './modals/delete';
import { CategoryFilters } from './sections/filters';
import { CategoryFormModal } from './modals/form';
import { CategoryHeader } from './sections/header';
import { CategoryTable } from './sections/table';
import { Pagination } from '../../../components/common/pagination/Pagination';
import { usePagination } from '../../../hooks';
import { useCategoryFetching } from './hooks/fetch_hook';
import { useCategoryDelete } from './hooks/delete_hook';
import { useCategoryForm } from './hooks/form_hook';
import { useCategoryFilters } from './hooks/filter_hook';

export function AdminCategory() {
  const form = useCategoryForm();
  const deletion = useCategoryDelete();
  const pagination = usePagination();
  const filter = useCategoryFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useCategoryFetching({
    pagination,
    search: filter.search,
    statusFilter: filter.statusFilter,
  });

  return (
    <div>
      <CategoryHeader total={pagination.total} onCreate={form.openNew} />

      <CategoryFilters
        search={filter.search}
        statusFilter={filter.statusFilter}
        onSearchChange={filter.onSearchChange}
        onStatusFilterChange={filter.onStatusFilterChange}
        onClear={filter.clearFilters}
      />

      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <CategoryTable
          categories={query.categories}
          isLoading={query.isLoading}
          onEdit={form.openEdit}
          onDelete={deletion.requestDelete}
        />

        {pagination.total_pages > 1 && (
          <Pagination mode="minimalist" {...pagination} />
        )}
      </div>

      <CategoryFormModal
        isOpen={form.isModalOpen}
        editingCategory={form.editingCategory}
        form={form.form}
        isSaving={form.isSaving}
        saveError={form.saveError}
        onClose={form.closeModal}
        onSave={form.saveCategory}
        onFormChange={form.updateForm}
      />

      <CategoryDeleteModal
        target={deletion.deleteTarget}
        error={deletion.deleteError}
        isDeleting={deletion.isDeleting}
        onCancel={deletion.cancelDelete}
        onConfirm={deletion.deleteCategory}
      />
    </div>
  );
}
