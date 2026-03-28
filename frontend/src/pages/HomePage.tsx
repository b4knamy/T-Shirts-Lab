import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, Truck, Shield, Palette } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../store';
import { fetchFeaturedProducts, fetchCategories } from '../store/slices/productSlice';
import { ProductCard } from '../components/common/ProductCard';
import { LoadingSpinner } from '../components/common/LoadingSpinner';

export function HomePage() {
  const dispatch = useAppDispatch();
  const { featuredProducts, categories, isLoading } = useAppSelector((state) => state.products);

  useEffect(() => {
    dispatch(fetchFeaturedProducts(8));
    dispatch(fetchCategories());
  }, [dispatch]);

  return (
    <div>
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-primary to-secondary text-white">
        <div className="max-w-7xl mx-auto px-4 py-20 md:py-32">
          <div className="max-w-2xl">
            <h1 className="text-4xl md:text-6xl font-bold leading-tight">
              Express Yourself with <span className="text-accent">Unique</span> T-Shirts
            </h1>
            <p className="mt-6 text-lg text-gray-300 leading-relaxed">
              Discover our collection of high-quality custom t-shirts with original designs.
              From casual to creative — find the perfect fit for your style.
            </p>
            <div className="mt-8 flex flex-wrap gap-4">
              <Link
                to="/products"
                className="bg-accent hover:bg-accent-light text-white px-8 py-3 rounded-lg font-semibold flex items-center gap-2 transition-colors"
              >
                Shop Now <ArrowRight className="w-5 h-5" />
              </Link>
              <Link
                to="/products?featured=true"
                className="border border-white/30 hover:bg-white/10 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
              >
                View Featured
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="py-12 bg-surface">
        <div className="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
          <div className="flex items-center gap-4 p-6 bg-white rounded-xl shadow-sm">
            <div className="p-3 bg-accent/10 rounded-lg">
              <Truck className="w-6 h-6 text-accent" />
            </div>
            <div>
              <h3 className="font-semibold">Free Shipping</h3>
              <p className="text-sm text-gray-500">On orders over $50</p>
            </div>
          </div>
          <div className="flex items-center gap-4 p-6 bg-white rounded-xl shadow-sm">
            <div className="p-3 bg-accent/10 rounded-lg">
              <Shield className="w-6 h-6 text-accent" />
            </div>
            <div>
              <h3 className="font-semibold">Secure Payment</h3>
              <p className="text-sm text-gray-500">100% secure checkout</p>
            </div>
          </div>
          <div className="flex items-center gap-4 p-6 bg-white rounded-xl shadow-sm">
            <div className="p-3 bg-accent/10 rounded-lg">
              <Palette className="w-6 h-6 text-accent" />
            </div>
            <div>
              <h3 className="font-semibold">Unique Designs</h3>
              <p className="text-sm text-gray-500">Original artist creations</p>
            </div>
          </div>
        </div>
      </section>

      {/* Categories */}
      {categories.length > 0 && (
        <section className="py-16">
          <div className="max-w-7xl mx-auto px-4">
            <h2 className="text-3xl font-bold text-center mb-10">Shop by Category</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {categories.filter((c) => c.isActive).map((category) => (
                <Link
                  key={category.id}
                  to={`/products?categoryId=${category.id}`}
                  className="group relative overflow-hidden rounded-xl aspect-square bg-surface flex items-center justify-center hover:shadow-lg transition-shadow"
                >
                  {category.imageUrl ? (
                    <img src={category.imageUrl} alt={category.name} className="w-full h-full object-cover" />
                  ) : (
                    <div className="bg-gradient-to-br from-primary/10 to-secondary/10 w-full h-full" />
                  )}
                  <div className="absolute inset-0 bg-black/40 group-hover:bg-black/50 transition-colors flex items-center justify-center">
                    <span className="text-white font-semibold text-lg">{category.name}</span>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Featured Products */}
      <section className="py-16 bg-surface">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex items-center justify-between mb-10">
            <h2 className="text-3xl font-bold">Featured Products</h2>
            <Link to="/products?featured=true" className="text-accent hover:text-accent-light font-medium flex items-center gap-1">
              View All <ArrowRight className="w-4 h-4" />
            </Link>
          </div>

          {isLoading ? (
            <LoadingSpinner message="Loading products..." />
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}

          {!isLoading && featuredProducts.length === 0 && (
            <p className="text-center text-gray-500 py-12">No featured products yet. Check back soon!</p>
          )}
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-primary text-white text-center">
        <div className="max-w-2xl mx-auto px-4">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">Ready to Stand Out?</h2>
          <p className="text-gray-300 mb-8">Browse our complete collection and find the perfect t-shirt that speaks to you.</p>
          <Link
            to="/products"
            className="inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-8 py-3 rounded-lg font-semibold transition-colors"
          >
            Explore Collection <ArrowRight className="w-5 h-5" />
          </Link>
        </div>
      </section>
    </div>
  );
}
