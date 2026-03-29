import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import type { Product, Category } from '../../types';
import { productsApi, type ProductQueryParams } from '../../services/api/products';

interface ProductState {
  products: Product[];
  currentProduct: Product | null;
  categories: Category[];
  featuredProducts: Product[];
  total: number;
  page: number;
  limit: number;
  isLoading: boolean;
  error: string | null;
}

const initialState: ProductState = {
  products: [],
  currentProduct: null,
  categories: [],
  featuredProducts: [],
  total: 0,
  page: 1,
  limit: 20,
  isLoading: false,
  error: null,
};

export const fetchProducts = createAsyncThunk(
  'products/fetchAll',
  async (params: ProductQueryParams | undefined) => {
    const response = await productsApi.getAll(params);
    return response.data.data;
  },
);

export const fetchProductById = createAsyncThunk(
  'products/fetchById',
  async (id: string) => {
    const response = await productsApi.getById(id);
    return response.data.data;
  },
);

export const fetchProductBySlug = createAsyncThunk(
  'products/fetchById',
  async (slug: string) => {
    const response = await productsApi.getBySlug(slug);
    return response.data.data;
  },
);

export const fetchCategories = createAsyncThunk(
  'products/fetchCategories',
  async () => {
    const response = await productsApi.getCategories();
    return response.data.data;
  },
);

export const fetchFeaturedProducts = createAsyncThunk(
  'products/fetchFeatured',
  async (limit?: number) => {
    const response = await productsApi.getFeatured(limit);
    return response.data.data;
  },
);

const productSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {
    clearCurrentProduct: (state) => {
      state.currentProduct = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchProducts.pending, (state) => {
        state.isLoading = true;
        state.error = null;
      })
      .addCase(fetchProducts.fulfilled, (state, action) => {
        state.isLoading = false;
        state.products = action.payload.products;
        state.total = action.payload.total;
        state.page = action.payload.page;
        state.limit = action.payload.limit;
      })
      .addCase(fetchProducts.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.error.message || 'Failed to load products';
      });

    builder
      .addCase(fetchProductById.pending, (state) => {
        state.isLoading = true;
      })
      .addCase(fetchProductById.fulfilled, (state, action) => {
        state.isLoading = false;
        state.currentProduct = action.payload;
      })
      .addCase(fetchProductById.rejected, (state, action) => {
        state.isLoading = false;
        state.error = action.error.message || 'Failed to load product';
      });

    builder
      .addCase(fetchCategories.fulfilled, (state, action) => {
        state.categories = action.payload;
      });

    builder
      .addCase(fetchFeaturedProducts.fulfilled, (state, action) => {
        state.featuredProducts = action.payload;
      });
  },
});

export const { clearCurrentProduct } = productSlice.actions;
export default productSlice.reducer;
