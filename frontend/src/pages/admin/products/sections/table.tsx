import { Edit3, ImageIcon, Package, Star, Trash2 } from 'lucide-react';
import type { ProductTableProps } from '../types';
import { STATUS_STYLES } from '../constants';

function StockBadge({ qty, reserved }: { qty: number; reserved: number }) {
  const available = qty - reserved;

  if (available <= 0) {
    return (
      <span className="text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
        Out of stock
      </span>
    );
  }

  if (available <= 10) {
    return (
      <span className="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded-full">
        Low: {available}
      </span>
    );
  }

  return (
    <span className="text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
      {available} available
    </span>
  );
}

export function ProductTable({
  products,
  isLoading,
  onEdit,
  onDelete,
}: ProductTableProps) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-gray-100">
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Product
            </th>
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
              SKU
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Price
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Stock
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-50">
          {isLoading ? (
            Array.from({ length: 5 }).map((_, index) => (
              <tr key={index}>
                <td colSpan={6} className="px-5 py-4">
                  <div className="h-5 bg-gray-100 rounded animate-pulse" />
                </td>
              </tr>
            ))
          ) : products.length === 0 ? (
            <tr>
              <td colSpan={6} className="px-5 py-16 text-center text-gray-400">
                <Package className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                <p className="font-medium">No products found</p>
              </td>
            </tr>
          ) : (
            products.map((product) => {
              const img =
                product.images?.find((i) => i.is_primary) ||
                product.images?.[0];
              return (
                <tr
                  key={product.id}
                  className="hover:bg-gray-50/50 transition-colors"
                >
                  <td className="px-5 py-3.5">
                    <div className="flex items-center gap-3">
                      <div className="w-11 h-11 rounded-lg bg-surface overflow-hidden flex-shrink-0 border border-gray-100">
                        {img ? (
                          <img
                            src={img.image_url}
                            alt=""
                            className="w-full h-full object-cover"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center text-gray-300">
                            <ImageIcon className="w-5 h-5" />
                          </div>
                        )}
                      </div>
                      <div className="min-w-0">
                        <p className="font-medium text-gray-900 truncate max-w-[200px]">
                          {product.name}
                          {product.is_featured && (
                            <Star className="w-3.5 h-3.5 inline ml-1.5 text-yellow-500 fill-yellow-500" />
                          )}
                        </p>
                        <p className="text-xs text-gray-400 truncate max-w-[200px]">
                          {product.category?.name}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td className="px-5 py-3.5 hidden md:table-cell">
                    <span className="font-mono text-xs text-gray-500">
                      {product.sku}
                    </span>
                  </td>
                  <td className="px-5 py-3.5 text-right">
                    <p className="font-semibold">
                      ${Number(product.price).toFixed(2)}
                    </p>
                    {product.discount_price ? (
                      <p className="text-xs text-accent">
                        ${Number(product.discount_price).toFixed(2)}
                      </p>
                    ) : null}
                  </td>
                  <td className="px-5 py-3.5 text-center">
                    <StockBadge
                      qty={product.stock_quantity}
                      reserved={product.reserved_quantity}
                    />
                  </td>
                  <td className="px-5 py-3.5 text-center">
                    <span
                      className={`inline-block text-xs font-medium px-2.5 py-0.5 rounded-full border ${STATUS_STYLES[product.status] ?? STATUS_STYLES['DRAFT']}`}
                    >
                      {product.status}
                    </span>
                  </td>
                  <td className="px-5 py-3.5 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => onEdit(product)}
                        className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                        title="Edit"
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => onDelete(product)}
                        className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                        title="Delete"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
}
