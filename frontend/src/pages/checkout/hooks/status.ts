import { useSearchParams } from 'react-router-dom';

export function useCheckoutStatus() {
  const [searchParams] = useSearchParams();

  const checkoutStatus = (searchParams.get('checkout') || '').toLowerCase();
  const isCancelledCheckoutStatus = checkoutStatus === 'cancelled' || checkoutStatus === 'canceled';
  const cancelledOrderId = isCancelledCheckoutStatus
    ? searchParams.get('order_id') || searchParams.get('orderId')
    : null;

  return {
    cancelledOrderId,
    isCancelledCheckoutStatus,
  };
}