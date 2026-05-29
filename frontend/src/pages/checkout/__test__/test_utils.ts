import type { CheckoutDraftState, CheckoutFormData, SelectedCheckoutItem } from '../types';
import type { CartItem, Coupon, Product } from '../../../types';

export function createProduct(overrides: Partial<Product> = {}): Product {
  return {
    id: 'product-1',
    sku: 'TEE-001',
    name: 'Premium Tee',
    slug: 'premium-tee',
    description: 'Soft premium cotton tee',
    category_id: 'category-1',
    price: 20,
    discount_price: 18,
    stock_quantity: 10,
    reserved_quantity: 0,
    status: 'ACTIVE',
    is_featured: false,
    images: [],
    designs: [],
    created_at: '2026-05-04T00:00:00Z',
    updated_at: '2026-05-04T00:00:00Z',
    ...overrides,
  };
}

export function createCartItem(quantity = 2, overrides: Partial<CartItem> = {}): CartItem {
  return {
    product: createProduct(),
    quantity,
    ...overrides,
  };
}

export function createSelectedCheckoutItem(
  quantity = 2,
  checkoutQuantity = quantity,
  overrides: Partial<CartItem> = {},
): SelectedCheckoutItem {
  return {
    ...createCartItem(quantity, overrides),
    checkoutQuantity,
  };
}

export function createCoupon(code = 'SAVE10', overrides: Partial<Coupon> = {}): Coupon {
  return {
    id: 'coupon-1',
    code,
    type: 'FIXED',
    value: 10,
    usage_count: 0,
    per_user_limit: 1,
    is_active: true,
    is_public: true,
    created_at: '2026-05-04T00:00:00Z',
    updated_at: '2026-05-04T00:00:00Z',
    ...overrides,
  };
}

export function createPreloadedState(options?: {
  cartItems?: CartItem[];
  isCartOpen?: boolean;
}) {
  const cartItems = options?.cartItems ?? [createCartItem()];

  return {
    cart: {
      items: cartItems,
      isOpen: options?.isCartOpen ?? false,
    },
  };
}

export function createCheckoutDraftState(options?: {
  cartItems?: CartItem[];
  checkoutItems?: Array<{ productId: string; quantity: number }>;
  draftInitialized?: boolean;
}): CheckoutDraftState {
  const cartItems = options?.cartItems ?? [createCartItem()];

  return {
    items: options?.checkoutItems ?? cartItems.map((item) => ({
      productId: item.product.id,
      quantity: item.quantity,
    })),
    draftInitialized: options?.draftInitialized ?? true,
  };
}

export function createCheckoutFormData(
  overrides: Partial<CheckoutFormData> = {},
): CheckoutFormData {
  return {
    shippingAddress: {
      street: 'Rua das Flores',
      number: '123',
      neighborhood: 'Centro',
      city: 'Sao Paulo',
      state: 'SP',
      zipCode: '12345',
      country: 'BR',
      ...overrides.shippingAddress,
    },
    customerNotes: 'Leave at the front desk',
    ...overrides,
  };
}