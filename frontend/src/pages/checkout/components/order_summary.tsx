import { Check, CreditCard, Loader2, Tag, X } from 'lucide-react';
import type { OrderSummarySectionProps } from '../types';
import { getPrimaryImageUrl } from '../utils';

export function OrderSummarySection({
  coupon,
  draft,
  submission,
  finalTotal,
}: OrderSummarySectionProps) {
  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm sticky top-20">
      <h2 className="font-semibold text-lg mb-4">Order Summary</h2>

      <ul className="space-y-3 mb-4">
        {draft.selectedItems.map((item) => {
          const primaryImageUrl = getPrimaryImageUrl(item.product.images);

          return (
            <li key={item.id} className="flex items-center justify-between gap-3 text-sm">
              <div className="flex items-center gap-3 min-w-0">
                <div className="w-12 h-12 rounded-lg bg-surface overflow-hidden flex-shrink-0 border border-gray-100">
                  {primaryImageUrl ? (
                    <img
                      src={primaryImageUrl}
                      alt={item.product.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-[10px] text-gray-400">
                      No image
                    </div>
                  )}
                </div>

                <span className="text-gray-600 truncate">
                  {item.product.name} × {item.checkoutQuantity}
                </span>
              </div>

              <span className="font-medium flex-shrink-0">
                R${(Number(item.product.discount_price || item.product.price) * item.checkoutQuantity).toFixed(2)}
              </span>
            </li>
          );
        })}
      </ul>

      {!coupon.appliedCoupon ? (
        <div className="mb-4">
          <div className="flex gap-2">
            <input
              type="text"
              value={coupon.code}
              onChange={(event) => coupon.setCode(event.target.value.toUpperCase())}
              placeholder="Coupon code"
              className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-accent"
            />
            <button
              type="button"
              onClick={() => void coupon.handleApplyCoupon()}
              disabled={coupon.couponLoading || !coupon.code.trim()}
              className="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700 disabled:opacity-50 flex items-center gap-1"
            >
              {coupon.couponLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <>
                  <Tag className="w-4 h-4" /> Apply
                </>
              )}
            </button>
          </div>
          {coupon.error && <p className="text-red-500 text-xs mt-1">{coupon.error}</p>}
        </div>
      ) : (
        <div className="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 mb-4">
          <div className="flex items-center gap-2 text-green-700 text-sm">
            <Check className="w-4 h-4" />
            <span className="font-medium">{coupon.appliedCoupon.code}</span>
            <span className="text-green-600">applied</span>
          </div>
          <button
            type="button"
            onClick={coupon.handleRemoveCoupon}
            className="text-gray-400 hover:text-red-500 transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      <div className="border-t pt-4 space-y-2">
        <div className="flex justify-between text-sm">
          <span className="text-gray-500">Subtotal</span>
          <span>R${draft.subtotal.toFixed(2)}</span>
        </div>
        {coupon.discountAmount > 0 && (
          <div className="flex justify-between text-sm text-green-600">
            <span>Discount ({coupon.appliedCoupon?.code})</span>
            <span>-R${coupon.discountAmount.toFixed(2)}</span>
          </div>
        )}
        <div className="flex justify-between text-sm">
          <span className="text-gray-500">Shipping</span>
          <span className={draft.shipping === 0 ? 'text-green-600' : ''}>
            {draft.shipping === 0 ? 'Free' : `R$${draft.shipping.toFixed(2)}`}
          </span>
        </div>
        <div className="flex justify-between font-semibold text-lg pt-2 border-t">
          <span>Total</span>
          <span>R${finalTotal.toFixed(2)}</span>
        </div>
      </div>

      <button
        type="submit"
        disabled={submission.isProcessing}
        className="w-full mt-6 bg-accent hover:bg-accent-light text-white py-3.5 rounded-xl font-semibold transition-all duration-200 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-accent/25 hover:shadow-accent/40"
      >
        {submission.isProcessing ? (
          <>
            <Loader2 className="w-5 h-5 animate-spin" /> Processing...
          </>
        ) : submission.isRedirecting ? (
          <>
            <Loader2 className="w-5 h-5 animate-spin" /> Redirecting...
          </>
        ) : (
          <>
            <CreditCard className="w-5 h-5" /> Place Order
          </>
        )}
      </button>
    </div>
  );
}