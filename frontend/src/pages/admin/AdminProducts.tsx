import { useEffect, useState, useCallback, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Plus, Search, Edit3, Trash2, X, Save, Package, ImageIcon,
  ChevronLeft, ChevronRight, AlertTriangle, Check, Star,
  Upload, Link as LinkIcon, Crown, Loader2,
} from 'lucide-react';
import { adminApi, type AdminProductPayload } from '../../services/api/admin';
import type { Product, Category, ProductImage } from '../../types';

/* ─── Status badge ──────────────────────────────────────────────────────── */
const STATUS_STYLES: Record<string, string> = {
  ACTIVE:       'bg-green-50 text-green-700 border-green-200',
  INACTIVE:     'bg-gray-50 text-gray-600 border-gray-200',
  DRAFT:        'bg-yellow-50 text-yellow-700 border-yellow-200',
  OUT_OF_STOCK: 'bg-red-50 text-red-700 border-red-200',
};

/* ─── Empty form ────────────────────────────────────────────────────────── */
const EMPTY_FORM: AdminProductPayload = {
  name: '', description: '', long_description: '', category_id: '',
  price: 0, cost_price: 0, discount_price: 0, discount_percent: 0,
  stock_quantity: 0, sku: '', is_featured: false, status: 'ACTIVE',
  color: '', size: '',
};

