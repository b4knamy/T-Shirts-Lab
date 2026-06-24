import { Edit3, Tags, Trash2 } from 'lucide-react';
import type { CategoryTableProps } from '../types';

export function CategoryTable({
  categories,
  isLoading,
  onEdit,
  onDelete,
}: CategoryTableProps) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-gray-100">
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Category
            </th>
            <th className="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">
              Slug
            </th>
            <th className="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-50">
          {isLoading ? (
            Array.from({ length: 4 }).map((_, index) => (
              <tr key={index}>
                <td colSpan={4} className="px-5 py-4">
                  <div className="h-5 bg-gray-100 rounded animate-pulse" />
                </td>
              </tr>
            ))
          ) : categories.length === 0 ? (
            <tr>
              <td colSpan={4} className="px-5 py-16 text-center text-gray-400">
                <Tags className="w-12 h-12 mx-auto mb-3 text-gray-200" />
                <p className="font-medium">No categories</p>
              </td>
            </tr>
          ) : (
            categories.map((category) => (
              <tr
                key={category.id}
                className="hover:bg-gray-50/50 transition-colors"
              >
                <td className="px-5 py-3.5">
                  <div className="flex items-center gap-3">
                    {category.image_url ? (
                      <img
                        src={category.image_url}
                        alt=""
                        className="w-10 h-10 rounded-lg object-cover border border-gray-100"
                      />
                    ) : (
                      <div className="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300">
                        <Tags className="w-5 h-5" />
                      </div>
                    )}
                    <div>
                      <p className="font-medium text-gray-900">
                        {category.name}
                      </p>
                      {category.description && (
                        <p className="text-xs text-gray-400 truncate max-w-[250px]">
                          {category.description}
                        </p>
                      )}
                    </div>
                  </div>
                </td>
                <td className="px-5 py-3.5 hidden md:table-cell">
                  <span className="font-mono text-xs text-gray-500">
                    {category.slug}
                  </span>
                </td>
                <td className="px-5 py-3.5 text-center">
                  <span
                    className={`inline-block text-xs font-medium px-2.5 py-0.5 rounded-full border ${
                      category.is_active
                        ? 'bg-green-50 text-green-700 border-green-200'
                        : 'bg-gray-50 text-gray-500 border-gray-200'
                    }`}
                  >
                    {category.is_active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-5 py-3.5 text-right">
                  <div className="flex items-center justify-end gap-1">
                    <button
                      onClick={() => onEdit(category)}
                      className="p-2 text-gray-400 hover:text-accent hover:bg-accent/5 rounded-lg transition-colors"
                      title="Edit"
                    >
                      <Edit3 className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => onDelete(category)}
                      className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                      title="Delete"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}
