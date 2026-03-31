import { useEffect, useState, useCallback } from 'react';
import {
  Plus, Edit3, Trash2, X, Save, Ticket, AlertTriangle, Check,
  ChevronLeft, ChevronRight, Eye, Percent, DollarSign, Globe, Lock,
} from 'lucide-react';
import { adminApi } from '../../services/api/admin';
import type { Coupon } from '../../types';

const EMPTY_FORM = {
  code: '', description: '', type: 'PERCENTAGE' as 'PERCENTAGE' | 'FIXED',
  value: '', min_order_amount: '', max_discount_amount: '',
  usage_limit: '', per_user_limit: '', is_active: true, is_public: false,
  starts_at: '', expires_at: '',
};

function formatDate(d?: string | null) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function couponStatus(c: Coupon) {
  if (!c.is_active) return { label: 'Inactive', cls: 'bg-gray-50 text-gray-500 border-gray-200' };
  if (c.expires_at && new Date(c.expires_at) < new Date()) return { label: 'Expired', cls: 'bg-red-50 text-red-600 border-red-200' };
  if (c.starts_at && new Date(c.starts_at) > new Date()) return { label: 'Scheduled', cls: 'bg-blue-50 text-blue-600 border-blue-200' };
  return { label: 'Active', cls: 'bg-green-50 text-green-700 border-green-200' };
}

function toLocalInput(iso?: string | null) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.getFullYear() + '-' +
    String(d.getMonth() + 1).padStart(2, '0') + '-' +
    String(d.getDate()).padStart(2, '0') + 'T' +
    String(d.getHours()).padStart(2, '0') + ':' +
    String(d.getMinutes()).padStart(2, '0');
}

