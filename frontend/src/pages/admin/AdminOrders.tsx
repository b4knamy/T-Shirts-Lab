import { useEffect, useState, useCallback } from 'react';
import {
  ShoppingCart, ChevronLeft, ChevronRight, Eye, X, Save,
  Clock, CheckCircle2, Truck, XCircle, RefreshCw, Package,
} from 'lucide-react';
import { adminApi } from '../../services/api/admin';
import type { Order } from '../../types';

/* ─── Status config ─────────────────────────────────────────────────────── */
const STATUS_CFG: Record<string, { label: string; style: string; icon: typeof Clock }> = {
  PENDING:    { label: 'Pending',    style: 'bg-yellow-50 text-yellow-700 border-yellow-200', icon: Clock },
  CONFIRMED:  { label: 'Confirmed',  style: 'bg-blue-50 text-blue-700 border-blue-200',      icon: CheckCircle2 },
  PROCESSING: { label: 'Processing', style: 'bg-indigo-50 text-indigo-700 border-indigo-200', icon: RefreshCw },
  SHIPPED:    { label: 'Shipped',    style: 'bg-purple-50 text-purple-700 border-purple-200', icon: Truck },
  DELIVERED:  { label: 'Delivered',  style: 'bg-green-50 text-green-700 border-green-200',    icon: CheckCircle2 },
  CANCELLED:  { label: 'Cancelled',  style: 'bg-red-50 text-red-700 border-red-200',          icon: XCircle },
  REFUNDED:   { label: 'Refunded',   style: 'bg-gray-50 text-gray-700 border-gray-200',       icon: RefreshCw },
};

const PAYMENT_CFG: Record<string, { label: string; style: string }> = {
  PENDING:    { label: 'Awaiting',  style: 'text-yellow-600' },
  PROCESSING: { label: 'Processing', style: 'text-indigo-600' },
  COMPLETED:  { label: 'Paid',       style: 'text-green-600' },
  FAILED:     { label: 'Failed',     style: 'text-red-600' },
  REFUNDED:   { label: 'Refunded',   style: 'text-gray-600' },
};

const TRANSITIONS: string[] = [
  'PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED',
];

