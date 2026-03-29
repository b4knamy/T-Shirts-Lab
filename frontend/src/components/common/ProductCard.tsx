import { Link } from 'react-router-dom';
import { ShoppingCart, Heart } from 'lucide-react';
import type { Product } from '../../types';
import { useCart } from '../../hooks/useCart';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const { add } = useCart();
  const primaryImage = product.images?.find((img) => img.isPrimary) || product.images?.[0];
  const hasDiscount = product.discountPrice && product.discountPrice < product.price;

  return (
    <div className="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:-translate-y-1">
      {/* Image */}
      <Link to={`/products/${product.slug}`} className="block relative overflow-hidden">
        <div className="aspect-square bg-surface">
          {primaryImage ? (
            <img
              src={primaryImage.imageUrl}
              alt={primaryImage.altText || product.name}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-gray-400">
              No Image
            </div>
          )}
        </div>

        {/* Badges */}
        <div className="absolute top-3 left-3 flex flex-col gap-1">
          {hasDiscount && (
            <span className="bg-accent text-white text-xs font-bold px-2 py-1 rounded">
              -{product.discountPercent || Math.round((1 - product.discountPrice! / product.price) * 100)}%
            </span>
          )}
          {product.isFeatured && (
            <span className="bg-secondary text-white text-xs font-bold px-2 py-1 rounded">
              Featured
            </span>
          )}
        </div>

        {/* Wishlist */}
        <button
          className="absolute top-3 right-3 p-2 bg-white/80 rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white"
          aria-label="Add to wishlist"
        >
          <Heart className="w-4 h-4 text-gray-600" />
        </button>
      </Link>

      {/* Content */}
      <div className="p-4">
        {product.category && (
          <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">{product.category.name}</p>
        )}
        <Link to={`/products/${product.slug}`}>
          <h3 className="font-medium text-sm line-clamp-2 hover:text-accent transition-colors">
            {product.name}
          </h3>
        </Link>

        <div className="flex items-center justify-between mt-3">
          <div className="flex items-center gap-2">
            {hasDiscount ? (
              <>
                <span className="font-bold text-accent">${Number(product.discountPrice).toFixed(2)}</span>
                <span className="text-sm text-gray-400 line-through">${Number(product.price).toFixed(2)}</span>
              </>
            ) : (
              <span className="font-bold">${Number(product.price).toFixed(2)}</span>
            )}
          </div>

          <button
            onClick={() => add(product)}
            className="p-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors"
            aria-label="Add to cart"
            disabled={product.stockQuantity - product.reservedQuantity <= 0}
          >
            <ShoppingCart className="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  );
}
