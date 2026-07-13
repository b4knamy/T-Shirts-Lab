import { Users } from 'lucide-react';
import type { StaffTableProps } from '../types';
import { StaffRow } from './row';

export function StaffTable({
  users,
  isLoading,
  currentUser,
  isSuperAdmin,
}: StaffTableProps) {
  if (isLoading) {
    return (
      <div className="text-center py-16 text-gray-400">Loading users...</div>
    );
  }

  if (users.length === 0) {
    return (
      <div className="text-center py-16 text-gray-400">
        <Users className="w-12 h-12 mx-auto mb-3 text-gray-300" />
        <p>No users found.</p>
      </div>
    );
  }

  return (
    <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
      <table className="w-full text-sm">
        <thead className="bg-gray-50 border-b">
          <tr>
            <th className="text-left px-4 py-3 font-medium text-gray-500">
              User
            </th>
            <th className="text-left px-4 py-3 font-medium text-gray-500">
              Email
            </th>
            <th className="text-left px-4 py-3 font-medium text-gray-500">
              Role
            </th>
            <th className="text-left px-4 py-3 font-medium text-gray-500">
              Status
            </th>
            <th className="text-left px-4 py-3 font-medium text-gray-500">
              Joined
            </th>
            <th className="text-right px-4 py-3 font-medium text-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {users.map((user) => (
            <StaffRow
              key={user.id}
              user={user}
              currentUser={currentUser}
              isSuperAdmin={isSuperAdmin}
            />
          ))}
        </tbody>
      </table>
    </div>
  );
}
