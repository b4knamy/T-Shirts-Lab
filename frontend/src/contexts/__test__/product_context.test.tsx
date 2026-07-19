import { renderHook, act } from '@testing-library/react';
import { describe, expect, it, beforeEach, vi } from 'vitest';
import { ProductProvider } from '../product_context';
import { useProducts } from '../../hooks/useProducts';
import type { PropsWithChildren } from 'react';

const { getAllMock, getByIdMock, getBySlugMock, getCategoriesMock, getFeaturedMock } = vi.hoisted(() => ({
  getAllMock: vi.fn(),
  getByIdMock: vi.fn(),
  getBySlugMock: vi.fn(),
  getCategoriesMock: vi.fn(),
  getFeaturedMock: vi.fn(),
}));

vi.mock('../../services/api/products', () => ({
  productsApi: {
    getAll: getAllMock,
    getById: getByIdMock,
    getBySlug: getBySlugMock,
    getCategories: getCategoriesMock,
    getFeatured: getFeaturedMock,
  },
}));

describe('ProductContext Integration', () => {
  beforeEach(() => {
    getAllMock.mockReset();
    getByIdMock.mockReset();
    getBySlugMock.mockReset();
    getCategoriesMock.mockReset();
    getFeaturedMock.mockReset();
  });

  it('should initialize with empty states', () => {
    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    expect(result.current.products).toEqual([]);
    expect(result.current.currentProduct).toBeNull();
    expect(result.current.categories).toEqual([]);
    expect(result.current.featuredProducts).toEqual([]);
    expect(result.current.total).toBe(0);
    expect(result.current.page).toBe(1);
    expect(result.current.limit).toBe(20);
    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBeNull();
  });

  it('should fetch products successfully', async () => {
    const mockProducts = [{ id: 'p-1', name: 'Product 1' }] as any;
    getAllMock.mockResolvedValue({
      data: {
        data: {
          data: mockProducts,
          total: 10,
          page: 1,
          limit: 10,
        },
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    let fetchResult: any;
    await act(async () => {
      fetchResult = await result.current.fetchProducts();
    });

    expect(fetchResult.data).toEqual(mockProducts);
    expect(result.current.products).toEqual(mockProducts);
    expect(result.current.total).toBe(10);
    expect(result.current.page).toBe(1);
    expect(result.current.limit).toBe(10);
    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBeNull();
  });

  it('should handle fetch products error', async () => {
    getAllMock.mockRejectedValue(new Error('Network error'));

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    await act(async () => {
      await expect(result.current.fetchProducts()).rejects.toThrow('Network error');
    });

    expect(result.current.products).toEqual([]);
    expect(result.current.isLoading).toBe(false);
    expect(result.current.error).toBe('Network error');
  });

  it('should fetch product by id successfully', async () => {
    const mockProduct = { id: 'p-1', name: 'Product 1' } as any;
    getByIdMock.mockResolvedValue({
      data: {
        data: mockProduct,
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    let fetchResult: any;
    await act(async () => {
      fetchResult = await result.current.fetchProductById('p-1');
    });

    expect(fetchResult).toEqual(mockProduct);
    expect(result.current.currentProduct).toEqual(mockProduct);
    expect(result.current.isLoading).toBe(false);
  });

  it('should fetch product by slug successfully', async () => {
    const mockProduct = { id: 'p-1', name: 'Product 1', slug: 'product-1' } as any;
    getBySlugMock.mockResolvedValue({
      data: {
        data: mockProduct,
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    let fetchResult: any;
    await act(async () => {
      fetchResult = await result.current.fetchProductBySlug('product-1');
    });

    expect(fetchResult).toEqual(mockProduct);
    expect(result.current.currentProduct).toEqual(mockProduct);
    expect(result.current.isLoading).toBe(false);
  });

  it('should fetch categories successfully', async () => {
    const mockCategories = [{ id: 'c-1', name: 'Category 1' }] as any;
    getCategoriesMock.mockResolvedValue({
      data: {
        data: mockCategories,
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    let fetchResult: any;
    await act(async () => {
      fetchResult = await result.current.fetchCategories();
    });

    expect(fetchResult).toEqual(mockCategories);
    expect(result.current.categories).toEqual(mockCategories);
  });

  it('should fetch featured products successfully', async () => {
    const mockFeatured = [{ id: 'p-1', name: 'Product 1', is_featured: true }] as any;
    getFeaturedMock.mockResolvedValue({
      data: {
        data: mockFeatured,
      },
    });

    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    let fetchResult: any;
    await act(async () => {
      fetchResult = await result.current.fetchFeaturedProducts(5);
    });

    expect(fetchResult).toEqual(mockFeatured);
    expect(result.current.featuredProducts).toEqual(mockFeatured);
    expect(result.current.isLoading).toBe(false);
  });

  it('should clear current product', () => {
    const wrapper = ({ children }: PropsWithChildren) => (
      <ProductProvider>{children}</ProductProvider>
    );
    const { result } = renderHook(() => useProducts(), { wrapper });

    act(() => {
      result.current.clearCurrentProduct();
    });

    expect(result.current.currentProduct).toBeNull();
  });
});
