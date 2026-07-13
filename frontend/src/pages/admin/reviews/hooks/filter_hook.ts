import { useState } from 'react';

export function useReviewFilters({
  onResetPagination,
}: {
  onResetPagination: () => void;
}) {
  const [filterUnreplied, setFilterUnreplied] = useState(false);
  const [ratingFilter, setRatingFilter] = useState('');
  const [searchProduct, setSearchProduct] = useState('');

  const onSearchProductChange = (value: string) => {
    setSearchProduct(value);
    onResetPagination();
  };

  const onRatingFilterChange = (value: string) => {
    setRatingFilter(value);
    onResetPagination();
  };

  const onToggleUnreplied = () => {
    setFilterUnreplied((current) => !current);
    onResetPagination();
  };

  return {
    filterUnreplied,
    ratingFilter,
    searchProduct,
    onSearchProductChange,
    onRatingFilterChange,
    onToggleUnreplied,
  };
}
