import {
  createContext,
  useState,
  useCallback,
  type PropsWithChildren,
} from 'react';
import type { Product, Category } from '../types';
import { productsApi, type ProductQueryParams } from '../services/api/products';

interface ProductContextType {
  products: Product[];
  currentProduct: Product | null;
  categories: Category[];
  featuredProducts: Product[];
  total: number;
  page: number;
  limit: number;
  isLoading: boolean;
  error: string | null;
  fetchProducts: (params?: ProductQueryParams) => Promise<any>;
  fetchProductById: (id: string) => Promise<Product>;
  fetchProductBySlug: (slug: string) => Promise<Product>;
  fetchCategories: () => Promise<Category[]>;
  fetchFeaturedProducts: (limit?: number) => Promise<Product[]>;
  clearCurrentProduct: () => void;
}

export const ProductContext = createContext<ProductContextType | undefined>(
  undefined,
);

export function ProductProvider({ children }: PropsWithChildren) {
  const [products, setProducts] = useState<Product[]>([]);
  const [currentProduct, setCurrentProduct] = useState<Product | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [featuredProducts, setFeaturedProducts] = useState<Product[]>([]);
  const [total, setTotal] = useState<number>(0);
  const [page, setPage] = useState<number>(1);
  const [limit, setLimit] = useState<number>(20);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const clearCurrentProduct = useCallback(() => {
    setCurrentProduct(null);
  }, []);

  const fetchProducts = useCallback(async (params?: ProductQueryParams) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await productsApi.getAll(params);
      const data = response.data.data;
      setProducts(data.data);
      setTotal(data.total);
      setPage(data.page);
      setLimit(data.limit);
      return data;
    } catch (err: unknown) {
      const msg = (err as Error).message || 'Failed to load products';
      setError(msg);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const fetchProductById = useCallback(async (id: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await productsApi.getById(id);
      const product = response.data.data;
      setCurrentProduct(product);
      return product;
    } catch (err: unknown) {
      const msg = (err as Error).message || 'Failed to load product';
      setError(msg);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const fetchProductBySlug = useCallback(async (slug: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await productsApi.getBySlug(slug);
      const product = response.data.data;
      setCurrentProduct(product);
      return product;
    } catch (err: unknown) {
      const msg = (err as Error).message || 'Failed to load product';
      setError(msg);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const fetchCategories = useCallback(async () => {
    try {
      const response = await productsApi.getCategories();
      const data = response.data.data;
      setCategories(data);
      return data;
    } catch (err: unknown) {
      throw err;
    }
  }, []);

  const fetchFeaturedProducts = useCallback(async (limitCount?: number) => {
    setIsLoading(true);
    try {
      const response = await productsApi.getFeatured(limitCount);
      const data = response.data.data;
      setFeaturedProducts(data);
      return data;
    } catch (err: unknown) {
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  return (
    <ProductContext.Provider
      value={{
        products,
        currentProduct,
        categories,
        featuredProducts,
        total,
        page,
        limit,
        isLoading,
        error,
        fetchProducts,
        fetchProductById,
        fetchProductBySlug,
        fetchCategories,
        fetchFeaturedProducts,
        clearCurrentProduct,
      }}
    >
      {children}
    </ProductContext.Provider>
  );
}
