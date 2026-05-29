import type { CartItem } from '../../types';
import type { CheckoutDraftItem, SelectedCheckoutItem } from './types';

export const buildSelectedCheckoutItems = (
  cartItems: CartItem[],
  checkoutDraftItems: CheckoutDraftItem[],
): SelectedCheckoutItem[] => {
  const draftQuantities = new Map(checkoutDraftItems.map((item) => [item.productId, item.quantity]));

  return cartItems.flatMap((item) => {
    const selectedQuantity = draftQuantities.get(item.product.id);

    if (!selectedQuantity) {
      return [];
    }

    return [{
      ...item,
      checkoutQuantity: Math.min(selectedQuantity, item.quantity),
    }];
  });
};

export const getPrimaryImageUrl = (
  productImages: { image_url: string; is_primary: boolean }[] | undefined,
): string | null => {
  if (!productImages || productImages.length === 0) {
    return null;
  }

  const primary = productImages.find((image) => image.is_primary);

  return primary?.image_url || productImages[0].image_url || null;
};

export const resolveErrorMessage = (err: unknown): string => {
  const error = err as { response?: { data?: { message?: string; error?: { message?: string } } }; message?: string };

  return error.response?.data?.message
    || error.response?.data?.error?.message
    || error.message
    || 'Failed to process order. Please try again.';
};