export function AdminCoupons() {
  const [coupons, setCoupons] = useState<Coupon[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editing, setEditing] = useState<Coupon | null>(null);
  const [form, setForm] = useState(EMPTY_FORM);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const [viewTarget, setViewTarget] = useState<Coupon | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Coupon | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const LIMIT = 20;
  const totalPages = Math.ceil(total / LIMIT);

  const load = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await adminApi.getCoupons({ page, limit: LIMIT });
      setCoupons(res.data.data.data || []);
      setTotal(res.data.meta?.total ?? 0);
    } catch { /* silent */ } finally { setIsLoading(false); }
  }, [page]);

  useEffect(() => { load(); }, [load]);

  const openNew = () => { setEditing(null); setForm(EMPTY_FORM); setSaveError(null); setIsModalOpen(true); };
  const openEdit = (c: Coupon) => {
    setEditing(c);
    setForm({
      code: c.code, description: c.description || '', type: c.type,
      value: String(c.value), min_order_amount: c.min_order_amount ? String(c.min_order_amount) : '',
      max_discount_amount: c.max_discount_amount ? String(c.max_discount_amount) : '',
      usage_limit: c.usage_limit ? String(c.usage_limit) : '',
      per_user_limit: c.per_user_limit ? String(c.per_user_limit) : '',
      is_active: c.is_active, is_public: c.is_public,
      starts_at: toLocalInput(c.starts_at), expires_at: toLocalInput(c.expires_at),
    });
    setSaveError(null); setIsModalOpen(true);
  };

  const buildPayload = () => {
    const p: Record<string, unknown> = {
      code: form.code, type: form.type, value: Number(form.value),
      is_active: form.is_active, is_public: form.is_public,
    };
    if (form.description) p.description = form.description;
    if (form.min_order_amount) p.min_order_amount = Number(form.min_order_amount);
    if (form.max_discount_amount) p.max_discount_amount = Number(form.max_discount_amount);
    if (form.usage_limit) p.usage_limit = Number(form.usage_limit);
    if (form.per_user_limit) p.per_user_limit = Number(form.per_user_limit);
    if (form.starts_at) p.starts_at = new Date(form.starts_at).toISOString();
    if (form.expires_at) p.expires_at = new Date(form.expires_at).toISOString();
    return p;
  };

  const handleSave = async () => {
    setIsSaving(true); setSaveError(null);
    try {
      const payload = buildPayload();
      if (editing) { await adminApi.updateCoupon(editing.id, payload); }
      else { await adminApi.createCoupon(payload); }
      setIsModalOpen(false); load();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      const errors = e.response?.data?.errors;
      setSaveError(errors ? Object.values(errors).flat().join('. ') : (e.response?.data?.message || 'Failed to save'));
    } finally { setIsSaving(false); }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setIsDeleting(true);
    try { await adminApi.deleteCoupon(deleteTarget.id); setDeleteTarget(null); load(); }
    catch { /* silent */ } finally { setIsDeleting(false); }
  };

  const valLabel = (c: Coupon) => c.type === 'PERCENTAGE' ? `${c.value}%` : `R$ ${Number(c.value).toFixed(2)}`;

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Coupons</h1>
          <p className="text-gray-500 mt-1">{total} coupons</p>
        </div>
        <button onClick={openNew} className="inline-flex items-center gap-2 bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-accent/20">
          <Plus className="w-4 h-4" /> New Coupon
        </button>
      </div>

      {/* Table */}
      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-100">
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Discount</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Usage</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Visibility</th>
                <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Expires</th>
                <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {isLoading ? Array.from({ length: 4 }).map((_, i) => (
                <tr key={i}><td colSpan={7} className="px-5 py-4"><div className="h-5 bg-gray-100 rounded animate-pulse" /></td></tr>
              )) : coupons.length === 0 ? (
                <tr><td colSpan={7} className="px-5 py-16 text-center text-gray-400">
                  <Ticket className="w-12 h-12 mx-auto mb-3 text-gray-200" /><p className="font-medium">No coupons</p>
                </td></tr>
              ) : coupons.map((c) => {
                const st = couponStatus(c);
                return (
                  <tr key={c.id} className="hover:bg-gray-50/50 transition-colors">
                    <td className="px-5 py-3.5">
                      <span className="font-mono font-bold text-gray-900">{c.code}</span>
                      {c.description && <p className="text-xs text-gray-400 truncate max-w-[200px]">{c.description}</p>}
                    </td>
                    <td className="px-5 py-3.5 hidden md:table-cell">
                      <span className="inline-flex items-center gap-1 font-semibold text-gray-900">
                        {c.type === 'PERCENTAGE' ? <Percent className="w-3.5 h-3.5 text-gray-400" /> : <DollarSign className="w-3.5 h-3.5 text-gray-400" />}
                        {valLabel(c)}
                      </span>
                      {c.min_order_amount && <p className="text-xs text-gray-400">min R$ {Number(c.min_order_amount).toFixed(2)}</p>}
                    </td>
                    <td className="px-5 py-3.5 text-center hidden lg:table-cell">
                      <span className="text-gray-700 font-medium">{c.usage_count}</span>
                      {c.usage_limit ? <span className="text-gray-400">/{c.usage_limit}</span> : null}
                    </td>
                    <td className="px-5 py-3.5 text-center">
                      {c.is_public ? (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-blue-600"><Globe className="w-3.5 h-3.5" />Public</span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><Lock className="w-3.5 h-3.5" />Private</span>
                      )}
                    </td>
                    <td className="px-5 py-3.5 text-center">
                      <span className={`inline-block text-xs font-medium px-2.5 py-0.5 rounded-full border ${st.cls}`}>{st.label}</span>
                    </td>
                    <td className="px-5 py-3.5 hidden xl:table-cell text-xs text-gray-500">{formatDate(c.expires_at)}</td>
                    <td className="px-5 py-3.5 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button onClick={() => setViewTarget(c)} className="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="View"><Eye className="w-4 h-4" /></button>
                        <button onClick={() => openEdit(c)} className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors" title="Edit"><Edit3 className="w-4 h-4" /></button>
                        <button onClick={() => setDeleteTarget(c)} className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete"><Trash2 className="w-4 h-4" /></button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">Page {page} of {totalPages}</p>
            <div className="flex gap-1">
              <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"><ChevronLeft className="w-4 h-4" /></button>
              <button onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={page >= totalPages} className="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30"><ChevronRight className="w-4 h-4" /></button>
            </div>
          </div>
        )}
      </div>

      {/* ─── View Detail Drawer ──────────────────────────────────────── */}
      {viewTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={() => setViewTarget(null)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6">
            <div className="flex items-center justify-between mb-5">
              <h2 className="text-lg font-bold">{viewTarget.code}</h2>
              <button onClick={() => setViewTarget(null)} className="p-1 text-gray-400 hover:text-gray-600 rounded-lg"><X className="w-5 h-5" /></button>
            </div>
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div><p className="text-gray-500">Type</p><p className="font-medium">{viewTarget.type}</p></div>
              <div><p className="text-gray-500">Value</p><p className="font-medium">{valLabel(viewTarget)}</p></div>
              <div><p className="text-gray-500">Min Order</p><p className="font-medium">{viewTarget.min_order_amount ? `R$ ${Number(viewTarget.min_order_amount).toFixed(2)}` : '—'}</p></div>
              <div><p className="text-gray-500">Max Discount</p><p className="font-medium">{viewTarget.max_discount_amount ? `R$ ${Number(viewTarget.max_discount_amount).toFixed(2)}` : '—'}</p></div>
              <div><p className="text-gray-500">Usage</p><p className="font-medium">{viewTarget.usage_count}{viewTarget.usage_limit ? ` / ${viewTarget.usage_limit}` : ''}</p></div>
              <div><p className="text-gray-500">Per User Limit</p><p className="font-medium">{viewTarget.per_user_limit ?? '—'}</p></div>
              <div><p className="text-gray-500">Starts</p><p className="font-medium">{formatDate(viewTarget.starts_at)}</p></div>
              <div><p className="text-gray-500">Expires</p><p className="font-medium">{formatDate(viewTarget.expires_at)}</p></div>
              <div><p className="text-gray-500">Visibility</p><p className="font-medium">{viewTarget.is_public ? 'Public' : 'Private'}</p></div>
              <div><p className="text-gray-500">Active</p><p className="font-medium">{viewTarget.is_active ? 'Yes' : 'No'}</p></div>
              {viewTarget.description && <div className="col-span-2"><p className="text-gray-500">Description</p><p className="font-medium">{viewTarget.description}</p></div>}
            </div>
          </div>
        </div>
      )}

      {/* ─── Create / Edit Modal ──────────────────────────────────────── */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={() => setIsModalOpen(false)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10 rounded-t-2xl">
              <h2 className="text-lg font-bold">{editing ? 'Edit Coupon' : 'New Coupon'}</h2>
              <button onClick={() => setIsModalOpen(false)} className="p-1 text-gray-400 hover:text-gray-600 rounded-lg"><X className="w-5 h-5" /></button>
            </div>
            <div className="px-6 py-5 space-y-4">
              {saveError && (
                <div className="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm">
                  <AlertTriangle className="w-4 h-4 mt-0.5 flex-shrink-0" />{saveError}
                </div>
              )}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                  <input value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:border-accent" placeholder="SUMMER20" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                  <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value as 'PERCENTAGE' | 'FIXED' })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent bg-white">
                    <option value="PERCENTAGE">Percentage (%)</option>
                    <option value="FIXED">Fixed (R$)</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Value *</label>
                  <input type="number" step="0.01" value={form.value} onChange={(e) => setForm({ ...form, value: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder={form.type === 'PERCENTAGE' ? '10' : '25.00'} />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Min Order Amount</label>
                  <input type="number" step="0.01" value={form.min_order_amount} onChange={(e) => setForm({ ...form, min_order_amount: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder="100.00" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Max Discount</label>
                  <input type="number" step="0.01" value={form.max_discount_amount} onChange={(e) => setForm({ ...form, max_discount_amount: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder="50.00" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                  <input type="number" value={form.usage_limit} onChange={(e) => setForm({ ...form, usage_limit: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder="∞" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Per User Limit</label>
                  <input type="number" value={form.per_user_limit} onChange={(e) => setForm({ ...form, per_user_limit: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder="1" />
                </div>
                <div className="sm:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                  <input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" placeholder="Short description for promos" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Starts At</label>
                  <input type="datetime-local" value={form.starts_at} onChange={(e) => setForm({ ...form, starts_at: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
                  <input type="datetime-local" value={form.expires_at} onChange={(e) => setForm({ ...form, expires_at: e.target.value })} className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-accent" />
                </div>
              </div>

              <div className="flex flex-wrap gap-6 pt-2">
                <label className="flex items-center gap-3 cursor-pointer">
                  <button type="button" onClick={() => setForm({ ...form, is_active: !form.is_active })} className={`w-10 h-6 rounded-full transition-colors relative ${form.is_active ? 'bg-accent' : 'bg-gray-300'}`}>
                    <span className={`absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${form.is_active ? 'left-[18px]' : 'left-0.5'}`} />
                  </button>
                  <span className="text-sm font-medium text-gray-700">Active</span>
                </label>
                <label className="flex items-center gap-3 cursor-pointer">
                  <button type="button" onClick={() => setForm({ ...form, is_public: !form.is_public })} className={`w-10 h-6 rounded-full transition-colors relative ${form.is_public ? 'bg-blue-500' : 'bg-gray-300'}`}>
                    <span className={`absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${form.is_public ? 'left-[18px]' : 'left-0.5'}`} />
                  </button>
                  <span className="text-sm font-medium text-gray-700">Public (show in promo banner)</span>
                </label>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white z-10 rounded-b-2xl">
              <button onClick={() => setIsModalOpen(false)} className="px-5 py-2.5 text-sm font-medium border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
              <button onClick={handleSave} disabled={isSaving} className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-accent text-white rounded-xl hover:bg-accent-light transition-colors disabled:opacity-50 shadow-md shadow-accent/20">
                {isSaving ? <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : <Save className="w-4 h-4" />}
                {editing ? 'Update' : 'Create'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ─── Delete Confirmation ──────────────────────────────────────── */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-black/40" onClick={() => setDeleteTarget(null)} />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center"><AlertTriangle className="w-5 h-5" /></div>
              <div>
                <h3 className="font-bold text-gray-900">Delete Coupon</h3>
                <p className="text-sm text-gray-500">This action cannot be undone.</p>
              </div>
            </div>
            <p className="text-sm text-gray-600 mb-6">Are you sure you want to delete coupon <strong className="font-mono">{deleteTarget.code}</strong>?</p>
            <div className="flex justify-end gap-3">
              <button onClick={() => setDeleteTarget(null)} className="px-4 py-2 text-sm font-medium border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
              <button onClick={handleDelete} disabled={isDeleting} className="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors disabled:opacity-50">
                {isDeleting ? <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : <Check className="w-4 h-4" />}
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
