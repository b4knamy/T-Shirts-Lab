import type { ProductFormData } from './types';

export const STATUS_STYLES: Record<string, string> = {
  ACTIVE: 'bg-green-50 text-green-700 border-green-200',
  INACTIVE: 'bg-gray-50 text-gray-600 border-gray-200',
  DRAFT: 'bg-yellow-50 text-yellow-700 border-yellow-200',
  OUT_OF_STOCK: 'bg-red-50 text-red-700 border-red-200',
};

export const EMPTY_PRODUCT_FORM: ProductFormData = {
  name: '',
  description: '',
  long_description: '',
  category_id: '',
  price: 0,
  cost_price: 0,
  discount_price: 0,
  discount_percent: 0,
  stock_quantity: 0,
  sku: '',
  is_featured: false,
  status: 'ACTIVE',
  color: '',
  size: '',
};
