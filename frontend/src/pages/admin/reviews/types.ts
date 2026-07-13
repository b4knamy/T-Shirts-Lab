import type { PaginationMeta } from '../../../components/common/pagination/type';
import type { ProductReview } from '../../../types';

export interface AdminReview extends ProductReview {
  product?: {
    id: string;
    name: string;
    slug: string;
  };
}

// Review Fetching
export interface UseReviewFetchingOptions {
  pagination: PaginationMeta;
  filterUnreplied: boolean;
  ratingFilter: string;
  searchProduct: string;
}

// Review Header
export interface ReviewHeaderProps {
  total: number;
}

// Review Filters
export interface ReviewFiltersProps {
  searchProduct: string;
  ratingFilter: string;
  filterUnreplied: boolean;
  onSearchProductChange: (value: string) => void;
  onRatingFilterChange: (value: string) => void;
  onToggleUnreplied: () => void;
}

// Review List
export interface ReviewListProps {
  reviews: AdminReview[];
  isLoading: boolean;
  filterUnreplied: boolean;
  onDelete: (id: string) => void;
}

// Review Card
export interface ReviewCardProps {
  review: AdminReview;
  onDelete: (id: string) => void;
}
