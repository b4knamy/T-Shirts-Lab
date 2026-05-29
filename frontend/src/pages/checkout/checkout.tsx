import { ArrowLeft } from 'lucide-react';
import { Link } from 'react-router-dom';
import { AdditionalNotesSection } from './components/additional_notes';
import { OrderSummarySection } from './components/order_summary';
import { ShippingAddressSection } from './components/shipping_address';
import { CancelledCheckoutState } from './states/cancelled_checkout';
import { EmptyCartState } from './states/empty_cart';
import { NoItemsSelectedState } from './states/no_items_selected';
import { PreparingCheckoutState } from './states/preparing_checkout';
import { useCheckout } from './hooks/checkout';

export function CheckoutPage() {
  const {
    cart,
    coupon,
    draft,
    finalTotal,
    form,
    status,
    submission,
  } = useCheckout();

  if (status.isCancelledCheckoutStatus) {
    return (
      <CancelledCheckoutState
        cancelledOrderId={status.cancelledOrderId}
        error={submission.error}
        isProcessing={submission.isProcessing}
        onRetryCheckout={submission.handleRetryCheckout}
      />
    );
  }

  if (draft.isPreparingDraft) {
    return <PreparingCheckoutState />;
  }

  if (cart.items.length === 0) {
    return <EmptyCartState />;
  }

  if (draft.selectedItems.length === 0) {
    return <NoItemsSelectedState onChooseCartItems={() => cart.setOpen(true)} />;
  }

  return (
    <div className="w-full max-w-6xl mx-auto px-6 py-10">
      <Link to="/products" className="inline-flex items-center gap-2 text-gray-500 hover:text-accent mb-8">
        <ArrowLeft className="w-4 h-4" /> Continue Shopping
      </Link>

      <h1 className="text-3xl font-bold mb-8">Checkout</h1>

      {submission.error && (
        <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
          {submission.error}
        </div>
      )}

      <form onSubmit={form.handleSubmit(submission.onSubmit)}>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <ShippingAddressSection errors={form.formState.errors} register={form.register} />
            <AdditionalNotesSection register={form.register} />
          </div>

          <div>
            <OrderSummarySection
              coupon={coupon}
              draft={draft}
              finalTotal={finalTotal}
              submission={submission}
            />
          </div>
        </div>
      </form>
    </div>
  );
}