import type { PaginationMeta } from '../../../components/common/pagination/type';
import type { Category } from '../../../types';

// Category Form
export interface CategoryFormData {
  name: string;
  description: string;
  image_url: string;
  is_active: boolean;
}

export interface CategoryApiError {
  response?: {
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

export interface UseCategoryFetchingOptions {
  pagination: PaginationMeta;
  search: string;
  statusFilter: string;
}

export interface CategoryDeleteModalProps {
  target: Category | null;
  error: string | null;
  isDeleting: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}

export interface CategoryTableProps {
  categories: Category[];
  isLoading: boolean;
  onEdit: (category: Category) => void;
  onDelete: (category: Category) => void;
}

export interface CategoryHeaderProps {
  total: number;
  onCreate: () => void;
}

export interface CategoryFiltersProps {
  search: string;
  statusFilter: string;
  onSearchChange: (value: string) => void;
  onStatusFilterChange: (value: string) => void;
  onClear: () => void;
}

export interface CategoryFormModalProps {
  isOpen: boolean;
  editingCategory: Category | null;
  form: CategoryFormData;
  isSaving: boolean;
  saveError: string | null;
  onClose: () => void;
  onSave: () => void;
  onFormChange: <K extends keyof CategoryFormData>(field: K, value: CategoryFormData[K]) => void;
}