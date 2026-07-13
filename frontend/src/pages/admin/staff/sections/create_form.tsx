import { Save, X } from 'lucide-react';
import { useStaffCreate } from '../hooks/create_hook';
import type { StaffCreateFormProps } from '../types';

export function StaffCreateForm({
  isSuperAdmin,
  onCreated,
  onCancel,
}: StaffCreateFormProps) {
  const { form, saving, error, updateForm, handleSubmit } = useStaffCreate();

  const submit = async () => {
    await handleSubmit();
    onCreated();
  };

  return (
    <div className="bg-white border border-gray-200 rounded-xl p-6 mb-6">
      <h3 className="font-semibold mb-4">New Staff Member</h3>
      {error && <p className="text-sm text-red-500 mb-3">{error}</p>}

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm text-gray-500 mb-1">
            First Name *
          </label>
          <input
            type="text"
            value={form.first_name}
            onChange={(event) => updateForm('first_name', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">
            Last Name *
          </label>
          <input
            type="text"
            value={form.last_name}
            onChange={(event) => updateForm('last_name', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Email *</label>
          <input
            type="email"
            value={form.email}
            onChange={(event) => updateForm('email', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Password *</label>
          <input
            type="password"
            value={form.password}
            onChange={(event) => updateForm('password', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Phone</label>
          <input
            type="text"
            value={form.phone}
            onChange={(event) => updateForm('phone', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-500 mb-1">Role *</label>
          <select
            value={form.role}
            onChange={(event) => updateForm('role', event.target.value)}
            className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none text-sm"
          >
            <option value="MODERATOR">Moderator</option>
            {isSuperAdmin && <option value="ADMIN">Admin</option>}
          </select>
        </div>
      </div>

      <div className="flex items-center gap-3 mt-4">
        <button
          onClick={submit}
          disabled={
            saving ||
            !form.email ||
            !form.password ||
            !form.first_name ||
            !form.last_name
          }
          className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-accent/90 disabled:opacity-50"
        >
          <Save className="w-4 h-4" /> {saving ? 'Creating...' : 'Create'}
        </button>
        <button
          onClick={onCancel}
          className="flex items-center gap-2 text-gray-500 hover:text-gray-700 text-sm px-4 py-2"
        >
          <X className="w-4 h-4" /> Cancel
        </button>
      </div>
    </div>
  );
}
