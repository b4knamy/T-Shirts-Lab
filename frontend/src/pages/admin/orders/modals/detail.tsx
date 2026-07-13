import { Package, Save, X } from 'lucide-react';
import type { OrderDetailModalProps } from '../types';
import { STATUS_CFG, TRANSITIONS } from '../constants';
import { formatCurrency, getOrderPaymentConfig } from '../utils';

export function OrderDetailModal({
  order,
  newStatus,
  adminNotes,
  isSaving,
  onStatusChange,
  onNotesChange,
  onClose,
  onSave,
}: OrderDetailModalProps) {
  if (!order) {
    return null;
  }

  const paymentCfg = getOrderPaymentConfig(order.payment_status);

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center pt-10 pb-10">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto mx-4">
        <div className="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-100 rounded-t-2xl z-10">
          <h2 className="text-lg font-bold">Order #{order.order_number}</h2>
          <button
            onClick={onClose}
            className="p-1 text-gray-400 hover:text-gray-600 rounded-lg"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="px-6 py-5 space-y-5">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-gray-500">Customer</p>
              <p className="font-medium">{order.user_id.slice(0, 8)}…</p>
            </div>
            <div>
              <p className="text-gray-500">Date</p>
              <p className="font-medium">
                {new Date(order.created_at).toLocaleDateString()}
              </p>
            </div>
            <div>
              <p className="text-gray-500">Total</p>
              <p className="font-bold text-lg">{formatCurrency(order.total)}</p>
            </div>
            <div>
              <p className="text-gray-500">Payment</p>
              <p className={`font-semibold ${paymentCfg.style}`}>
                {paymentCfg.label}
              </p>
            </div>
          </div>

          {order.items && order.items.length > 0 && (
            <div>
              <p className="text-sm font-semibold text-gray-700 mb-2">Items</p>
              <div className="space-y-2">
                {order.items.map((item) => (
                  <div
                    key={item.id}
                    className="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5 text-sm"
                  >
                    <div className="flex items-center gap-3">
                      <Package className="w-4 h-4 text-gray-400" />
                      <div>
                        <p className="font-medium">
                          {item.product?.name ??
                            `Product #${item.product_id.slice(0, 8)}`}
                        </p>
                        <p className="text-xs text-gray-400">
                          Qty: {item.quantity} ×{' '}
                          {formatCurrency(item.unit_price)}
                        </p>
                      </div>
                    </div>
                    <p className="font-semibold">
                      {formatCurrency(item.total_price)}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          )}

          {order.customer_notes && (
            <div>
              <p className="text-sm font-semibold text-gray-700 mb-1">
                Customer Notes
              </p>
              <p className="text-sm text-gray-600 bg-gray-50 rounded-xl px-4 py-2.5">
                {order.customer_notes}
              </p>
            </div>
          )}

          <div className="border-t border-gray-100 pt-4 space-y-1 text-sm">
            <div className="flex justify-between">
              <span className="text-gray-500">Subtotal</span>
              <span>{formatCurrency(order.subtotal)}</span>
            </div>
            {Number(order.discount_amount) > 0 && (
              <div className="flex justify-between text-green-600">
                <span>Discount</span>
                <span>-{formatCurrency(order.discount_amount)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span className="text-gray-500">Tax</span>
              <span>{formatCurrency(order.tax_amount)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">Shipping</span>
              <span>
                {Number(order.shipping_cost) === 0
                  ? 'Free'
                  : formatCurrency(order.shipping_cost)}
              </span>
            </div>
            <div className="flex justify-between font-bold text-base pt-2 border-t">
              <span>Total</span>
              <span>{formatCurrency(order.total)}</span>
            </div>
          </div>

          <div className="border-t border-gray-100 pt-4">
            <p className="text-sm font-semibold text-gray-700 mb-3">
              Update Status
            </p>
            <div className="flex flex-wrap gap-2 mb-4">
              {TRANSITIONS.map((status) => {
                const cfg = STATUS_CFG[status] ?? STATUS_CFG['PENDING'];
                const isActive = newStatus === status;
                return (
                  <button
                    key={status}
                    onClick={() => onStatusChange(status)}
                    className={`text-xs font-medium px-3 py-1.5 rounded-full border transition-all ${
                      isActive
                        ? `${cfg.style} ring-2 ring-offset-1 ring-current`
                        : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'
                    }`}
                  >
                    {cfg.label}
                  </button>
                );
              })}
            </div>

            <label className="block text-sm font-medium text-gray-700 mb-1">
              Admin Notes
            </label>
            <textarea
              value={adminNotes}
              onChange={(event) => onNotesChange(event.target.value)}
              rows={2}
              className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent resize-none"
              placeholder="Internal notes about this order…"
            />
          </div>
        </div>

        <div className="sticky bottom-0 bg-white flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 rounded-b-2xl">
          <button
            onClick={onClose}
            className="px-5 py-2.5 text-sm font-medium border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
          >
            Close
          </button>
          <button
            onClick={onSave}
            disabled={isSaving || newStatus === order.status}
            className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-accent text-white rounded-xl hover:bg-accent-light transition-colors disabled:opacity-50 shadow-md shadow-accent/20"
          >
            {isSaving ? (
              <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            Update Status
          </button>
        </div>
      </div>
    </div>
  );
}
