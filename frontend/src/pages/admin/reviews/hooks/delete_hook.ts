import { useQueryClient } from '@tanstack/react-query';
import { reviewsApi } from '../../../../services/api/reviews';

export function useReviewDelete() {
  const queryClient = useQueryClient();

  const deleteReview = async (id: string) => {
    if (!confirm('Delete this review permanently?')) return;

    try {
      await reviewsApi.deleteReview(id);
      await queryClient.invalidateQueries({ queryKey: ['admin-reviews'] });
    } catch {
      // silent
    }
  };

  return { deleteReview };
}
