import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useCart } from '../../../hooks/useCart';
import { useCoupon } from './coupon';
import { useCheckoutDraft } from './draft';
import { useCheckoutStatus } from './status';
import { useCheckoutSubmission } from './submission';
import { checkoutSchema } from '../schemas';
import type { CheckoutFormData } from '../types';

export function useCheckout() {
  const cart = useCart();
  const status = useCheckoutStatus();
  const draft = useCheckoutDraft(cart.items, status.isCancelledCheckoutStatus);
  const coupon = useCoupon(draft.subtotal);
  const submission = useCheckoutSubmission({
    cancelledOrderId: status.cancelledOrderId,
    couponCode: coupon.appliedCoupon?.code,
    selectedItems: draft.selectedItems,
  });
  const finalTotal = Math.max(
    0,
    draft.subtotal - coupon.discountAmount + draft.shipping,
  );

  const form = useForm<CheckoutFormData>({
    resolver: zodResolver(checkoutSchema),
    defaultValues: {
      shippingAddress: { country: 'BR' },
    },
  });

  return {
    cart,
    coupon,
    draft,
    finalTotal,
    form,
    status,
    submission,
  };
}
