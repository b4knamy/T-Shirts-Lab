// Business Entity Types
export const UserRole = {
  CUSTOMER: 'CUSTOMER',
  VENDOR: 'VENDOR',
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
  firstName: string;
  lastName: string;
  phone?: string;
  role: UserRole;
  isActive: boolean;
  profilePictureUrl?: string;
  createdAt: string;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  description?: string;
  imageUrl?: string;
  isActive: boolean;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  slug: string;
  description: string;
  longDescription?: string;
  categoryId: string;
  category?: Category;
  price: number;
  costPrice?: number;
  discountPrice?: number;
  discountPercent?: number;
  stockQuantity: number;
  reservedQuantity: number;
  status: string;
  isFeatured: boolean;
  color?: string;
  size?: string;
  images: ProductImage[];
  designs: Design[];
  createdAt: string;
}

export interface ProductImage {
  id: string;
  imageUrl: string;
  altText?: string;
  sortOrder: number;
  isPrimary: boolean;
}

export interface Design {
  id: string;
  name: string;
  description?: string;
  imageUrl: string;
  category: string;
  isApproved: boolean;
}

export interface Order {
  id: string;
  orderNumber: string;
  userId: string;
  subtotal: number;
  discountAmount: number;
  taxAmount: number;
  shippingCost: number;
  total: number;
  status: OrderStatus;
  paymentStatus: PaymentStatus;
  trackingNumber?: string;
  customerNotes?: string;
  items: OrderItem[];
  createdAt: string;
}

export interface OrderItem {
  id: string;
  productId: string;
  product?: Product;
  designId?: string;
  quantity: number;
  unitPrice: number;
}

export interface CartItem {
  product: Product;
  designId?: string;
  quantity: number;
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
}

export interface LoginResponse {
  user: User;
  accessToken: string;
  refreshToken: string;
}
