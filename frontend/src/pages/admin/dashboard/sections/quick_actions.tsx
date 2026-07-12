import { Link } from 'react-router-dom';
import { Package, ShoppingCart } from 'lucide-react';

export function QuickActions() {
  return (
    <>
      <h2 className="font-semibold text-gray-800 mb-4">Quick Actions</h2>
      <div className="flex flex-wrap gap-3">
        <Link
          to="/admin/products?action=new"
          className="inline-flex items-center gap-2 bg-accent text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-accent-light transition-colors shadow-md shadow-accent/20"
        >
          <Package className="w-4 h-4" /> New Product
        </Link>
        <Link
          to="/admin/orders"
          className="inline-flex items-center gap-2 border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          <ShoppingCart className="w-4 h-4" /> Manage Orders
        </Link>
      </div>
    </>
  );
}
