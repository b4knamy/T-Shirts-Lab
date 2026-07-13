import { useStaffRow } from '../hooks/row_hook';
import { ROLE_BADGE } from '../constants';
import type { StaffRowProps } from '../types';
import { UserCheck, UserX } from 'lucide-react';

export function StaffRow({ user, currentUser, isSuperAdmin }: StaffRowProps) {
  const {
    editing,
    newRole,
    saving,
    setNewRole,
    startEditing,
    cancelEditing,
    handleSaveRole,
    handleToggleActive,
  } = useStaffRow(user);

  const badge = ROLE_BADGE[user.role] || ROLE_BADGE.CUSTOMER;
  const BadgeIcon = badge.icon;
  const isSelf = user.id === currentUser.id;
  const isTargetSuperAdmin = user.role === 'SUPER_ADMIN';
  const isTargetAdmin = user.role === 'ADMIN';
  const canModify =
    !isSelf && !isTargetSuperAdmin && (isSuperAdmin || !isTargetAdmin);

  return (
    <tr className="hover:bg-gray-50">
      <td className="px-4 py-3">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center text-xs font-semibold">
            {user.first_name?.[0]}
            {user.last_name?.[0]}
          </div>
          <span className="font-medium">
            {user.first_name} {user.last_name}
          </span>
        </div>
      </td>
      <td className="px-4 py-3 text-gray-500">{user.email}</td>
      <td className="px-4 py-3">
        {editing ? (
          <div className="flex items-center gap-2">
            <select
              value={newRole}
              onChange={(event) =>
                setNewRole(event.target.value as typeof newRole)
              }
              className="px-2 py-1 border rounded text-xs"
            >
              <option value="CUSTOMER">Customer</option>
              <option value="MODERATOR">Moderator</option>
              {isSuperAdmin && <option value="ADMIN">Admin</option>}
            </select>
            <button
              onClick={handleSaveRole}
              disabled={saving}
              className="text-xs text-accent hover:underline"
            >
              {saving ? '...' : 'Save'}
            </button>
            <button
              onClick={cancelEditing}
              className="text-xs text-gray-400 hover:text-gray-600"
            >
              Cancel
            </button>
          </div>
        ) : (
          <span
            className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full ${badge.color}`}
          >
            <BadgeIcon className="w-3 h-3" /> {badge.label}
          </span>
        )}
      </td>
      <td className="px-4 py-3">
        <span
          className={`text-xs font-medium ${user.is_active ? 'text-green-600' : 'text-red-500'}`}
        >
          {user.is_active ? 'Active' : 'Inactive'}
        </span>
      </td>
      <td className="px-4 py-3 text-gray-400 text-xs">
        {new Date(user.created_at).toLocaleDateString()}
      </td>
      <td className="px-4 py-3 text-right">
        {canModify && (
          <div className="flex items-center justify-end gap-2">
            <button
              onClick={startEditing}
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
              {user.is_active ? (
                <UserX className="w-4 h-4" />
              ) : (
                <UserCheck className="w-4 h-4" />
              )}
            </button>
          </div>
        )}
        {isSelf && <span className="text-xs text-gray-400">(You)</span>}
      </td>
    </tr>
  );
}
