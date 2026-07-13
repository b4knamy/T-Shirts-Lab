import type { ReviewHeaderProps } from '../types';

export function ReviewHeader({ total }: ReviewHeaderProps) {
  return (
    <div>
      <h1 className="text-2xl font-bold">Reviews</h1>
      <p className="text-sm text-gray-500 mt-1">
        {total} total review{total !== 1 ? 's' : ''}
      </p>
    </div>
  );
}
