import { AlertTriangle, CreditCard, Loader2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { CancelledCheckoutStateProps } from '../types';

export function CancelledCheckoutState({
  cancelledOrderId,
  error,
  isProcessing,
  isRedirecting,
  onRetryCheckout,
}: CancelledCheckoutStateProps) {
  return (
    <div className="w-full max-w-4xl mx-auto px-6 py-20 text-center">
      <AlertTriangle className="w-16 h-16 text-amber-400 mx-auto mb-4" />
      <h1 className="text-2xl font-bold mb-2">Checkout cancelled</h1>
      <p className="text-gray-500 mb-2">Your payment was not completed.</p>
      <p className="text-gray-500 mb-6">
        {cancelledOrderId
          ? 'You can try paying again for this order without rebuilding your cart.'
          : 'You can return to your orders and try payment again from there.'}
      </p>

      {error && (
        <div className="max-w-xl mx-auto bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
          {error}
        </div>
      )}

      <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
        {cancelledOrderId ? (
          <>
            <button
              type="button"
              onClick={onRetryCheckout}
              disabled={isProcessing || isRedirecting}
              className="inline-flex items-center justify-center gap-2 bg-accent text-white px-6 py-2 rounded-lg hover:bg-accent-light transition-colors disabled:opacity-50"
            >
              {isProcessing || isRedirecting ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" /> Redirecting...
                </>
              ) : (
                <>
                  <CreditCard className="w-4 h-4" /> Try payment again
                </>
              )}
            </button>
            <Link
              to={`/orders/${cancelledOrderId}`}
              className="inline-flex items-center justify-center px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
            >
              View order
            </Link>
          </>
        ) : (
          <Link
            to="/orders"
            className="inline-flex items-center justify-center px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
          >
            View my orders
          </Link>
        )}
      </div>
    </div>
  );
}