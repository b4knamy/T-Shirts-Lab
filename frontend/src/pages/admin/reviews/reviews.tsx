import { Pagination } from '../../../components/common/pagination/Pagination';
import { usePagination } from '../../../hooks';
import { useReviewDelete } from './hooks/delete_hook';
import { useReviewFetching } from './hooks/fetch_hook';
import { useReviewFilters } from './hooks/filter_hook';
import { ReviewFilters } from './sections/filters';
import { ReviewHeader } from './sections/header';
import { ReviewList } from './sections/list';

export function AdminReviews() {
  const pagination = usePagination();
  const filter = useReviewFilters({
    onResetPagination: pagination.resetPagination,
  });
  const query = useReviewFetching({
    pagination,
    filterUnreplied: filter.filterUnreplied,
    ratingFilter: filter.ratingFilter,
    searchProduct: filter.searchProduct,
  });
  const deletion = useReviewDelete();

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <ReviewHeader total={pagination.total} />

        <ReviewFilters
          searchProduct={filter.searchProduct}
          ratingFilter={filter.ratingFilter}
          filterUnreplied={filter.filterUnreplied}
          onSearchProductChange={filter.onSearchProductChange}
          onRatingFilterChange={filter.onRatingFilterChange}
          onToggleUnreplied={filter.onToggleUnreplied}
        />
      </div>

      <ReviewList
        reviews={query.reviews}
        isLoading={query.isLoading}
        filterUnreplied={filter.filterUnreplied}
        onDelete={deletion.deleteReview}
      />

      {pagination.total_pages > 1 && (
        <div className="mt-8">
          <Pagination mode="minimalist" {...pagination} />
        </div>
      )}
    </div>
  );
}
