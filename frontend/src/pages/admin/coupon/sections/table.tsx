import {
  DollarSign,
  Edit3,
  Eye,
  Globe,
  Lock,
  Percent,
  Ticket,
  Trash2,
} from "lucide-react";
import { couponValueLabel, formatCouponDate, getCouponStatus } from "../utils";
import type { CouponTableProps } from "../types";

export function CouponTable({
  coupons,
  isLoading,
  onView,
  onEdit,
  onDelete,
}: CouponTableProps) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-gray-100">
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Code
            </th>
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
              Discount
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">
              Usage
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Visibility
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">
              Expires
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-50">
          {isLoading ? (
            Array.from({ length: 4 }).map((_, index) => (
              <tr key={index}>
                <td colSpan={7} className="px-5 py-4">
                  <div className="h-5 bg-gray-100 rounded animate-pulse" />
                </td>
              </tr>
            ))
          ) : coupons.length === 0 ? (
            <tr>
              <td colSpan={7} className="px-5 py-16 text-center text-gray-400">
                <Ticket className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                <p className="font-medium">No coupons</p>
              </td>
            </tr>
          ) : (
            coupons.map((coupon) => {
              const status = getCouponStatus(coupon);

              return (
                <tr
                  key={coupon.id}
                  className="hover:bg-gray-50/50 transition-colors"
                >
                  <td className="px-5 py-3.5">
                    <span className="font-mono font-bold text-gray-900">
                      {coupon.code}
                    </span>
                    {coupon.description && (
                      <p className="text-xs text-gray-400 truncate max-w-[200px]">
                        {coupon.description}
                      </p>
                    )}
                  </td>

                  <td className="px-5 py-3.5 hidden md:table-cell">
                    <span className="inline-flex items-center gap-1 font-semibold text-gray-900">
                      {coupon.type === "PERCENTAGE" ? (
                        <Percent className="w-3.5 h-3.5 text-gray-400" />
                      ) : (
                        <DollarSign className="w-3.5 h-3.5 text-gray-400" />
                      )}
                      {couponValueLabel(coupon)}
                    </span>
                    {coupon.min_order_amount && (
                      <p className="text-xs text-gray-400">
                        min R$ {Number(coupon.min_order_amount).toFixed(2)}
                      </p>
                    )}
                  </td>

                  <td className="px-5 py-3.5 text-center hidden lg:table-cell">
                    <span className="text-gray-700 font-medium">
                      {coupon.usage_count}
                    </span>
                    {coupon.usage_limit ? (
                      <span className="text-gray-400">
                        /{coupon.usage_limit}
                      </span>
                    ) : null}
                  </td>

                  <td className="px-5 py-3.5 text-center">
                    {coupon.is_public ? (
                      <span className="inline-flex items-center gap-1 text-xs font-medium text-blue-600">
                        <Globe className="w-3.5 h-3.5" />
                        Public
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                        <Lock className="w-3.5 h-3.5" />
                        Private
                      </span>
                    )}
                  </td>

                  <td className="px-5 py-3.5 text-center">
                    <span
                      className={`inline-block text-xs font-medium px-2.5 py-0.5 rounded-full border ${status.cls}`}
                    >
                      {status.label}
                    </span>
                  </td>

                  <td className="px-5 py-3.5 hidden xl:table-cell text-xs text-gray-500">
                    {formatCouponDate(coupon.expires_at)}
                  </td>

                  <td className="px-5 py-3.5 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => onView(coupon)}
                        className="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                        title="View"
                      >
                        <Eye className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => onEdit(coupon)}
                        className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                        title="Edit"
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => onDelete(coupon)}
                        className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                        title="Delete"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
}
