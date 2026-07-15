import { useContext } from 'react';
import { ProductContext } from '../contexts/product_context';

export function useProducts() {
  const context = useContext(ProductContext);
  if (!context) {
    throw new Error('useProducts must be used inside a ProductProvider');
  }
  return context;
}
