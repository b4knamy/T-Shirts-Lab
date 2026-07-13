import { MessageSquare } from 'lucide-react';
import type { ReviewListProps } from '../types';
import { ReviewCard } from './review_card';

export function ReviewList({
  reviews,
  isLoading,
  filterUnreplied,
  onDelete,
}: ReviewListProps) {
  if (isLoading) {
    return (
      <div className="text-center py-16 text-gray-400">Loading reviews...</div>
    );
  }

  if (reviews.length === 0) {
    return (
      <div className="text-center py-16 text-gray-400">
        <MessageSquare className="w-12 h-12 mx-auto mb-3 text-gray-300" />
        <p>
          {filterUnreplied
            ? 'All reviews have been replied to!'
            : 'No reviews yet.'}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {reviews.map((review) => (
        <ReviewCard key={review.id} review={review} onDelete={onDelete} />
      ))}
    </div>
  );
}
