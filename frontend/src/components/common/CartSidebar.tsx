import { X, Minus, Plus, Trash2, ShoppingBag } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useCart } from '../../hooks/useCart';

export function CartSidebar() {
  const { items, itemCount, total, isOpen, setOpen, remove, update } = useCart();

  if (!isOpen) return null;

  return (
    <>
      {/* Overlay */}
      <div className="fixed inset-0 bg-black/50 z-40" onClick={() => setOpen(false)} />

      {/* Sidebar */}
      <div className="fixed right-0 top-0 h-full w-full max-w-md bg-white z-50 shadow-2xl flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b">
          <h2 className="font-semibold text-lg flex items-center gap-2">
            <ShoppingBag className="w-5 h-5" />
            Cart ({itemCount})
          </h2>
          <button onClick={() => setOpen(false)} className="p-1 hover:bg-gray-100 rounded-full">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Items */}
        <div className="flex-1 overflow-y-auto p-4">
          {items.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-gray-500">
              <ShoppingBag className="w-16 h-16 mb-4 text-gray-300" />
              <p className="font-medium">Your cart is empty</p>
              <p className="text-sm mt-1">Add some t-shirts to get started!</p>
              <Link
                to="/products"
                onClick={() => setOpen(false)}
                className="mt-4 bg-accent text-white px-6 py-2 rounded-lg hover:bg-accent-light transition-colors text-sm font-medium"
              >
                Browse Products
              </Link>
            </div>
          ) : (
            <ul className="space-y-4">
              {items.map((item) => (
                <li key={item.product.id} className="flex gap-3 pb-4 border-b last:border-0">
                  {/* Image */}
                  <div className="w-20 h-20 bg-surface rounded-lg overflow-hidden flex-shrink-0">
                    {item.product.images?.[0] ? (
                      <img
                        src={item.product.images[0].imageUrl}
                        alt={item.product.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                        No image
                      </div>
                    )}
                  </div>

                  {/* Details */}
                  <div className="flex-1 min-w-0">
                    <h3 className="font-medium text-sm truncate">{item.product.name}</h3>
                    <p className="text-accent font-semibold text-sm mt-1">
                      ${Number(item.product.discountPrice || item.product.price).toFixed(2)}
                    </p>

                    {/* Quantity Controls */}
                    <div className="flex items-center gap-2 mt-2">
                      <button
                        onClick={() => update(item.product.id, item.quantity - 1)}
                        className="w-7 h-7 flex items-center justify-center border rounded hover:bg-gray-100"
                        disabled={item.quantity <= 1}
                      >
                        <Minus className="w-3 h-3" />
                      </button>
                      <span className="text-sm font-medium w-6 text-center">{item.quantity}</span>
                      <button
                        onClick={() => update(item.product.id, item.quantity + 1)}
                        className="w-7 h-7 flex items-center justify-center border rounded hover:bg-gray-100"
                      >
                        <Plus className="w-3 h-3" />
                      </button>
                      <button
                        onClick={() => remove(item.product.id)}
                        className="ml-auto p-1 text-gray-400 hover:text-red-500 transition-colors"
                        aria-label="Remove item"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        {/* Footer */}
        {items.length > 0 && (
          <div className="border-t p-4 space-y-3">
            <div className="flex justify-between font-semibold text-lg">
              <span>Total</span>
              <span>${total.toFixed(2)}</span>
            </div>
            <Link
              to="/checkout"
              onClick={() => setOpen(false)}
              className="block w-full bg-accent hover:bg-accent-light text-white text-center py-3 rounded-lg font-medium transition-colors"
            >
              Checkout
            </Link>
            <button
              onClick={() => setOpen(false)}
              className="block w-full text-center text-sm text-gray-500 hover:text-gray-700"
            >
              Continue Shopping
            </button>
          </div>
        )}
      </div>
    </>
  );
}
