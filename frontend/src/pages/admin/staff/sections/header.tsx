import { Plus } from 'lucide-react';
import type { StaffHeaderProps } from '../types';

export function StaffHeader({ total, onCreate }: StaffHeaderProps) {
  return (
    <div className="flex items-center justify-between mb-6">
      <div>
        <h1 className="text-2xl font-bold">Staff Management</h1>
        <p className="text-sm text-gray-500 mt-1">
          {total} total user{total !== 1 ? 's' : ''}
        </p>
      </div>
      <button
        onClick={onCreate}
        className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors"
      >
        <Plus className="w-4 h-4" /> Add Staff Member
      </button>
    </div>
  );
}