export function AdminOrders() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);

  /* Detail / status modal */
  const [selected, setSelected] = useState<Order | null>(null);
  const [newStatus, setNewStatus] = useState('');
  const [adminNotes, setAdminNotes] = useState('');
  const [isSaving, setIsSaving] = useState(false);

  const LIMIT = 15;
  const totalPages = Math.ceil(total / LIMIT);

  const loadOrders = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await adminApi.getOrders({ page, limit: LIMIT });
      setOrders(res.data.data.data || []);
      setTotal(res.data.meta?.total ?? 0);
    } catch {
      // silently fail
    } finally {
      setIsLoading(false);
    }
  }, [page]);

  useEffect(() => { loadOrders(); }, [loadOrders]);

  const openOrder = async (order: Order) => {
    try {
      const res = await adminApi.getOrder(order.id);
      setSelected(res.data.data);
      setNewStatus(res.data.data.status);
      setAdminNotes(res.data.data.admin_notes || '');
    } catch {
      setSelected(order);
      setNewStatus(order.status);
      setAdminNotes('');
    }
  };

  const handleStatusUpdate = async () => {
    if (!selected || newStatus === selected.status) return;
    setIsSaving(true);
    try {
      await adminApi.updateOrderStatus(selected.id, newStatus, adminNotes || undefined);
      setSelected(null);
      loadOrders();
    } catch {
      // silently fail
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div>
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Orders</h1>
        <p className="text-gray-500 mt-1">{total} orders total</p>
      </div>

      {/* Table */}
      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-100">
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Date</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment</th>
                <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={7} className="px-5 py-4"><div className="h-5 bg-gray-100 rounded animate-pulse" /></td>
                  </tr>
                ))
              ) : orders.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-5 py-16 text-center text-gray-400">
                    <ShoppingCart className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                    <p className="font-medium">No orders yet</p>
                  </td>
                </tr>
              ) : (
                orders.map((order) => {
                  const sCfg = STATUS_CFG[order.status] ?? STATUS_CFG['PENDING'];
                  const pCfg = PAYMENT_CFG[order.payment_status] ?? PAYMENT_CFG['PENDING'];
                  return (
                    <tr key={order.id} className="hover:bg-gray-50/50 transition-colors">
                      <td className="px-5 py-3.5">
                        <p className="font-semibold text-gray-900">#{order.order_number}</p>
                        <p className="text-xs text-gray-400">{order.id.slice(0, 8)}…</p>
                      </td>
                      <td className="px-5 py-3.5 hidden md:table-cell text-gray-500">
                        {new Date(order.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                      </td>
                      <td className="px-5 py-3.5 text-center text-gray-600">{order.items?.length ?? '—'}</td>
                      <td className="px-5 py-3.5 text-right font-semibold">${Number(order.total).toFixed(2)}</td>
                      <td className="px-5 py-3.5 text-center">
                        <span className={`inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full border ${sCfg.style}`}>
                          {sCfg.label}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-center">
                        <span className={`text-xs font-semibold ${pCfg.style}`}>{pCfg.label}</span>
                      </td>
                      <td className="px-5 py-3.5 text-right">
                        <button
                          onClick={() => openOrder(order)}
                          className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                          title="View / Update"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">Page {page} of {totalPages}</p>
            <div className="flex gap-1">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1}
                className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <button
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={page >= totalPages}
                className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ─── Order Detail / Status Update Modal ───────────────────────────── */}
      {selected && (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-10 pb-10">
          <div className="absolute inset-0 bg-black/40" onClick={() => setSelected(null)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto mx-4">
            {/* Header */}
            <div className="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-100 rounded-t-2xl z-10">
              <h2 className="text-lg font-bold">Order #{selected.order_number}</h2>
              <button onClick={() => setSelected(null)} className="p-1 text-gray-400 hover:text-gray-600 rounded-lg">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="px-6 py-5 space-y-5">
              {/* Summary */}
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-gray-500">Customer</p>
                  <p className="font-medium">{selected.user_id.slice(0, 8)}…</p>
                </div>
                <div>
                  <p className="text-gray-500">Date</p>
                  <p className="font-medium">{new Date(selected.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <p className="text-gray-500">Total</p>
                  <p className="font-bold text-lg">${Number(selected.total).toFixed(2)}</p>
                </div>
                <div>
                  <p className="text-gray-500">Payment</p>
                  <p className={`font-semibold ${PAYMENT_CFG[selected.payment_status]?.style ?? ''}`}>
                    {PAYMENT_CFG[selected.payment_status]?.label ?? selected.payment_status}
                  </p>
                </div>
              </div>

              {/* Items */}
              {selected.items && selected.items.length > 0 && (
                <div>
                  <p className="text-sm font-semibold text-gray-700 mb-2">Items</p>
                  <div className="space-y-2">
                    {selected.items.map((item) => (
                      <div key={item.id} className="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5 text-sm">
                        <div className="flex items-center gap-3">
                          <Package className="w-4 h-4 text-gray-400" />
                          <div>
                            <p className="font-medium">{item.product?.name ?? `Product #${item.product_id.slice(0, 8)}`}</p>
                            <p className="text-xs text-gray-400">Qty: {item.quantity} × ${Number(item.unit_price).toFixed(2)}</p>
                          </div>
                        </div>
                        <p className="font-semibold">${Number(item.total_price).toFixed(2)}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Customer notes */}
              {selected.customer_notes && (
                <div>
                  <p className="text-sm font-semibold text-gray-700 mb-1">Customer Notes</p>
                  <p className="text-sm text-gray-600 bg-gray-50 rounded-xl px-4 py-2.5">{selected.customer_notes}</p>
                </div>
              )}

              {/* Price breakdown */}
              <div className="border-t border-gray-100 pt-4 space-y-1 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">Subtotal</span><span>${Number(selected.subtotal).toFixed(2)}</span></div>
                {Number(selected.discount_amount) > 0 && (
                  <div className="flex justify-between text-green-600"><span>Discount</span><span>-${Number(selected.discount_amount).toFixed(2)}</span></div>
                )}
                <div className="flex justify-between"><span className="text-gray-500">Tax</span><span>${Number(selected.tax_amount).toFixed(2)}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">Shipping</span><span>{Number(selected.shipping_cost) === 0 ? 'Free' : `$${Number(selected.shipping_cost).toFixed(2)}`}</span></div>
                <div className="flex justify-between font-bold text-base pt-2 border-t"><span>Total</span><span>${Number(selected.total).toFixed(2)}</span></div>
              </div>

              {/* Update status */}
              <div className="border-t border-gray-100 pt-4">
                <p className="text-sm font-semibold text-gray-700 mb-3">Update Status</p>
                <div className="flex flex-wrap gap-2 mb-4">
                  {TRANSITIONS.map((s) => {
                    const cfg = STATUS_CFG[s] ?? STATUS_CFG['PENDING'];
                    const isActive = newStatus === s;
                    return (
                      <button
                        key={s}
                        onClick={() => setNewStatus(s)}
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

                <label className="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                <textarea
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  rows={2}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent resize-none"
                  placeholder="Internal notes about this order…"
                />
              </div>
            </div>

            {/* Footer */}
            <div className="sticky bottom-0 bg-white flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 rounded-b-2xl">
              <button
                onClick={() => setSelected(null)}
                className="px-5 py-2.5 text-sm font-medium border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
              >
                Close
              </button>
              <button
                onClick={handleStatusUpdate}
                disabled={isSaving || newStatus === selected.status}
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
      )}
    </div>
  );
}
