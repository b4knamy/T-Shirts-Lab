import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ShoppingCart, Minus, Plus, ArrowLeft, Check } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../store';
import { clearCurrentProduct, fetchProductBySlug } from '../store/slices/productSlice';
import { useCart } from '../hooks/useCart';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { StarRating } from '../components/common/StarRating';
import { ProductReviews } from '../components/product/ProductReviews';

export function ProductDetailPage() {
  const { slug } = useParams<{ slug: string }>();
  const dispatch = useAppDispatch();
  const { currentProduct: product, isLoading } = useAppSelector((state) => state.products);
  const { add } = useCart();
  const [quantity, setQuantity] = useState(1);
  const [selectedImage, setSelectedImage] = useState(0);
  const [addedToCart, setAddedToCart] = useState(false);

  useEffect(() => {
    if (slug) {
      // We fetch by slug using the id field; the backend resolves by slug endpoint
      dispatch(fetchProductBySlug(slug));
    }
    return () => {
      dispatch(clearCurrentProduct());
    };
  }, [dispatch, slug]);

  const handleAddToCart = () => {
    if (product) {
      add(product, quantity);
      setAddedToCart(true);
      setTimeout(() => setAddedToCart(false), 2000);
    }
  };

  if (isLoading) return <LoadingSpinner size="lg" message="Loading product..." />;

  if (!product) {
    return (
      <div className="w-full max-w-7xl mx-auto px-6 py-20 text-center">
        <h1 className="text-2xl font-bold mb-4">Product Not Found</h1>
        <Link to="/products" className="text-accent hover:underline">
          Back to Products
        </Link>
      </div>
    );
  }

  const hasDiscount = product.discount_price && product.discount_price < product.price;
  const available = product.stock_quantity - product.reserved_quantity;
  const images = product.images?.length ? product.images : [];

  return (
    <div className="w-full max-w-7xl mx-auto px-6 py-10">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-sm text-gray-500 mb-8">
        <Link to="/" className="hover:text-accent">Home</Link>
        <span>/</span>
        <Link to="/products" className="hover:text-accent">Products</Link>
        <span>/</span>
        <span className="text-gray-800">{product.name}</span>
      </nav>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16">
        {/* Images */}
        <div>
          <div className="aspect-square bg-surface rounded-2xl overflow-hidden shadow-sm">
            {images.length > 0 ? (
              <img
                src={images[selectedImage]?.image_url}
                alt={images[selectedImage]?.alt_text || product.name}
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400 text-lg">
                No Image Available
              </div>
            )}
          </div>

          {/* Thumbnails */}
          {images.length > 1 && (
            <div className="flex gap-3 mt-4 overflow-x-auto">
              {images.map((img, idx) => (
                <button
                  key={img.id}
                  onClick={() => setSelectedImage(idx)}
                  className={`w-20 h-20 rounded-lg overflow-hidden border-2 flex-shrink-0 ${
                    selectedImage === idx ? 'border-accent' : 'border-transparent'
                  }`}
                >
                  <img src={img.image_url} alt={img.alt_text || ''} className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Product Info */}
        <div>
          {product.category && (
            <Link to={`/products?categoryId=${product.category_id}`} className="text-sm text-accent uppercase tracking-wide font-medium">
              {product.category.name}
            </Link>
          )}

          <h1 className="text-3xl font-bold mt-2 mb-4">{product.name}</h1>

          {/* Rating Summary */}
          {product.reviews_count !== undefined && product.reviews_count > 0 && (
            <div className="flex items-center gap-2 mb-4">
              <StarRating rating={Math.round(product.average_rating || 0)} size="sm" />
              <span className="text-sm text-gray-500">
                {product.average_rating} ({product.reviews_count} review{product.reviews_count !== 1 ? 's' : ''})
              </span>
            </div>
          )}

          {/* Price */}
          <div className="flex items-center gap-4 mb-6">
            {hasDiscount ? (
              <>
                <span className="text-3xl font-bold text-accent">${Number(product.discount_price).toFixed(2)}</span>
                <span className="text-xl text-gray-400 line-through">${Number(product.price).toFixed(2)}</span>
                <span className="bg-accent/10 text-accent text-sm font-bold px-3 py-1 rounded">
                  -{product.discount_percent || Math.round((1 - product.discount_price! / product.price) * 100)}% OFF
                </span>
              </>
            ) : (
              <span className="text-3xl font-bold">${Number(product.price).toFixed(2)}</span>
            )}
          </div>

          {/* Description */}
          <p className="text-gray-600 leading-relaxed mb-6">{product.description}</p>

          {/* SKU & Stock */}
          <div className="flex flex-wrap gap-4 text-sm text-gray-500 mb-6">
            <span>SKU: {product.sku}</span>
            <span className={available > 0 ? 'text-green-600' : 'text-red-500'}>
              {available > 0 ? `${available} in stock` : 'Out of stock'}
            </span>
          </div>

          {/* Quantity & Add to Cart */}
          {available > 0 && (
            <div className="flex items-center gap-4 mb-8">
              <div className="flex items-center border rounded-lg">
                <button
                  onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                  className="p-3 hover:bg-gray-100"
                >
                  <Minus className="w-4 h-4" />
                </button>
                <span className="px-4 font-medium">{quantity}</span>
                <button
                  onClick={() => setQuantity((q) => Math.min(available, q + 1))}
                  className="p-3 hover:bg-gray-100"
                >
                  <Plus className="w-4 h-4" />
                </button>
              </div>

              <button
                onClick={handleAddToCart}
                className={`flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-white transition-all duration-200 shadow-md ${
                  addedToCart ? 'bg-green-500 shadow-green-500/25' : 'bg-accent hover:bg-accent-light shadow-accent/25 hover:shadow-accent/40'
                }`}
              >
                {addedToCart ? (
                  <>
                    <Check className="w-5 h-5" /> Added to Cart
                  </>
                ) : (
                  <>
                    <ShoppingCart className="w-5 h-5" /> Add to Cart
                  </>
                )}
              </button>
            </div>
          )}

          {/* Extended description */}
          {product.long_description && (
            <div className="border-t pt-6">
              <h3 className="font-semibold mb-3">Details</h3>
              <div className="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                {product.long_description}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Reviews Section */}
      <ProductReviews productId={product.id} />

      {/* Back button */}
      <div className="mt-12">
        <Link to="/products" className="inline-flex items-center gap-2 text-gray-500 hover:text-accent transition-colors">
          <ArrowLeft className="w-4 h-4" /> Back to Products
        </Link>
      </div>
    </div>
  );
}
