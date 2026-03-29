import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ShoppingCart, Minus, Plus, ArrowLeft, Check } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../store';
import { clearCurrentProduct, fetchProductBySlug } from '../store/slices/productSlice';
import { useCart } from '../hooks/useCart';
import { LoadingSpinner } from '../components/common/LoadingSpinner';

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
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <h1 className="text-2xl font-bold mb-4">Product Not Found</h1>
        <Link to="/products" className="text-accent hover:underline">
          Back to Products
        </Link>
      </div>
    );
  }

  const hasDiscount = product.discountPrice && product.discountPrice < product.price;
  const available = product.stockQuantity - product.reservedQuantity;
  const images = product.images?.length ? product.images : [];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
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
                src={images[selectedImage]?.imageUrl}
                alt={images[selectedImage]?.altText || product.name}
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
                  <img src={img.imageUrl} alt={img.altText || ''} className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Product Info */}
        <div>
          {product.category && (
            <Link to={`/products?categoryId=${product.categoryId}`} className="text-sm text-accent uppercase tracking-wide font-medium">
              {product.category.name}
            </Link>
          )}

          <h1 className="text-3xl font-bold mt-2 mb-4">{product.name}</h1>

          {/* Price */}
          <div className="flex items-center gap-4 mb-6">
            {hasDiscount ? (
              <>
                <span className="text-3xl font-bold text-accent">${Number(product.discountPrice).toFixed(2)}</span>
                <span className="text-xl text-gray-400 line-through">${Number(product.price).toFixed(2)}</span>
                <span className="bg-accent/10 text-accent text-sm font-bold px-3 py-1 rounded">
                  -{product.discountPercent || Math.round((1 - product.discountPrice! / product.price) * 100)}% OFF
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
          {product.longDescription && (
            <div className="border-t pt-6">
              <h3 className="font-semibold mb-3">Details</h3>
              <div className="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                {product.longDescription}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Back button */}
      <div className="mt-12">
        <Link to="/products" className="inline-flex items-center gap-2 text-gray-500 hover:text-accent transition-colors">
          <ArrowLeft className="w-4 h-4" /> Back to Products
        </Link>
      </div>
    </div>
  );
}
