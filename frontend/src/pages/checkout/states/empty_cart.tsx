import { ShoppingBag } from 'lucide-react';
import { Link } from 'react-router-dom';

export function EmptyCartState() {
  return (
    <div className="w-full max-w-4xl mx-auto px-6 py-20 text-center">
      <ShoppingBag className="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <h1 className="text-2xl font-bold mb-2">Your cart is empty</h1>
      <p className="text-gray-500 mb-4">Add some products before checking out.</p>
      <Link to="/products" className="inline-flex bg-accent text-white px-6 py-2 rounded-lg hover:bg-accent-light transition-colors">
        Browse Products
      </Link>
    </div>
  );
}