export function AdminProducts() {
  const [searchParams, setSearchParams] = useSearchParams();

  /* ── List state ────────────────────────────────────────────────────────── */
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  /* ── Modal state ───────────────────────────────────────────────────────── */
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [form, setForm] = useState<AdminProductPayload>(EMPTY_FORM);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  /* ── Delete confirm ────────────────────────────────────────────────────── */
  const [deleteTarget, setDeleteTarget] = useState<Product | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const LIMIT = 15;
  const totalPages = Math.ceil(total / LIMIT);

  /* ── Load data ─────────────────────────────────────────────────────────── */
  const loadProducts = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await adminApi.getProducts({ page, limit: LIMIT, search: search || undefined });
      setProducts(res.data.data.data);
      setTotal(res.data.meta?.total ?? res.data.data.total);
    } catch {
      // silently fail
    } finally {
      setIsLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    adminApi.getCategories().then((r) => setCategories(r.data.data));
  }, []);

  useEffect(() => { loadProducts(); }, [loadProducts]);

  /* Open new product modal from URL param */
  useEffect(() => {
    if (searchParams.get('action') === 'new') {
      openNew();
      setSearchParams({}, { replace: true });
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* ── Handlers ──────────────────────────────────────────────────────────── */
  const openNew = () => {
    setEditingProduct(null);
    setForm(EMPTY_FORM);
    setSaveError(null);
    setIsModalOpen(true);
  };

  const openEdit = (product: Product) => {
    setEditingProduct(product);
    setForm({
      name: product.name,
      description: product.description,
      long_description: product.long_description || '',
      category_id: product.category_id,
      price: product.price,
      cost_price: product.cost_price || 0,
      discount_price: product.discount_price || 0,
      discount_percent: product.discount_percent || 0,
      stock_quantity: product.stock_quantity,
      sku: product.sku || '',
      is_featured: product.is_featured,
      status: product.status,
      color: product.color || '',
      size: product.size || '',
    });
    setSaveError(null);
    setIsModalOpen(true);
  };

  const handleSave = async () => {
    setIsSaving(true);
    setSaveError(null);
    try {
      if (editingProduct) {
        await adminApi.updateProduct(editingProduct.id, form);
      } else {
        await adminApi.createProduct(form);
      }
      setIsModalOpen(false);
      loadProducts();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const errors = e.response?.data?.errors;
      if (errors) {
        setSaveError(Object.values(errors).flat().join('. '));
      } else {
        setSaveError(e.response?.data?.message || 'Failed to save product');
      }
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try {
      await adminApi.deleteProduct(deleteTarget.id);
      setDeleteTarget(null);
      loadProducts();
    } catch {
      // silently fail
    } finally {
      setIsDeleting(false);
    }
  };

  const updateForm = (key: keyof AdminProductPayload, value: unknown) =>
    setForm((f) => ({ ...f, [key]: value }));

  /* ── Stock indicator ───────────────────────────────────────────────────── */
  const stockBadge = (qty: number, reserved: number) => {
    const available = qty - reserved;
    if (available <= 0) return <span className="text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Out of stock</span>;
    if (available <= 10) return <span className="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded-full">Low: {available}</span>;
    return <span className="text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full">{available} available</span>;
  };

  return (
    <div>
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Products</h1>
          <p className="text-gray-500 mt-1">{total} products total</p>
        </div>
        <button
          onClick={openNew}
          className="inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-accent/20"
        >
          <Plus className="w-4 h-4" /> New Product
        </button>
      </div>

      {/* Search */}
      <div className="bg-white border border-gray-100 rounded-2xl mb-6 overflow-hidden">
        <div className="flex items-center px-5 py-3 gap-3">
          <Search className="w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search products by name, SKU…"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="flex-1 outline-none text-sm bg-transparent"
          />
          {search && (
            <button onClick={() => setSearch('')} className="text-gray-400 hover:text-gray-600">
              <X className="w-4 h-4" />
            </button>
          )}
        </div>
      </div>

      {/* Table */}
      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-100">
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">SKU</th>
                <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    <td colSpan={6} className="px-5 py-4"><div className="h-5 bg-gray-100 rounded animate-pulse" /></td>
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
                  const img = product.images?.find((i) => i.is_primary) || product.images?.[0];
                  return (
                    <tr key={product.id} className="hover:bg-gray-50/50 transition-colors">
                      {/* Product */}
                      <td className="px-5 py-3.5">
                        <div className="flex items-center gap-3">
                          <div className="w-11 h-11 rounded-lg bg-surface overflow-hidden flex-shrink-0 border border-gray-100">
                            {img ? (
                              <img src={img.image_url} alt="" className="w-full h-full object-cover" />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center text-gray-300">
                                <ImageIcon className="w-5 h-5" />
                              </div>
                            )}
                          </div>
                          <div className="min-w-0">
                            <p className="font-medium text-gray-900 truncate max-w-[200px]">
                              {product.name}
                              {product.is_featured && <Star className="w-3.5 h-3.5 inline ml-1.5 text-yellow-500 fill-yellow-500" />}
                            </p>
                            <p className="text-xs text-gray-400 truncate max-w-[200px]">{product.category?.name}</p>
                          </div>
                        </div>
                      </td>
                      {/* SKU */}
                      <td className="px-5 py-3.5 hidden md:table-cell">
                        <span className="font-mono text-xs text-gray-500">{product.sku}</span>
                      </td>
                      {/* Price */}
                      <td className="px-5 py-3.5 text-right">
                        <p className="font-semibold">${Number(product.price).toFixed(2)}</p>
                        {product.discount_price ? (
                          <p className="text-xs text-accent">${Number(product.discount_price).toFixed(2)}</p>
                        ) : null}
                      </td>
                      {/* Stock */}
                      <td className="px-5 py-3.5 text-center">
                        {stockBadge(product.stock_quantity, product.reserved_quantity)}
                      </td>
                      {/* Status */}
                      <td className="px-5 py-3.5 text-center">
                        <span className={`inline-block text-xs font-medium px-2.5 py-0.5 rounded-full border ${STATUS_STYLES[product.status] ?? STATUS_STYLES['DRAFT']}`}>
                          {product.status}
                        </span>
                      </td>
                      {/* Actions */}
                      <td className="px-5 py-3.5 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => openEdit(product)}
                            className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                            title="Edit"
                          >
                            <Edit3 className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => setDeleteTarget(product)}
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

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">Page {page} of {totalPages}</p>
            <div className="flex gap-1">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1}
                className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>
              <button
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={page >= totalPages}
                className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ─── Create / Edit Modal ──────────────────────────────────────────── */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-10 pb-10">
          <div className="absolute inset-0 bg-black/40" onClick={() => setIsModalOpen(false)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
            {/* Modal header */}
            <div className="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-gray-100 rounded-t-2xl z-10">
              <h2 className="text-lg font-bold">{editingProduct ? 'Edit Product' : 'New Product'}</h2>
              <button onClick={() => setIsModalOpen(false)} className="p-1 text-gray-400 hover:text-gray-600 rounded-lg">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="px-6 py-5 space-y-5">
              {saveError && (
                <div className="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">
                  <AlertTriangle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                  {saveError}
                </div>
              )}

              {/* Name + SKU */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                  <input
                    value={form.name}
                    onChange={(e) => updateForm('name', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                    placeholder="T-Shirt name"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                  <input
                    value={form.sku}
                    onChange={(e) => updateForm('sku', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent font-mono"
                    placeholder="Auto-generated"
                  />
                </div>
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                <textarea
                  value={form.description}
                  onChange={(e) => updateForm('description', e.target.value)}
                  rows={2}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent resize-none"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Long Description</label>
                <textarea
                  value={form.long_description}
                  onChange={(e) => updateForm('long_description', e.target.value)}
                  rows={3}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent resize-none"
                />
              </div>

              {/* Category + Status */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                  <select
                    value={form.category_id}
                    onChange={(e) => updateForm('category_id', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent bg-white"
                  >
                    <option value="">Select…</option>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select
                    value={form.status}
                    onChange={(e) => updateForm('status', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent bg-white"
                  >
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                    <option value="DRAFT">Draft</option>
                  </select>
                </div>
              </div>

              {/* Pricing */}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                  <input
                    type="number" step="0.01" min="0"
                    value={form.price || ''}
                    onChange={(e) => updateForm('price', parseFloat(e.target.value) || 0)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
                  <input
                    type="number" step="0.01" min="0"
                    value={form.cost_price || ''}
                    onChange={(e) => updateForm('cost_price', parseFloat(e.target.value) || 0)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Discount $</label>
                  <input
                    type="number" step="0.01" min="0"
                    value={form.discount_price || ''}
                    onChange={(e) => updateForm('discount_price', parseFloat(e.target.value) || 0)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
                  <input
                    type="number" step="1" min="0" max="100"
                    value={form.discount_percent || ''}
                    onChange={(e) => updateForm('discount_percent', parseFloat(e.target.value) || 0)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                  />
                </div>
              </div>

              {/* Stock + Variants */}
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                  <input
                    type="number" min="0"
                    value={form.stock_quantity || ''}
                    onChange={(e) => updateForm('stock_quantity', parseInt(e.target.value) || 0)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Color</label>
                  <input
                    value={form.color}
                    onChange={(e) => updateForm('color', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                    placeholder="e.g. Black"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Size</label>
                  <input
                    value={form.size}
                    onChange={(e) => updateForm('size', e.target.value)}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent"
                    placeholder="e.g. M"
                  />
                </div>
              </div>

              {/* Featured */}
              <label className="flex items-center gap-3 cursor-pointer">
                <button
                  type="button"
                  onClick={() => updateForm('is_featured', !form.is_featured)}
                  className={`w-10 h-6 rounded-full transition-colors relative ${form.is_featured ? 'bg-accent' : 'bg-gray-300'}`}
                >
                  <span className={`absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${form.is_featured ? 'left-[18px]' : 'left-0.5'}`} />
                </button>
                <span className="text-sm font-medium text-gray-700">Featured product</span>
              </label>

              {/* Product images manager (edit mode) */}
              {editingProduct && (
                <ImageManager productId={editingProduct.id} images={editingProduct.images || []} onRefresh={loadProducts} />
              )}
            </div>

            {/* Modal footer */}
            <div className="sticky bottom-0 bg-white flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 rounded-b-2xl">
              <button
                onClick={() => setIsModalOpen(false)}
                className="px-5 py-2.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleSave}
                disabled={isSaving}
                className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-accent text-white rounded-xl hover:bg-accent-light transition-colors disabled:opacity-50 shadow-md shadow-accent/20"
              >
                {isSaving ? (
                  <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                ) : (
                  <Save className="w-4 h-4" />
                )}
                {editingProduct ? 'Update' : 'Create'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ─── Delete Confirmation ──────────────────────────────────────────── */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={() => setDeleteTarget(null)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center">
                <AlertTriangle className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-bold text-gray-900">Delete Product</h3>
                <p className="text-sm text-gray-500">This action cannot be undone.</p>
              </div>
            </div>
            <p className="text-sm text-gray-600 mb-6">
              Are you sure you want to delete <strong>{deleteTarget.name}</strong>?
            </p>
            <div className="flex justify-end gap-3">
              <button
                onClick={() => setDeleteTarget(null)}
                className="px-4 py-2 text-sm font-medium border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                disabled={isDeleting}
                className="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors disabled:opacity-50"
              >
                {isDeleting ? (
                  <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                ) : (
                  <Check className="w-4 h-4" />
                )}
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════════════════
   Image Manager — inline component for managing product images
   ═══════════════════════════════════════════════════════════════════════════ */
function ImageManager({ productId, images: initialImages, onRefresh }: {
  productId: string;
  images: ProductImage[];
  onRefresh: () => void;
}) {
  const [images, setImages] = useState<ProductImage[]>(initialImages);
  const [urlInput, setUrlInput] = useState('');
  const [altInput, setAltInput] = useState('');
  const [isAdding, setIsAdding] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [busy, setBusy] = useState<string | null>(null); // imageId being acted on
  const [error, setError] = useState<string | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  const reload = async () => {
    try {
      const res = await adminApi.getProductImages(productId);
      setImages(res.data.data || []);
      onRefresh();
    } catch { /* silent */ }
  };

  const handleAddUrl = async () => {
    if (!urlInput.trim()) return;
    setIsAdding(true); setError(null);
    try {
      await adminApi.addProductImage(productId, { image_url: urlInput.trim(), alt_text: altInput.trim() || undefined });
      setUrlInput(''); setAltInput('');
      await reload();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e.response?.data?.message || 'Failed to add image');
    } finally { setIsAdding(false); }
  };

  const handleUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setIsUploading(true); setError(null);
    try {
      const fd = new FormData();
      fd.append('image', file);
      await adminApi.uploadProductImage(productId, fd);
      await reload();
    } catch (err: unknown) {
      const ex = err as { response?: { data?: { message?: string } } };
      setError(ex.response?.data?.message || 'Upload failed');
    } finally { setIsUploading(false); if (fileRef.current) fileRef.current.value = ''; }
  };

  const handleSetPrimary = async (imageId: string) => {
    setBusy(imageId); setError(null);
    try {
      await adminApi.updateProductImage(productId, imageId, { is_primary: true });
      await reload();
    } catch { setError('Failed to set primary'); } finally { setBusy(null); }
  };

  const handleDeleteImage = async (imageId: string) => {
    setBusy(imageId); setError(null);
    try {
      await adminApi.deleteProductImage(productId, imageId);
      await reload();
    } catch { setError('Failed to delete image'); } finally { setBusy(null); }
  };

  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-2">Images</label>

      {error && (
        <div className="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-xl text-xs mb-3">
          <AlertTriangle className="w-3.5 h-3.5 mt-0.5 flex-shrink-0" />{error}
        </div>
      )}

      {/* Existing images grid */}
      {images.length > 0 && (
        <div className="flex gap-2 flex-wrap mb-3">
          {images.map((img) => (
            <div key={img.id} className="relative group w-20 h-20">
              <img src={img.image_url} alt={img.alt_text || ''} className="w-full h-full rounded-xl object-cover border border-gray-200" />
              {img.is_primary && (
                <span className="absolute -top-1 -right-1 w-5 h-5 bg-accent text-white rounded-full flex items-center justify-center z-10">
                  <Crown className="w-3 h-3" />
                </span>
              )}
              {/* Overlay actions */}
              <div className="absolute inset-0 bg-black/50 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                {busy === img.id ? (
                  <Loader2 className="w-5 h-5 text-white animate-spin" />
                ) : (
                  <>
                    {!img.is_primary && (
                      <button onClick={() => handleSetPrimary(img.id)} className="p-1.5 bg-white/20 rounded-lg hover:bg-white/40 transition-colors" title="Set as primary">
                        <Star className="w-3.5 h-3.5 text-white" />
                      </button>
                    )}
                    <button onClick={() => handleDeleteImage(img.id)} className="p-1.5 bg-white/20 rounded-lg hover:bg-red-500/80 transition-colors" title="Delete">
                      <Trash2 className="w-3.5 h-3.5 text-white" />
                    </button>
                  </>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Add by URL */}
      <div className="flex gap-2 items-end mb-2">
        <div className="flex-1">
          <input value={urlInput} onChange={(e) => setUrlInput(e.target.value)} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-accent" placeholder="Image URL…" />
        </div>
        <div className="w-28">
          <input value={altInput} onChange={(e) => setAltInput(e.target.value)} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:border-accent" placeholder="Alt text" />
        </div>
        <button onClick={handleAddUrl} disabled={isAdding || !urlInput.trim()} className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-40" title="Add by URL">
          {isAdding ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <LinkIcon className="w-3.5 h-3.5" />} Add
        </button>
      </div>

      {/* Upload file */}
      <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" onChange={handleUpload} className="hidden" />
      <button onClick={() => fileRef.current?.click()} disabled={isUploading} className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium border border-dashed border-gray-300 text-gray-500 hover:border-accent hover:text-accent rounded-lg transition-colors disabled:opacity-40">
        {isUploading ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Upload className="w-3.5 h-3.5" />} Upload File
      </button>
    </div>
  );
}
