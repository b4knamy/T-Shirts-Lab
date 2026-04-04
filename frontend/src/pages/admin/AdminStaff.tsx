import { useEffect, useState, useCallback } from 'react';
import {
  Users, Plus, Shield, ShieldCheck, ShieldAlert, Search,
  ChevronLeft, ChevronRight, Save, X, UserCheck, UserX,
} from 'lucide-react';
import apiClient from '../../services/api/client';
import { useAuth } from '../../hooks/useAuth';
import type { User } from '../../types';

interface UsersMeta {
  total: number;
  page: number;
  limit: number;
  total_pages: number;
}

const ROLE_BADGE: Record<string, { label: string; color: string; icon: typeof Shield }> = {
  SUPER_ADMIN: { label: 'Super Admin', color: 'bg-purple-100 text-purple-700', icon: ShieldAlert },
  ADMIN:       { label: 'Admin',       color: 'bg-red-100 text-red-700',       icon: ShieldCheck },
  MODERATOR:   { label: 'Moderator',   color: 'bg-blue-100 text-blue-700',     icon: Shield },
  CUSTOMER:    { label: 'Customer',    color: 'bg-gray-100 text-gray-600',     icon: Users },
  VENDOR:      { label: 'Vendor',      color: 'bg-green-100 text-green-700',   icon: Users },
};

