import { ShoppingBag } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { NoItemsSelectedStateProps } from '../types';

export function NoItemsSelectedState({ onChooseCartItems }: NoItemsSelectedStateProps) {
  return (
    <div className="w-full max-w-4xl mx-auto px-6 py-20 text-center">
      <ShoppingBag className="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <h1 className="text-2xl font-bold mb-2">No items selected</h1>
      <p className="text-gray-500 mb-6">Choose which cart item and quantity you want to buy before continuing.</p>
      <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          type="button"
          onClick={onChooseCartItems}
          className="inline-flex items-center justify-center px-6 py-2 rounded-lg bg-accent text-white hover:bg-accent-light transition-colors"
        >
          Choose Cart Items
        </button>
        <Link
          to="/products"
          className="inline-flex items-center justify-center px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Continue Shopping
        </Link>
      </div>
    </div>
  );
}