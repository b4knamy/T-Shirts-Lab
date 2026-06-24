import { X } from "lucide-react";
import { couponValueLabel, formatCouponDate } from "../utils";
import type { CouponViewModalProps } from "../types";

export function CouponViewModal({ target, onClose }: CouponViewModalProps) {
  if (!target) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
        <div className="flex items-center justify-between mb-5">
          <h2 className="text-lg font-bold">{target.code}</h2>
          <button
            onClick={onClose}
            className="p-1 text-gray-400 hover:text-gray-600 rounded-lg"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-gray-500">Type</p>
            <p className="font-medium">{target.type}</p>
          </div>
          <div>
            <p className="text-gray-500">Value</p>
            <p className="font-medium">{couponValueLabel(target)}</p>
          </div>
          <div>
            <p className="text-gray-500">Min Order</p>
            <p className="font-medium">
              {target.min_order_amount
                ? `R$ ${Number(target.min_order_amount).toFixed(2)}`
                : "-"}
            </p>
          </div>
          <div>
            <p className="text-gray-500">Max Discount</p>
            <p className="font-medium">
              {target.max_discount_amount
                ? `R$ ${Number(target.max_discount_amount).toFixed(2)}`
                : "-"}
            </p>
          </div>
          <div>
            <p className="text-gray-500">Usage</p>
            <p className="font-medium">
              {target.usage_count}
              {target.usage_limit ? ` / ${target.usage_limit}` : ""}
            </p>
          </div>
          <div>
            <p className="text-gray-500">Per User Limit</p>
            <p className="font-medium">{target.per_user_limit ?? "-"}</p>
          </div>
          <div>
            <p className="text-gray-500">Starts</p>
            <p className="font-medium">{formatCouponDate(target.starts_at)}</p>
          </div>
          <div>
            <p className="text-gray-500">Expires</p>
            <p className="font-medium">{formatCouponDate(target.expires_at)}</p>
          </div>
          <div>
            <p className="text-gray-500">Visibility</p>
            <p className="font-medium">
              {target.is_public ? "Public" : "Private"}
            </p>
          </div>
          <div>
            <p className="text-gray-500">Active</p>
            <p className="font-medium">{target.is_active ? "Yes" : "No"}</p>
          </div>
          {target.description && (
            <div className="col-span-2">
              <p className="text-gray-500">Description</p>
              <p className="font-medium">{target.description}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
