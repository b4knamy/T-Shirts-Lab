import { useEffect, useState } from 'react';
import { useParams, Link, useLocation } from 'react-router-dom';
import {
  ArrowLeft,
  Package,
  CheckCircle2,
  Clock,
  Truck,
  XCircle,
  RefreshCw,
  CreditCard,
  ShoppingBag,
  ReceiptText,
  MapPin,
} from 'lucide-react';
import { ordersApi } from '../services/api';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import type { Order } from '../types';

/* ─── Status config ─────────────────────────────────────────────────────── */
const STATUS_CONFIG: Record<
  string,
  { label: string; color: string; bg: string; icon: React.ComponentType<{ className?: string }> }
> = {
  PENDING:    { label: 'Pending',    color: 'text-yellow-700', bg: 'bg-yellow-50 border-yellow-200',  icon: Clock         },
  CONFIRMED:  { label: 'Confirmed',  color: 'text-blue-700',   bg: 'bg-blue-50 border-blue-200',      icon: CheckCircle2  },
  PROCESSING: { label: 'Processing', color: 'text-indigo-700', bg: 'bg-indigo-50 border-indigo-200',  icon: RefreshCw     },
  SHIPPED:    { label: 'Shipped',    color: 'text-purple-700', bg: 'bg-purple-50 border-purple-200',  icon: Truck         },
  DELIVERED:  { label: 'Delivered',  color: 'text-green-700',  bg: 'bg-green-50 border-green-200',    icon: CheckCircle2  },
  CANCELLED:  { label: 'Cancelled',  color: 'text-red-700',    bg: 'bg-red-50 border-red-200',        icon: XCircle       },
  REFUNDED:   { label: 'Refunded',   color: 'text-gray-700',   bg: 'bg-gray-50 border-gray-200',      icon: RefreshCw     },
};

const PAYMENT_STATUS_CONFIG: Record<string, { label: string; color: string }> = {
  PENDING:    { label: 'Awaiting Payment', color: 'text-yellow-600' },
  PROCESSING: { label: 'Processing',       color: 'text-indigo-600' },
  COMPLETED:  { label: 'Paid',             color: 'text-green-600'  },
  FAILED:     { label: 'Payment Failed',   color: 'text-red-600'    },
  REFUNDED:   { label: 'Refunded',         color: 'text-gray-600'   },
};

/* ─── Order timeline steps ──────────────────────────────────────────────── */
const TIMELINE = ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED'] as const;

function OrderTimeline({ status }: { status: string }) {
  const isCancelled = status === 'CANCELLED' || status === 'REFUNDED';
  const currentIdx = TIMELINE.indexOf(status as (typeof TIMELINE)[number]);

  if (isCancelled) {
    return (
      <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200">
        <XCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
        <span className="text-sm font-medium text-red-700">
          Order {status.charAt(0) + status.slice(1).toLowerCase()}
        </span>
      </div>
    );
  }

  return (
    <div className="relative flex items-center justify-between">
      {/* connecting line */}
      <div className="absolute left-0 right-0 top-4 h-0.5 bg-gray-200 -z-0" />
      <div
        className="absolute left-0 top-4 h-0.5 bg-accent transition-all duration-500 -z-0"
        style={{ width: `${currentIdx >= 0 ? (currentIdx / (TIMELINE.length - 1)) * 100 : 0}%` }}
      />

      {TIMELINE.map((step, idx) => {
        const done    = currentIdx >= idx;
        const current = currentIdx === idx;
        const cfg     = STATUS_CONFIG[step];
        const Icon    = cfg.icon;

        return (
          <div key={step} className="flex flex-col items-center gap-2 z-10">
            <div
              className={`w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all duration-300 ${
                done
                  ? 'bg-accent border-accent text-white'
                  : 'bg-white border-gray-300 text-gray-400'
              } ${current ? 'ring-4 ring-accent/20' : ''}`}
            >
              <Icon className="w-4 h-4" />
            </div>
            <span className={`text-xs font-medium hidden sm:block ${done ? 'text-accent' : 'text-gray-400'}`}>
              {cfg.label}
            </span>
          </div>
        );
      })}
    </div>
  );
}

