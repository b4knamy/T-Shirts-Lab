import type { PaginationMeta } from '../../../components/common/pagination/type';
import type { Order } from '../../../types';

// Order Status
export interface OrderStatusConfig {
  label: string;
  style: string;
  icon: typeof import('lucide-react').Clock;
}

export interface OrderPaymentConfig {
  label: string;
  style: string;
}

// Order Fetching
export interface UseOrderFetchingOptions {
  pagination: PaginationMeta;
  search: string;
  statusFilter: string;
  paymentFilter: string;
}

// Order Header
export interface OrderHeaderProps {
  total: number;
}

// Order Filters
export interface OrderFiltersProps {
  search: string;
  statusFilter: string;
  paymentFilter: string;
  onSearchChange: (value: string) => void;
  onStatusFilterChange: (value: string) => void;
  onPaymentFilterChange: (value: string) => void;
  onClear: () => void;
}

// Order Table
export interface OrderTableProps {
  orders: Order[];
  isLoading: boolean;
  onView: (order: Order) => void;
}

// Order Detail Modal
export interface OrderDetailModalProps {
  order: Order | null;
  newStatus: string;
  adminNotes: string;
  isSaving: boolean;
  onStatusChange: (status: string) => void;
  onNotesChange: (notes: string) => void;
  onClose: () => void;
  onSave: () => void;
}
