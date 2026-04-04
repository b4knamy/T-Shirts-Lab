// Business Entity Types
export const UserRole = {
  CUSTOMER: 'CUSTOMER',
  VENDOR: 'VENDOR',
  MODERATOR: 'MODERATOR',
  ADMIN: 'ADMIN',
  SUPER_ADMIN: 'SUPER_ADMIN',
} as const;
export type UserRole = (typeof UserRole)[keyof typeof UserRole];

export const OrderStatus = {
  PENDING: 'PENDING',
  CONFIRMED: 'CONFIRMED',
  PROCESSING: 'PROCESSING',
  SHIPPED: 'SHIPPED',
  DELIVERED: 'DELIVERED',
  CANCELLED: 'CANCELLED',
  REFUNDED: 'REFUNDED',
} as const;
export type OrderStatus = (typeof OrderStatus)[keyof typeof OrderStatus];

export const PaymentStatus = {
  PENDING: 'PENDING',
  PROCESSING: 'PROCESSING',
  COMPLETED: 'COMPLETED',
  FAILED: 'FAILED',
  REFUNDED: 'REFUNDED',
} as const;
export type PaymentStatus = (typeof PaymentStatus)[keyof typeof PaymentStatus];

export interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  phone?: string;
  role: UserRole;
  is_active: boolean;
  profile_picture_url?: string;
  created_at: string;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  description?: string;
  image_url?: string;
  is_active: boolean;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  slug: string;
  description: string;
  long_description?: string;
  category_id: string;
  category?: Category;
  price: number;
  cost_price?: number;
  discount_price?: number;
  discount_percent?: number;
  stock_quantity: number;
  reserved_quantity: number;
  status: string;
  is_featured: boolean;
  color?: string;
  size?: string;
  images: ProductImage[];
  designs: Design[];
  average_rating?: number;
  reviews_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ProductImage {
  id: string;
  image_url: string;
  alt_text?: string;
  sort_order: number;
  is_primary: boolean;
}

export interface Design {
  id: string;
  name: string;
  description?: string;
  image_url: string;
  category: string;
  is_approved: boolean;
}

export interface Order {
  id: string;
  order_number: string;
  user_id: string;
  subtotal: number;
  discount_amount: number;
  tax_amount: number;
  shipping_cost: number;
  total: number;
  status: OrderStatus;
  payment_status: PaymentStatus;
  tracking_number?: string;
  customer_notes?: string;
  admin_notes?: string;
  items: OrderItem[];
  payment?: Payment;
  created_at: string;
  updated_at?: string;
}

export interface OrderItem {
  id: string;
  product_id: string;
  product?: {
    id: string;
    name: string;
    slug: string;
    price: number;
    image?: string;
  };
  design_id?: string;
  design?: Design;
  quantity: number;
  unit_price: number;
  total_price: number;
  customization_data?: Record<string, unknown>;
}

export interface Payment {
  id: string;
  order_id: string;
  stripe_payment_intent_id?: string;
  amount: number;
  currency: string;
  status: string;
  payment_method?: string;
  refund_amount?: number;
  refunded_at?: string;
  paid_at?: string;
}

export interface CartItem {
  product: Product;
  design_id?: string;
  quantity: number;
}

export interface Coupon {
  id: string;
  code: string;
  description?: string;
  type: 'PERCENTAGE' | 'FIXED';
  value: number;
  min_order_amount?: number;
  max_discount_amount?: number;
  usage_limit?: number;
  usage_count: number;
  per_user_limit: number;
  is_active: boolean;
  is_public: boolean;
  starts_at?: string;
  expires_at?: string;
  created_at: string;
  updated_at: string;
}

export interface AuthTokens {
  access_token: string;
  refresh_token: string;
}

export interface LoginResponse {
  user: User;
  access_token: string;
  refresh_token: string;
}

export interface ProductReview {
  id: string;
  user_id: string;
  product_id: string;
  rating: number;
  comment?: string;
  admin_reply?: string;
  admin_replied_at?: string;
  user: {
    id: string;
    first_name: string;
    last_name: string;
    profile_picture_url?: string;
  };
  created_at: string;
  updated_at: string;
}

export interface ReviewsResponse {
  reviews: ProductReview[];
  average_rating: number;
  total_reviews: number;
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
