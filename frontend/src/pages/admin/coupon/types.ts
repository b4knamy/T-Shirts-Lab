import type z from "zod";
import type { PaginationMeta } from "../../../components/common/pagination/type";
import type { Coupon } from "../../../types";
import type { couponSchema } from "./schema";

export type CouponType = "PERCENTAGE" | "FIXED";

export interface CouponFormData {
  code: string;
  description: string;
  type: CouponType;
  value: string;
  min_order_amount: string;
  max_discount_amount: string;
  usage_limit: string;
  per_user_limit: string;
  is_active: boolean;
  is_public: boolean;
  starts_at: string;
  expires_at: string;
}

export type CouponPayload = Partial<Coupon>;

export interface CouponApiError {
  response?: {
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

export interface CouponStatusTag {
  label: string;
  cls: string;
}

export interface UseCouponFetchingOptions {
  pagination: PaginationMeta;
  search: string;
  typeFilter: string;
  statusFilter: string;
}

export interface CouponHeaderProps {
  total: number;
  onCreate: () => void;
}

export interface CouponFiltersProps {
  search: string;
  typeFilter: string;
  statusFilter: string;
  onSearchChange: (value: string) => void;
  onTypeFilterChange: (value: string) => void;
  onStatusFilterChange: (value: string) => void;
  onClear: () => void;
}

export interface CouponTableProps {
  coupons: Coupon[];
  isLoading: boolean;
  onView: (coupon: Coupon) => void;
  onEdit: (coupon: Coupon) => void;
  onDelete: (coupon: Coupon) => void;
}

export interface CouponFormModalProps {
  isOpen: boolean;
  editingCoupon: Coupon | null;
  isSaving: boolean;
  saveError: string | null;
  onClose: () => void;
  onSave: (data: CouponFormData) => void;
}

export interface CouponDeleteModalProps {
  target: Coupon | null;
  error: string | null;
  isDeleting: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}

export interface CouponViewModalProps {
  target: Coupon | null;
  onClose: () => void;
}

export type CouponFormValues = z.infer<typeof couponSchema>;