export function AdminStaff() {
  const { user: currentUser } = useAuth();
  const [users, setUsers] = useState<User[]>([]);
  const [meta, setMeta] = useState<UsersMeta>({ total: 0, page: 1, limit: 15, total_pages: 1 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [showCreateForm, setShowCreateForm] = useState(false);

  const isSuperAdmin = currentUser?.role === 'SUPER_ADMIN';

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<{ data: { data: User[]; meta: UsersMeta } }>('/api/v1/users', {
        params: {
          page,
          limit: 15,
          ...(search ? { search } : {}),
          ...(roleFilter ? { role: roleFilter } : {}),
        },
      });
      setUsers(res.data.data.data);
      setMeta(res.data.data.meta);
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, [page, search, roleFilter]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    fetchUsers();
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold">Staff Management</h1>
          <p className="text-sm text-gray-500 mt-1">{meta.total} total user{meta.total !== 1 ? 's' : ''}</p>
        </div>
        <button
          onClick={() => setShowCreateForm(true)}
          className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors"
        >
          <Plus className="w-4 h-4" /> Add Staff Member
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap items-center gap-4 mb-6">
        <form onSubmit={handleSearch} className="flex items-center gap-2">
          <div className="relative">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search users..."
              className="pl-9 pr-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none w-64"
            />
          </div>
        </form>
        <select
          value={roleFilter}
          onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
          className="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none"
        >
          <option value="">All Roles</option>
          <option value="SUPER_ADMIN">Super Admin</option>
          <option value="ADMIN">Admin</option>
          <option value="MODERATOR">Moderator</option>
          <option value="CUSTOMER">Customer</option>
        </select>
      </div>

      {/* Create Form */}
      {showCreateForm && (
        <CreateStaffForm
          isSuperAdmin={isSuperAdmin}
          onCreated={() => { setShowCreateForm(false); fetchUsers(); }}
          onCancel={() => setShowCreateForm(false)}
        />
      )}

      {/* Users Table */}
      {loading ? (
        <div className="text-center py-16 text-gray-400">Loading users...</div>
      ) : users.length === 0 ? (
        <div className="text-center py-16 text-gray-400">
          <Users className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p>No users found.</p>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="text-left px-4 py-3 font-medium text-gray-500">User</th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">Email</th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">Role</th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                <th className="text-left px-4 py-3 font-medium text-gray-500">Joined</th>
                <th className="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {users.map((u) => (
                <UserRow
                  key={u.id}
                  user={u}
                  currentUser={currentUser!}
                  isSuperAdmin={isSuperAdmin}
                  onUpdated={fetchUsers}
                />
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {meta.total_pages > 1 && (
        <div className="flex items-center justify-center gap-4 mt-6">
          <button
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={page === 1}
            className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40"
          >
            <ChevronLeft className="w-4 h-4" /> Previous
          </button>
          <span className="text-sm text-gray-500">Page {meta.page} of {meta.total_pages}</span>
          <button
            onClick={() => setPage((p) => Math.min(meta.total_pages, p + 1))}
            disabled={page === meta.total_pages}
            className="flex items-center gap-1 px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40"
          >
            Next <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      )}
    </div>
  );
}

/* ── Create Staff Form ─────────────────────────────────────────── */

function CreateStaffForm({
  isSuperAdmin,
  onCreated,
  onCancel,
}: {
  isSuperAdmin: boolean;
  onCreated: () => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState({
    email: '',
    password: '',
    first_name: '',
    last_name: '',
    phone: '',
    role: 'MODERATOR',
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async () => {
    setSaving(true);
    setError(null);
    try {
      await apiClient.post('/api/v1/users', form);
      onCreated();
    } catch (err: unknown) {
      const e = err as { response?: { data?: { message?: string } } };
      setError(e.response?.data?.message || 'Failed to create staff member');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
      <h3 className="font-semibold mb-4">New Staff Member</h3>
      {error && <p className="text-sm text-red-500 mb-3">{error}</p>}

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm text-gray-500 mb-1">First Name *</label>
          <input type="text" value={form.first_name}
            onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm" />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Last Name *</label>
          <input type="text" value={form.last_name}
            onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm" />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Email *</label>
          <input type="email" value={form.email}
            onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm" />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Password *</label>
          <input type="password" value={form.password}
            onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm" />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Phone</label>
          <input type="text" value={form.phone}
            onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm" />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Role *</label>
          <select value={form.role}
            onChange={(e) => setForm((f) => ({ ...f, role: e.target.value }))}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm">
            <option value="MODERATOR">Moderator</option>
            {isSuperAdmin && <option value="ADMIN">Admin</option>}
          </select>
        </div>
      </div>

      <div className="flex items-center gap-3 mt-4">
        <button
          onClick={handleSubmit}
          disabled={saving || !form.email || !form.password || !form.first_name || !form.last_name}
          className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-accent/90 disabled:opacity-50"
        >
          <Save className="w-4 h-4" /> {saving ? 'Creating...' : 'Create'}
        </button>
        <button onClick={onCancel} className="flex items-center gap-2 text-gray-500 hover:text-gray-700 text-sm px-4 py-2">
          <X className="w-4 h-4" /> Cancel
        </button>
      </div>
    </div>
  );
}

/* ── User Row ──────────────────────────────────────────────────── */

function UserRow({
  user,
  currentUser,
  isSuperAdmin,
  onUpdated,
}: {
  user: User;
  currentUser: User;
  isSuperAdmin: boolean;
  onUpdated: () => void;
}) {
  const [editing, setEditing] = useState(false);
  const [newRole, setNewRole] = useState(user.role);
  const [saving, setSaving] = useState(false);

  const badge = ROLE_BADGE[user.role] || ROLE_BADGE.CUSTOMER;
  const BadgeIcon = badge.icon;
  const isSelf = user.id === currentUser.id;
  const isTargetSuperAdmin = user.role === 'SUPER_ADMIN';
  const isTargetAdmin = user.role === 'ADMIN';
  const canModify = !isSelf && !isTargetSuperAdmin && (isSuperAdmin || !isTargetAdmin);

  const handleSaveRole = async () => {
    setSaving(true);
    try {
      await apiClient.patch(`/api/v1/users/${user.id}`, { role: newRole });
      setEditing(false);
      onUpdated();
    } catch {
      // silent
    } finally {
      setSaving(false);
    }
  };

  const handleToggleActive = async () => {
    try {
      await apiClient.patch(`/api/v1/users/${user.id}`, { is_active: !user.is_active });
      onUpdated();
    } catch {
      // silent
    }
  };

  return (
    <tr className="hover:bg-gray-50">
      <td className="px-4 py-3">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-semibold">
            {user.first_name?.[0]}{user.last_name?.[0]}
          </div>
          <span className="font-medium">{user.first_name} {user.last_name}</span>
        </div>
      </td>
      <td className="px-4 py-3 text-gray-500">{user.email}</td>
      <td className="px-4 py-3">
        {editing ? (
          <div className="flex items-center gap-2">
            <select value={newRole} onChange={(e) => setNewRole(e.target.value as User['role'])}
              className="px-2 py-1 border rounded text-xs">
              <option value="CUSTOMER">Customer</option>
              <option value="MODERATOR">Moderator</option>
              {isSuperAdmin && <option value="ADMIN">Admin</option>}
            </select>
            <button onClick={handleSaveRole} disabled={saving}
              className="text-xs text-accent hover:underline">{saving ? '...' : 'Save'}</button>
            <button onClick={() => { setEditing(false); setNewRole(user.role); }}
              className="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
          </div>
        ) : (
          <span className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full ${badge.color}`}>
            <BadgeIcon className="w-3 h-3" /> {badge.label}
          </span>
        )}
      </td>
      <td className="px-4 py-3">
        <span className={`text-xs font-medium ${user.is_active ? 'text-green-600' : 'text-red-500'}`}>
          {user.is_active ? 'Active' : 'Inactive'}
        </span>
      </td>
      <td className="px-4 py-3 text-gray-400 text-xs">{new Date(user.created_at).toLocaleDateString()}</td>
      <td className="px-4 py-3 text-right">
        {canModify && (
          <div className="flex items-center justify-end gap-2">
            <button
              onClick={() => setEditing(true)}
              className="text-xs text-accent hover:underline"
              title="Change role"
            >
              Role
            </button>
            <button
              onClick={handleToggleActive}
              className={`p-1 rounded transition-colors ${user.is_active ? 'text-gray-400 hover:text-red-500' : 'text-gray-400 hover:text-green-500'}`}
              title={user.is_active ? 'Deactivate' : 'Activate'}
            >
              {user.is_active ? <UserX className="w-4 h-4" /> : <UserCheck className="w-4 h-4" />}
            </button>
          </div>
        )}
        {isSelf && <span className="text-xs text-gray-400">(You)</span>}
      </td>
    </tr>
  );
}