/* ─── Main component ────────────────────────────────────────────────────── */
export function OrderDetailPage() {
  const { id } = useParams<{ id: string }>();
  const location = useLocation();
  const justCreated = (location.state as { justCreated?: boolean })?.justCreated ?? false;

  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    ordersApi
      .getById(id)
      .then((res) => setOrder(res.data.data))
      .catch(() => setError('Order not found or you do not have access to it.'))
      .finally(() => setIsLoading(false));
  }, [id]);

  if (isLoading) return <LoadingSpinner size="lg" message="Loading order…" />;

  if (error || !order) {
    return (
      <div className="w-full max-w-3xl mx-auto px-6 py-20 text-center">
        <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h1 className="text-2xl font-bold mb-2">Order Not Found</h1>
        <p className="text-gray-500 mb-6">{error}</p>
        <Link to="/orders" className="inline-flex items-center gap-2 bg-accent text-white px-6 py-2.5 rounded-xl hover:bg-accent-light transition-colors">
          <ArrowLeft className="w-4 h-4" /> Back to Orders
        </Link>
      </div>
    );
  }

  const statusCfg  = STATUS_CONFIG[order.status]  ?? STATUS_CONFIG['PENDING'];
  const paymentCfg = PAYMENT_STATUS_CONFIG[order.payment_status] ?? PAYMENT_STATUS_CONFIG['PENDING'];
  const StatusIcon = statusCfg.icon;

  return (
    <div className="w-full max-w-4xl mx-auto px-6 py-10">

      {/* Success banner */}
      {justCreated && (
        <div className="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-2xl mb-8 shadow-sm">
          <CheckCircle2 className="w-5 h-5 flex-shrink-0" />
          <div>
            <p className="font-semibold">Order placed successfully!</p>
            <p className="text-sm text-green-600">We'll notify you as your order progresses.</p>
          </div>
        </div>
      )}

      {/* Back */}
      <Link to="/orders" className="inline-flex items-center gap-2 text-gray-500 hover:text-accent transition-colors text-sm mb-8">
        <ArrowLeft className="w-4 h-4" /> Back to Orders
      </Link>

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold">Order #{order.order_number}</h1>
          <p className="text-gray-500 mt-1 text-sm flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5" />
            Placed on {new Date(order.created_at).toLocaleDateString('en-US', {
              year: 'numeric', month: 'long', day: 'numeric',
            })}
          </p>
        </div>

        <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-semibold ${statusCfg.bg} ${statusCfg.color}`}>
          <StatusIcon className="w-4 h-4" />
          {statusCfg.label}
        </div>
      </div>

      {/* Timeline */}
      <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm mb-6">
        <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-6">Order Progress</h2>
        <OrderTimeline status={order.status} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {/* Items list — takes 2 cols */}
        <div className="lg:col-span-2 space-y-6">

          {/* Items */}
          <div className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-6 py-4 border-b border-gray-100">
              <ShoppingBag className="w-4 h-4 text-accent" />
              <h2 className="font-semibold">
                Items <span className="text-gray-400 font-normal">({order.items.length})</span>
              </h2>
            </div>

            <ul className="divide-y divide-gray-50">
              {order.items.map((item) => (
                <li key={item.id} className="flex gap-4 px-6 py-4">
                  {/* Product image */}
                  <div className="w-16 h-16 rounded-xl bg-surface overflow-hidden flex-shrink-0 border border-gray-100">
                    {item.product?.image ? (
                      <img
                        src={item.product.image}
                        alt={item.product.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-300">
                        <Package className="w-6 h-6" />
                      </div>
                    )}
                  </div>

                  {/* Info */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        {item.product?.slug ? (
                          <Link
                            to={`/products/${item.product.slug}`}
                            className="font-medium text-sm hover:text-accent transition-colors line-clamp-1"
                          >
                            {item.product.name}
                          </Link>
                        ) : (
                          <p className="font-medium text-sm line-clamp-1">
                            {item.product?.name ?? `Product #${item.product_id.slice(0, 8)}`}
                          </p>
                        )}
                        <p className="text-xs text-gray-400 mt-0.5">
                          Qty: {item.quantity} × ${Number(item.unit_price).toFixed(2)}
                        </p>
                        {item.design && (
                          <p className="text-xs text-accent mt-0.5">Design: {item.design.name}</p>
                        )}
                      </div>
                      <p className="font-semibold text-sm flex-shrink-0">
                        ${Number(item.total_price).toFixed(2)}
                      </p>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          </div>

          {/* Customer notes */}
          {order.customer_notes && (
            <div className="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
              <div className="flex items-center gap-2 mb-3">
                <ReceiptText className="w-4 h-4 text-accent" />
                <h2 className="font-semibold">Order Notes</h2>
              </div>
              <p className="text-sm text-gray-600 leading-relaxed">{order.customer_notes}</p>
            </div>
          )}

          {/* Tracking */}
          {order.tracking_number && (
            <div className="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
              <div className="flex items-center gap-2 mb-3">
                <MapPin className="w-4 h-4 text-accent" />
                <h2 className="font-semibold">Tracking</h2>
              </div>
              <p className="text-sm font-mono bg-surface px-3 py-2 rounded-lg inline-block">
                {order.tracking_number}
              </p>
            </div>
          )}
        </div>

        {/* Summary sidebar */}
        <div className="space-y-6">

          {/* Price breakdown */}
          <div className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-6 py-4 border-b border-gray-100">
              <ReceiptText className="w-4 h-4 text-accent" />
              <h2 className="font-semibold">Summary</h2>
            </div>

            <div className="px-6 py-4 space-y-3 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>${Number(order.subtotal).toFixed(2)}</span>
              </div>

              {order.discount_amount > 0 && (
                <div className="flex justify-between text-green-600">
                  <span>Discount</span>
                  <span>-${Number(order.discount_amount).toFixed(2)}</span>
                </div>
              )}

              <div className="flex justify-between text-gray-600">
                <span>Tax (8%)</span>
                <span>${Number(order.tax_amount).toFixed(2)}</span>
              </div>

              <div className="flex justify-between text-gray-600">
                <span>Shipping</span>
                <span>
                  {Number(order.shipping_cost) === 0
                    ? <span className="text-green-600">Free</span>
                    : `$${Number(order.shipping_cost).toFixed(2)}`}
                </span>
              </div>

              <div className="flex justify-between font-bold text-base pt-3 border-t border-gray-100">
                <span>Total</span>
                <span className="text-accent">${Number(order.total).toFixed(2)}</span>
              </div>
            </div>
          </div>

          {/* Payment */}
          <div className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-6 py-4 border-b border-gray-100">
              <CreditCard className="w-4 h-4 text-accent" />
              <h2 className="font-semibold">Payment</h2>
            </div>

            <div className="px-6 py-4 space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-500">Status</span>
                <span className={`font-semibold ${paymentCfg.color}`}>
                  {paymentCfg.label}
                </span>
              </div>

              {order.payment?.payment_method && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Method</span>
                  <span className="font-medium capitalize">{order.payment.payment_method}</span>
                </div>
              )}

              {order.payment?.paid_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Paid at</span>
                  <span className="font-medium">
                    {new Date(order.payment.paid_at).toLocaleDateString('en-US', {
                      month: 'short', day: 'numeric', year: 'numeric',
                    })}
                  </span>
                </div>
              )}

              {order.payment?.refund_amount && (
                <div className="flex justify-between text-gray-500">
                  <span>Refunded</span>
                  <span className="font-medium text-gray-700">
                    ${Number(order.payment.refund_amount).toFixed(2)}
                  </span>
                </div>
              )}
            </div>
          </div>

          {/* Actions */}
          <div className="flex flex-col gap-2">
            <Link
              to="/orders"
              className="flex items-center justify-center gap-2 border border-gray-200 text-gray-700 hover:bg-gray-50 py-2.5 rounded-xl text-sm font-medium transition-colors"
            >
              <Package className="w-4 h-4" /> All Orders
            </Link>
            <Link
              to="/products"
              className="flex items-center justify-center gap-2 bg-accent hover:bg-accent-light text-white py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-md shadow-accent/20"
            >
              <ShoppingBag className="w-4 h-4" /> Continue Shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
