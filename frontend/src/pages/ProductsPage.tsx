import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { SlidersHorizontal, Grid3x3, LayoutList } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../store';
import { fetchProducts, fetchCategories } from '../store/slices/productSlice';
import { ProductCard } from '../components/common/ProductCard';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { useDebounce } from '../hooks';

export function ProductsPage() {
  const dispatch = useAppDispatch();
  const { products, categories, total, limit, isLoading } = useAppSelector((state) => state.products);
  const [searchParams, setSearchParams] = useSearchParams();
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [gridView, setGridView] = useState(true);

  const search = searchParams.get('search') || '';
  const categoryId = searchParams.get('categoryId') || '';
  const sortBy = searchParams.get('sortBy') || '';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const debouncedSearch = useDebounce(search, 300);

  useEffect(() => {
    dispatch(fetchCategories());
  }, [dispatch]);

  useEffect(() => {
    dispatch(
      fetchProducts({
        search: debouncedSearch || undefined,
        categoryId: categoryId || undefined,
        sortBy: sortBy || undefined,
        page: currentPage,
        limit: 20,
      }),
    );
  }, [dispatch, debouncedSearch, categoryId, sortBy, currentPage]);

  const updateParam = (key: string, value: string) => {
    const params = new URLSearchParams(searchParams);
    if (value) {
      params.set(key, value);
    } else {
      params.delete(key);
    }
    if (key !== 'page') params.delete('page');
    setSearchParams(params);
  };

  const totalPages = Math.ceil(total / limit);
  return (
    <div className="w-full max-w-7xl mx-auto px-6 py-10">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 className="text-3xl font-bold">
            {categoryId
              ? categories.find((c) => c.id === categoryId)?.name || 'Products'
              : search
                ? `Search: "${search}"`
                : 'All Products'}
          </h1>
          <p className="text-gray-500 mt-1">{total} products found</p>
        </div>

        <div className="flex items-center gap-3">
          {/* Sort */}
          <select
            value={sortBy}
            onChange={(e) => updateParam('sortBy', e.target.value)}
            className="border rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-accent"
          >
            <option value="">Sort by: Default</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="newest">Newest First</option>
            <option value="name_asc">Name: A-Z</option>
          </select>

          {/* View Toggle */}
          <div className="flex border rounded-lg overflow-hidden">
            <button
              onClick={() => setGridView(true)}
              className={`p-2 ${gridView ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'}`}
            >
              <Grid3x3 className="w-4 h-4" />
            </button>
            <button
              onClick={() => setGridView(false)}
              className={`p-2 ${!gridView ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'}`}
            >
              <LayoutList className="w-4 h-4" />
            </button>
          </div>

          {/* Filter Toggle Mobile */}
          <button
            onClick={() => setFiltersOpen(!filtersOpen)}
            className="md:hidden flex items-center gap-2 border rounded-lg px-3 py-2 text-sm hover:bg-gray-50"
          >
            <SlidersHorizontal className="w-4 h-4" /> Filters
          </button>
        </div>
      </div>

      <div className="flex gap-8">
        {/* Sidebar Filters */}
        <aside className={`w-64 flex-shrink-0 ${filtersOpen ? 'block' : 'hidden'} md:block`}>
          <div className="sticky top-20 space-y-6">
            {/* Search */}
            <div>
              <h3 className="font-semibold mb-2">Search</h3>
              <input
                type="text"
                value={search}
                onChange={(e) => updateParam('search', e.target.value)}
                placeholder="Search products..."
                className="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:border-accent"
              />
            </div>

            {/* Categories */}
            <div>
              <h3 className="font-semibold mb-2">Categories</h3>
              <ul className="space-y-1">
                <li>
                  <button
                    onClick={() => updateParam('categoryId', '')}
                    className={`w-full text-left px-3 py-1.5 rounded text-sm ${!categoryId ? 'bg-accent text-white' : 'hover:bg-gray-100'}`}
                  >
                    All Categories
                  </button>
                </li>
                {categories
                  .filter((c) => c.is_active)
                  .map((cat) => (
                    <li key={cat.id}>
                      <button
                        onClick={() => updateParam('categoryId', cat.id)}
                        className={`w-full text-left px-3 py-1.5 rounded text-sm ${categoryId === cat.id ? 'bg-accent text-white' : 'hover:bg-gray-100'}`}
                      >
                        {cat.name}
                      </button>
                    </li>
                  ))}
              </ul>
            </div>
          </div>
        </aside>

        {/* Product Grid */}
        <div className="flex-1">
          {isLoading ? (
            <LoadingSpinner message="Loading products..." />
          ) : products.length === 0 ? (
            <div className="text-center py-20 text-gray-500">
              <p className="text-lg font-medium">No products found</p>
              <p className="text-sm mt-1">Try adjusting your filters or search.</p>
            </div>
          ) : (
            <>
              <div
                className={
                  gridView
                    ? 'grid grid-cols-2 lg:grid-cols-3 gap-6'
                    : 'flex flex-col gap-4'
                }
              >
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="flex justify-center items-center gap-2 mt-10">
                  <button
                    onClick={() => updateParam('page', String(currentPage - 1))}
                    disabled={currentPage <= 1}
                    className="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
                  >
                    Previous
                  </button>
                  {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                    const pageNum = i + 1;
                    return (
                      <button
                        key={pageNum}
                        onClick={() => updateParam('page', String(pageNum))}
                        className={`w-10 h-10 rounded-lg text-sm font-medium ${
                          currentPage === pageNum
                            ? 'bg-accent text-white'
                            : 'border hover:bg-gray-50'
                        }`}
                      >
                        {pageNum}
                      </button>
                    );
                  })}
                  <button
                    onClick={() => updateParam('page', String(currentPage + 1))}
                    disabled={currentPage >= totalPages}
                    className="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
                  >
                    Next
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
