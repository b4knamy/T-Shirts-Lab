import type { PaginationMeta } from '../../../components/common/pagination/type';
import type { AdminProductPayload } from '../../../services/api/admin';
import type { Category, Product, ProductImage } from '../../../types';

export type ProductFormData = AdminProductPayload;

export interface ProductApiError {
  response?: {
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

// Product Fetching
export interface UseProductFetchingOptions {
  pagination: PaginationMeta;
  search: string;
  statusFilter: string;
  categoryFilter: string;
}

// Product Header
export interface ProductHeaderProps {
  total: number;
  onCreate: () => void;
}

// Product Filters
export interface ProductFiltersProps {
  search: string;
  statusFilter: string;
  categoryFilter: string;
  categories: Category[];
  onSearchChange: (value: string) => void;
  onStatusFilterChange: (value: string) => void;
  onCategoryFilterChange: (value: string) => void;
  onClear: () => void;
}

// Product Table
export interface ProductTableProps {
  products: Product[];
  isLoading: boolean;
  onEdit: (product: Product) => void;
  onDelete: (product: Product) => void;
}

// Product Form Modal
export interface ProductFormModalProps {
  isOpen: boolean;
  editingProduct: Product | null;
  categories: Category[];
  form: ProductFormData;
  isSaving: boolean;
  saveError: string | null;
  onClose: () => void;
  onChange: <K extends keyof ProductFormData>(
    field: K,
    value: ProductFormData[K],
  ) => void;
  onSave: () => void;
  onRefresh: () => void;
}

// Product Delete Modal
export interface ProductDeleteModalProps {
  target: Product | null;
  isDeleting: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}

// Product Image Manager
export interface UseProductImageManagerOptions {
  productId: string;
  initialImages: ProductImage[];
  onRefresh: () => void;
}

export interface ProductImageManagerProps {
  productId: string;
  images: ProductImage[];
  onRefresh: () => void;
}
